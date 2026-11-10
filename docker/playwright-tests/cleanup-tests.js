const { test, expect } = require('@playwright/test');
const {
  activatePluginIfNeeded, runWordPressTestAction,
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
      form: { action: 'wp_cassify_cleanup', nonce, operation, ...(operation === 'confirm' ? { backup_confirmed: '1' } : {}), ...values },
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
      // The parent suite already bootstrapped WordPress. Sign in as the local
      // operator before navigating: the CAS account need not belong to the main site.
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

    test('listed accounts ignore activity but retain protections and require a reviewed recipient', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' },
        { name: 'recent', cas: true, date: 'recent', posts: true },
        { name: 'unlisted', cas: true, date: 'old' },
        { name: 'local' },
        { name: 'admin', cas: true, role: 'administrator' },
        { name: 'api', cas: true },
      ]) });
      await fixture(page, 'session', { id: ids.recent });
      await fixture(page, 'application_password', { id: ids.api });
      const prefix = 'cassify_cleanup_e2e_';
      await page.locator('#cassify-cleanup-identifiers').fill(
        ['recent', 'recent', 'local', 'admin', 'api', 'unknown'].map(name => prefix + name).join('\n')
      );
      await page.locator('#cassify-cleanup-list').click();
      await expect(page.locator('#cassify-cleanup-confirm')).toBeVisible();
      let job = await api(page, 'status');
      expect(job.total).toBe(1);
      expect(job.protected).toBe(2);
      expect(job.input_report.join(' ')).toContain('duplicate');
      expect(job.input_report.join(' ')).toContain('not found');
      expect(job.input_report.join(' ')).toContain('not_cas');
      expect((await fixture(page, 'snapshot')).users.recent).toBeDefined();
      expect((await api(page, 'prepare', { token: job.token, target_username: prefix + 'recent' }, 400)).message).toContain('candidate');
      await page.reload();
      await expect(page.locator('#cassify-cleanup-confirm')).toBeVisible();
      job = await execute(page, job, prefix + 'target');
      expect(job.success).toBe(1);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.recent).toBeUndefined();
      for (const name of ['unlisted', 'local', 'admin', 'api']) expect(snapshot.users[name]).toBeDefined();
      for (const post of snapshot.posts) expect(Number(post.post_author)).toBe(ids.target);
      job = await preview(page, { selection: 'list', identifier_type: 'id', identifiers: `${ids.unlisted}\n${ids.unlisted}\n${ids.local}\n123invalid` });
      expect(job.total).toBe(1);
      expect(job.users[0].id).toBe(ids.unlisted);
      expect(job.input_report.join(' ')).toContain('duplicate');
      expect(job.input_report.join(' ')).toContain('not found');
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
      expect(job.old).toBe(1);
      expect(job.missing_count).toBe(0);
      expect(job.invalid).toBe(2);
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
      expect((await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_old' }, 400)).message).toContain('candidate');
      const result = await execute(page, job, 'cassify_cleanup_e2e_target');
      expect(result.success).toBe(1);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.old).toBeUndefined();
      expect(snapshot.users.admin).toBeDefined();
      for (const name of ['recent', 'missing', 'local', 'invalid', 'future']) expect(snapshot.users[name]).toBeDefined();
      expect(snapshot.posts).toHaveLength(4);
      expect(snapshot.posts.find(post => post.post_type === 'attachment').file_exists).toBe(true);
      for (const post of snapshot.posts) {
        expect(Number(post.post_author)).toBe(ids.target);
        expect(post.meta).toBe('preserved');
        expect(post.terms).toContain('cassify_cleanup_fixture');
      }
      expect(snapshot.links).toHaveLength(1);
      expect(Number(snapshot.links[0].link_owner)).toBe(ids.target);
      expect(snapshot.comments).toBe(1);
      expect(snapshot.cron).toBe(false);
    });

    test('purge controls reflect the saved phase and reject an empty recipient without AJAX', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'old', cas: true, date: 'old' },
      ]) });
      for (const name of ['confirm', 'resume', 'prev', 'next', 'recipient', 'backup-row', 'history-first', 'history-next']) {
        await expect(page.locator(`#cassify-cleanup-${name}`)).toBeHidden();
      }
      await page.locator('#cassify-cleanup-purge').click();
      await expect(page.locator('#cassify-cleanup-confirm')).toBeVisible();
      await expect(page.locator('#cassify-cleanup-recipient')).toBeVisible();
      await expect(page.locator('#cassify-cleanup-target')).toBeEditable();
      await expect(page.locator('#cassify-cleanup-resume')).toBeHidden();
      await page.reload();
      await expect(page.locator('#cassify-cleanup-confirm')).toHaveText('Review content recipient');
      await expect(page.locator('#cassify-cleanup-target')).toBeVisible();
      await expect(page.locator('#cassify-cleanup-resume')).toBeHidden();
      const prepares = [];
      page.on('request', request => {
        if (new URLSearchParams(request.postData() || '').get('operation') === 'prepare') prepares.push(request);
      });
      await page.locator('#cassify-cleanup-backup').check();
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('existing WordPress username');
      await expect(page.locator('#cassify-cleanup-target')).toBeFocused();
      expect(prepares).toHaveLength(0);
      await page.locator('#cassify-cleanup-target').fill('cassify_cleanup_e2e_target');
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-confirm')).toHaveText('Confirm deletion and content recipient');
      await page.reload();
      await expect(page.locator('#cassify-cleanup-target')).toHaveValue('cassify_cleanup_e2e_target');
      await expect(page.locator('#cassify-cleanup-target')).toBeDisabled();
      await page.locator('#cassify-cleanup-backup').check();
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('completed');
      await expect(page.locator('#cassify-cleanup-confirm')).toBeHidden();
      await expect(page.locator('#cassify-cleanup-resume')).toBeHidden();
      await expect(page.locator('#cassify-cleanup-recipient')).toBeHidden();
      await page.locator('#cassify-cleanup-discard').click();
      await expect(page.locator('#cassify-cleanup-status')).toHaveText('No saved operation.');
      await expect(page.locator('#cassify-cleanup-confirm')).toBeHidden();
    });

    test('content recipient is entered as a username and unknown usernames cannot delete accounts', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' }, { name: 'old', cas: true, date: 'old', posts: true },
      ]) });
      await expect(page.locator('#cassify-cleanup-target')).toHaveAttribute('type', 'text');
      await page.locator('#cassify-cleanup-purge').click();
      await expect(page.locator('#cassify-cleanup-confirm')).toBeVisible();
      await page.locator('#cassify-cleanup-target').fill('cassify_cleanup_e2e_unknown');
      await page.locator('#cassify-cleanup-backup').check();
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('existing WordPress username');
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
      // A database ID is not treated as a username fallback.
      const job = await api(page, 'status');
      await api(page, 'confirm', { token: job.token, target_username: String(ids.target) }, 400);
      await page.locator('#cassify-cleanup-target').fill('  cassify_cleanup_e2e_target  ');
      await page.locator('#cassify-cleanup-backup').check();
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-warning')).toContainText(`#${ids.target}`);
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('completed');
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.old).toBeUndefined();
      expect(snapshot.posts).toHaveLength(4);
      expect(snapshot.posts.find(post => post.post_type === 'attachment').file_exists).toBe(true);
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
      job = await preview(page, { mode: 'mark', all_accounts: '1' });
      await execute(page, job);
      snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.editor.cas).toBe('1');
      expect(snapshot.users.editor.date).toBe('');
      expect(Object.keys(snapshot.users)).toHaveLength(2);
      expect((await preview(page, { mode: 'mark', all_accounts: '1' })).total).toBe(0);
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

    test('short inactivity periods are applied in days', async ({ page }) => {
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
      if (multisite) {
        expect(job.success).toBe(0); // one site step per request
      } else {
        expect(job.success).toBeGreaterThan(0);
        expect(job.success).toBeLessThanOrEqual(20);
      }
      await page.reload();
      await expect(page.locator('#cassify-cleanup-resume')).toBeVisible();
      await page.locator('#cassify-cleanup-resume').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('completed');
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
      await expect(page.locator('#cassify-cleanup-status')).toContainText('completed');
      expect((await fixture(page, 'snapshot')).users.editor.cas).toBe('1');
      const invalidNonce = await page.request.post('/wp-admin/admin-ajax.php', { form: { action: 'wp_cassify_cleanup', operation: 'status', nonce: 'invalid' } });
      expect(invalidNonce.status()).toBe(403);
      await fixture(page, 'signin', { name: 'editor' });
      const denied = await page.request.post('/wp-admin/admin-ajax.php', { form: { action: 'wp_cassify_cleanup', operation: 'status' } });
      expect(denied.status()).toBe(403);
    });

    test('multisite preserves child-site content and excludes super administrators', async ({ page }) => {
      test.skip(!multisite, 'Network only');
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' },
        { name: 'network', cas: true, date: 'old', blog: 2 },
        { name: 'super', cas: true, date: 'old', super: true },
      ]) });
      const result = await execute(page, await preview(page), 'cassify_cleanup_e2e_target');
      expect(result.success).toBe(1);
      expect(result.failed).toBe(0);
      expect(result.protected).toBeGreaterThan(0);
      const child = await fixture(page, 'child_snapshot', { blog: '2' });
      expect(child.posts.some(post => Number(post.post_author) === ids.target)).toBe(true);
      expect((await fixture(page, 'snapshot')).users.super).toBeDefined();
    });

    test('multisite reassigns content on an archived site without author membership', async ({ page }) => {
      test.skip(!multisite, 'Network only');
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' },
        { name: 'orphan', cas: true, date: 'old', blog: 2, orphan: true, archived: true },
      ]) });
      const result = await execute(page, await preview(page), 'cassify_cleanup_e2e_target');
      expect(result.success).toBe(1);
      expect(result.failed).toBe(0);
      const child = await fixture(page, 'child_snapshot', { blog: '2' });
      expect(child.posts).toHaveLength(1);
      expect(Number(child.posts[0].post_author)).toBe(ids.target);
      expect((await fixture(page, 'snapshot')).users.orphan).toBeUndefined();
    });

    test('a silent reassignment failure pauses deletion and permits a safe retry', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target', date: 'recent' },
        { name: 'failure', cas: true, date: 'old', posts: true },
      ]) });
      await fixture(page, 'fail_reassignment', { id: String(ids.failure) });
      let job = await preview(page);
      job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      const error = await api(page, 'batch', { token: job.token }, 400);
      expect(error.message).toMatch(/reassignment/i);
      const paused = await fixture(page, 'audit', { job: job.token, event: 'paused', id: '0' });
      expect(paused.message).toMatch(/reassignment/i);
      expect((await api(page, 'status')).phase).toBe('executing');
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.posts).toHaveLength(4);
      expect(snapshot.posts.find(post => post.post_type === 'attachment').file_exists).toBe(true);
      for (const post of snapshot.posts) expect(Number(post.post_author)).toBe(ids.failure);
      expect(snapshot.users.failure).toBeDefined();
      await fixture(page, 'fail_reassignment', { id: '0' });
      job = await api(page, 'status');
      while (job.phase === 'executing') job = await api(page, 'batch', { token: job.token });
      expect(job.success).toBe(1);
      expect((await fixture(page, 'snapshot')).users.failure).toBeUndefined();
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

    test('the operator and administrators remain protected even with old dates', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'operator', cas: true, date: 'old', role: 'administrator', super: multisite },
        { name: 'other', cas: true, date: 'old', role: 'administrator' },
      ]) });
      await fixture(page, 'signin', { name: 'operator' });
      await page.goto(settings);
      await fixture(page, 'setdate', { id: String(ids.operator) });
      const job = await preview(page);
      expect(job.total).toBe(0);
      expect(job.protected).toBeGreaterThanOrEqual(2);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.operator).toBeDefined();
      expect(snapshot.users.other).toBeDefined();
    });

    test('complete backup acknowledgement is required in the UI and on the server', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'old', cas: true, date: 'old' },
      ]) });
      await page.locator('#cassify-cleanup-purge').click();
      await expect(page.locator('#cassify-cleanup-confirm')).toBeVisible();
      await expect(page.locator('#cassify-cleanup-backup-row')).toContainText('complete backup');
      await page.locator('#cassify-cleanup-target').fill('cassify_cleanup_e2e_target');
      await page.locator('#cassify-cleanup-confirm').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('backup');
      const job = await api(page, 'status');
      expect(job.phase).toBe('ready');
      expect((await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target', backup_confirmed: '' }, 400)).message).toContain('backup');
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
    });

    test('active sessions, new CAS markings and unknown marking dates are retained', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' },
        { name: 'session', cas: true, date: 'old' },
        { name: 'newmark', cas: true, marked: Math.floor(Date.now() / 1000) },
        { name: 'unknownmark', cas: true, marked: '' },
        { name: 'oldmark', cas: true },
      ]) });
      await fixture(page, 'session', { id: String(ids.session) });
      const job = await preview(page, { missing: '1' });
      expect(job.total).toBe(1);
      const result = await execute(page, job, 'cassify_cleanup_e2e_target');
      expect(result.success).toBe(1);
      const snapshot = await fixture(page, 'snapshot');
      for (const name of ['session', 'newmark', 'unknownmark']) expect(snapshot.users[name]).toBeDefined();
      expect(snapshot.users.oldmark).toBeUndefined();
    });

    test('a live session opened after preview protects its account', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'active', cas: true, date: 'old' },
      ]) });
      const job = await preview(page);
      await fixture(page, 'session', { id: String(ids.active) });
      const result = await execute(page, job, 'cassify_cleanup_e2e_target');
      expect(result.skipped).toBe(1);
      expect((await fixture(page, 'snapshot')).users.active).toBeDefined();
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

    test('content transfers are bounded, resumable, and leave the account until completion', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'large', cas: true, date: 'old', extra_posts: 65 },
      ]) });
      let job = await preview(page);
      job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      job = await api(page, 'batch', { token: job.token });
      expect(job.phase).toBe('executing');
      expect(job.success).toBe(0);
      expect((await fixture(page, 'snapshot')).users.large).toBeDefined();
      await page.reload();
      await page.locator('#cassify-cleanup-resume').click();
      await expect(page.locator('#cassify-cleanup-status')).toContainText('completed');
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.large).toBeUndefined();
      expect(snapshot.posts).toHaveLength(65);
      for (const post of snapshot.posts) expect(Number(post.post_author)).toBe(snapshot.users.target.id);
    });

    test('a failed confirmation-state write cannot begin deletion', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'old', cas: true, date: 'old' },
      ]) });
      const job = await preview(page);
      await fixture(page, 'fail_state', { enabled: '1' });
      const error = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' }, 400);
      expect(error.message).toContain('saved');
      await fixture(page, 'fail_state', { enabled: '' });
      expect((await api(page, 'status')).phase).toBe('ready');
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
    });

    test('a missing preview page stops execution without deleting accounts', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'old', cas: true, date: 'old' },
      ]) });
      let job = await preview(page);
      job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      await fixture(page, 'remove_page');
      expect((await api(page, 'batch', { token: job.token }, 400)).message).toContain('missing');
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
    });

    test('a connection-owned lock rejects concurrent requests and is released afterwards', async ({ page }) => {
      const holding = fixture(page, 'hold_lock');
      let held = false;
      for (let i = 0; i < 50 && !held; i++) {
        held = (await fixture(page, 'lock_status')).held;
      }
      expect(held).toBe(true);
      expect((await api(page, 'status', {}, 400)).message).toContain('Another request');
      await holding;
      expect((await api(page, 'status')).phase).toBe('idle');
    });

    test('marking all accounts requires an explicit server-side acknowledgement', async ({ page }) => {
      expect((await api(page, 'start', { mode: 'mark', days: '730' }, 400)).message).toContain('explicitly');
    });

    test('application-password accounts are protected from purge', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'api', cas: true, date: 'old' },
      ]) });
      expect((await fixture(page, 'application_password', { id: String(ids.api) })).ok).toBe(true);
      expect((await preview(page)).total).toBe(0);
      expect((await fixture(page, 'snapshot')).users.api).toBeDefined();
    });

    test('only executed tasks retain a simple result after clearing', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'old', cas: true, date: 'old' },
      ]) });
      let job = await preview(page);
      expect((await api(page, 'history')).operations).toEqual([]);
      await api(page, 'discard');
      expect((await api(page, 'history')).operations).toEqual([]);
      job = await execute(page, await preview(page), 'cassify_cleanup_e2e_target');
      await api(page, 'discard');
      const results = (await api(page, 'history')).operations;
      expect(results).toHaveLength(1);
      expect(results[0]).toEqual({ mode: 'purge', selection: 'inactive', time: job.now, phase: 'done', success: 1, skipped: 0, failed: 0 });
      expect(await fixture(page, 'audit', { job: job.token, event: 'deleted', id: String(ids.old) })).toEqual([]);
      await page.reload();
      await expect(page.locator('#cassify-cleanup-history')).toContainText('1 completed, 0 skipped, 0 failed');
      await expect(page.locator('#cassify-cleanup-export')).toHaveCount(0);
    });

    test('a protected administrator outside the preview can receive the content', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'targetadmin', cas: true, date: 'old', role: 'administrator' },
        { name: 'old', cas: true, date: 'old', posts: true },
      ]) });
      const job = await execute(page, await preview(page), 'cassify_cleanup_e2e_targetadmin');
      expect(job.success).toBe(1);
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.targetadmin).toBeDefined();
      expect(snapshot.users.old).toBeUndefined();
      for (const post of snapshot.posts) expect(Number(post.post_author)).toBe(ids.targetadmin);
    });

    test('an explicit re-mark establishes a grace period for a legacy CAS marker', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([{ name: 'legacy', cas: true }]) });
      await fixture(page, 'forget_marked', { id: String(ids.legacy) });
      expect((await preview(page, { missing: '1' })).total).toBe(0);
      const job = await preview(page, { mode: 'mark', role: 'subscriber', blog: '1' });
      expect(job.total).toBeGreaterThan(0);
      await execute(page, job);
      expect((await preview(page, { missing: '1' })).total).toBe(0);
      expect((await fixture(page, 'snapshot')).users.legacy).toBeDefined();
    });

    test('a lost connection lock stops a confirmed batch before deletion', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'old', cas: true, date: 'old', posts: true },
      ]) });
      let job = await preview(page);
      job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      await fixture(page, 'fail_lock', { enabled: '1' });
      expect((await api(page, 'batch', { token: job.token }, 400)).message).toContain('lock was lost');
      await fixture(page, 'fail_lock', { enabled: '' });
      expect((await fixture(page, 'snapshot')).users.old).toBeDefined();
      expect((await api(page, 'status')).phase).toBe('executing');
    });

    for (const change of ['touch', 'promote']) {
      test(`a ${change} during a partial transfer protects the account`, async ({ page }) => {
        const ids = await fixture(page, 'create', { users: JSON.stringify([
          { name: 'target' }, { name: 'partial', cas: true, date: 'old', extra_posts: 65 },
        ]) });
        let job = await preview(page);
        job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
        await api(page, 'batch', { token: job.token });
        await fixture(page, change, { id: String(ids.partial) });
        job = await api(page, 'batch', { token: job.token });
        expect(job.skipped).toBe(1);
        const snapshot = await fixture(page, 'snapshot');
        expect(snapshot.users.partial).toBeDefined();
        expect(snapshot.posts).toHaveLength(65);
        expect(snapshot.posts.some(post => Number(post.post_author) === ids.target)).toBe(true);
      });
    }

    test('new content on a transferred network site blocks global deletion', async ({ page }) => {
      test.skip(!multisite, 'Network only');
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'late', cas: true, date: 'old', posts: true },
      ]) });
      let job = await preview(page);
      job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      job = await api(page, 'batch', { token: job.token }); // main site transferred
      await fixture(page, 'late_post', { id: String(ids.late), blog: '1' });
      while (job.site_offset < job.site_count) job = await api(page, 'batch', { token: job.token });
      expect((await api(page, 'batch', { token: job.token }, 400)).message).toContain('previously transferred');
      const snapshot = await fixture(page, 'snapshot');
      expect(snapshot.users.late).toBeDefined();
      expect(snapshot.posts).toHaveLength(5);
    });

    for (const mode of ['late', 'delete_attachment', 'delete_post', 'trash_post', 'delete_link', 'touch']) {
      test(`deletion hooks cannot bypass safety checks: ${mode}`, async ({ page }) => {
        await fixture(page, 'create', { users: JSON.stringify([
          { name: 'target' }, { name: 'hook', cas: true, date: 'old', posts: true },
        ]) });
        await fixture(page, 'deletion_hook', { mode });
        let job = await preview(page);
        job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
        if (multisite) {
          for (let site = 0; site < job.site_count; site++) job = await api(page, 'batch', { token: job.token });
        }
        const error = await api(page, 'batch', { token: job.token }, 400);
        expect(error.message).toMatch(/content|eligibility/i);
        const snapshot = await fixture(page, 'snapshot');
        expect(snapshot.users.hook).toBeDefined();
        expect(snapshot.posts).toHaveLength(mode === 'late' ? 5 : 4);
        expect(snapshot.links).toHaveLength(1);
        expect(snapshot.posts.find(post => post.post_type === 'attachment').file_exists).toBe(true);
        await fixture(page, 'deletion_hook', { mode: '' });
      });
    }

    for (const mode of ['transfer', 'delete']) {
      test(`state-write failure after ${mode} is safe to retry`, async ({ page }) => {
        await fixture(page, 'create', { users: JSON.stringify([
          { name: 'target' }, { name: 'interrupted', cas: true, date: 'old', posts: true },
        ]) });
        let job = await preview(page);
        job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
        if (multisite && mode === 'delete') {
          for (let site = 0; site < job.site_count; site++) job = await api(page, 'batch', { token: job.token });
        }
        await fixture(page, 'fail_after_change', { mode });
        expect((await api(page, 'batch', { token: job.token }, 400)).message).toContain('saved');
        await fixture(page, 'fail_after_change', { mode: '' });
        job = await api(page, 'status');
        while (job.phase === 'executing') job = await api(page, 'batch', { token: job.token });
        const snapshot = await fixture(page, 'snapshot');
        expect(snapshot.users.interrupted).toBeUndefined();
        expect(snapshot.posts).toHaveLength(4);
        expect(snapshot.posts.find(post => post.post_type === 'attachment').file_exists).toBe(true);
        expect((await api(page, 'history')).operations[0].success).toBe(1);
        expect(job.success).toBe(1);
      });
    }

    test('an interrupted task retains its result after clearing', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'interrupted', cas: true, date: 'old', posts: true },
      ]) });
      let job = await preview(page);
      job = await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      await fixture(page, 'fail_reassignment', { id: String(ids.interrupted) });
      await api(page, 'batch', { token: job.token }, 400);
      await fixture(page, 'fail_reassignment', { id: '0' });
      await api(page, 'discard');
      expect((await api(page, 'history')).operations).toEqual([
        { mode: 'purge', selection: 'inactive', time: job.now, phase: 'executing', success: 0, skipped: 0, failed: 0 },
      ]);
      expect((await fixture(page, 'snapshot')).users.interrupted).toBeDefined();
    });

    test('a reviewed recipient cannot silently resolve to a replacement account', async ({ page }) => {
      const ids = await fixture(page, 'create', { users: JSON.stringify([
        { name: 'target' }, { name: 'candidate', cas: true, date: 'old' },
      ]) });
      const job = await preview(page);
      await api(page, 'prepare', { token: job.token, target_username: 'cassify_cleanup_e2e_target' });
      await fixture(page, 'remove', { id: String(ids.target) });
      await fixture(page, 'create', { users: JSON.stringify([{ name: 'target' }]) });
      expect((await api(page, 'confirm', { token: job.token, target_username: 'cassify_cleanup_e2e_target' }, 400)).message).toContain('recipient changed');
      expect((await fixture(page, 'snapshot')).users.candidate).toBeDefined();
    });

    test('task results remain restricted to the original operator', async ({ page }) => {
      await fixture(page, 'create', { users: JSON.stringify([
        { name: 'otheradmin', role: 'administrator', super: multisite },
        { name: 'old', cas: true, date: 'old' },
      ]) });
      await execute(page, await preview(page), ADMIN_USER);
      expect((await api(page, 'history')).operations).toHaveLength(1);
      await api(page, 'discard');
      await fixture(page, 'signin', { name: 'otheradmin' });
      await page.goto(settings);
      expect((await api(page, 'history')).operations).toHaveLength(0);
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
      await page.reload();
      await expect(page.locator('#cassify-cleanup-history')).toContainText('600 completed, 0 skipped, 0 failed');
    });
  });
};
