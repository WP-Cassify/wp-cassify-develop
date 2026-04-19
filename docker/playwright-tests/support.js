const fs = require('fs');
const path = require('path');
const { expect } = require('@playwright/test');

const BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://wordpress.example.org';
const CAS_BASE_URL = process.env.PLAYWRIGHT_CAS_BASE_URL || 'http://cas.example.org:8080/cas';
const ADMIN_USER = process.env.PLAYWRIGHT_ADMIN_USER || 'adm';
const ADMIN_PASSWORD = process.env.PLAYWRIGHT_ADMIN_PASSWORD || 'pass';
const CAS_USER = process.env.PLAYWRIGHT_CAS_USER || 'joe';
const CAS_PASSWORD = process.env.PLAYWRIGHT_CAS_PASSWORD || 'pass';
const BASELINE_ROLE_RULE = '(CAS{cas_user_id} -EQ "joe")';
const MULTISITE_MODE = process.env.PLAYWRIGHT_MULTISITE === 'true';
const BASELINE_ROLE_RULE_OPTION = MULTISITE_MODE
  ? `administrator|0|${BASELINE_ROLE_RULE}`
  : `administrator|${BASELINE_ROLE_RULE}`;
const WORDPRESS_VOLUME_PATH = process.env.PLAYWRIGHT_WORDPRESS_VOLUME_PATH || '/work/wordpress';
const WORDPRESS_CONFIG_PATH = path.join(WORDPRESS_VOLUME_PATH, 'wp-config.php');
const WORDPRESS_HTACCESS_PATH = path.join(WORDPRESS_VOLUME_PATH, '.htaccess');

function buildRoleRuleOption(roleKey, ruleExpression, blogId = null) {
  if (MULTISITE_MODE) {
    return `${roleKey}|${blogId ?? 0}|${ruleExpression}`;
  }

  return `${roleKey}|${ruleExpression}`;
}

function ensureWordPressFileDirectory(filePath) {
  fs.mkdirSync(path.dirname(filePath), { recursive: true });
}

function readWordPressFile(filePath) {
  return fs.readFileSync(filePath, 'utf8');
}

function writeWordPressFile(filePath, content) {
  ensureWordPressFileDirectory(filePath);
  fs.writeFileSync(filePath, content, 'utf8');
}

async function setInputValueAtomically(page, selector, value) {
  const locator = page.locator(selector);
  await expect(locator).toBeVisible();

  await locator.evaluate((element, nextValue) => {
    const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(element, nextValue);
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
  }, value);

  await expect(locator).toHaveValue(value);
}

async function writeMultisiteBootstrapFiles(page) {
  const wpConfigRules = await page.locator('#network-wpconfig-rules').inputValue();
  const htaccessRules = await page.locator('#network-htaccess-rules').inputValue();
  const stopEditingMarker = '/* That\'s all, stop editing! Happy publishing. */';

  let config = readWordPressFile(WORDPRESS_CONFIG_PATH);
  if (!config.includes("define( 'MULTISITE', true );")) {
    config = config.replace(stopEditingMarker, `${wpConfigRules}\n\n${stopEditingMarker}\n\ndefine("WP_CASSIFY_ENABLE_URL_BYPASS", true);\n`);
    writeWordPressFile(WORDPRESS_CONFIG_PATH, config);
  }

  if (htaccessRules) {
    writeWordPressFile(WORDPRESS_HTACCESS_PATH, `${htaccessRules}\n`);
  }
}

async function ensureMultisiteNetwork(page, { siteName = 'wp-cassify-network', adminEmail = 'adm@example.org', subdomainInstall = true } = {}) {

  await page.goto('/wp-admin/network.php');

  if (await page.locator('#sitename').isVisible().catch(() => false)) {
    await page.locator('#sitename').fill(siteName);
    await page.locator('#email').fill(adminEmail);

    const subdomainChoice = page.locator(`input[name="subdomain_install"][value="${subdomainInstall ? '1' : '0'}"]`);
    if (await subdomainChoice.isVisible().catch(() => false)) {
      await subdomainChoice.check();
    }

    await Promise.all([
      page.waitForNavigation({waitUntil: 'load'}),
      await page.locator('#submit').click()
    ]);
  }

  if (await page.locator('#network-wpconfig-rules').isVisible().catch(() => false)) {
    await writeMultisiteBootstrapFiles(page);
    await new Promise(resolve => setTimeout(resolve, 2000));
  }

  await page.goto('/wp-login.php');
  if (await page.locator('#user_login').isVisible().catch(() => false)) {
    await fillAndSubmitLocalLogin(page);
  }
  await page.goto('/wp-admin/network/site-new.php');
  if (await page.locator('#user_login').isVisible().catch(() => false)) {
    await fillAndSubmitLocalLogin(page);
  }
  const siteAddress = page.locator('#site-address');
  if (!(await siteAddress.isVisible().catch(() => false))) {
    await page.goto('/wp-admin/network/site-new.php');

    if (await page.locator('#user_login').isVisible().catch(() => false)) {
      await fillAndSubmitLocalLogin(page);
    }

    await expect(siteAddress).toBeVisible();
  }
}

async function createNetworkSite(page, { domain, title, email = `${ADMIN_USER}@example.org` }) {
  await page.goto('/wp-admin/network/site-new.php');
  await expect(page.locator('#site-address')).toBeVisible();

  await page.locator('#site-address').fill(domain);
  await page.locator('#site-title').fill(title);
  await page.locator('#admin-email').fill(email);
  await Promise.all([
    page.waitForNavigation({waitUntil: 'load'}),
    await page.locator('#add-site').click()
  ]);

  await expect(page.locator('a').filter({ hasText: 'Edit Site' })).toBeVisible();
  await page.locator('a').filter({ hasText: 'Edit Site' }).click();
  await expect(page.locator('input[name="blog[url]"]')).toBeVisible();
  await page.locator('input[name="blog[url]"]').fill('http://' + domain + '.example.org');
  await page.locator('#submit').click();
}


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
  await page.waitForLoadState('domcontentloaded');

  if (await page.locator('#language-continue').isVisible().catch(() => false)) {
    await Promise.all([
      page.waitForNavigation({waitUntil: 'load'}),
      await page.locator('#language-continue').click()
    ]);
  }
  if (await page.locator('#weblog_title').isVisible().catch(() => false)) {
    await page.locator('#weblog_title').fill('wp-cassify-test');
    await page.locator('#user_login').fill(ADMIN_USER);
    await setInputValueAtomically(page, '#pass1', ADMIN_PASSWORD);
    const weakPassword = page.locator('input[name="pw_weak"]');
    if (await weakPassword.isVisible().catch(() => false)) {
      await weakPassword.check();
    }
    const pass2 = page.locator('#pass2');
    if (await pass2.isVisible().catch(() => false)) {
      await setInputValueAtomically(page, '#pass2', ADMIN_PASSWORD);
    }
    await page.locator('#admin_email').fill('adm@example.org');
    await expect(page.locator('#pass1')).toHaveValue(ADMIN_PASSWORD);
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
  await page.goto(MULTISITE_MODE ? '/wp-admin/network/plugins.php' : '/wp-admin/plugins.php');

  const pluginRow = page.locator('tr').filter({ hasText: 'WP Cassify' });
  const activateLink = pluginRow.getByRole('link', { name: MULTISITE_MODE ? /Network Activate|Activate/i : /Activate/i });
  if (await activateLink.isVisible().catch(() => false)) {
    await activateLink.click();
  }

  await expect(page.locator('#wp-admin-bar-my-account')).toBeVisible();
}

async function openPluginSettings(page) {
  await page.goto(MULTISITE_MODE ? '/wp-admin/network/settings.php?page=wp-cassify.php' : '/wp-admin/options-general.php?page=wp-cassify.php');

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

async function configureRoleRule(page, {
  purgeRolesBeforeApplying = true,
  role = 'administrator',
  rule = BASELINE_ROLE_RULE,
  blogId = MULTISITE_MODE ? 0 : null,
} = {}) {
  await openPluginSettings(page);

  const expectedRoleRuleOption = buildRoleRuleOption(role, rule, blogId);
  const existingRoleRuleOptions = await page.locator('#wp_cassify_user_role_rules option').allTextContents();
  if (!existingRoleRuleOptions.includes(expectedRoleRuleOption)) {
    if (MULTISITE_MODE && blogId !== null && blogId !== undefined && await page.locator('#wp_cassify_user_role_blog_id').isVisible().catch(() => false)) {
      await page.locator('#wp_cassify_user_role_blog_id').selectOption(String(blogId));
    }

    await page.locator('#wp_cassify_user_role_rule').fill(rule);
    await page.locator('#wp_cassify_add_user_role_rule').click();
  }

  const roleRuleOptions = await page.locator('#wp_cassify_user_role_rules option').allTextContents();
  expect(roleRuleOptions).toContain(expectedRoleRuleOption);

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
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.locator('#wp-admin-bar-logout a').click(),
  ]);
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
  MULTISITE_MODE,
  buildRoleRuleOption,
  activatePluginIfNeeded,
  createNetworkSite,
  configureGeneralSettings,
  configureRoleRule,
  configureUrlSettings,
  ensureAdminAccess,
  ensureMultisiteNetwork,
  fillAndSubmitCasLogin,
  fillAndSubmitLocalLogin,
  installFreshWordPressIfNeeded,
  loginCasDirectly,
  loginLocalDirectly,
  loginThroughWordPress,
  logoutCas,
  logoutWordPress,
  openPluginSettings,
  WORDPRESS_CONFIG_PATH,
  WORDPRESS_HTACCESS_PATH,
  WORDPRESS_VOLUME_PATH,
};


