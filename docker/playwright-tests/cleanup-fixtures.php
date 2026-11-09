<?php
// Test-only MU plugin. Installed by support.js, never shipped in WP Cassify.
// Simulate a silent core reassignment failure without damaging database tables.
add_action( 'init', function () {
    $GLOBALS['cassify_cleanup_e2e_fail_lock'] = (bool) get_option( 'cassify_cleanup_e2e_fail_lock', false );
    $GLOBALS['cassify_cleanup_e2e_fail_state'] = (bool) get_option( 'cassify_cleanup_e2e_fail_state', false );
    $GLOBALS['cassify_cleanup_e2e_fail_author'] = (int) get_option( 'cassify_cleanup_e2e_fail_author', 0 );
}, -1 );
add_filter( 'query', function ( $query ) {
    if ( ! empty( $GLOBALS['cassify_cleanup_e2e_fail_lock'] ) && 0 === strpos( $query, 'SELECT GET_LOCK(' ) ) { return 'SELECT 1'; }
    if ( ! empty( $GLOBALS['cassify_cleanup_e2e_fail_state'] ) && false !== strpos( $query, 'UPDATE ' ) && false !== strpos( $query, "'wp_cassify_cleanup_job'" ) ) { return 'SELECT 1'; }
    $id = isset( $GLOBALS['cassify_cleanup_e2e_fail_author'] ) ? $GLOBALS['cassify_cleanup_e2e_fail_author'] : 0;
    global $wpdb;
    $unquoted = str_replace( '`', '', $query );
    if ( $id && preg_match( '/^UPDATE\s+' . preg_quote( $wpdb->posts, '/' ) . '\s+SET\s+post_author\s*=/i', $unquoted )
        && preg_match( '/(?:WHERE|AND)\s+post_author\s*=\s*' . $id . '(?:\s|$)/i', $unquoted ) ) {
        return 'SELECT 1';
    }
    return $query;
} );

add_action( 'init', function () {
    if ( 'wp_cassify_cleanup' !== ( $_POST['action'] ?? '' ) || 'batch' !== ( $_POST['operation'] ?? '' ) ) { return; }
    $failure = get_option( 'cassify_cleanup_e2e_fail_after_change', '' );
    $fail = function () { $GLOBALS['cassify_cleanup_e2e_fail_state'] = true; };
    if ( 'transfer' === $failure ) { add_action( 'clean_post_cache', $fail ); }
    if ( 'delete' === $failure ) { add_action( 'deleted_user', $fail ); }
    $mode = get_option( 'cassify_cleanup_e2e_deletion_hook', '' );
    if ( ! $mode ) { return; }
    $hook = is_multisite() ? 'wpmu_delete_user' : 'delete_user';
    add_action( $hook, function ( $id ) use ( $mode ) {
        if ( 'late' === $mode ) {
            wp_insert_post( array( 'post_author' => $id, 'post_title' => 'cassify_cleanup_e2e_hook', 'post_status' => 'publish' ) );
        } elseif ( 'delete_attachment' === $mode ) {
            global $wpdb;
            $attachment = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE 'cassify_cleanup_e2e_%' AND post_type = 'attachment' LIMIT 1" );
            wp_delete_attachment( $attachment, true );
        } elseif ( in_array( $mode, array( 'delete_post', 'trash_post', 'delete_link' ), true ) ) {
            global $wpdb;
            if ( 'delete_link' === $mode ) {
                require_once ABSPATH . 'wp-admin/includes/bookmark.php';
                $link = $wpdb->get_var( "SELECT link_id FROM {$wpdb->links} WHERE link_name LIKE 'cassify_cleanup_e2e_%' LIMIT 1" );
                wp_delete_link( $link );
            } else {
                $post = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE 'cassify_cleanup_e2e_%' AND post_type = 'post' LIMIT 1" );
                if ( 'trash_post' === $mode ) { wp_trash_post( $post ); }
                else { wp_delete_post( $post, true ); }
            }
        } elseif ( 'touch' === $mode ) {
            update_user_meta( $id, 'wp_cassify_last_login_at', time() );
        }
    } );
} );

add_action( 'init', function () {
    if ( 'cleanup_fixture' !== ( $_REQUEST['wp_cassify_playwright_action'] ?? '' ) ) {
        return;
    }
    $expected = trim( (string) @file_get_contents( __DIR__ . '/wp-cassify-playwright-helper.token' ) );
    if ( ! $expected || ! hash_equals( $expected, (string) ( $_POST['token'] ?? '' ) ) ) {
        wp_send_json_error( array( 'message' => 'Invalid fixture token.' ), 403 );
    }
    $class = '\wp_cassify\WP_Cassify_User_Cleanup';
    if ( ! class_exists( $class ) ) {
        wp_send_json_error( array( 'message' => 'Plugin is not active.' ) );
    }
    $command = $_POST['command'] ?? 'snapshot';
    $prefix = 'cassify_cleanup_e2e_';
    $ids = get_users( array( 'blog_id' => 0, 'search' => $prefix . '*', 'fields' => 'ids' ) );
    if ( 'checkpoint' === $command ) {
        $original = array();
        foreach ( get_users( array( 'blog_id' => 0, 'fields' => 'ids' ) ) as $id ) {
            $original[$id] = array();
            foreach ( array( $class::CAS_META, $class::LOGIN_META, $class::SINCE_META ) as $key ) {
                $original[$id][$key] = array( metadata_exists( 'user', $id, $key ), get_user_meta( $id, $key, true ) );
            }
        }
        update_site_option( 'cassify_cleanup_e2e_original', $original );
        global $wp_cassify_network_activated;
        $settings = array();
        foreach ( array( $class::DAYS ) as $key ) {
            $settings[$key] = \wp_cassify\WP_Cassify_Utils::wp_cassify_get_option( $wp_cassify_network_activated, $key );
        }
        update_site_option( 'cassify_cleanup_e2e_settings', $settings );
        wp_send_json_success( array() );
    }
    if ( 'fail_lock' === $command ) {
        update_option( 'cassify_cleanup_e2e_fail_lock', ! empty( $_POST['enabled'] ) );
        wp_send_json_success( array() );
    }
    if ( 'forget_marked' === $command ) {
        delete_user_meta( absint( $_POST['id'] ), $class::SINCE_META );
        wp_send_json_success( array() );
    }
    if ( 'fail_state' === $command ) {
        update_option( 'cassify_cleanup_e2e_fail_state', ! empty( $_POST['enabled'] ) );
        wp_send_json_success( array() );
    }
    if ( 'remove_page' === $command ) {
        $job = get_option( $class::STATE );
        delete_option( $class::STATE . '_' . $job['token'] . '_0' );
        wp_send_json_success( array() );
    }
    if ( 'hold_lock' === $command || 'lock_status' === $command ) {
        global $wpdb;
        $key = 'cassify_' . md5( $wpdb->dbname . ':' . $wpdb->base_prefix );
        if ( 'lock_status' === $command ) {
            wp_send_json_success( array( 'held' => (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT IS_USED_LOCK(%s)', $key ) ) ) );
        }
        $locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $key ) );
        if ( $locked ) {
            usleep( 2000000 );
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $key ) );
        }
        wp_send_json_success( array() );
    }
    if ( 'fail_reassignment' === $command ) {
        update_option( 'cassify_cleanup_e2e_fail_author', absint( $_POST['id'] ) );
        wp_send_json_success( array() );
    }
    if ( 'inspect' === $command ) {
        $user = get_user_by( 'login', sanitize_user( $_POST['login'] ) );
        wp_send_json_success( array( 'cas' => get_user_meta( $user->ID, $class::CAS_META, true ), 'date' => get_user_meta( $user->ID, $class::LOGIN_META, true ) ) );
    }
    if ( 'child_snapshot' === $command ) {
        switch_to_blog( absint( $_POST['blog'] ) );
        global $wpdb;
        $posts = $wpdb->get_results( $wpdb->prepare( "SELECT post_author FROM {$wpdb->posts} WHERE post_title LIKE %s", $wpdb->esc_like( $prefix ) . '%' ), ARRAY_A );
        restore_current_blog();
        wp_send_json_success( array( 'posts' => $posts ) );
    }
    if ( 'reset' === $command ) {
        delete_option( 'cassify_cleanup_e2e_fail_author' );
        delete_option( 'cassify_cleanup_e2e_fail_state' );
        delete_option( 'cassify_cleanup_e2e_fail_lock' );
        delete_option( 'cassify_cleanup_e2e_deletion_hook' );
        delete_option( 'cassify_cleanup_e2e_fail_after_change' );
        if ( $archived = get_site_option( 'cassify_cleanup_e2e_archived' ) ) {
            update_blog_status( $archived, 'archived', 0 );
            delete_site_option( 'cassify_cleanup_e2e_archived' );
        }
        require_once ABSPATH . 'wp-admin/includes/bookmark.php';
        require_once ABSPATH . 'wp-admin/includes/user.php';
        require_once ABSPATH . 'wp-admin/includes/ms.php';
        foreach ( $ids as $id ) {
            if ( is_multisite() ) {
                revoke_super_admin( $id );
                wpmu_delete_user( $id );
            } else {
                wp_delete_user( $id );
            }
        }
        foreach ( get_site_option( 'cassify_cleanup_e2e_original', array() ) as $id => $meta ) {
            if ( ! get_userdata( $id ) ) { continue; }
            foreach ( $meta as $key => $saved ) {
                if ( $saved[0] ) { update_user_meta( $id, $key, $saved[1] ); }
                else { delete_user_meta( $id, $key ); }
            }
        }
        delete_site_option( 'cassify_cleanup_e2e_original' );
        $settings = get_site_option( 'cassify_cleanup_e2e_settings', array() );
        global $wp_cassify_network_activated;
        foreach ( $settings as $key => $value ) {
            if ( false === $value ) {
                if ( $wp_cassify_network_activated ) { delete_site_option( $key ); }
                else { delete_option( $key ); }
            } else {
                \wp_cassify\WP_Cassify_Utils::wp_cassify_update_option( $wp_cassify_network_activated, $key, $value );
            }
        }
        delete_site_option( 'cassify_cleanup_e2e_settings' );
        global $wpdb;
        $sites = is_multisite() ? get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) : array( get_current_blog_id() );
        foreach ( $sites as $site_id ) {
            if ( is_multisite() ) { switch_to_blog( $site_id ); }
            foreach ( get_bookmarks( array( 'hide_invisible' => false, 'limit' => -1 ) ) as $link ) {
                if ( 0 === strpos( $link->link_name, $prefix ) ) { wp_delete_link( $link->link_id ); }
            }
            foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ) as $post_id ) { wp_delete_post( $post_id, true ); }
            foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_content LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ) as $comment_id ) { wp_delete_comment( $comment_id, true ); }
            if ( is_multisite() ) { restore_current_blog(); }
        }
        if ( is_multisite() ) {
            switch_to_blog( get_main_site_id() );
        }

        $options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wp_cassify_cleanup_job%' OR option_name LIKE 'wp_cassify_cleanup_archive_%' OR option_name LIKE 'wp_cassify_cleanup_audit_%'" );
        foreach ( $options as $option ) {
            delete_option( $option );
        }
        if ( is_multisite() ) {
            restore_current_blog();
        }
        wp_send_json_success( array() );
    }
    if ( 'create' === $command ) {
        $definitions = json_decode( wp_unslash( $_POST['users'] ?? '[]' ), true );
        $created = array();
        foreach ( $definitions as $definition ) {
            $name = $prefix . sanitize_key( $definition['name'] );
            $id = wp_insert_user( array( 'user_login' => $name, 'user_pass' => 'cleanup-pass', 'user_email' => $name . '@example.org', 'role' => $definition['role'] ?? 'subscriber' ) );
            if ( is_wp_error( $id ) ) {
                wp_send_json_error( array( 'message' => $id->get_error_message() ) );
            }
            if ( ! empty( $definition['cas'] ) ) {
                update_user_meta( $id, $class::CAS_META, '1' );
                update_user_meta( $id, $class::SINCE_META, isset( $definition['marked'] ) ? $definition['marked'] : strtotime( '-3 years' ) );
            }
            if ( array_key_exists( 'date', $definition ) ) {
                $date = $definition['date'];
                if ( 'old' === $date ) {
                    $date = strtotime( '-3 years' );
                } elseif ( 'recent' === $date ) {
                    $date = time();
                } elseif ( 'future' === $date ) {
                    $date = time() + 86400;
                }
                update_user_meta( $id, $class::LOGIN_META, $date );
            }
            if ( ! empty( $definition['super'] ) && is_multisite() ) {
                grant_super_admin( $id );
            }
            if ( ! empty( $definition['posts'] ) ) {
                foreach ( array( 'post', 'page', 'attachment', 'cassify_fixture' ) as $type ) {
                    $post_id = wp_insert_post( array( 'post_author' => $id, 'post_title' => $name, 'post_type' => $type, 'post_status' => 'publish' ) );
                    if ( 'attachment' === $type ) {
                        $upload = wp_upload_bits( $name . '.txt', null, 'Cleanup preservation fixture' );
                        update_attached_file( $post_id, $upload['file'] );
                    }
                    update_post_meta( $post_id, 'cassify_cleanup_fixture', 'preserved' );
                    wp_set_object_terms( $post_id, 'cassify_cleanup_fixture', 'category' );
                }
                require_once ABSPATH . 'wp-admin/includes/bookmark.php';
                wp_insert_link( array( 'link_owner' => $id, 'link_name' => $name, 'link_url' => 'https://example.org/' ) );
                wp_insert_comment( array( 'user_id' => $id, 'comment_content' => $name ) );
            }
            if ( ! empty( $definition['blog'] ) ) {
                add_user_to_blog( (int) $definition['blog'], $id, 'editor' );
                switch_to_blog( (int) $definition['blog'] );
                wp_insert_post( array( 'post_author' => $id, 'post_title' => $name, 'post_status' => 'publish' ) );
                if ( ! empty( $definition['orphan'] ) ) { remove_user_from_blog( $id, (int) $definition['blog'] ); }
                restore_current_blog();
                if ( ! empty( $definition['archived'] ) ) {
                    update_blog_status( (int) $definition['blog'], 'archived', 1 );
                    update_site_option( 'cassify_cleanup_e2e_archived', (int) $definition['blog'] );
                }
            }
            for ( $i = 0; $i < ( $definition['extra_posts'] ?? 0 ); $i++ ) {
                wp_insert_post( array( 'post_author' => $id, 'post_title' => $name . '_' . $i, 'post_status' => 'draft' ) );
            }
            $created[ $definition['name'] ] = $id;
        }
        wp_send_json_success( $created );
    }
    if ( 'remove' === $command ) {
        $user = get_userdata( absint( $_POST['id'] ) );
        if ( ! $user || 0 !== strpos( $user->user_login, $prefix ) ) { wp_send_json_error( array( 'message' => 'Fixture account required.' ) ); }
        require_once ABSPATH . 'wp-admin/includes/bookmark.php';
        require_once ABSPATH . 'wp-admin/includes/user.php';
        require_once ABSPATH . 'wp-admin/includes/ms.php';
        if ( is_multisite() ) { wpmu_delete_user( $user->ID ); }
        else { wp_delete_user( $user->ID ); }
        wp_send_json_success( array() );
    }
    if ( 'late_post' === $command ) {
        if ( is_multisite() ) { switch_to_blog( absint( $_POST['blog'] ) ); }
        $id = wp_insert_post( array( 'post_author' => absint( $_POST['id'] ), 'post_title' => $prefix . 'late', 'post_status' => 'publish' ) );
        if ( is_multisite() ) { restore_current_blog(); }
        wp_send_json_success( array( 'id' => $id ) );
    }
    if ( 'promote' === $command ) {
        $user = new WP_User( absint( $_POST['id'] ) );
        $user->set_role( 'administrator' );
        wp_send_json_success( array() );
    }
    if ( 'deletion_hook' === $command || 'fail_after_change' === $command ) {
        update_option( $prefix . $command, sanitize_key( $_POST['mode'] ?? '' ) );
        wp_send_json_success( array() );
    }
    if ( 'setdate' === $command ) {
        $user = get_userdata( absint( $_POST['id'] ) );
        if ( ! $user || 0 !== strpos( $user->user_login, $prefix ) ) { wp_send_json_error( array( 'message' => 'Fixture account required.' ) ); }
        update_user_meta( $user->ID, $class::LOGIN_META, strtotime( '-3 years' ) );
        wp_send_json_success( array() );
    }
    if ( 'application_password' === $command ) {
        $result = WP_Application_Passwords::create_new_application_password( absint( $_POST['id'] ), array( 'name' => 'Cleanup test' ) );
        wp_send_json_success( array( 'ok' => ! is_wp_error( $result ) ) );
    }
    if ( 'audit' === $command ) {
        $event = sanitize_key( $_POST['event'] );
        global $wpdb;
        $keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC", $wpdb->esc_like( 'wp_cassify_cleanup_audit_' . sanitize_text_field( $_POST['job'] ) . '_' ) . '%' ) );
        foreach ( $keys as $key ) {
            $row = get_option( $key );
            if ( $row['event'] === $event && $row['user'] === absint( $_POST['id'] ) ) { wp_send_json_success( $row ); }
        }
        wp_send_json_success( array() );
    }
    if ( 'session' === $command ) {
        WP_Session_Tokens::get_instance( absint( $_POST['id'] ) )->create( time() + 86400 );
        wp_send_json_success( array() );
    }
    if ( 'touch' === $command ) {
        $id = absint( $_POST['id'] );
        $user = get_userdata( $id );
        do_action( 'wp_login', $user->user_login, $user );
        wp_send_json_success( array() );
    }
    if ( 'admin_signin' === $command ) {
        // Authenticate the local network administrator without entering the CAS redirect.
        if ( isset( $GLOBALS['wp-cassify'] ) ) {
            remove_action( 'wp_authenticate', array( $GLOBALS['wp-cassify'], 'wp_cassify_redirect' ), 1 );
        }
        $user = wp_signon( array( 'user_login' => sanitize_user( $_POST['login'] ), 'user_password' => $_POST['password'] ), false );
        if ( is_wp_error( $user ) ) { wp_send_json_error( array( 'message' => $user->get_error_message() ) ); }
        wp_send_json_success( array( 'ok' => true ) );
    }
    if ( 'signin' === $command ) {
        if ( isset( $GLOBALS['wp-cassify'] ) ) { remove_action( 'wp_authenticate', array( $GLOBALS['wp-cassify'], 'wp_cassify_redirect' ), 1 ); }
        $user = wp_signon( array( 'user_login' => $prefix . sanitize_key( $_POST['name'] ), 'user_password' => ! empty( $_POST['wrong'] ) ? 'wrong-password' : 'cleanup-pass' ), false );
        wp_send_json_success( array( 'ok' => ! is_wp_error( $user ) ) );
    }
    if ( 'calendar' === $command ) {
        $now = strtotime( '2024-02-29 12:34:56 UTC' );
        $cutoff = $class::cutoff( $now, 1 );
        $id = absint( $_POST['id'] );
        update_user_meta( $id, $class::LOGIN_META, $cutoff );
        $exact = $class::reason( $id, $cutoff, $now, true );
        update_user_meta( $id, $class::LOGIN_META, $cutoff - 1 );
        wp_send_json_success( array( 'date' => gmdate( 'Y-m-d H:i:s', $cutoff ), 'exact' => $exact, 'old' => $class::reason( $id, $cutoff, $now, true ) ) );
    }
    if ( 'snapshot' === $command ) {
        global $wpdb;
        $data = array();
        foreach ( $ids as $id ) {
            $user = get_userdata( $id );
            $data[ str_replace( $prefix, '', $user->user_login ) ] = array(
                'id' => (int) $id, 'cas' => get_user_meta( $id, $class::CAS_META, true ),
                'date' => get_user_meta( $id, $class::LOGIN_META, true ),
            );
        }
        $posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_author, post_type, post_title FROM {$wpdb->posts} WHERE post_title LIKE %s", $wpdb->esc_like( $prefix ) . '%' ), ARRAY_A );
        $links = array_values( array_filter( get_bookmarks( array( 'hide_invisible' => false, 'limit' => -1 ) ), function ( $link ) use ( $prefix ) { return 0 === strpos( $link->link_name, $prefix ); } ) );
        $comments = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_content LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) );
        foreach ( $posts as &$post ) {
            $post_id = (int) $post['ID'];
            $post['file_exists'] = 'attachment' === $post['post_type'] ? file_exists( get_attached_file( $post_id ) ) : null;
            $post['meta'] = get_post_meta( $post_id, 'cassify_cleanup_fixture', true );
            $post['terms'] = wp_get_object_terms( $post_id, 'category', array( 'fields' => 'slugs' ) );
        }
        unset( $post );
        $cron = _get_cron_array();
        $cleanup_cron = false;
        foreach ( $cron as $events ) {
            foreach ( array_keys( $events ) as $hook ) {
                if ( false !== strpos( $hook, 'cassify_cleanup' ) ) {
                    $cleanup_cron = true;
                }
            }
        }
        wp_send_json_success( array( 'links' => $links, 'users' => $data, 'posts' => $posts, 'comments' => $comments, 'cron' => $cleanup_cron ) );
    }
    wp_send_json_error( array( 'message' => 'Unknown fixture command.' ) );
} );
