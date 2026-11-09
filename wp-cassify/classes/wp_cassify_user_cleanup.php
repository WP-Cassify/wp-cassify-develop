<?php
namespace wp_cassify;

defined( 'ABSPATH' ) || exit;

/** Manual, resumable operations. No scheduled tasks and no implicit CAS migration. */
class WP_Cassify_User_Cleanup {
    const CAS_META = 'wp_cassify_cas_user';
    const LOGIN_META = 'wp_cassify_last_login_at';
    const SINCE_META = 'wp_cassify_cas_marked_at';
    const SCHEMA = 3;
    const CONTENT_SIZE = 25;
    const STATE = 'wp_cassify_cleanup_job';
    const DAYS = 'wp_cassify_cleanup_days';
    const SCAN_SIZE = 200;
    const BATCH_SIZE = 20;

    private static $connection_lock = '';

    private static function assert_lock() {
        global $wpdb;
        if ( self::$connection_lock && '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT IS_USED_LOCK(%s) = CONNECTION_ID()', self::$connection_lock ) ) ) {
            self::error( 'The database connection lock was lost. Processing stopped; reload before resuming.' );
        }
    }

    public static function init() {
        add_action( 'wp_login', array( __CLASS__, 'record_login' ), 10, 2 );
        add_action( 'wp_ajax_wp_cassify_cleanup', array( __CLASS__, 'ajax' ) );
    }

    public static function record_login( $login, $user ) {
        update_user_meta( $user->ID, self::LOGIN_META, time() );
        if ( '1' === (string) get_user_meta( $user->ID, self::CAS_META, true ) ) {
            self::mark_cas( $user->ID );
        }
    }

    /** Also establish a grace period; never reconstruct historical CAS logins. */
    public static function mark_cas( $id ) {
        if ( ! metadata_exists( 'user', $id, self::SINCE_META ) ) {
            add_user_meta( $id, self::SINCE_META, time(), true );
        }
        update_user_meta( $id, self::CAS_META, '1' );
    }

    private static function supported() {
        global $wp_version;
        return version_compare( $wp_version, '4.9', '>=' )
            && ( ! is_multisite() || count( get_networks( array( 'number' => 2, 'fields' => 'ids' ) ) ) === 1 );
    }

    /** Privileged accounts, the operator, and live browser sessions are never purged. */
    private static function protected_user( $id ) {
        $account = get_userdata( $id );
        if ( ! $account ) { return true; }
        if ( (int) $id === get_current_user_id() || is_super_admin( $id )
            || user_can( $id, 'manage_options' ) || user_can( $id, 'delete_users' )
            || in_array( 'administrator', (array) $account->roles, true ) ) {
            return true;
        }
        if ( is_multisite() ) {
            foreach ( get_blogs_of_user( $id, true ) as $site ) {
                $user = new \WP_User( $id, '', $site->userblog_id );
                if ( in_array( 'administrator', $user->roles, true ) || $user->has_cap( 'manage_options' ) || $user->has_cap( 'delete_users' ) ) {
                    return true;
                }
            }
        }
        foreach ( \WP_Session_Tokens::get_instance( $id )->get_all() as $session ) {
            if ( ! empty( $session['expiration'] ) && $session['expiration'] > time() ) {
                return true;
            }
        }
        // API-only use does not reliably fire wp_login. Keep these accounts safe.
        return class_exists( '\WP_Application_Passwords' )
            && (bool) \WP_Application_Passwords::get_user_application_passwords( $id );
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
        return 730;
    }

    /** Public date-only result; protection checks belong to decision(). */
    public static function reason( $id, $cutoff, $now, $missing ) {
        $reason = self::date_reason( $id, $cutoff, $now, $missing );
        return in_array( $reason, array( 'old', 'missing', 'invalid' ), true ) ? $reason : '';
    }

    /** Invalid metadata must never be treated as a missing date. */
    private static function date_reason( $id, $cutoff, $now, $missing ) {
        if ( '1' !== (string) get_user_meta( $id, self::CAS_META, true ) ) { return 'not_cas'; }
        if ( ! metadata_exists( 'user', $id, self::LOGIN_META ) ) {
            $since = get_user_meta( $id, self::SINCE_META, true );
            if ( ! is_scalar( $since ) || ! ctype_digit( (string) $since ) || (float) $since <= 0 || (float) $since > $now ) {
                return 'unknown_marking_date';
            }
            if ( ! $missing ) { return 'missing_login_excluded'; }
            return (float) $since < $cutoff ? 'missing' : 'marking_grace_period';
        }
        $value = get_user_meta( $id, self::LOGIN_META, true );
        if ( ! is_scalar( $value ) || ! ctype_digit( (string) $value ) || (float) $value <= 0 || (float) $value > $now ) {
            return 'invalid';
        }
        return (float) $value < $cutoff ? 'old' : 'recent_login';
    }

    /** One decision policy, evaluated afresh at preview and each processing step. */
    private static function decision( $id, $job ) {
        clean_user_cache( $id );
        $reason = self::date_reason( $id, $job['cutoff'], time(), $job['missing'] );
        return in_array( $reason, array( 'old', 'missing' ), true ) && self::protected_user( $id ) ? 'protected' : $reason;
    }

    private static function eligible( $id, $job ) {
        return in_array( self::decision( $id, $job ), array( 'old', 'missing' ), true );
    }

    private static function snapshot( $id, $reason ) {
        $user = get_userdata( $id );
        return array( 'id' => $id, 'login' => $user ? $user->user_login : '(deleted)',
            'reason' => $reason, 'last_login_at' => get_user_meta( $id, self::LOGIN_META, true ),
            'cas_marked_at' => get_user_meta( $id, self::SINCE_META, true ) );
    }

    private static function archive_key( $token ) {
        return 'wp_cassify_cleanup_archive_' . $token;
    }

    private static function archive_job( $job ) {
        self::persist( self::archive_key( $job['token'] ), $job );
    }

    private static function discard( $job ) {
        if ( $job ) {
            self::archive_job( $job );
            for ( $page = 0; $page < $job['pages']; $page++ ) {
                delete_option( self::page_key( $job, $page ) );
            }
        }
        delete_option( self::STATE );
    }

    /** Audit snapshots survive discarding/replacing the working preview. */
    private static function export_archive( $request, $owner ) {
        global $wpdb;
        $token = isset( $request['archive_token'] ) ? $request['archive_token'] : '';
        if ( ! preg_match( '/^[a-f0-9-]{36}$/D', $token ) ) { self::error( 'Invalid audit identifier.' ); }
        $job = get_option( self::archive_key( $token ), array() );
        if ( ! $job || $job['owner'] !== $owner ) { self::error( 'Audit unavailable for this operator.' ); }
        // Retained previews are immutable; progress comes from the latest working state.
        $working = get_option( self::STATE, array() );
        if ( $working && $working['token'] === $token && $working['owner'] === $owner ) { $job = $working; }
        $offset = isset( $request['audit_offset'] ) ? max( 0, (int) $request['audit_offset'] ) : 0;
        $prefix = 'wp_cassify_cleanup_audit_' . $token . '_';
        $keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id LIMIT 200 OFFSET %d", $wpdb->esc_like( $prefix ) . '%', $offset ) );
        if ( $wpdb->last_error ) { self::error( 'Could not read the operation audit.' ); }
        $page = isset( $request['selection_page'] ) ? max( 0, (int) $request['selection_page'] ) : 0;
        $selection = $page < $job['pages'] ? get_option( self::archive_key( $token ) . '_page_' . $page, false ) : array();
        if ( ! is_array( $selection ) ) { self::error( 'An archived selection page is missing.' ); }
        return array( 'operation' => $job, 'audit' => array_map( 'get_option', $keys ), 'selection' => $selection );
    }

    private static function history( $request, $owner ) {
        global $wpdb;
        $offset = isset( $request['history_offset'] ) ? max( 0, (int) $request['history_offset'] ) : 0;
        $prefix = self::archive_key( '' );
        // Manifests only; snapshot pages have a longer suffix.
        $keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND LENGTH(option_name) = %d ORDER BY option_id DESC LIMIT 20 OFFSET %d", $wpdb->esc_like( $prefix ) . '%', strlen( $prefix ) + 36, $offset ) );
        if ( $wpdb->last_error ) { self::error( 'Could not read retained operations.' ); }
        $items = array();
        foreach ( $keys as $key ) {
            $job = get_option( $key );
            if ( $job && $job['owner'] === $owner ) {
                $items[] = array( 'token' => $job['token'], 'mode' => $job['mode'], 'time' => $job['now'], 'phase' => $job['phase'] );
            }
        }
        return array( 'operations' => $items, 'next_offset' => count( $keys ) === 20 ? $offset + 20 : null );
    }

    private static function page_key( $job, $page ) {
        return self::STATE . '_' . $job['token'] . '_' . $page;
    }

    private static function save( $job ) {
        self::persist( self::STATE, $job );
    }

    private static function persist( $key, $value ) {
        self::assert_lock();
        update_option( $key, $value, false );
        // Read the database, not a value left in the object cache after a failed write.
        global $wpdb;
        $stored = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ) );
        if ( null === $stored || maybe_unserialize( $stored ) !== $value ) {
            self::error( 'Operation state could not be saved. Processing stopped; check the database before resuming.' );
        }
    }

    private static function entries( $job, $page ) {
        $entries = get_option( self::page_key( $job, $page ), false );
        if ( ! is_array( $entries ) || ! $entries ) {
            self::error( 'A saved preview page is missing. Processing stopped.' );
        }
        return $entries;
    }

    /** Append-only events: retries never overwrite prior evidence. */
    private static function journal( $job, $event, $id = 0, $message = '' ) {
        $key = 'wp_cassify_cleanup_audit_' . $job['token'] . '_' . $event . '_' . $id . '_' . wp_generate_uuid4();
        self::persist( $key, array( 'time' => time(), 'operator' => $job['owner'],
            'mode' => $job['mode'], 'days' => $job['days'], 'missing' => $job['missing'],
            'target' => isset( $job['target'] ) ? $job['target'] : 0,
            'backup_confirmed_at' => isset( $job['backup_confirmed_at'] ) ? $job['backup_confirmed_at'] : 0,
            'site_offset' => isset( $job['site_offset'] ) ? $job['site_offset'] : 0,
            'event' => $event, 'user' => $id, 'message' => $message ) );
    }

    private static function has_event( $job, $event, $id ) {
        global $wpdb;
        $prefix = 'wp_cassify_cleanup_audit_' . $job['token'] . '_' . $event . '_' . $id . '_';
        $key = $wpdb->get_var( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 1", $wpdb->esc_like( $prefix ) . '%' ) );
        if ( $wpdb->last_error ) { self::error( 'Could not verify the previous account outcome.' ); }
        return null !== $key;
    }

    private static function error( $message ) {
        throw new \RuntimeException( $message );
    }

    /** All operation data lives on the main site, including AJAX from sub-sites. */
    public static function ajax() {
        if ( ! self::allowed() || ! self::supported() ) {
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
        // Connection-owned advisory lock: no lease can expire during a slow hook.
        // Shared database proxies that do not support named locks fail closed.
        $lock = 'cassify_' . md5( $wpdb->dbname . ':' . $wpdb->base_prefix );
        $locked = '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) );
        $status = 200;
        try {
            if ( ! $locked ) {
                self::error( 'Could not acquire the database lock. Another request may be running, or connection-owned named locks are unavailable.' );
            }
            self::$connection_lock = $lock;
            $result = self::dispatch( wp_unslash( $_POST ) );
        } catch ( \Throwable $error ) {
            $status = 400;
            $result = array( 'message' => $error->getMessage() );
            if ( $locked && self::$connection_lock ) {
                try {
                    $saved = get_option( self::STATE, array() );
                    if ( $saved && $saved['owner'] === get_current_user_id() ) {
                        self::journal( $saved, 'paused', 0, $error->getMessage() );
                    }
                } catch ( \Throwable $journal_error ) {
                    // Preserve the original failure when the database/lock is unavailable.
                }
            }
        } finally {
            if ( $locked ) {
                $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
            }
            self::$connection_lock = '';
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
        foreach ( $request as $value ) {
            if ( ! is_scalar( $value ) ) {
                self::error( 'Invalid request parameters.' );
            }
        }
        $action = isset( $request['operation'] ) ? $request['operation'] : '';
        $owner = get_current_user_id();
        if ( 'history' === $action ) { return self::history( $request, $owner ); }
        if ( 'archive' === $action ) { return self::export_archive( $request, $owner ); }
        $job = get_option( self::STATE, array() );
        if ( $job && $job['owner'] !== $owner && $job['phase'] !== 'done' ) {
            self::error( 'Another administrator owns the current operation.' );
        }
        if ( $job && ( ! isset( $job['schema'] ) || self::SCHEMA !== $job['schema'] ) && 'discard' !== $action ) {
            self::error( 'This preview predates the safety update. Discard it and create a new preview.' );
        }
        if ( 'discard' === $action ) {
            self::discard( $job );
            return array( 'phase' => 'idle' );
        }
        if ( 'start' === $action ) {
            $job = self::start( $request, $job, $owner );
        } elseif ( 'status' !== $action ) {
            if ( ! $job || empty( $request['token'] ) || ! hash_equals( $job['token'], $request['token'] ) || $job['owner'] !== $owner ) {
                self::error( 'Operation expired. Reload the preview.' );
            }
            if ( 'scan' === $action && 'scanning' === $job['phase'] ) {
                self::scan( $job );
            } elseif ( in_array( $action, array( 'prepare', 'confirm' ), true ) && 'ready' === $job['phase'] ) {
                self::confirm( $job, $request, $action );
            } elseif ( 'batch' === $action && 'executing' === $job['phase'] ) {
                self::batch( $job );
            } elseif ( ! in_array( $action, array( 'preview', 'export' ), true ) ) {
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
        $result['site_count'] = count( $job['sites'] );
        unset( $result['sites'] );
        $page = isset( $request['preview_page'] ) ? max( 0, (int) $request['preview_page'] ) : 0;
        $result['preview_page'] = $page;
        $result['users'] = $job['pages'] ? self::entries( $job, min( $page, $job['pages'] - 1 ) ) : array();
        if ( 'export' === $action ) {
            self::archive_job( $job );
            $export = self::export_archive( array_merge( $request, array( 'archive_token' => $job['token'] ) ), $owner );
            $result = array_merge( $result, $export );
        }
        return $result;
    }

    private static function start( $request, $job, $owner ) {
        global $wpdb, $wp_cassify_network_activated;
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
        if ( 'mark' === $mode && ! $role && ( ! isset( $request['all_accounts'] ) || '1' !== $request['all_accounts'] ) ) {
            self::error( 'Choose a role or explicitly acknowledge marking all accounts, including local accounts.' );
        }
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
        self::discard( $job );
        $now = time();
        $job = array(
            'schema' => self::SCHEMA, 'token' => wp_generate_uuid4(), 'owner' => $owner, 'mode' => $mode,
            'phase' => 'scanning', 'cursor' => 0, 'max' => (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->users}" ),
            'now' => $now, 'cutoff' => self::cutoff( $now, (int) $days ),
            'days' => (int) $days, 'missing' => ! empty( $request['missing'] ),
            'role' => $role, 'blog' => $blog, 'pages' => 0, 'page' => 0, 'index' => 0,
            'old' => 0, 'missing_count' => 0, 'invalid' => 0, 'total' => 0,
            'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => array(), 'protected' => 0,
            'sites' => is_multisite() ? get_sites( array( 'network_id' => get_current_network_id(), 'fields' => 'ids', 'number' => 0, 'orderby' => 'id', 'order' => 'ASC' ) ) : array(),
        );
        if ( is_multisite() && ! $job['sites'] ) { self::error( 'Could not read the network site list.' ); }
        WP_Cassify_Utils::wp_cassify_update_option( $wp_cassify_network_activated, self::DAYS, (int) $days );
        self::save( $job );
        self::archive_job( $job );
        return $job;
    }

    private static function scan( &$job ) {
        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID > %d AND ID <= %d ORDER BY ID LIMIT %d", $job['cursor'], $job['max'], self::SCAN_SIZE ) );
        if ( $wpdb->last_error ) { self::error( 'Could not read the user list.' ); }
        $entries = array();
        foreach ( $ids as $id ) {
            $id = (int) $id;
            $job['cursor'] = $id;
            if ( 'mark' === $job['mode'] ) {
                $user = new \WP_User( $id, '', $job['blog'] );
                if ( $job['role'] && ! in_array( $job['role'], $user->roles, true ) ) {
                    continue;
                }
                if ( '1' === (string) get_user_meta( $id, self::CAS_META, true ) && metadata_exists( 'user', $id, self::SINCE_META ) ) {
                    continue;
                }
                $reason = 'mark';
            } else {
                $reason = self::decision( $id, $job );
                if ( 'invalid' === $reason ) {
                    $job['invalid']++;
                    continue;
                }
                if ( ! in_array( $reason, array( 'old', 'missing', 'protected' ), true ) ) {
                    continue;
                }
                if ( 'protected' === $reason ) {
                    $job['protected']++;
                    continue;
                }
                $job[ 'missing' === $reason ? 'missing_count' : 'old' ]++;
            }
            $entries[] = self::snapshot( $id, $reason );
            $job['total']++;
        }
        if ( $entries ) {
            self::persist( self::page_key( $job, $job['pages'] ), $entries );
            self::persist( self::archive_key( $job['token'] ) . '_page_' . $job['pages'], $entries );
            $job['pages']++;
        }
        if ( count( $ids ) < self::SCAN_SIZE ) {
            $job['phase'] = 'ready';
            self::archive_job( $job );
        }
    }

    private static function confirm( &$job, $request, $action ) {
        if ( 'purge' === $job['mode'] ) {
            if ( 'confirm' === $action && ( ! isset( $request['backup_confirmed'] ) || '1' !== $request['backup_confirmed'] ) ) {
                self::error( 'Before purging, create a complete backup of the database and all site files, verify it can be restored, and confirm the backup checkbox.' );
            }
            $username = isset( $request['target_username'] ) ? trim( $request['target_username'] ) : '';
            $recipient = $username !== '' ? get_user_by( 'login', $username ) : false;
            if ( ! $recipient ) {
                self::error( 'Enter an existing WordPress username for content reassignment.' );
            }
            if ( 'confirm' === $action && isset( $job['target'] ) && (int) $recipient->ID !== $job['target'] ) {
                self::error( 'The reviewed content recipient changed. Create a new preview.' );
            }
            // Keep the resolved ID stable when resuming an operation.
            $job['target'] = (int) $recipient->ID;
            $job['target_login'] = $recipient->user_login;
            self::validate_target( $job );
            if ( 'confirm' === $action ) { $job['backup_confirmed_at'] = time(); }
        }
        if ( 'confirm' === $action ) {
            self::journal( $job, 'confirmed' );
            $job['phase'] = 'executing';
        }
        self::archive_job( $job );
    }

    private static function validate_target( $job ) {
        if ( ! $job['target'] || ! get_userdata( $job['target'] ) ) {
            self::error( 'Choose an existing recipient for the content.' );
        }
        if ( self::eligible( $job['target'], $job ) ) {
            self::error( 'The content recipient is a purge candidate. Choose another account.' );
        }
        // Also forbid recipients present in the confirmed preview, even if they reconnected.
        for ( $page = 0; $page < $job['pages']; $page++ ) {
            foreach ( self::entries( $job, $page ) as $entry ) {
                if ( (int) $entry['id'] === $job['target'] ) {
                    self::error( 'The content recipient belongs to the preview.' );
                }
            }
        }
    }

    private static function ensure_recipient( $job, $id ) {
        clean_user_cache( $job['target'] );
        if ( (int) $id === (int) $job['target'] || ! get_userdata( $job['target'] ) || self::eligible( $job['target'], $job ) ) {
            self::error( 'Content recipient is no longer valid. Operation paused.' );
        }
    }

    private static function batch( &$job ) {
        $deadline = microtime( true ) + 5;
        $processed = 0;
        while ( $job['page'] < $job['pages'] && $processed < self::BATCH_SIZE && microtime( true ) < $deadline ) {
            $entries = self::entries( $job, $job['page'] );
            if ( $job['index'] >= count( $entries ) ) {
                $job['page']++;
                $job['index'] = 0;
                continue;
            }
            $id = $entries[ $job['index'] ]['id'];
            // Save intent before touching any user or content; failures stop the request.
            self::journal( $job, 'processing', $id );
            if ( ! self::process_user( $job, $id ) ) {
                self::save( $job );
                return; // Content/site cursor is resumable, one bounded step at a time.
            }
            unset( $job['site_offset'] );
            $job['index']++;
            $processed++;
            self::save( $job );
        }
        if ( $job['page'] >= $job['pages'] ) {
            $job['phase'] = 'done';
            self::journal( $job, 'done' );
            self::archive_job( $job );
        }
    }

    /** true = account finished; false = another bounded content/site step needed. */
    private static function process_user( &$job, $id ) {
        // Avoid accepting metadata cached before a concurrent successful login.
        clean_user_cache( $id );
        if ( ! get_userdata( $id ) ) {
            $deleted = self::has_event( $job, 'deleted', $id );
            $job[ $deleted ? 'success' : 'skipped' ]++;
            self::journal( $job, $deleted ? 'recovered_deleted' : 'already_absent', $id );
            return true;
        }
        if ( 'mark' === $job['mode'] ) {
            $user = new \WP_User( $id, '', $job['blog'] );
            if ( $job['role'] && ! in_array( $job['role'], $user->roles, true ) ) {
                $job['skipped']++;
                return true;
            }
            self::mark_cas( $id );
            if ( '1' !== (string) get_user_meta( $id, self::CAS_META, true ) ) {
                self::failure( $job, $id, 'CAS marker could not be saved.' );
            } else {
                $job['success']++;
                self::journal( $job, 'marked', $id );
            }
            return true;
        }
        if ( ! self::eligible( $id, $job ) ) {
            $job['skipped']++;
            self::journal( $job, 'skipped', $id, self::decision( $id, $job ) . '; prior transfers, if any, are retained.' );
            return true;
        }
        self::ensure_recipient( $job, $id );
        require_once ABSPATH . 'wp-admin/includes/user.php';
        require_once ABSPATH . 'wp-admin/includes/bookmark.php';
        if ( is_multisite() ) {
            $offset = isset( $job['site_offset'] ) ? $job['site_offset'] : 0;
            if ( isset( $job['sites'][ $offset ] ) ) {
                $site_id = $job['sites'][ $offset ];
                if ( ! get_site( $site_id ) ) {
                    self::error( 'The network site list changed. Discard and recreate the preview.' );
                }
                self::assert_lock();
                switch_to_blog( $site_id );
                try {
                    $complete = self::transfer_content( $id, $job['target'] );
                    // Keep memberships until the final core network deletion. Retrying
                    // a transfer must not replay site-level user-deletion hooks.
                } finally {
                    restore_current_blog();
                }
                if ( $complete ) { $job['site_offset'] = $offset + 1; }
                return false;
            }
        } elseif ( ! self::transfer_content( $id, $job['target'] ) ) {
            return false;
        }
        if ( ! self::eligible( $id, $job ) ) {
            $job['skipped']++;
            self::journal( $job, 'skipped', $id, 'Activity changed during transfer; previous transfers are retained.' );
            return true;
        }
        self::ensure_recipient( $job, $id );
        self::assert_lock();
        if ( is_multisite() ) {
            require_once ABSPATH . 'wp-admin/includes/ms.php';
            self::verify_network_content( $job, $id );
        }
        // Core still owns account deletion and its standard hooks.
        $ok = self::delete_account( $job, $id );
        if ( ! $ok || get_userdata( $id ) ) {
            self::failure( $job, $id, 'WordPress could not delete this account.' );
            return true;
        }
        $job['success']++;
        self::journal( $job, 'deleted', $id );
        return true;
    }

    private static function verify_network_content( $job, $id ) {
        $sites = get_sites( array( 'network_id' => get_current_network_id(), 'fields' => 'ids', 'number' => 0, 'orderby' => 'id', 'order' => 'ASC' ) );
        if ( array_map( 'intval', $sites ) !== array_map( 'intval', $job['sites'] ) ) {
            self::error( 'The network site list changed. Discard and recreate the preview.' );
        }
        foreach ( $sites as $site_id ) {
            switch_to_blog( $site_id );
            try {
                if ( ! self::content_reassigned( $id ) ) {
                    self::error( 'Content appeared on a previously transferred site. Account retained; discard and recreate the preview.' );
                }
            } finally {
                restore_current_blog();
            }
        }
    }

    /** Guard core deletion, including content introduced by deletion hooks. */
    private static function delete_account( $job, $id ) {
        $depth = isset( $GLOBALS['_wp_switched_stack'] ) ? count( $GLOBALS['_wp_switched_stack'] ) : 0;
        $block = function () {
            self::error( 'A deletion hook attempted to delete content. Operation paused; inspect plugin compatibility before resuming.' );
        };
        $checkpoint = function ( $user_id ) use ( $job, $id ) {
            if ( (int) $user_id !== (int) $id ) { self::error( 'A deletion hook attempted to delete another account.' ); }
            if ( ! self::eligible( $id, $job ) ) { self::error( 'Account eligibility changed in a deletion hook. Account retained.' ); }
            self::ensure_recipient( $job, $id );
            self::assert_lock();
            if ( is_multisite() ) { self::verify_network_content( $job, $id ); }
            elseif ( ! self::content_reassigned( $id ) ) { self::error( 'Content appeared during deletion hooks. Account retained.' ); }
        };
        $guards = array( 'pre_delete_post', 'pre_delete_attachment', 'pre_trash_post', 'wp_trash_post', 'before_delete_post', 'delete_attachment', 'delete_link', 'wp_delete_file' );
        $user_hooks = array( 'delete_user', 'wpmu_delete_user', 'remove_user_from_blog' );
        foreach ( $guards as $hook ) { add_action( $hook, $block, -PHP_INT_MAX, 0 ); }
        foreach ( $user_hooks as $hook ) { add_action( $hook, $checkpoint, PHP_INT_MAX, 1 ); }
        try {
            return is_multisite() ? wpmu_delete_user( $id ) : wp_delete_user( $id, $job['target'] );
        } finally {
            foreach ( $guards as $hook ) { remove_action( $hook, $block, -PHP_INT_MAX ); }
            foreach ( $user_hooks as $hook ) { remove_action( $hook, $checkpoint, PHP_INT_MAX ); }
            // Core does not restore its switched site if a hook throws.
            while ( is_multisite() && count( $GLOBALS['_wp_switched_stack'] ) > $depth ) { restore_current_blog(); }
        }
    }

    /**
     * Transfer only ownership, with the same cache invalidation as wp_delete_user().
     * WordPress has no standalone ownership-transfer API: wp_delete_user() deletes
     * even after an UPDATE fails, while wp_update_post() modifies other fields,
     * invokes editing hooks and rejects post types of disabled plugins.
     * This narrowly scoped SQL exception is verified before deletion.
     */
    private static function transfer_content( $id, $target ) {
        global $wpdb;
        $deadline = microtime( true ) + 3;
        $posts = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author = %d ORDER BY ID LIMIT %d", $id, self::CONTENT_SIZE ) );
        if ( $wpdb->last_error ) { self::error( 'Could not read content. Account retained.' ); }
        foreach ( $posts as $post_id ) {
            if ( microtime( true ) >= $deadline ) { return false; }
            self::assert_lock();
            $result = $wpdb->update( $wpdb->posts, array( 'post_author' => $target ), array( 'ID' => (int) $post_id, 'post_author' => $id ), array( '%d' ), array( '%d', '%d' ) );
            clean_post_cache( $post_id );
            if ( false === $result || (string) $target !== (string) $wpdb->get_var( $wpdb->prepare( "SELECT post_author FROM {$wpdb->posts} WHERE ID = %d", $post_id ) ) ) {
                self::error( 'Content reassignment failed for post ' . $post_id . '. Account retained; prior transfers are kept.' );
            }
        }
        $links = $wpdb->get_col( $wpdb->prepare( "SELECT link_id FROM {$wpdb->links} WHERE link_owner = %d ORDER BY link_id LIMIT %d", $id, self::CONTENT_SIZE ) );
        if ( $wpdb->last_error ) { self::error( 'Could not read links. Account retained.' ); }
        foreach ( $links as $link_id ) {
            if ( microtime( true ) >= $deadline ) { return false; }
            self::assert_lock();
            $result = $wpdb->update( $wpdb->links, array( 'link_owner' => $target ), array( 'link_id' => (int) $link_id, 'link_owner' => $id ), array( '%d' ), array( '%d', '%d' ) );
            clean_bookmark_cache( $link_id );
            if ( false === $result || (string) $target !== (string) $wpdb->get_var( $wpdb->prepare( "SELECT link_owner FROM {$wpdb->links} WHERE link_id = %d", $link_id ) ) ) {
                self::error( 'Link reassignment failed. Account retained; prior transfers are kept.' );
            }
        }
        return self::content_reassigned( $id );
    }

    private static function content_reassigned( $id ) {
        // Read-only safety check: WP_Query omits unregistered types/statuses.
        // Fail closed on SQL errors (null is not '0'), before network deletion.
        // Content transfers are verified before invoking the irreversible core deletion.
        global $wpdb;
        $posts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d", $id ) );
        if ( null === $posts || ! ctype_digit( (string) $posts ) ) {
            self::error( 'Could not verify remaining content. Account retained.' );
        }
        $links = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->links} WHERE link_owner = %d", $id ) );
        if ( null === $links || ! ctype_digit( (string) $links ) ) {
            self::error( 'Could not verify remaining links. Account retained.' );
        }
        return '0' === (string) $posts && '0' === (string) $links;
    }

    private static function failure( &$job, $id, $message ) {
        self::journal( $job, 'failed', $id, $message );
        $job['failed']++;
        // Keep responses bounded even for a large failing import.
        $job['errors'][] = 'User ' . $id . ': ' . $message;
        $job['errors'] = array_slice( $job['errors'], -20 );
        WP_Cassify_Utils::wp_cassify_log( 'Manual CAS purge: user ID ' . $id . ': ' . $message, 'ERROR' );
    }

    public static function uninstall() {
        delete_metadata( 'user', 0, self::CAS_META, '', true );
        delete_metadata( 'user', 0, self::LOGIN_META, '', true );
        delete_metadata( 'user', 0, self::SINCE_META, '', true );
        if ( is_multisite() ) {
            switch_to_blog( function_exists( 'get_main_site_id' ) ? get_main_site_id() : get_current_site()->blog_id );
        }
        global $wpdb;
        $keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'wp_cassify_cleanup_' ) . '%' ) );
        foreach ( $keys as $key ) {
            delete_option( $key );
        }
        delete_site_option( self::DAYS );
        if ( is_multisite() ) {
            restore_current_blog();
        }
    }

    public static function render() {
        require __DIR__ . '/../admin/user-cleanup.php';
    }
}
