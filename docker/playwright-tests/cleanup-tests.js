const { test, expect } = require('@playwright/test');
const {
  ensureAdminAccess, activatePluginIfNeeded, runWordPressTestAction,
  loginThroughWordPress, logoutWordPress, CAS_USER, ADMIN_USER, ADMIN_PASSWORD,
} = require('./support');

// Registered at the end of each existing suite, after its single-site/network bootstrap.
module.exports = function registerCleanupTests() {
  const multisite = process.env.PLAYWRIGHT_MULTISITE === 'true';
  const settings = multisite
    ? '/wp-admin/network/settings.php?page=wp-cassify.php'
    : '/wp-admin/options-general.php?page=wp-cassify.php';
  const fixture = (page, command, values = {}) => runWordPressTestAction(page, 'cleanup_fixture', { command, ...values });

  async function api(page, operation, values = {}, expectedStatus = 200) {
    const nonce = await page.evaluate(() => wpCassifyCleanup.nonce);
    const response = await page.request.post('/wp-admin/admin-ajax.php', {
      form: { action: 'wp_cassify_cleanup', nonce, operation, ...values },
    });
    expect(response.status()).toBe(expectedStatus);
    const body = await response.json();
    if (expectedStatus === 200) expect(body.success, JSON.stringify(body)).toBeTruthy();
    return body.data;
  }
  async function preview(page, values = {}) {
    let job = await api(page, 'start', { mode: 'purge', days: '730', ...values });
    while (job.phase === 'scanning') job = await api(page, 'scan', { token: job.token });
    expect(job.phase).toBe('ready');
    return job;
  }
  async function execute(page, job, target = '') {
    job = await api(page, 'confirm', { token: job.token, target_username: target });
    while (job.phase === 'executing') job = await api(page, 'batch', { token: job.token });
    expect(job.phase).toBe('done');
    return job;
  }

  test.describe('Manual CAS account tools', () => {
    test.beforeEach(async ({ page }) => {
      await ensureAdminAccess(page);
      await fixture(page, 'admin_signin', { login: ADMIN_USER, password: ADMIN_PASSWORD });
      await activatePluginIfNeeded(page);
      await fixture(page, 'reset');
      await fixture(page, 'checkpoint');
      await page.goto(settings);
      await expect(page.locator('#wp-cassify-cleanup')).toBeVisible();
      await expect(page.locator('#cassify-cleanup-settings')).toBeEnabled();
    });
    test.afterEach(async ({ page }) => {
      await fixture(page, 'reset');
    });

    test('CAS login records its origin; local login records activity without claiming CAS', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([{ name: 'local' }]) });
      expect((await fixture(page, 'signin', { name: 'local', wrong: '1' })).ok).toBe(false);
      expect((await fixture(page, 'snapshot')).users.local.date).toBe('');
      const signed = await fixture(page, 'signin', { name: 'local' });
      expect(signed.ok).toBe(true);
      let snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.local.cas).toBe('');
      expect(Number(snapshot.users.local.date)).toBeGreaterThan(0);
      await page.goto('/wp-admin/profile.php');
      await logoutWordPress(page);
      await loginThroughWordPress(page, '/wp-admin/profile.php');
      const cas = await fixture(page, 'inspect', { login: CAS_USER });
      expect(cas.cas).toBe('1');
      expect(Number(cas.date)).toBeGreaterThan(0);
    });

    test('preview is read-only; purge respects dates, roles and content reassignment', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' },
        { name: 'old', cas: true, date: 'old', posts: true },
        { name: 'admin', cas: true, date: 'old', role: 'administrator' },
        { name: 'recent', cas: true, date: 'recent' },
        { name: 'missing', cas: true },
        { name: 'local', date: 'old' },
        { name: 'invalid', cas: true, date: 'broken' },
        { name: 'future', cas: true, date: 'future' },
      ]) });
      const job = await preview(page);
      expect(job.old).toBe(2);
      expect(job.missing_count).toBe(0);
      expect(job.invalid).toBe(2);
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
      expect((await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_old' }, 400)).message).toContain('candidate');
      const result = await execute(page, job, 'cassify_cleanup_e2e_target');
      expect(result.success).toBe(2);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.old).toBeUndefined();
      expect(snapshot.users.admin).toBeUndefined();
      for (const name of ['recent', 'missing', 'local', 'invalid', 'future']) expect(snapshot.users[name]).toBeDefined();
      expect(snapshot.posts).toHaveLength(4);
      for (const post of snapshot.posts) expect(Number(post.post_author)).toBe(ids.target);
      expect(snapshot.comments).toBe(1);
      expect(snapshot.cron).toBe(false);
    });

    test('content recipient is entered as a username and unknown usernames cannot delete accounts', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' }, { name: 'old', cas: true, date: 'old', posts: true },
      ]) });
      await expect(page.locator('#cassify-cleanup-target')).toHaveAttribute('type', 'text');
      await page.locator('#cassify-cleanup-purge').click();
      await expect(page.locator('#cassify-cleanup-confirm')).toBeVisible();
      await page.locator('#cassify-cleanup-target').fill('cassify_cleanup_e2e_unknown');
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('existing WordPress username');
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
      // A database ID is not treated as a username fallback.
      const job = await api(page, 'status');
      await api(page, 'confirm', { token: job.token, target_username: String(ids.target) }, 400);
      await page.locator('#cassify-cleanup-target').fill('  cassify_cleanup_e2e_target  ');
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('done');
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.old).toBeUndefined();
      expect(snapshot.posts).toHaveLength(4);
      for (const post of snapshot.posts) expect(Number(post.post_author)).toBe(ids.target);
    });

    test('missing dates require an explicit option; a reconnection after preview protects the account', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' }, { name: 'missing', cas: true },
        { name: 'local' }, { name: 'reconnect', cas: true, date: 'old' },
      ]) });
      const job = await preview(page, { missing: '1' });
      expect(job.missing_count).toBe(1);
      await fixture(page, 'touch', { id: String(ids.reconnect) });
      const result = await execute(page, job, 'cassify_cleanup_e2e_target');
      expect(result.success).toBe(1);
      expect(result.skipped).toBe(1);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.missing).toBeUndefined();
      expect(snapshot.users.local).toBeDefined();
      expect(snapshot.users.reconnect).toBeDefined();
    });

    test('marking by role and global marking preserve dates and never delete accounts', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'subscriber', date: 'recent' }, { name: 'editor', role: 'editor' },
      ]) });
      const before = await fixture(page, 'snapshot');
      let job = await preview(page, { mode: 'mark', role: 'subscriber', blog: '1' });
      await execute(page, job);
      let snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.subscriber.cas).toBe('1');
      expect(snapshot.users.subscriber.date).toBe(before.users.subscriber.date);
      expect(snapshot.users.editor.cas).toBe('');
      job = await preview(page, { mode: 'mark' });
      await execute(page, job);
      snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.editor.cas).toBe('1');
      expect(snapshot.users.editor.date).toBe('');
      expect(Object.keys(snapshot.users)).toHaveLength(2);
      expect((await preview(page, { mode: 'mark' })).total).toBe(0);
    });

    test('day cutoff is strict across leap days and duration persists', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([{ name: 'boundary', cas: true, date: 'old' }]) });
      const calendar = await fixture(page, 'calendar', { id: String(ids.boundary) });
      expect(calendar).toEqual({ date: '2024-02-28 12:34:56', exact: '', old: 'old' });
      await preview(page, { days: '30' });
      await page.reload();
      await expect(page.locator('#cassify-cleanup-days')).toHaveValue('30');
      for (const days of ['0', '-1', '1.5', '36601', 'invalid']) {
        expect((await api(page, 'start', { mode: 'purge', days }, 400)).message).toContain('days');
      }
      await preview(page, { days: '1' });
      await page.reload();
      await expect(page.locator('#cassify-cleanup-days')).toHaveValue('1');
    });

    test('short inactivity periods are applied in days and legacy year settings are converted', async ({ page }) => {
      await fixture(page, 'legacy_duration');
      await page.reload();
      const migrated = Number(await page.locator('#cassify-cleanup-days').inputValue());
      expect([730, 731]).toContain(migrated);
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'fortyfive', cas: true, date: Math.floor(Date.now() / 1000) - 45 * 86400 },
      ]) });
      expect((await preview(page, { days: '60' })).old).toBe(0);
      expect((await preview(page, { days: '30' })).old).toBe(1);
      await page.reload();
      await expect(page.locator('#cassify-cleanup-days')).toHaveValue('30');
    });

    test('multiple batches resume after reload and reject duplicate confirmation', async ({ page }) => {
      const users = [{ name: 'target', date: 'recent' }];
      for (let i = 0; i < 45; i++) users.push({ name: `batch${i}`, cas: true, date: 'old' });
      const ids = await fixture(page, 'create', { users: JSON.stringify(users) });
      let job = await preview(page);
      job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      job = await api(page, 'batch', { token: job.token });
      expect(job.phase).toBe('executing');
      expect(job.success).toBe(20);
      await page.reload();
      await expect(page.locator('#cassify-cleanup-resume')).toBeVisible();
      await page.locator('#cassify-cleanup-resume').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('done');
      job = await api(page, 'status');
      expect(job.success).toBe(45);
      expect(job.failed).toBe(0);
      expect(Object.keys((await fixture(page, 'snapshot')).users)).toEqual(['target']);
      await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' }, 400);
    });

    test('UI requires preview and confirmation, and AJAX requires nonce and permissions', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([{ name: 'editor', role: 'editor' }]) });
      await page.locator('#cassify-cleanup-scope').selectOption('role');
      await page.locator('#cassify-cleanup-role').fill('editor');
      await page.locator('#cassify-cleanup-mark').click();
      await expect(page.locator('#cassify-cleanup-confirm')).toBeVisible();
      expect((await fixture(page, 'snapshot')).users.editor.cas).toBe('');
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('done');
      expect((await fixture(page, 'snapshot')).users.editor.cas).toBe('1');
      const invalidNonce = await page.request.post('/wp-admin/admin-ajax.php', { form: { action: 'wp_cassify_cleanup', operation: 'status', nonce: 'invalid' } });
      expect(invalidNonce.status()).toBe(403);
      await fixture(page, 'signin', { name: 'editor' });
      const denied = await page.request.post('/wp-admin/admin-ajax.php', { form: { action: 'wp_cassify_cleanup', operation: 'status' } });
      expect(denied.status()).toBe(403);
    });

    test('multisite preserves child-site content and reports the core super-admin refusal', async ({ page }) => {
      test.skip(!multisite, 'Network only');
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' },
        { name: 'network', cas: true, date: 'old', blog: 2 },
        { name: 'super', cas: true, date: 'old', super: true },
      ]) });
      const result = await execute(page, await preview(page), 'cassify_cleanup_e2e_target');
      expect(result.success).toBe(1);
      expect(result.failed).toBe(1);
      expect(result.errors.join(' ')).toContain('super administrator');
      const child = await fixture(page, 'child_snapshot', { blog: '2' });
      expect(child.posts.some(post => Number(post.post_author) === ids.target)).toBe(true);
      expect((await fixture(page, 'snapshot')).users.super).toBeDefined();
    });

    test('a missing content recipient pauses deletion and the saved operation can be discarded', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' }, { name: 'old', cas: true, date: 'old', posts: true },
      ]) });
      const job = await preview(page);
      await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      await fixture(page, 'remove', { id: String(ids.target) });
      const result = await api(page, 'batch', { token: job.token }, 400);
      expect(result.message).toContain('recipient');
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
      expect((await api(page, 'discard')).phase).toBe('idle');
      expect((await preview(page)).old).toBe(1);
    });

    test('the operator is deleted last when their own CAS account is eligible', async ({ page }) => {
      test.skip(multisite, 'A network operator is a super administrator protected by WordPress');
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'operator', cas: true, role: 'administrator' },
        { name: 'target', date: 'recent' }, { name: 'other', cas: true, date: 'old' },
      ]) });
      await fixture(page, 'signin', { name: 'operator' });
      await page.goto(settings);
      await expect(page.locator('#cassify-cleanup-settings')).toBeEnabled();
      await fixture(page, 'setdate', { id: String(ids.operator) });
      const job = await preview(page);
      expect(job.self).toBe(true);
      const result = await execute(page, job, 'cassify_cleanup_e2e_target');
      expect(result.success).toBe(2);
      expect(result.session_ended).toBe(true);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.operator).toBeUndefined();
      expect(snapshot.users.other).toBeUndefined();
    });

    test('a CAS account can log in locally and its activity remains protective', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([{ name: 'caslocal', cas: true, date: 'old' }]) });
      expect((await fixture(page, 'signin', { name: 'caslocal' })).ok).toBe(true);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.caslocal.cas).toBe('1');
      expect(Number(snapshot.users.caslocal.date)).toBeGreaterThan(Math.floor(Date.now() / 1000) - 60);
    });

    test('network role marking uses the selected child site', async ({ page }) => {
      test.skip(!multisite, 'Network only');
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'child', blog: 2 }, { name: 'main', role: 'editor' },
      ]) });
      const job = await preview(page, { mode: 'mark', role: 'editor', blog: '2' });
      await execute(page, job);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.child.cas).toBe('1');
      expect(snapshot.users.main.cas).toBe('');
    });

    test('volume exercise with 600 accounts', async ({ page }) => {
      test.skip(process.env.PLAYWRIGHT_CLEANUP_VOLUME !== 'true', 'Opt in to the volume fixture');
      test.setTimeout(3600000);
      for (let offset = 0; offset < 600; offset += 200) {
        await fixture(page, 'create', { users: JSON.stringify(Array.from({ length: 200 }, (_, i) => ({ name: `volume${offset + i}`, cas: true, date: 'old' }))) });
      }
      const ids = await fixture(page, 'create', { users: JSON.stringify([{ name: 'target', date: 'recent' }]) });
      const job = await preview(page);
      expect(job.total).toBe(600);
      expect((await execute(page, job, 'cassify_cleanup_e2e_target')).success).toBe(600);
    });
  });
};
