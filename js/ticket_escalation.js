window.TicketEscalation = (function() {
  let ref = null;
  const formId = 'escalateForm';
  const escStatusId = 'escStatus';
  const deptSelectId = 'esc_dept';
  const techSelectId = 'esc_tech';

  function init(ticketRef) {
    ref = ticketRef || window.TICKET_REF;
    if (!ref) {
      console.error("No reference ID provided to TicketEscalation.init()");
      return;
    }

    const form = document.getElementById(formId);
    if (!form) return;

    // populate lookups
    // populateLookups();

    form.addEventListener("submit", async (e) => {
    e.preventDefault(); 
    const reason = document.getElementById("reasonInput").value.trim();

    const fd = new FormData();
    fd.append("ref", ref);
    fd.append("reason", reason);

    const res = await fetch("../php/escalate_ticket.php", { method: "POST", body: fd });
    const data = await res.json();

    if (data.ok) {
      alert("Ticket successfully escalated!");
      window.location.href = `view_ticket.php?ref=${encodeURIComponent(ref)}`;
    } else {
      alert("Error: " + data.error);
    }
  });
  }

  document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("escalationForm");
  const reasonInput = document.getElementById("escalationReason");
  const ref = new URLSearchParams(window.location.search).get("ref");

  if (!form || !ref) {
    console.error("Missing escalation form or reference ID");
    return;
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const reason = reasonInput.value.trim();
    if (!reason) {
      alert("Please enter a reason for escalation.");
      return;
    }

    try {
      const response = await fetch("../php/escalate_ticket.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          ref, 
          reason,
        }),
      });

      const text = await response.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (err) {
        console.error("Invalid JSON response:", text);
        alert("Unexpected server response.");
        return;
      }

      if (data.ok) {
        alert("Ticket successfully escalated.");
        location.reload();
      } else {
        alert("Escalation failed: " + (data.error || "Unknown error"));
      }
    } catch (err) {
      console.error("Error escalating ticket:", err);
      alert("An error occurred during escalation.");
    }
  });
});

  function submitEscalation(fd) {
    const statusEl = document.getElementById(escStatusId);
    fd.append('ref', ref);

    statusEl.textContent = 'Submitting...';
    fetch(window.API_BASE + 'escalate_ticket.php', {
      method: 'POST',
      body: fd
    }).then(r => r.json())
      .then(j => {
        if (j && j.ok) {
          statusEl.textContent = 'Escalation saved';
          if (window.TicketLogs && typeof TicketLogs.refresh === 'function') TicketLogs.refresh();
        } else {
          statusEl.textContent = 'Error: ' + (j && j.error ? j.error : 'unknown');
        }
      }).catch(e => {
        console.error(e);
        statusEl.textContent = 'Error';
      }).finally(() => setTimeout(()=> statusEl.textContent = '', 3000));
  }

  return { init, submitEscalation };
})();
