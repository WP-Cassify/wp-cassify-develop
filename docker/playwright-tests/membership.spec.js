const { test, expect } = require('@playwright/test');
const fs = require('fs');
const crypto = require('crypto');
const { fillAndSubmitCasLogin } = require('./support');
const token = crypto.randomBytes(24).toString('hex');
const mu = '/work/wordpress/wp-content/mu-plugins';
let child;
async function fixture(page, action, base = 'http://wordpress.example.org') {
  const response = await page.request.post(`${base}/`, {form: {cassify_membership_test: action, token}});
  const body = await response.json();
  expect(body.success).toBeTruthy();
  return body.data;
}
async function visitProtected(page, url) {
  const chain = [];
  page.on('response', r => { if (r.request().isNavigationRequest()) chain.push({url:r.url(), status:r.status()}); });
  await page.goto(url);
  if (await page.locator('#username').isVisible()) await fillAndSubmitCasLogin(page);
  console.log(JSON.stringify(chain).replace(/(ticket=)[^&" ]+/g, '$1[redacted]'));
  expect(chain.length).toBeLessThan(15);
  return chain;
}
test.describe.configure({mode:'serial'});
test.beforeAll(async () => {
  fs.mkdirSync(mu, {recursive:true});
  fs.writeFileSync(`${mu}/cassify-membership.php`, fs.readFileSync('/work/membership-fixtures.php'));
  fs.writeFileSync(`${mu}/cassify-membership.token`, token);
});
test.beforeEach(async ({page}) => {
  child = (await fixture(page, 'prepare')).child.replace(/\/$/, '');
});
test.afterAll(async ({request}) => {
  await request.post('http://wordpress.example.org/', {form: {cassify_membership_test:'cleanup', token}});
  fs.unlinkSync(`${mu}/cassify-membership.php`);
  fs.unlinkSync(`${mu}/cassify-membership.token`);
});
test('public pages stay accessible with network cookie and no membership', async ({page}) => {
  await fixture(page, 'signin');
  const r = await page.goto(`${child}/`);
  expect(r.status()).toBe(200);
  expect(page.url()).toBe(`${child}/`);
  expect((await fixture(page, 'snapshot')).caps).toBeFalsy();
});
test('protected access validates real CAS and grants only the configured role', async ({page}) => {
  await fixture(page, 'signin');
  const chain = await visitProtected(page, `${child}/wp-admin/`);
  expect(chain.some(r=>r.url.includes('cas.example.org'))).toBeTruthy();
  await expect(page.locator('#wpadminbar')).toBeVisible();
  expect((await fixture(page, 'snapshot')).caps).toEqual({administrator:true});
});
test('missing role stops with 403 without a CAS loop; public pages remain accessible', async ({page}) => {
  await fixture(page, 'no_role');
  await fixture(page, 'signin');
  const chain = await visitProtected(page, `${child}/wp-admin/`);
  expect(chain.at(-1).status).toBe(403);
  expect((await fixture(page, 'snapshot')).caps).toBeFalsy();
  expect((await page.reload()).status()).toBe(403);
  expect((await page.goto(`${child}/`)).status()).toBe(200);
});
test('archived child admin retains role after network user switch and repeated dashboard refresh', async ({page}) => {
  await fixture(page, 'archive');
  await fixture(page, 'signin');
  const before = await fixture(page, 'snapshot');
  expect(before.member).toBeFalsy();
  expect(before.caps).toEqual({administrator:true});
  await page.goto(`${child}/wp-admin/`);
  await page.reload();
  expect((await fixture(page, 'snapshot')).caps).toEqual({administrator:true});
  await fixture(page, 'unarchive');
});
for (const removeLoggedIn of [false, true]) {
test(`stale PHP CAS session reauthenticates with missing ${removeLoggedIn ? 'all WordPress cookies' : 'admin cookie only'}`, async ({page, context}) => {
  await fixture(page, 'signin');
  await visitProtected(page, `${child}/wp-admin/`);
  const session = await fixture(page, 'session_snapshot', child);
  expect(session.user_auth).toBe(true);
  expect(session.has_attributes).toBe(true);
  console.log('PHP session before cookie expiry:', session);
  const before = await context.cookies();
  expect(before.some(c=>c.name === 'PHPSESSID')).toBeTruthy();
  expect(before.some(c=>/^wordpress_logged_in_/.test(c.name))).toBeTruthy();
  await context.clearCookies({name:/^wordpress_(?:sec_)?[0-9a-f]{32}$/});
  if (removeLoggedIn) await context.clearCookies({name:/^wordpress_logged_in_/});
  const after = await context.cookies();
  expect(after.some(c=>c.name === 'PHPSESSID')).toBeTruthy();
  expect(after.some(c=>/^wordpress_(?:sec_)?[0-9a-f]{32}$/.test(c.name))).toBeFalsy();
  expect(after.some(c=>/^wordpress_logged_in_/.test(c.name))).toBe(!removeLoggedIn);
  const chain = await visitProtected(page, `${child}/wp-admin/`);
  expect(chain.some(r=>r.url.includes('wp-login.php'))).toBeTruthy();
  expect(chain.some(r=>r.url.includes('cas.example.org'))).toBeTruthy();
  await expect(page).toHaveURL(/\/wp-admin\//);
  await expect(page.locator('#wpadminbar')).toBeVisible();
  expect((await context.cookies()).some(c=>/^wordpress_(?:sec_)?[0-9a-f]{32}$/.test(c.name))).toBeTruthy();
});

}

test('direct wp-login with an existing CAS session does not loop', async ({page}) => {
  await fixture(page, 'signin');
  await visitProtected(page, `${child}/wp-admin/`);
  await visitProtected(page, `${child}/wp-login.php`);
  await expect(page).toHaveURL(/\/wp-admin\//);
  await expect(page.locator('#wpadminbar')).toBeVisible();
});
