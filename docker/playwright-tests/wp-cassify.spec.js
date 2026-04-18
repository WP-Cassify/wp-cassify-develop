const { test, expect } = require('@playwright/test');
const {
  BASELINE_ROLE_RULE,
  BASELINE_ROLE_RULE_OPTION,
  CAS_BASE_URL,
  CAS_USER,
  activatePluginIfNeeded,
  configureGeneralSettings,
  configureRoleRule,
  configureUrlSettings,
  ensureAdminAccess,
  fillAndSubmitLocalLogin,
  loginCasDirectly,
  loginLocalDirectly,
  loginThroughWordPress,
  logoutCas,
  logoutWordPress,
} = require('./support');

test.describe.configure({ mode: 'serial' });

test('setup WordPress, activate WP Cassify and configure the baseline', async ({ page }) => {
  await ensureAdminAccess(page);
  await activatePluginIfNeeded(page);
  await configureGeneralSettings(page, {
    baseUrl: `${CAS_BASE_URL}/`,
    disableAuthentication: false,
    enableUrlBypass: false,
    createUserIfNotExist: true,
    enableGatewayMode: false,
    enableSlo: false,
  });
  await configureRoleRule(page, { purgeRolesBeforeApplying: true });

  await expect(page.locator('#wp_cassify_base_url')).toHaveValue(`${CAS_BASE_URL}/`);
  await expect(page.locator('#wp_cassify_create_user_if_not_exist')).toBeChecked();
  await expect(page.locator('#wp_cassify_disable_authentication')).not.toBeChecked();
  await expect(page.locator('#wp_cassify_enable_gateway_mode')).not.toBeChecked();
  await expect(page.locator('#wp_cassify_enable_slo')).not.toBeChecked();
  await expect(page.locator('#wp_cassify_enable_url_bypass')).not.toBeChecked();
  const roleRuleOptions = await page.locator('#wp_cassify_user_role_rules option').allTextContents();
  expect(roleRuleOptions).toContain(BASELINE_ROLE_RULE_OPTION);
});

test('CAS login opens the admin area and the profile page', async ({ page }) => {
  await loginThroughWordPress(page, '/wp-admin/profile.php');

  await expect(page.locator('#nickname')).toHaveValue(CAS_USER);
  await expect(page.locator('#wp-admin-bar-new-content')).toBeVisible();

  await logoutWordPress(page);
});

test('gateway mode can be enabled and disabled without breaking the SSO flow', async ({ page }) => {

  await loginCasDirectly(page);
  await expect(page.locator('#main-content')).toBeVisible();
  await page.goto('/');
  await expect(page.locator('#wp-admin-bar-my-account')).toHaveCount(0);

  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, { enableGatewayMode: true });
  await logoutWordPress(page);

  await loginCasDirectly(page);
  await expect(page.locator('#main-content')).toBeVisible();
  await page.goto('/');
  await expect(page.locator('#wp-admin-bar-my-account')).toBeVisible();

  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, { enableGatewayMode: false });
});

test('Single Logout can be enabled and disabled safely', async ({ page }) => {
  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, { enableSlo: false });
  await logoutWordPress(page);
  await loginCasDirectly(page);

  await page.goto('/wp-admin/');
  await expect(page.locator('#wpadminbar')).toBeVisible();

  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, { enableSlo: true });
  await logoutWordPress(page);

  await page.goto('/wp-admin/');
  await expect(page.locator('#wpadminbar')).toHaveCount(0);
  await expect(page.locator('#username')).toBeVisible();

  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, { enableSlo: false });
});

test('local login, create-user toggle and URL settings persist as expected', async ({ page }) => {
  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, { disableAuthentication: true });
  await logoutWordPress(page);

  await page.goto('/wp-login.php');
  await expect(page.locator('#user_login')).toBeVisible();
  await expect(page.locator('#username')).toHaveCount(0);
  await loginLocalDirectly(page);
  await expect(page.locator('#wpadminbar')).toBeVisible();

  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, {
    disableAuthentication: false,
    createUserIfNotExist: false,
  });
  await expect(page.locator('#wp_cassify_create_user_if_not_exist')).not.toBeChecked();

  await configureGeneralSettings(page, { createUserIfNotExist: true });
  await expect(page.locator('#wp_cassify_create_user_if_not_exist')).toBeChecked();

  await configureUrlSettings(page, {
    redirectUrlAfterLogout: 'http://wordpress.example.org/?from=playwright',
    overrideServiceUrl: 'https://wordpress.example.org/wp-admin/',
    serviceUrlValidationMode: 'enforce',
    serviceUrlAllowedHosts: 'wordpress.example.org,.example.org',
  });
  await expect(page.locator('#wp_cassify_redirect_url_after_logout')).toHaveValue('http://wordpress.example.org/?from=playwright');
  await expect(page.locator('#wp_cassify_override_service_url')).toHaveValue('https://wordpress.example.org/wp-admin/');
  await expect(page.locator('#wp_cassify_service_url_validation_mode')).toHaveValue('enforce');
  await expect(page.locator('#wp_cassify_service_url_allowed_hosts')).toHaveValue('wordpress.example.org,.example.org');

  await configureUrlSettings(page, {
    redirectUrlAfterLogout: '',
    overrideServiceUrl: '',
    serviceUrlValidationMode: 'monitor',
    serviceUrlAllowedHosts: '',
  });
  await expect(page.locator('#wp_cassify_redirect_url_after_logout')).toHaveValue('');
  await expect(page.locator('#wp_cassify_override_service_url')).toHaveValue('');
  await expect(page.locator('#wp_cassify_service_url_validation_mode')).toHaveValue('monitor');
  await expect(page.locator('#wp_cassify_service_url_allowed_hosts')).toHaveValue('');

  await configureGeneralSettings(page, { disableAuthentication: false, createUserIfNotExist: true });
});

test('URL bypass is disabled by default, configurable, and can be turned off again', async ({ page }) => {
  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, {
    enableUrlBypass: false,
    bypassParameterName: 'wp_cassify_bypass',
    bypassParameterValue: 'bypass',
    disableAuthentication: false,
  });
  await logoutWordPress(page);
  await logoutCas(page);

  await page.goto('/wp-login.php?wp_cassify_bypass=bypass&redirect_to=/wp-admin/');
  await expect(page.locator('#username')).toBeVisible();
  await expect(page.locator('#user_login')).toHaveCount(0);

  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, {
    enableUrlBypass: true,
    bypassParameterName: 'cassify_test_bypass',
    bypassParameterValue: 'letmein',
  });
  await expect(page.locator('#wp_cassify_enable_url_bypass')).toBeChecked();
  await expect(page.locator('#wp_cassify_bypass_parameter_name')).toHaveValue('cassify_test_bypass');
  await expect(page.locator('#wp_cassify_bypass_parameter_value')).toHaveValue('letmein');

  await logoutWordPress(page);
  await logoutCas(page);

  await page.goto('/wp-login.php?cassify_test_bypass=letmein&redirect_to=/wp-admin/');
  await expect(page.locator('#user_login')).toBeVisible();
  await expect(page.locator('#username')).toHaveCount(0);

  await fillAndSubmitLocalLogin(page);
  await expect(page.locator('#wpadminbar')).toBeVisible();

  await loginThroughWordPress(page, '/wp-admin/options-general.php?page=wp-cassify.php');
  await configureGeneralSettings(page, {
    enableUrlBypass: false,
    bypassParameterName: 'wp_cassify_bypass',
    bypassParameterValue: 'bypass',
  });

  await logoutWordPress(page);
  await logoutCas(page);
  await page.goto('/wp-login.php?cassify_test_bypass=letmein&redirect_to=/wp-admin/');
  await expect(page.locator('#username')).toBeVisible();
  await expect(page.locator('#user_login')).toHaveCount(0);
});



