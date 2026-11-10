<?php
// Local browser-test fixture, installed temporarily by membership.spec.js.
add_action('init', function () {
    if (empty($_POST['cassify_membership_test'])) return;
    $token = trim((string) @file_get_contents(__DIR__ . '/cassify-membership.token'));
    if (!$token || !hash_equals($token, (string) ($_POST['token'] ?? ''))) wp_die('Invalid fixture token', '', array('response' => 403));
    $sites = get_sites(array('number' => 3, 'orderby' => 'id', 'order' => 'ASC'));
    $child = (int) $sites[2]->blog_id;
    $user = get_user_by('login', 'joe');
    if (!$user) $user = get_user_by('id', wp_create_user('joe', wp_generate_password(), 'joe@example.org'));
    $action = $_POST['cassify_membership_test'];
    if ($action === 'session_snapshot') {
        $GLOBALS['wp-cassify']->wp_cassify_session_start(true);
        $id = get_current_blog_id();
        wp_send_json_success(array('user_auth' => $_SESSION['wp_cassify'][$id]['user_auth'] ?? false, 'has_attributes' => !empty($_SESSION['wp_cassify'][$id]['wp_cassify_cas_user_datas']), 'handler' => ini_get('session.save_handler')));
    }
    if ($action === 'prepare') {
        if (!get_site_option('cassify_membership_backup')) {
            global $wpdb;
            $keys = array('wp_cassify_base_url', 'wp_cassify_disable_authentication', 'wp_cassify_enable_gateway_mode', 'wp_cassify_enable_slo', 'wp_cassify_create_user_if_not_exist', 'wp_cassify_user_purge_user_roles_before_applying_rules', 'wp_cassify_autorization_rules', 'wp_cassify_expiration_rules', 'wp_cassify_user_role_rules', 'wp_cassify_service_url_validation_mode');
            $backup = array('options' => array(), 'caps' => array(), 'archived' => get_site($child)->archived);
            foreach ($keys as $key) $backup['options'][$key] = get_site_option($key, null);
            foreach (array(1, $child) as $id) {
                foreach (array('capabilities', 'user_level') as $suffix) {
                    $key = $wpdb->get_blog_prefix($id) . $suffix;
                    $backup['caps'][$key] = get_user_meta($user->ID, $key, true);
                }
            }
            update_site_option('cassify_membership_backup', $backup);
        }

        if (get_site_option('cassify_membership_original_super', null) === null) update_site_option('cassify_membership_original_super', is_super_admin($user->ID));
        revoke_super_admin($user->ID);
        update_blog_status($child, 'archived', 0);
        remove_user_from_blog($user->ID, $child);
        add_user_to_blog(1, $user->ID, 'subscriber');
        $rule = '(CAS{cas_user_id} -EQ "joe")';
        update_site_option('wp_cassify_base_url', 'http://cas.example.org:8080/cas/');
        update_site_option('wp_cassify_disable_authentication', '');
        update_site_option('wp_cassify_enable_gateway_mode', '');
        update_site_option('wp_cassify_enable_slo', '');
        update_site_option('wp_cassify_create_user_if_not_exist', 'create_user_if_not_exist');
        update_site_option('wp_cassify_user_purge_user_roles_before_applying_rules', '');
        update_site_option('wp_cassify_autorization_rules', serialize(array('ALLOW|' . $rule)));
        update_site_option('wp_cassify_expiration_rules', serialize(array()));
        update_site_option('wp_cassify_user_role_rules', serialize(array('administrator|' . $child . '|' . $rule)));
        update_site_option('wp_cassify_service_url_validation_mode', 'enforce');
    }
    if ($action === 'signin') {
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
    }
    if ($action === 'no_role') update_site_option('wp_cassify_user_role_rules', serialize(array('subscriber|1|')));
    if ($action === 'archive') {
        add_user_to_blog($child, $user->ID, 'administrator');
        update_site_option('wp_cassify_user_role_rules', serialize(array('subscriber|1|')));
        update_blog_status($child, 'archived', 1);
    }
    if ($action === 'cleanup') {
        update_blog_status($child, 'archived', 0);
        if (get_site_option('cassify_membership_original_super')) grant_super_admin($user->ID);
        delete_site_option('cassify_membership_original_super');
        $backup = get_site_option('cassify_membership_backup');
        if ($backup) {
            foreach ($backup['options'] as $key => $value) {
                if ($value === null) delete_site_option($key); else update_site_option($key, $value);
            }
            foreach ($backup['caps'] as $key => $value) {
                if ($value === '') delete_user_meta($user->ID, $key); else update_user_meta($user->ID, $key, $value);
            }
            update_blog_status($child, 'archived', $backup['archived']);
            delete_site_option('cassify_membership_backup');
        }
    }
    if ($action === 'unarchive') update_blog_status($child, 'archived', 0);
    global $wpdb;
    wp_send_json_success(array('child' => get_home_url($child), 'id' => $child, 'caps' => get_user_meta($user->ID, $wpdb->get_blog_prefix($child) . 'capabilities', true), 'member' => is_user_member_of_blog($user->ID, $child)));
}, -100);
