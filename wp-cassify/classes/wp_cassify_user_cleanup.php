<?php
namespace wp_cassify;

defined( 'ABSPATH' ) || exit;

/** Manual, resumable operations. No scheduled tasks and no implicit CAS migration. */
class WP_Cassify_User_Cleanup {
    const CAS_META = 'wp_cassify_cas_user';
    const LOGIN_META = 'wp_cassify_last_login_at';
    const STATE = 'wp_cassify_cleanup_job';
    const LOCK = 'wp_cassify_cleanup_lock';
    const YEARS = 'wp_cassify_cleanup_years'; // Legacy setting, read only for conversion.
    const DAYS = 'wp_cassify_cleanup_days';
    const SCAN_SIZE = 200;
    const BATCH_SIZE = 20;

    public static function init() {
        add_action( 'wp_login', array( __CLASS__, 'record_login' ), 10, 2 );
        add_action( 'wp_ajax_wp_cassify_cleanup', array( __CLASS__, 'ajax' ) );
    }

    public static function record_login( $login, $user ) {
        update_user_meta( $user->ID, self::LOGIN_META, time() );
    }

    public static function allowed() {
        global $wp_cassify_network_activated;
        return is_multisite()
            ? $wp_cassify_network_activated && current_user_can( 'manage_network_users' ) && current_user_can( 'delete_users' )
            : current_user_can( 'manage_options' ) && current_user_can( 'delete_users' );
    }

    /** Days are full 24-hour periods, independent of timezone and daylight saving. */
    public static function cutoff( $now, $days ) {
        return $now - $days * DAY_IN_SECONDS;
    }

    public static function configured_days() {
        global $wp_cassify_network_activated;
        $days = WP_Cassify_Utils::wp_cassify_get_option( $wp_cassify_network_activated, self::DAYS );
        if ( ctype_digit( (string) $days ) && (int) $days >= 1 && (int) $days <= 36600 ) {
            return (int) $days;
        }
        $years = WP_Cassify_Utils::wp_cassify_get_option( $wp_cassify_network_activated, self::YEARS );
        if ( ! ctype_digit( (string) $years ) || (int) $years < 1 || (int) $years > 100 ) {
            return 730;
        }
        // Preserve the previous calendar interval, including leap days, on first use.
        $now = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
        $year = (int) $now->format( 'Y' ) - (int) $years;
        $month = (int) $now->format( 'm' );
        $first = $now->setDate( $year, $month, 1 );
        $then = $now->setDate( $year, $month, min( (int) $now->format( 'd' ), (int) $first->format( 't' ) ) );
        return min( 36600, (int) ceil( ( $now->getTimestamp() - $then->getTimestamp() ) / DAY_IN_SECONDS ) );
    }

    /** Invalid metadata must never be treated as a missing date. */
    public static function reason( $id, $cutoff, $now, $missing ) {
        if ( '1' !== (string) get_user_meta( $id, self::CAS_META, true ) ) {
            return '';
        }
        if ( ! metadata_exists( 'user', $id, self::LOGIN_META ) ) {
            return $missing ? 'missing' : '';
        }
        $value = get_user_meta( $id, self::LOGIN_META, true );
        if ( ! is_scalar( $value ) || ! ctype_digit( (string) $value ) || (float) $value <= 0 || (float) $value > $now ) {
            return 'invalid';
        }
        return (float) $value < $cutoff ? 'old' : '';
    }

    private static function page_key( $job, $page ) {
        return self::STATE . '_' . $job['token'] . '_' . $page;
    }

    private static function save( $job ) {
        update_option( self::STATE, $job, false );
    }

    private static function error( $message ) {
        throw new \RuntimeException( $message );
    }

    /** All operation data lives on the main site, including AJAX from sub-sites. */
    public static function ajax() {
        if ( ! self::allowed() ) {
            wp_send_json_error( array( 'message' => 'You cannot manage CAS accounts here.' ), 403 );
        }
        check_ajax_referer( 'wp_cassify_cleanup', 'nonce' );
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            wp_send_json_error( array( 'message' => 'POST required.' ), 405 );
        }
        $switched = is_multisite();
        if ( $switched ) {
            switch_to_blog( get_main_site_id() );
        }
        global $wpdb;
        // Atomic lock, with compare-and-delete recovery after an interrupted request.
        $old = get_option( self::LOCK );
        if ( $old && (int) $old < time() - 600 ) {
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", self::LOCK, $old ) );
            wp_cache_delete( self::LOCK, 'options' );
        }
        $locked = add_option( self::LOCK, time(), '', false );
        $status = 200;
        try {
            if ( ! $locked ) {
                self::error( 'Another request is running. Retry in a moment.' );
            }
            $result = self::dispatch( wp_unslash( $_POST ) );
        } catch ( \Throwable $error ) {
            $status = 400;
            $result = array( 'message' => $error->getMessage() );
        } finally {
            if ( $locked ) {
                delete_option( self::LOCK );
            }
            if ( $switched ) {
                restore_current_blog();
            }
        }
        if ( 200 !== $status ) {
            wp_send_json_error( $result, $status );
        }
        wp_send_json_success( $result );
    }

    private static function dispatch( $request ) {
        global $wpdb, $wp_cassify_network_activated;
        foreach ( $request as $value ) {
            if ( ! is_scalar( $value ) ) {
                self::error( 'Invalid request parameters.' );
            }
        }
        $action = isset( $request['operation'] ) ? $request['operation'] : '';
        $job = get_option( self::STATE, array() );
        $owner = get_current_user_id();
        if ( 'discard' === $action ) {
            if ( $job ) {
                for ( $page = 0; $page < $job['pages']; $page++ ) {
                    delete_option( self::page_key( $job, $page ) );
                }
            }
            delete_option( self::STATE );
            return array( 'phase' => 'idle' );
        }
        if ( $job && $job['owner'] !== $owner && $job['phase'] !== 'done' ) {
            self::error( 'Another administrator owns the current operation.' );
        }
        if ( 'start' === $action ) {
            if ( $job && 'executing' === $job['phase'] ) {
                self::error( 'Resume the current operation before starting another one.' );
            }
            $mode = isset( $request['mode'] ) ? $request['mode'] : '';
            if ( ! in_array( $mode, array( 'mark', 'purge' ), true ) ) {
                self::error( 'Invalid operation.' );
            }
            $days = isset( $request['days'] ) ? (string) $request['days'] : (string) self::configured_days();
            if ( ! ctype_digit( $days ) || (int) $days < 1 || (int) $days > 36600 ) {
                self::error( 'Choose a whole number of days between 1 and 36600.' );
            }
            $role = isset( $request['role'] ) ? sanitize_key( $request['role'] ) : '';
            $blog = is_multisite() ? absint( isset( $request['blog'] ) ? $request['blog'] : 0 ) : get_current_blog_id();
            if ( $role ) {
                if ( is_multisite() ) {
                    $site = get_site( $blog );
                    if ( ! $site || (int) $site->network_id !== get_current_network_id() ) {
                        self::error( 'Choose a valid site for the role.' );
                    }
                }
                if ( is_multisite() ) { switch_to_blog( $blog ); }
                $valid = wp_roles()->is_role( $role );
                if ( is_multisite() ) { restore_current_blog(); }
                if ( ! $valid ) {
                    self::error( 'Unknown role on this site.' );
                }
            }
            if ( $job ) {
                for ( $page = 0; $page < $job['pages']; $page++ ) {
                    delete_option( self::page_key( $job, $page ) );
                }
            }
            $now = time();
            $job = array(
                'token' => wp_generate_uuid4(), 'owner' => $owner, 'mode' => $mode,
                'phase' => 'scanning', 'cursor' => 0, 'max' => (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->users}" ),
                'now' => $now, 'cutoff' => self::cutoff( $now, (int) $days ),
                'days' => (int) $days, 'missing' => ! empty( $request['missing'] ),
                'role' => $role, 'blog' => $blog, 'pages' => 0, 'page' => 0, 'index' => 0,
                'old' => 0, 'missing_count' => 0, 'invalid' => 0, 'total' => 0,
                'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => array(), 'self' => false,
            );
            WP_Cassify_Utils::wp_cassify_update_option( $wp_cassify_network_activated, self::DAYS, (int) $days );
            self::save( $job );
        } elseif ( 'status' !== $action ) {
            if ( ! $job || empty( $request['token'] ) || ! hash_equals( $job['token'], $request['token'] ) || $job['owner'] !== $owner ) {
                self::error( 'Operation expired. Reload the preview.' );
            }
            if ( 'scan' === $action && 'scanning' === $job['phase'] ) {
                $ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID > %d AND ID <= %d ORDER BY ID LIMIT %d", $job['cursor'], $job['max'], self::SCAN_SIZE ) );
                $entries = array();
                foreach ( $ids as $id ) {
                    $id = (int) $id;
                    $job['cursor'] = $id;
                    if ( 'mark' === $job['mode'] ) {
                        $user = new \WP_User( $id, '', $job['blog'] );
                        if ( $job['role'] && ! in_array( $job['role'], $user->roles, true ) ) {
                            continue;
                        }
                        if ( '1' === (string) get_user_meta( $id, self::CAS_META, true ) ) {
                            continue;
                        }
                        $reason = 'mark';
                    } else {
                        $reason = self::reason( $id, $job['cutoff'], time(), $job['missing'] );
                        if ( 'invalid' === $reason ) {
                            $job['invalid']++;
                            continue;
                        }
                        if ( ! $reason ) {
                            continue;
                        }
                        $job[ 'missing' === $reason ? 'missing_count' : 'old' ]++;
                    }
                    $entries[] = array( 'id' => $id, 'reason' => $reason );
                    $job['total']++;
                    if ( $id === $owner ) {
                        $job['self'] = true;
                    }
                }
                if ( $entries ) {
                    update_option( self::page_key( $job, $job['pages'] ), $entries, false );
                    $job['pages']++;
                }
                if ( count( $ids ) < self::SCAN_SIZE ) {
                    $job['phase'] = 'ready';
                }
            } elseif ( 'confirm' === $action && 'ready' === $job['phase'] ) {
                if ( 'purge' === $job['mode'] ) {
                    $username = isset( $request['target_username'] ) ? trim( $request['target_username'] ) : '';
                    $recipient = $username !== '' ? get_user_by( 'login', $username ) : false;
                    if ( ! $recipient ) {
                        self::error( 'Enter an existing WordPress username for content reassignment.' );
                    }
                    // Keep the resolved ID stable when resuming an operation.
                    $job['target'] = (int) $recipient->ID;
                    self::validate_target( $job );
                }
                $job['phase'] = 'executing';
            } elseif ( 'batch' === $action && 'executing' === $job['phase'] ) {
                self::batch( $job );
            } elseif ( 'preview' !== $action ) {
                self::error( 'Invalid operation phase. Refresh the status.' );
            }
            self::save( $job );
        }
        if ( $job && $job['owner'] !== $owner ) {
            return array( 'phase' => 'idle' );
        }
        if ( ! $job ) {
            return array( 'phase' => 'idle' );
        }
        $result = $job;
        $page = isset( $request['preview_page'] ) ? max( 0, (int) $request['preview_page'] ) : 0;
        $result['preview_page'] = $page;
        $result['users'] = array();
        foreach ( (array) get_option( self::page_key( $job, $page ), array() ) as $entry ) {
            $user = get_userdata( $entry['id'] );
            $result['users'][] = array( 'id' => $entry['id'], 'login' => $user ? $user->user_login : '(deleted)', 'reason' => $entry['reason'] );
        }
        return $result;
    }

    private static function validate_target( $job ) {
        if ( ! $job['target'] || ! get_userdata( $job['target'] ) ) {
            self::error( 'Choose an existing recipient for the content.' );
        }
        $reason = self::reason( $job['target'], $job['cutoff'], time(), $job['missing'] );
        if ( in_array( $reason, array( 'old', 'missing' ), true ) ) {
            self::error( 'The content recipient is a purge candidate. Choose another account.' );
        }
        // Also forbid recipients present in the confirmed preview, even if they reconnected.
        for ( $page = 0; $page < $job['pages']; $page++ ) {
            foreach ( (array) get_option( self::page_key( $job, $page ), array() ) as $entry ) {
                if ( (int) $entry['id'] === $job['target'] ) {
                    self::error( 'The content recipient belongs to the preview.' );
                }
            }
        }
    }

    private static function batch( &$job ) {
        $processed = 0;
        while ( $job['page'] < $job['pages'] && $processed < self::BATCH_SIZE ) {
            $entries = get_option( self::page_key( $job, $job['page'] ), array() );
            if ( $job['index'] >= count( $entries ) ) {
                $job['page']++;
                $job['index'] = 0;
                continue;
            }
            $entry = $entries[ $job['index'] ];
            // Delete the operator last so all remaining batches can authenticate.
            if ( 'purge' === $job['mode'] && $entry['id'] === $job['owner'] ) {
                $job['deferred_self'] = $entry['id'];
            } else {
                self::process_user( $job, $entry['id'] );
            }
            $job['index']++;
            $processed++;
            self::save( $job );
        }
        if ( $job['page'] >= $job['pages'] ) {
            if ( ! empty( $job['deferred_self'] ) ) {
                self::process_user( $job, $job['deferred_self'] );
                unset( $job['deferred_self'] );
            }
            $job['phase'] = 'done';
        }
    }

    private static function process_user( &$job, $id ) {
        if ( ! get_userdata( $id ) ) {
            $job['skipped']++;
            return;
        }
        if ( 'mark' === $job['mode'] ) {
            $user = new \WP_User( $id, '', $job['blog'] );
            if ( $job['role'] && ! in_array( $job['role'], $user->roles, true ) ) {
                $job['skipped']++;
                return;
            }
            update_user_meta( $id, self::CAS_META, '1' );
            if ( '1' !== (string) get_user_meta( $id, self::CAS_META, true ) ) {
                self::failure( $job, $id, 'CAS marker could not be saved.' );
            } else {
                $job['success']++;
            }
            return;
        }
        $reason = self::reason( $id, $job['cutoff'], time(), $job['missing'] );
        if ( ! in_array( $reason, array( 'old', 'missing' ), true ) ) {
            $job['skipped']++;
            return;
        }
        if ( ! get_userdata( $job['target'] ) || in_array( self::reason( $job['target'], $job['cutoff'], time(), $job['missing'] ), array( 'old', 'missing' ), true ) ) {
            self::error( 'Content recipient is no longer valid. Operation paused.' );
        }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        if ( is_multisite() ) {
            require_once ABSPATH . 'wp-admin/includes/ms.php';
            if ( is_super_admin( $id ) ) {
                self::failure( $job, $id, 'WordPress refuses deletion of a network super administrator.' );
                return;
            }
            // Include archived sites and sites where the author no longer has membership.
            $offset = 0;
            do {
                $sites = get_sites( array( 'fields' => 'ids', 'number' => 100, 'offset' => $offset ) );
                foreach ( $sites as $blog_id ) {
                    switch_to_blog( $blog_id );
                    try {
                        $ok = self::reassign_content( $id, $job['target'] );
                        if ( $ok ) {
                            $ok = wp_delete_user( $id, $job['target'] );
                        }
                        $ok = $ok && self::content_reassigned( $id );
                    } finally {
                        restore_current_blog();
                    }
                    if ( ! $ok ) {
                        self::failure( $job, $id, 'Content reassignment failed on site ' . $blog_id . '.' );
                        return;
                    }
                }
                $offset += 100;
            } while ( count( $sites ) === 100 );
            $ok = wpmu_delete_user( $id );
        } else {
            // wp_delete_user does not verify the SQL reassignment result. Do it first.
            $ok = self::reassign_content( $id, $job['target'] );
            if ( $ok ) {
                $ok = wp_delete_user( $id, $job['target'] );
            }
        }
        if ( ! $ok || get_userdata( $id ) ) {
            self::failure( $job, $id, 'WordPress could not delete this account.' );
            return;
        }
        $job['success']++;
        WP_Cassify_Utils::wp_cassify_log( 'Manual CAS purge deleted user ID ' . $id, 'INFO' );
        if ( $id === $job['owner'] ) {
            wp_clear_auth_cookie();
            $job['session_ended'] = true;
        }
    }

    private static function content_reassigned( $id ) {
        global $wpdb;
        return '0' === (string) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d", $id ) )
            && '0' === (string) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->links} WHERE link_owner = %d", $id ) );
    }

    private static function reassign_content( $id, $target ) {
        global $wpdb;
        do {
            $posts = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %d ORDER BY ID LIMIT 200", $id ) );
            foreach ( $posts as $post_id ) {
                if ( false === $wpdb->update( $wpdb->posts, array( 'post_author' => $target ), array( 'ID' => $post_id ) ) ) {
                    return false;
                }
                clean_post_cache( $post_id );
            }
        } while ( count( $posts ) === 200 );
        if ( false === $wpdb->update( $wpdb->links, array( 'link_owner' => $target ), array( 'link_owner' => $id ) ) ) {
            return false;
        }
        return self::content_reassigned( $id );
    }

    private static function failure( &$job, $id, $message ) {
        $job['failed']++;
        // Keep responses bounded even for a large failing import.
        $job['errors'][] = 'User ' . $id . ': ' . $message;
        $job['errors'] = array_slice( $job['errors'], -20 );
        WP_Cassify_Utils::wp_cassify_log( 'Manual CAS purge: user ID ' . $id . ': ' . $message, 'ERROR' );
    }

    public static function uninstall() {
        delete_metadata( 'user', 0, self::CAS_META, '', true );
        delete_metadata( 'user', 0, self::LOGIN_META, '', true );
        if ( is_multisite() ) {
            switch_to_blog( get_main_site_id() );
        }
        global $wpdb;
        $keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'wp_cassify_cleanup_' ) . '%' ) );
        foreach ( $keys as $key ) {
            delete_option( $key );
        }
        delete_site_option( self::YEARS );
        delete_site_option( self::DAYS );
        if ( is_multisite() ) {
            restore_current_blog();
        }
    }

    public static function render() {
        if ( ! self::allowed() || ( is_multisite() && ! is_network_admin() ) ) {
            echo '<p>You do not have permission to use the manual CAS account tools. On multisite, network activation and network user management permissions are required.</p>';
            return;
        }
        global $wp_cassify_network_activated;
        $days = self::configured_days();
        wp_enqueue_script( 'wp-cassify-cleanup', plugins_url( '../js/user-cleanup.js', __FILE__ ), array(), WP_CASSIFY_VERSION, true );
        wp_localize_script( 'wp-cassify-cleanup', 'wpCassifyCleanup', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'wp_cassify_cleanup' ) ) );
        ?>
        <div id="wp-cassify-cleanup">
            <p>Manual operations only. No scheduled purge. All roles are eligible, including administrators. WordPress may refuse deletion of network super administrators.</p>
            <p>Previous CAS connections cannot be reconstructed. Mark pre-created CAS accounts explicitly before including accounts without a connection date.</p>
            <fieldset id="cassify-cleanup-settings">
                <h3>Mark accounts as CAS</h3>
                <label>Scope <select id="cassify-cleanup-scope"><option value="all">All WordPress accounts (including local accounts)</option><option value="role">Accounts with a role</option></select></label>
                <label>Role slug <input id="cassify-cleanup-role" type="text" placeholder="subscriber" /></label>
                <?php if ( is_multisite() ) : ?>
                <label>Site ID for this role <input id="cassify-cleanup-blog" type="number" min="1" value="<?php echo esc_attr( get_main_site_id() ); ?>" /></label>
                <?php endif; ?>
                <button type="button" class="button" id="cassify-cleanup-mark">Preview CAS marking</button>
                <h3>Purge inactive CAS accounts</h3>
                <label>Inactivity (days) <input id="cassify-cleanup-days" type="number" min="1" max="36600" step="1" value="<?php echo esc_attr( $days ); ?>" /></label>
                <p><label><input id="cassify-cleanup-missing" type="checkbox" /> Include CAS accounts without a connection date immediately, regardless of the inactivity period</label></p>
                <label>Reassign content to username <input id="cassify-cleanup-target" type="text" autocomplete="off" spellcheck="false" /></label>
                <p>The recipient must be outside the purge preview. Posts, attachments, custom post types and links are reassigned; comments are retained.</p>
                <button type="button" class="button" id="cassify-cleanup-purge">Preview purge</button>
            </fieldset>
            <p id="cassify-cleanup-status" role="status" aria-live="polite"></p>
            <p id="cassify-cleanup-warning"></p>
            <ul id="cassify-cleanup-errors"></ul>
            <ul id="cassify-cleanup-users"></ul>
            <button type="button" class="button" id="cassify-cleanup-prev" hidden>Previous page</button>
            <button type="button" class="button" id="cassify-cleanup-next" hidden>Next page</button>
            <button type="button" class="button button-primary" id="cassify-cleanup-confirm" hidden>Confirm operation</button>
            <button type="button" class="button" id="cassify-cleanup-resume" hidden>Resume operation</button>
            <button type="button" class="button" id="cassify-cleanup-discard">Discard saved operation (completed changes are kept)</button>
            <p>Keep this page open while processing. After an interruption, reopen it and click Resume. A deleted CAS account may be recreated on its next CAS login if automatic account creation is enabled.</p>
        </div>
        <?php
    }
}
