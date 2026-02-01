window.TicketLogs = (function() {
  const container = document.getElementById("logsContainer");
  let ticketRef = null;

  async function loadLogs() {
    if (!ticketRef) return;
    try {
      const res = await fetch(`../php/get_logs_monitor.php?ref=${ticketRef}`);
      const data = await res.json();
      if (!data.ok) {
        container.innerHTML = `<p class="text-gray-500 text-center py-4">${data.error || 'Error loading logs'}</p>`;
        return;
      }

      container.innerHTML = "";
      const logs = data.data.logs || [];

      if (logs.length === 0) {
        container.innerHTML = `<p class="text-gray-500 text-sm italic">No activity logs yet.</p>`;
        return;
      }

      logs.forEach(log => {
        const div = document.createElement("div");
        div.classList.add("p-3", "border", "rounded-lg", "mb-2", "bg-gray-50");
        div.innerHTML = `
          <div class="flex justify-between items-center mb-1">
            <span class="font-semibold">${log.user_name || 'System'}</span>
            <span class="text-xs text-gray-500">${log.created_at}</span>
          </div>
          <div class="text-sm text-gray-700">${log.action_details}</div>
          <span class="inline-block text-xs bg-blue-100 text-blue-800 px-2 py-0.5 mt-2 rounded">${log.action_type}</span>
        `;
        container.appendChild(div);
      });
    } catch (err) {
      console.error("Error loading logs:", err);
    }
  }

  return {
    init: (ref) => { ticketRef = ref; loadLogs(); },
    refresh: () => loadLogs()
  };
})();
