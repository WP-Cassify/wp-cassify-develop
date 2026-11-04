/* Manual batches only: closing the page stops processing. */
(() => {
  'use strict';
  const root = document.getElementById('wp-cassify-cleanup');
  if (!root) return;
  const el = name => document.getElementById(`cassify-cleanup-${name}`);
  let job = null;
  let busy = false;
  let previewPage = 0;

  async function request(operation, extra = {}) {
    const data = new URLSearchParams({
      action: 'wp_cassify_cleanup', nonce: wpCassifyCleanup.nonce,
      operation, token: job ? job.token : '', ...extra,
    });
    const response = await fetch(wpCassifyCleanup.url, { method: 'POST', credentials: 'same-origin', body: data });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.data?.message || 'Request refused. Reload the page to renew your session.');
    job = result.data;
    render();
    return job;
  }

  function render() {
    if (!job || job.phase === 'idle') {
      el('status').textContent = 'No saved operation.';
      for (const name of ['users', 'errors']) el(name).replaceChildren();
      for (const name of ['prev', 'next', 'confirm', 'resume']) el(name).hidden = true;
      el('warning').textContent = '';
      return;
    }
    const mode = job.mode === 'mark' ? 'CAS marking' : 'CAS purge';
    el('status').textContent = `${mode}: ${job.phase}. Selected: ${job.total}. Old: ${job.old}. Without date: ${job.missing_count}. Invalid dates: ${job.invalid}. Completed: ${job.success}. Skipped: ${job.skipped}. Failed: ${job.failed}.`;
    el('warning').textContent = job.self && job.mode === 'purge'
      ? 'Your own account is in the preview. If still eligible, it will be deleted last and your session will end.' : '';
    for (const [name, rows] of [['users', job.users || []], ['errors', job.errors || []]]) {
      el(name).replaceChildren();
      rows.forEach(row => {
        const li = document.createElement('li');
        li.textContent = typeof row === 'string' ? row : `#${row.id} ${row.login} (${row.reason})`;
        el(name).appendChild(li);
      });
    }
    previewPage = job.preview_page || 0;
    el('prev').hidden = previewPage <= 0 || job.phase === 'scanning';
    el('next').hidden = previewPage + 1 >= job.pages || job.phase === 'scanning';
    el('confirm').hidden = job.phase !== 'ready' || !job.total;
    el('confirm').textContent = job.mode === 'mark' ? 'Confirm CAS marking' : 'Confirm deletion of these accounts';
    el('resume').hidden = !['scanning', 'executing'].includes(job.phase);
    if (job.session_ended) {
      el('warning').textContent = 'Your account was deleted. Your session has ended.';
      root.querySelectorAll('button').forEach(button => { button.disabled = true; });
    }
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
      if (!job?.session_ended) {
        root.querySelectorAll('button').forEach(button => { button.disabled = false; });
        el('settings').disabled = false;
      }
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
      await request('start', {
        mode, days: el('days').value, missing: el('missing').checked ? '1' : '',
        role: mode === 'mark' ? role : '', blog: el('blog')?.value || '',
      });
      await advance();
    }));
  }
  el('confirm').addEventListener('click', () => run(async () => {
    await request('confirm', { target_username: el('target').value.trim() });
    await advance();
  }));
  el('discard').addEventListener('click', () => run(() => request('discard')));
  el('resume').addEventListener('click', () => run(async () => {
    await request('status');
    await advance();
  }));
  for (const [name, direction] of [['prev', -1], ['next', 1]]) {
    el(name).addEventListener('click', () => run(() => request('preview', { preview_page: previewPage + direction })));
  }
  // Loading the page only reads state; resuming always requires a click.
  run(() => request('status'));
})();
