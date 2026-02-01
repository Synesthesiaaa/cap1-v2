document.addEventListener('DOMContentLoaded', () => {
  if (!window.TICKET_REF) {
    console.error("Missing TICKET_REF");
    return;
  }
  loadTicketDetails();
  // init logs & reply & escalation modules (they expose init functions)
  if (window.TicketLogs && typeof TicketLogs.init === 'function') TicketLogs.init(window.TICKET_REF);
  if (window.TicketReply && typeof TicketReply.init === 'function') TicketReply.init(window.TICKET_REF);
  if (window.TicketEscalation && typeof TicketEscalation.init === 'function') TicketEscalation.init(window.TICKET_REF);

  // wire refresh button
  const rbtn = document.getElementById('refreshLogsBtn');
  if (rbtn) rbtn.addEventListener('click', () => { if (window.TicketLogs) window.TicketLogs.refresh(); });
});

function loadTicketDetails() {
  const ref = encodeURIComponent(window.TICKET_REF);
  fetch(window.API_BASE + 'get_ticket.php?ref=' + ref)
    .then(r => r.json())
    .then(data => {
      if (!data || !data.reference_id) {
        alert('Ticket not found.');
        return;
      }
      document.getElementById('page_ref').textContent = data.reference_id;
      document.getElementById('page_status').textContent = (data.status || '').toUpperCase();
      document.getElementById('page_priority').textContent = (data.priority || '').toUpperCase() + (data.sla_date ? ' • SLA: ' + data.sla_date : '');
      document.getElementById('requester_name').textContent = data.requester_name || '';
      document.getElementById('requester_email').textContent = data.requester_email || '';
      document.getElementById('dept_name').textContent = data.department_name || '';
      document.getElementById('assignee_name').textContent = data.assignee_name || 'Unassigned';
      document.getElementById('type_category').textContent = (data.type || '') + (data.category ? ' / ' + data.category : '');
      document.getElementById('description').textContent = data.description || '';

      // data.attachments can be single path or JSON list
      const attachEl = document.getElementById('attachments');
      attachEl.innerHTML = '';
      if (data.attachments) {
        try {
          const arr = typeof data.attachments === 'string' && data.attachments.startsWith('[') ? JSON.parse(data.attachments) : [data.attachments];
          arr.forEach(a => {
            if (!a) return;
            const link = document.createElement('a');
            link.href = a;
            link.target = '_blank';
            link.className = 'text-sm text-blue-600 hover:underline block';
            link.textContent = a.split('/').pop();
            attachEl.appendChild(link);
          });
        } catch (err) {
          const link = document.createElement('a');
          link.href = data.attachments;
          link.target = '_blank';
          link.className = 'text-sm text-blue-600 hover:underline block';
          link.textContent = (data.attachments || '').split('/').pop();
          attachEl.appendChild(link);
        }
      } else {
        attachEl.innerHTML = '<p class="text-sm muted">No attachments</p>';
      }

      // populate escalation 
      if (window.TicketEscalation && typeof TicketEscalation.populateLookups === 'function') {
        TicketEscalation.populateLookups();
      }
    }).catch(err => {
      console.error(err);
      alert('Failed to load ticket details.');
    });
}
