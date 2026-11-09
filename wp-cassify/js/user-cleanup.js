/* Manual batches only: closing the page stops processing. */
(() => {
  'use strict';
  const root = document.getElementById('wp-cassify-cleanup');
  if (!root) return;
  const el = name => document.getElementById(`cassify-cleanup-${name}`);
  let job = null;
  let busy = false;
  let previewPage = 0;

  async function request(operation, extra = {}, updateState = true) {
    const data = new URLSearchParams({
      action: 'wp_cassify_cleanup', nonce: wpCassifyCleanup.nonce,
      operation, token: job ? job.token : '', ...extra,
    });
    const response = await fetch(wpCassifyCleanup.url, { method: 'POST', credentials: 'same-origin', body: data });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.data?.message || 'Request refused. Reload the page to renew your session.');
    if (updateState) {
      job = result.data;
      render();
    }
    return result.data;
  }

  function render() {
    if (!job || job.phase === 'idle') {
      el('status').textContent = 'No saved operation.';
      el('target').disabled = false;
      for (const name of ['users', 'errors']) el(name).replaceChildren();
      for (const name of ['prev', 'next', 'confirm', 'resume', 'export']) el(name).hidden = true;
      el('warning').textContent = '';
      el('summary').textContent = '';
      el('backup-row').hidden = true;
      return;
    }
    el('export').hidden = false;
    el('target').disabled = Boolean(job.target);
    el('backup-row').hidden = job.mode !== 'purge' || job.phase !== 'ready';
    const mode = job.mode === 'mark' ? 'CAS marking' : 'CAS purge';
    el('status').textContent = `${mode}: ${job.phase}. Selected: ${job.total}. Old: ${job.old}. Without date: ${job.missing_count}. Invalid dates: ${job.invalid}. Protected: ${job.protected || 0}. Completed: ${job.success}. Skipped: ${job.skipped}. Failed: ${job.failed}.`;
    const date = value => {
      if (!/^\d+$/.test(String(value)) || Number(value) <= 0) return 'unknown';
      const parsed = new Date(Number(value) * 1000);
      return Number.isFinite(parsed.getTime()) ? parsed.toISOString() : 'unknown';
    };
    el('summary').textContent = job.mode === 'purge'
      ? `${job.total} accounts selected. Login cutoff: ${date(job.cutoff)} (${job.days} days). Include accounts without a login date: ${job.missing ? 'yes, subject to the marking grace period' : 'no'}.`
      : `${job.total} accounts will be marked as CAS. Scope: ${job.role ? `role ${job.role} on site ${job.blog}` : 'all WordPress accounts, including local accounts'}. This can make them eligible for future purges.`;
    el('warning').textContent = job.target_login ? `Content recipient: #${job.target} ${job.target_login}.` : '';
    for (const [name, rows] of [['users', job.users || []], ['errors', job.errors || []]]) {
      el(name).replaceChildren();
      rows.forEach(row => {
        const li = document.createElement('li');
        li.textContent = typeof row === 'string' ? row : `#${row.id} ${row.login} (${row.reason}); last login: ${date(row.last_login_at)}; CAS marked: ${date(row.cas_marked_at)}`;
        el(name).appendChild(li);
      });
    }
    previewPage = job.preview_page || 0;
    el('prev').hidden = previewPage <= 0 || job.phase === 'scanning';
    el('next').hidden = previewPage + 1 >= job.pages || job.phase === 'scanning';
    el('confirm').hidden = job.phase !== 'ready' || !job.total;
    el('confirm').textContent = job.mode === 'mark' ? 'Confirm CAS marking' : job.target ? 'Confirm deletion and content recipient' : 'Review content recipient';
    el('resume').hidden = !['scanning', 'executing'].includes(job.phase);
  }

  async function run(fn) {
    if (busy) return;
    busy = true;
    root.querySelectorAll('button').forEach(button => { button.disabled = true; });
    el('settings').disabled = true;
    try {
      await fn();
    } catch (error) {
      el('status').textContent = `Operation paused: ${error.message}`;
      if (job && ['scanning', 'executing'].includes(job.phase)) el('resume').hidden = false;
    } finally {
      busy = false;
      root.querySelectorAll('button').forEach(button => { button.disabled = false; });
      el('settings').disabled = false;
    }
  }

  async function advance() {
    while (job && ['scanning', 'executing'].includes(job.phase)) {
      await request(job.phase === 'scanning' ? 'scan' : 'batch');
    }
  }

  for (const mode of ['mark', 'purge']) {
    el(mode).addEventListener('click', () => run(async () => {
      const role = el('scope').value === 'role' ? el('role').value.trim() : '';
      if (mode === 'mark' && el('scope').value === 'role' && !role) throw new Error('Enter a role slug.');
      el('backup').checked = false;
      if (mode === 'mark' && el('scope').value === 'all' && !el('all-accounts').checked) throw new Error('Explicitly acknowledge marking all accounts, including local accounts.');
      await request('start', {
        all_accounts: el('scope').value === 'all' && el('all-accounts').checked ? '1' : '',
        mode, days: el('days').value, missing: el('missing').checked ? '1' : '',
        role: mode === 'mark' ? role : '', blog: el('blog')?.value || '',
      });
      await advance();
    }));
  }
  el('confirm').addEventListener('click', () => run(async () => {
    if (job.mode === 'purge' && !el('backup').checked) throw new Error('Create a complete backup of the database and all site files, verify it can be restored, and confirm the backup checkbox before purging.');
    if (job.mode === 'purge' && !job.target) {
      await request('prepare', { target_username: el('target').value.trim() });
      return;
    }
    await request('confirm', { target_username: job.target_login || '', backup_confirmed: el('backup').checked ? '1' : '' });
    await advance();
  }));
  async function downloadAudit(token) {
    const events = [];
    const selection = [];
    let result;
    let page = 0;
    do {
      result = await request('archive', { archive_token: token, selection_page: page, audit_offset: events.length }, false);
      events.push(...result.audit);
      selection.push(...result.selection);
      page++;
    } while (result.audit.length === 200 || page < result.operation.pages);
    const url = URL.createObjectURL(new Blob([JSON.stringify({ operation: result.operation, selection, events }, null, 2)], { type: 'application/json' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `wp-cassify-cleanup-${token}.json`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
  async function loadHistory(offset = 0) {
    const result = await request('history', { history_offset: offset }, false);
    el('history').replaceChildren();
    for (const item of result.operations) {
      const li = document.createElement('li');
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'button';
      button.textContent = `Download ${item.mode} audit: ${new Date(item.time * 1000).toISOString()} (${item.phase})`;
      button.addEventListener('click', () => run(() => downloadAudit(item.token)));
      li.appendChild(button);
      el('history').appendChild(li);
    }
    el('history-next').hidden = result.next_offset === null;
    el('history-next').onclick = () => run(() => loadHistory(result.next_offset));
    el('history-first').hidden = offset === 0;
  }
  el('history-first').addEventListener('click', () => run(() => loadHistory()));
  el('history-refresh').addEventListener('click', () => run(() => loadHistory()));
  el('export').addEventListener('click', () => run(async () => {
    await request('export'); // Refresh the retained operation summary before download.
    await downloadAudit(job.token);
  }));
  el('discard').addEventListener('click', () => run(async () => { await request('discard'); await loadHistory(); }));
  el('resume').addEventListener('click', () => run(async () => {
    await request('status');
    await advance();
  }));
  for (const [name, direction] of [['prev', -1], ['next', 1]]) {
    el(name).addEventListener('click', () => run(() => request('preview', { preview_page: previewPage + direction })));
  }
  // Loading the page only reads state; resuming always requires a click.
  run(async () => { await request('status'); await loadHistory(); });
})();
