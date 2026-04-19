const { test, expect } = require('@playwright/test');
const {
  CAS_BASE_URL,
  CAS_USER,
  activatePluginIfNeeded,
  configureGeneralSettings,
  configureRoleRule,
  createNetworkSite,
  ensureAdminAccess,
  ensureMultisiteNetwork,
  loginThroughWordPress,
  logoutCas,
  logoutWordPress,
} = require('./support');

test.describe.configure({ mode: 'serial' });

test('bootstrap multisite, network-activate WP Cassify, and write the WordPress config files', async ({ page }) => {
  await ensureAdminAccess(page);
  await ensureMultisiteNetwork(page);
  await activatePluginIfNeeded(page);

  await configureGeneralSettings(page, {
    baseUrl: `${CAS_BASE_URL}/`,
    disableAuthentication: false,
    enableUrlBypass: false,
    createUserIfNotExist: true,
    enableGatewayMode: false,
    enableSlo: false,
  });

  await createNetworkSite(page, {
    domain: 'wordpress1',
    title: 'WP Cassify multisite 1',
    email: 'adm@example.org',
  });
  await createNetworkSite(page, {
    domain: 'wordpress2',
    title: 'WP Cassify multisite 2',
    email: 'adm@example.org',
  });


  await configureRoleRule(page, { purgeRolesBeforeApplying: false, blogId: 2 });

  await logoutWordPress(page);
  await logoutCas(page);
});

test('CAS login on wordpress1.example.org opens the multisite admin area', async ({ page }) => {
  await logoutCas(page);
  await loginThroughWordPress(page, 'http://wordpress1.example.org/wp-admin/');

  await expect(page.locator('span.display-name').filter({ hasText: CAS_USER }).first()).toBeVisible();
  await expect(page.locator('#wp-admin-bar-new-content')).toBeVisible();

  await page.goto('http://wordpress.example.org/');
  await expect(page.locator('#wp-admin-bar-my-account')).toBeVisible();

  await logoutWordPress(page);
});

test('CAS login on wordpress2.example.org also works after multisite bootstrap', async ({ page }) => {
  await logoutCas(page);
  await loginThroughWordPress(page, 'http://wordpress2.example.org/wp-admin/');

  await expect(page.locator('span.display-name').filter({ hasText: CAS_USER }).first()).toBeVisible();
  await expect(page.locator('#wp-admin-bar-new-content')).toHaveCount(0);

  await page.goto('http://wordpress1.example.org/');
  await expect(page.locator('#wp-admin-bar-my-account')).toBeVisible();

  await logoutWordPress(page);
});

