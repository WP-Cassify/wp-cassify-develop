<?php
// Test-only MU plugin. Installed by support.js, never shipped in WP Cassify.
add_action( 'init', function () {
    if ( 'cleanup_fixture' !== ( $_REQUEST['wp_cassify_playwright_action'] ?? '' ) ) {
        return;
    }
    $expected = trim( (string) @file_get_contents( __DIR__ . '/wp-cassify-playwright-helper.token' ) );
    if ( ! $expected || ! hash_equals( $expected, (string) ( $_POST['token'] ?? '' ) ) ) {
        wp_send_json_error( array( 'message' => 'Invalid fixture token.' ), 403 );
    }
    $class = '\\wp_cassify\\WP_Cassify_User_Cleanup';
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
            foreach ( array( $class::CAS_META, $class::LOGIN_META ) as $key ) {
                $original[$id][$key] = array( metadata_exists( 'user', $id, $key ), get_user_meta( $id, $key, true ) );
            }
        }
        update_site_option( 'cassify_cleanup_e2e_original', $original );
        global $wp_cassify_network_activated;
        $settings = array();
        foreach ( array( $class::YEARS, $class::DAYS ) as $key ) {
            $settings[$key] = \wp_cassify\WP_Cassify_Utils::wp_cassify_get_option( $wp_cassify_network_activated, $key );
        }
        update_site_option( 'cassify_cleanup_e2e_settings', $settings );
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
            foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ) as $post_id ) { wp_delete_post( $post_id, true ); }
            foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_content LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ) as $comment_id ) { wp_delete_comment( $comment_id, true ); }
            if ( is_multisite() ) { restore_current_blog(); }
        }
        if ( is_multisite() ) {
            switch_to_blog( get_main_site_id() );
        }

        $options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wp_cassify_cleanup_job%' OR option_name = 'wp_cassify_cleanup_lock'" );
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
                    wp_insert_post( array( 'post_author' => $id, 'post_title' => $name, 'post_type' => $type, 'post_status' => 'publish' ) );
                }
                wp_insert_comment( array( 'user_id' => $id, 'comment_content' => $name ) );
            }
            if ( ! empty( $definition['blog'] ) ) {
                add_user_to_blog( (int) $definition['blog'], $id, 'editor' );
                switch_to_blog( (int) $definition['blog'] );
                wp_insert_post( array( 'post_author' => $id, 'post_title' => $name, 'post_status' => 'publish' ) );
                restore_current_blog();
            }
            $created[ $definition['name'] ] = $id;
        }
        wp_send_json_success( $created );
    }
    if ( 'remove' === $command ) {
        $user = get_userdata( absint( $_POST['id'] ) );
        if ( ! $user || 0 !== strpos( $user->user_login, $prefix ) ) { wp_send_json_error( array( 'message' => 'Fixture account required.' ) ); }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        require_once ABSPATH . 'wp-admin/includes/ms.php';
        if ( is_multisite() ) { wpmu_delete_user( $user->ID ); }
        else { wp_delete_user( $user->ID ); }
        wp_send_json_success( array() );
    }
    if ( 'setdate' === $command ) {
        $user = get_userdata( absint( $_POST['id'] ) );
        if ( ! $user || 0 !== strpos( $user->user_login, $prefix ) ) { wp_send_json_error( array( 'message' => 'Fixture account required.' ) ); }
        update_user_meta( $user->ID, $class::LOGIN_META, strtotime( '-3 years' ) );
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
    if ( 'legacy_duration' === $command ) {
        global $wp_cassify_network_activated;
        if ( $wp_cassify_network_activated ) { delete_site_option( $class::DAYS ); }
        else { delete_option( $class::DAYS ); }
        \wp_cassify\WP_Cassify_Utils::wp_cassify_update_option( $wp_cassify_network_activated, $class::YEARS, 2 );
        wp_send_json_success( array() );
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
        $posts = $wpdb->get_results( $wpdb->prepare( "SELECT post_author, post_type, post_title FROM {$wpdb->posts} WHERE post_title LIKE %s", $wpdb->esc_like( $prefix ) . '%' ), ARRAY_A );
        $comments = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_content LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) );
        $cron = _get_cron_array();
        $cleanup_cron = false;
        foreach ( $cron as $events ) {
            foreach ( array_keys( $events ) as $hook ) {
                if ( false !== strpos( $hook, 'cassify_cleanup' ) ) {
                    $cleanup_cron = true;
                }
            }
        }
        wp_send_json_success( array( 'users' => $data, 'posts' => $posts, 'comments' => $comments, 'cron' => $cleanup_cron ) );
    }
    wp_send_json_error( array( 'message' => 'Unknown fixture command.' ) );
} );
