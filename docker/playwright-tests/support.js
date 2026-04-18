const { expect } = require('@playwright/test');

const BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://wordpress.example.org';
const CAS_BASE_URL = process.env.PLAYWRIGHT_CAS_BASE_URL || 'http://cas.example.org:8080/cas';
const ADMIN_USER = process.env.PLAYWRIGHT_ADMIN_USER || 'adm';
const ADMIN_PASSWORD = process.env.PLAYWRIGHT_ADMIN_PASSWORD || 'pass';
const CAS_USER = process.env.PLAYWRIGHT_CAS_USER || 'joe';
const CAS_PASSWORD = process.env.PLAYWRIGHT_CAS_PASSWORD || 'pass';
const BASELINE_ROLE_RULE = '(CAS{cas_user_id} -EQ "joe")';
const BASELINE_ROLE_RULE_OPTION = `administrator|${BASELINE_ROLE_RULE}`;

async function fillAndSubmitLocalLogin(page, { username = ADMIN_USER, password = ADMIN_PASSWORD } = {}) {
  await expect(page.locator('#user_login')).toBeVisible();
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.locator('#wp-submit').click();
}

async function fillAndSubmitCasLogin(page, { username = CAS_USER, password = CAS_PASSWORD } = {}) {
  await expect(page.locator('#username')).toBeVisible();
  await page.locator('#username').fill(username);
  await page.locator('#password').fill(password);
  await page.locator('[name="submitBtn"]').click();
}

async function installFreshWordPressIfNeeded(page) {
  await page.goto('/wp-admin/install.php');

  if (await page.locator('#language-continue').isVisible().catch(() => false)) {
    await page.locator('#language-continue').click();
  }

  if (await page.locator('#weblog_title').isVisible().catch(() => false)) {
    await page.locator('#weblog_title').fill('wp-cassify-test');
    await page.locator('#user_login').fill(ADMIN_USER);
    await page.locator('#pass1').fill(ADMIN_PASSWORD);
    const weakPassword = page.locator('input[name="pw_weak"]');
    if (await weakPassword.isVisible().catch(() => false)) {
      await weakPassword.check();
    }
    const pass2 = page.locator('#pass2');
    if (await pass2.isVisible().catch(() => false)) {
      await pass2.fill(ADMIN_PASSWORD);
    }
    await page.locator('#admin_email').fill('adm@example.org');
    await page.locator('#submit').click();
    await page.getByRole('link', { name: 'Log In' }).click();
    await fillAndSubmitLocalLogin(page);
  }
}

async function ensureAdminAccess(page) {
  await installFreshWordPressIfNeeded(page);

  if (await page.locator('#username').isVisible().catch(() => false)) {
    await fillAndSubmitCasLogin(page);
  } else if (await page.locator('#user_login').isVisible().catch(() => false)) {
    await fillAndSubmitLocalLogin(page);
  }

  await expect(page.locator('#wpadminbar')).toBeVisible();
}

async function activatePluginIfNeeded(page) {
  await page.goto('/wp-admin/plugins.php');

  const activateButton = page.locator('#activate-wp-cassify');
  if (await activateButton.isVisible().catch(() => false)) {
    await activateButton.click();
  }

  await expect(page.locator('#wp-admin-bar-my-account')).toBeVisible();
}

async function openPluginSettings(page) {
  await page.goto('/wp-admin/options-general.php?page=wp-cassify.php');

  if (await page.locator('#username').isVisible().catch(() => false)) {
    await fillAndSubmitCasLogin(page);
  } else if (await page.locator('#user_login').isVisible().catch(() => false)) {
    await fillAndSubmitLocalLogin(page);
  }

  await expect(page.locator('#wp_cassify_base_url')).toBeVisible();
}

async function ensureCheckboxState(locator, checked) {
  if (checked) {
    if (!(await locator.isChecked())) {
      await locator.check();
    }
  } else if (await locator.isChecked()) {
    await locator.uncheck();
  }
}

async function configureGeneralSettings(page, options = {}) {
  await openPluginSettings(page);

  if (Object.prototype.hasOwnProperty.call(options, 'baseUrl')) {
    await page.locator('#wp_cassify_base_url').fill(options.baseUrl ?? '');
  }

  if (Object.prototype.hasOwnProperty.call(options, 'disableAuthentication')) {
    await ensureCheckboxState(page.locator('#wp_cassify_disable_authentication'), options.disableAuthentication);
  }

  if (Object.prototype.hasOwnProperty.call(options, 'enableUrlBypass')) {
    await ensureCheckboxState(page.locator('#wp_cassify_enable_url_bypass'), options.enableUrlBypass);
  }

  if (Object.prototype.hasOwnProperty.call(options, 'bypassParameterName')) {
    await page.locator('#wp_cassify_bypass_parameter_name').fill(options.bypassParameterName ?? '');
  }

  if (Object.prototype.hasOwnProperty.call(options, 'bypassParameterValue')) {
    await page.locator('#wp_cassify_bypass_parameter_value').fill(options.bypassParameterValue ?? '');
  }

  if (Object.prototype.hasOwnProperty.call(options, 'createUserIfNotExist')) {
    await ensureCheckboxState(page.locator('#wp_cassify_create_user_if_not_exist'), options.createUserIfNotExist);
  }

  if (Object.prototype.hasOwnProperty.call(options, 'enableGatewayMode')) {
    await ensureCheckboxState(page.locator('#wp_cassify_enable_gateway_mode'), options.enableGatewayMode);
  }

  if (Object.prototype.hasOwnProperty.call(options, 'enableSlo')) {
    await ensureCheckboxState(page.locator('#wp_cassify_enable_slo'), options.enableSlo);
  }

  await page.locator('#wp_cassify_save_options_general_settings').click();
  await expect(page.locator('#wp_cassify_save_options_general_settings')).toBeVisible();
}

async function configureUrlSettings(page, options = {}) {
  await openPluginSettings(page);

  if (Object.prototype.hasOwnProperty.call(options, 'redirectUrlAfterLogout')) {
    await page.locator('#wp_cassify_redirect_url_after_logout').fill(options.redirectUrlAfterLogout ?? '');
  }

  if (Object.prototype.hasOwnProperty.call(options, 'overrideServiceUrl')) {
    await page.locator('#wp_cassify_override_service_url').fill(options.overrideServiceUrl ?? '');
  }

  if (Object.prototype.hasOwnProperty.call(options, 'serviceUrlValidationMode')) {
    await page.locator('#wp_cassify_service_url_validation_mode').selectOption(options.serviceUrlValidationMode);
  }

  if (Object.prototype.hasOwnProperty.call(options, 'serviceUrlAllowedHosts')) {
    await page.locator('#wp_cassify_service_url_allowed_hosts').fill(options.serviceUrlAllowedHosts ?? '');
  }

  await page.locator('#wp_cassify_save_options_urls_settings').click();
  await expect(page.locator('#wp_cassify_save_options_urls_settings')).toBeVisible();
}

async function configureRoleRule(page, { purgeRolesBeforeApplying = true } = {}) {
  await openPluginSettings(page);

  const existingRoleRuleOptions = await page.locator('#wp_cassify_user_role_rules option').allTextContents();
  if (!existingRoleRuleOptions.includes(BASELINE_ROLE_RULE_OPTION)) {
    await page.locator('#wp_cassify_user_role_rule').fill(BASELINE_ROLE_RULE);
    await page.locator('#wp_cassify_add_user_role_rule').click();
  }

  const roleRuleOptions = await page.locator('#wp_cassify_user_role_rules option').allTextContents();
  expect(roleRuleOptions).toContain(BASELINE_ROLE_RULE_OPTION);

  await ensureCheckboxState(page.locator('#wp_cassify_user_purge_user_roles_before_applying_rules'), purgeRolesBeforeApplying);

  await page.locator('#wp_cassify_save_options_users_roles_settings').click();
  await expect(page.locator('#wp_cassify_save_options_users_roles_settings')).toBeVisible();
}

async function loginThroughWordPress(page, path = '/wp-admin/') {
  await page.goto(path);

  if (await page.locator('#username').isVisible().catch(() => false)) {
    await fillAndSubmitCasLogin(page);
  } else if (await page.locator('#user_login').isVisible().catch(() => false)) {
    await fillAndSubmitLocalLogin(page);
  }
}

async function loginCasDirectly(page) {
  await page.goto(`${CAS_BASE_URL}/login`);
  await fillAndSubmitCasLogin(page);
}

async function loginLocalDirectly(page) {
  await page.goto('/wp-login.php');
  await fillAndSubmitLocalLogin(page);
}

async function logoutWordPress(page) {
  const myAccount = page.locator('#wp-admin-bar-my-account');
  await expect(myAccount).toBeVisible();
  await myAccount.hover();
  await page.locator('#wp-admin-bar-logout a').click();
  await expect(page.locator('body')).toBeVisible();
}

async function logoutCas(page) {
  await page.goto(`${CAS_BASE_URL}/logout`);
}

module.exports = {
  ADMIN_PASSWORD,
  ADMIN_USER,
  BASELINE_ROLE_RULE,
  BASELINE_ROLE_RULE_OPTION,
  BASE_URL,
  CAS_BASE_URL,
  CAS_PASSWORD,
  CAS_USER,
  activatePluginIfNeeded,
  configureGeneralSettings,
  configureRoleRule,
  configureUrlSettings,
  ensureAdminAccess,
  fillAndSubmitCasLogin,
  fillAndSubmitLocalLogin,
  installFreshWordPressIfNeeded,
  loginCasDirectly,
  loginLocalDirectly,
  loginThroughWordPress,
  logoutCas,
  logoutWordPress,
  openPluginSettings,
};


