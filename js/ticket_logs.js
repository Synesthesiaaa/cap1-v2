// Not functional yet

window.TicketLogs = (function() {
  let ref = null;
  let timer = null;
  const interval = 30000; 

  function init(ticketRef) {
    ref = ticketRef;
    refresh();
    if (timer) clearInterval(timer);
    timer = setInterval(refresh, interval);
  }

  function refresh() {
    if (!ref) return;
    fetch(window.API_BASE + 'get_logs.php?ref=' + encodeURIComponent(ref))
      .then(r => r.json())
      .then(list => {
        render(list || []);
      }).catch(err => {
        console.error('Failed to load logs', err);
      });
  }

  function render(list) {
    const area = document.getElementById('logsArea');
    area.innerHTML = '';
    if (!Array.isArray(list) || list.length === 0) {
      area.innerHTML = '<p class="text-sm muted">No logs or replies yet.</p>';
      return;
    }

    // render newest first (if logs are oldest->newest, reverse)
    list.forEach(l => {
      const wrap = document.createElement('div');
      wrap.className = 'border rounded p-3 bg-gray-50';
      const header = document.createElement('div');
      header.className = 'flex items-center justify-between mb-1';
      header.innerHTML = `<div class="text-sm font-medium">${escapeHtml(l.author)}</div>
                          <div class="text-xs muted">${escapeHtml(l.created_at)}</div>`;
      const msg = document.createElement('div');
      msg.className = 'text-sm text-gray-700';
      msg.textContent = l.message || '';
      wrap.appendChild(header);
      wrap.appendChild(msg);

      if (l.attachment) {
        const a = document.createElement('a');
        a.href = l.attachment;
        a.target = '_blank';
        a.className = 'text-sm text-blue-600 hover:underline mt-2 block';
        a.textContent = 'Attachment: ' + (l.attachment.split('/').pop() || 'file');
        wrap.appendChild(a);
      }
      area.appendChild(wrap);
    });
  }

  function stop() {
    if (timer) { clearInterval(timer); timer = null; }
  }

  // expose
  return { init, refresh, stop };
})();

// small escape helper
function escapeHtml(s) {
  if (!s) return '';
  return String(s).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; });
}
