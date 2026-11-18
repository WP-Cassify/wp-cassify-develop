const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const crypto = require('node:crypto');
const {
  ADMIN_USER, CAS_BASE_URL, CAS_USER, activatePluginIfNeeded,
  configureGeneralSettings, configureRoleRule, createNetworkSite, ensureAdminAccess,
  ensureMultisiteNetwork, loginThroughWordPress,
} = require('./support');

test.describe.configure({ retries: 0 });

test('upgrade a running 2.5.0 site with its database, sessions and cleanup cursor', async ({ page, browser, baseURL }) => {
  const plugin = process.env.PLAYWRIGHT_UPGRADE_PLUGIN;
  const candidate = process.env.PLAYWRIGHT_UPGRADE_CANDIDATE;
  test.skip(!plugin || !candidate, 'Requires the isolated 2.5.0 upgrade fixture');

  const multisite = process.env.PLAYWRIGHT_MULTISITE === 'true';
  await ensureAdminAccess(page);
  if (multisite) await ensureMultisiteNetwork(page);
  await activatePluginIfNeeded(page);
  await configureGeneralSettings(page, {
    baseUrl: CAS_BASE_URL + '/', createUserIfNotExist: true, enableSlo: true,
    disableAuthentication: false, enableGatewayMode: false,
  });
  let existingSite;
  let newSite;
  if (multisite) {
    existingSite = await createNetworkSite(page, { domain: 'wordpress1', title: 'Existing membership' });
    newSite = await createNetworkSite(page, { domain: 'wordpress2', title: 'No membership' });
  }
  await configureRoleRule(page, { purgeRolesBeforeApplying: true, blogId: multisite ? 1 : null });

  const fixtureToken = crypto.randomBytes(24).toString('hex');
  const fixture = '/work/wordpress/wp-content/mu-plugins/cassify-upgrade.php';
  fs.mkdirSync('/work/wordpress/wp-content/mu-plugins', { recursive: true });
  fs.writeFileSync(fixture, `<?php
add_action( 'init', function () {
    if ( ! hash_equals( '${fixtureToken}', $_SERVER['HTTP_X_CASSIFY_UPGRADE_TEST'] ?? '' ) ) {
        return;
    }
    if ( ( $_GET['cassify_upgrade_fixture'] ?? '' ) === 'session' ) {
        wp_send_json( array( 'logged_in' => is_user_logged_in() ) );
    }
    if ( empty( $_GET['cassify_upgrade_fixture'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $read = is_multisite() ? 'get_site_option' : 'get_option';
    $write = is_multisite() ? 'update_site_option' : 'update_option';
    if ( $_GET['cassify_upgrade_fixture'] === 'prepare' ) {
        $write( 'wp_cassify_allow_deny_order', 'allow, deny' );
        $write( 'wp_cassify_autorization_rules', serialize( array(
            'ALLOW|(CAS{cas_user_id} -EQ "joe")',
            'DENY|(CAS{cas_user_id} -EQ "joe")'
        ) ) );
        $write( 'wp_cassify_redirect_url_white_list', '/public/' );
        foreach ( array( 'upgrade-deleted-before', 'upgrade-inactive' ) as $login ) {
            $inactive = wp_create_user( $login, wp_generate_password() );
            update_user_meta( $inactive, 'wp_cassify_cas_user', 1 );
            update_user_meta( $inactive, 'wp_cassify_last_login_at', time() - 1000 * DAY_IN_SECONDS );
        }
        // The old batch can delete one account and transfer only part of the next one's content.
        for ( $i = 0; $i < 26; $i++ ) {
            $post = wp_insert_post( array(
                'post_author' => $inactive, 'post_title' => 'Upgrade test content ' . $i, 'post_status' => 'publish'
            ) );
        }
        update_option( 'cassify_upgrade_test_post', $post );
    }
    if ( $_GET['cassify_upgrade_fixture'] === 'add_role_rule' && is_multisite() ) {
        $rules = maybe_unserialize( $read( 'wp_cassify_user_role_rules', array() ) );
        $rules[] = 'subscriber|${Number(newSite) || 0}|(CAS{cas_user_id} -EQ "${CAS_USER}")';
        $write( 'wp_cassify_user_role_rules', serialize( $rules ) );
    }
    $cas_user = get_user_by( 'login', '${CAS_USER}' );
    if ( $_GET['cassify_upgrade_fixture'] === 'provision' && $cas_user && is_multisite() ) {
        add_user_to_blog( ${Number(existingSite) || 0}, $cas_user->ID, 'subscriber' );
        ( new WP_User( $cas_user->ID, '', 1 ) )->add_role( 'editor' );
    }
    wp_send_json( array(
        'root_roles' => $cas_user ? ( new WP_User( $cas_user->ID, '', 1 ) )->roles : array(),
        'existing_roles' => $cas_user && is_multisite() ? ( new WP_User( $cas_user->ID, '', ${Number(existingSite) || 0} ) )->roles : array(),
        'new_roles' => $cas_user && is_multisite() ? ( new WP_User( $cas_user->ID, '', ${Number(newSite) || 0} ) )->roles : array(),
        'version' => WP_CASSIFY_VERSION,
        'saved_version' => $read( 'wp_cassify_plugin_version' ),
        'order' => $read( 'wp_cassify_allow_deny_order' ),
        'timeout' => $read( 'wp_cassify_validation_timeout' ),
        'whitelist' => $read( 'wp_cassify_redirect_url_white_list' ),
        'login' => wp_get_current_user()->user_login,
        'inactive' => (bool) username_exists( 'upgrade-inactive' ),
        'content_author' => get_the_author_meta( 'user_login', (int) get_post_field( 'post_author', get_option( 'cassify_upgrade_test_post' ) ) ),
    ) );
} );
`);

  const settings = multisite ? '/wp-admin/network/settings.php?page=wp-cassify.php' : '/wp-admin/options-general.php?page=wp-cassify.php';
  const info = async (browserPage, mode = 'status') => {
    const response = await browserPage.request.get('/?cassify_upgrade_fixture=' + mode, { headers: { 'X-Cassify-Upgrade-Test': fixtureToken } });
    expect(response.ok()).toBeTruthy();
    return response.json();
  };
  const cleanup = async (operation, fields = {}) => {
    const nonce = await page.evaluate(() => wpCassifyCleanup.nonce);
    const response = await page.request.post('/wp-admin/admin-ajax.php', {
      form: { action: 'wp_cassify_cleanup', nonce, operation, ...fields },
    });
    expect(response.ok()).toBeTruthy();
    const body = await response.json();
    expect(body.success, JSON.stringify(body)).toBeTruthy();
    return body.data;
  };

  const casContext = await browser.newContext({ baseURL });
  try {
    expect(await info(page, 'prepare')).toMatchObject({ version: '2.5.0', order: 'allow, deny', login: ADMIN_USER });
    const casPage = await casContext.newPage();
    await loginThroughWordPress(casPage, '/wp-admin/profile.php');
    await expect(casPage.locator('#nickname')).toHaveValue(CAS_USER);
    expect((await casContext.cookies()).some(cookie => cookie.name === 'PHPSESSID')).toBeTruthy();

    if (multisite) {
      expect(await info(page, 'provision')).toMatchObject({ root_roles: ['administrator', 'editor'], existing_roles: ['subscriber'], new_roles: [] });
    }

    await page.goto(settings);
    let job = await cleanup('start', { mode: 'purge', days: '730' });
    const token = job.token;
    while (job.phase === 'scanning') job = await cleanup('scan', { token });
    expect(job).toMatchObject({ schema: 3, phase: 'ready' });
    expect(job.total).toBe(2);
    job = await cleanup('confirm', { token, target_username: ADMIN_USER, backup_confirmed: '1' });
    expect(job.phase).toBe('executing');
    do { job = await cleanup('batch', { token }); } while (job.phase === 'executing' && job.success === 0);
    expect(job).toMatchObject({ phase: 'executing', index: 1, success: 1 });
    expect(await info(page)).toMatchObject({ inactive: true, content_author: 'upgrade-inactive' });

    fs.cpSync(candidate, plugin, { recursive: true });

    await expect.poll(() => info(page)).toMatchObject({
      version: '2.6.0', saved_version: '2.6.0', order: 'deny, allow',
      timeout: '0', whitelist: '/public/', login: ADMIN_USER,
    });
    await page.goto(settings);
    job = await cleanup('status');
    expect(job).toMatchObject({ schema: 4, token, phase: 'executing', selection: 'inactive', index: 1, success: 1 });
    while (job.phase === 'executing') job = await cleanup('batch', { token });
    expect(job).toMatchObject({ phase: 'done', success: 2 });
    expect(await info(page)).toMatchObject({ inactive: false, content_author: ADMIN_USER });

    const casRequests = [];
    casPage.on('request', request => {
      if (request.isNavigationRequest() && request.url().startsWith(CAS_BASE_URL + '/login')) casRequests.push(request);
    });
    await loginThroughWordPress(casPage, '/wp-admin/profile.php');
    await expect(casPage.locator('#nickname')).toHaveValue(CAS_USER);
    expect(casRequests).toHaveLength(1);
    await casPage.reload();
    await expect(casPage.locator('#nickname')).toHaveValue(CAS_USER);
    expect(casRequests).toHaveLength(1);
    expect(await info(casPage, 'session')).toEqual({ logged_in: true });
    if (multisite) {
      expect(await info(page)).toMatchObject({ root_roles: ['administrator'] });
      await casPage.goto('http://wordpress1.example.org/wp-admin/profile.php');
      await expect(casPage.locator('#nickname')).toHaveValue(CAS_USER);
      const missingProfile = 'http://wordpress2.example.org/wp-admin/profile.php';
      const beforeRetry = casRequests.length;
      const firstAttemptStarted = Date.now();
      const denied = await casPage.goto(missingProfile);
      expect(denied.status()).toBe(403);
      expect(casRequests).toHaveLength(beforeRetry + 1);
      expect(await info(page)).toMatchObject({ existing_roles: ['subscriber'], new_roles: [] });
      expect(await info(page, 'add_role_rule')).toMatchObject({ new_roles: [] });

      const markedReturn = new URL(denied.url());
      expect(markedReturn.searchParams.get('wp_cassify_membership_retry')).toBe('1');
      markedReturn.searchParams.delete('ticket');
      const callbackWithoutTicket = await casPage.request.get(markedReturn.href, { maxRedirects: 0 });
      expect(callbackWithoutTicket.status()).toBe(403);

      expect((await casPage.goto(missingProfile)).status()).toBe(403);
      expect(casRequests).toHaveLength(beforeRetry + 1);

      await expect.poll(async () => {
        expect((await casPage.request.get(markedReturn.href, { maxRedirects: 0 })).status()).toBe(403);
        return (await casPage.goto(missingProfile)).status();
      }, { timeout: 40_000, intervals: [1000] }).toBe(200);
      expect(Date.now() - firstAttemptStarted).toBeGreaterThanOrEqual(29_000);
      await expect(casPage.locator('#nickname')).toHaveValue(CAS_USER);
      expect(casRequests).toHaveLength(beforeRetry + 2);
      expect(await info(page)).toMatchObject({ existing_roles: ['subscriber'], new_roles: ['subscriber'] });
    }
    await casPage.goto(CAS_BASE_URL + '/logout');
    await expect.poll(() => info(casPage, 'session')).toEqual({ logged_in: false });
    expect(await info(page, 'session')).toEqual({ logged_in: true });

    if (multisite) {
      const freshContext = await browser.newContext({ baseURL });
      try {
        const child = await freshContext.newPage();
        const childProfile = 'http://wordpress1.example.org/wp-admin/profile.php';
        const callback = child.waitForRequest(request => {
          const url = new URL(request.url());
          return request.isNavigationRequest() && url.hostname === 'wordpress1.example.org' &&
            url.searchParams.get('ticket')?.startsWith('ST-');
        });
        await Promise.all([callback, loginThroughWordPress(child, childProfile)]);
        await expect(child).toHaveURL(childProfile);
        await expect(child.locator('#nickname')).toHaveValue(CAS_USER);
        expect(await info(page)).toMatchObject({ existing_roles: ['subscriber'], new_roles: ['subscriber'] });
      } finally {
        await freshContext.close();
      }
    }
  } finally {
    await casContext.close();
    fs.rmSync(fixture, { force: true });
  }
});
