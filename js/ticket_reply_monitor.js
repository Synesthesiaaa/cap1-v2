document.addEventListener("DOMContentLoaded", () => {
  const ref = new URLSearchParams(window.location.search).get("ref");
  const replyInput = document.getElementById("replyText");
  const fileInput = document.getElementById("replyAttachment");
  const sendBtn = document.getElementById("send-reply-button");

  if (!ref) {
    console.error("No reference ID found in URL");
    return;
  }

  loadReplies(ref);

  sendBtn.addEventListener("click", async () => {
    if (!replyInput) {
      console.error("replyInput element not found");
      return;
    }

    const replyText = replyInput.value.trim();  
    const file = fileInput.files[0];

    if (!replyText && !file) {
      alert("Please enter a reply or attach a file before sending.");
      return;
    }

    const formData = new FormData();
    formData.append("ref", ref);
    formData.append("reply", replyText);
    if (file) formData.append("reply_attachment", file);

    try {
      const response = await fetch("../php/post_reply_monitor.php", {
        method: "POST",
        body: formData
      });

      const text = await response.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (error) {
        console.error("Invalid JSON response:", text);
        alert("Server returned an unexpected response. Check logs.");
        return;
      }

      if (data.ok) {
        // Logging is handled by post_reply_monitor.php, no need for separate call
        replyInput.value = "";  
        if (fileInput) fileInput.value = "";
        setTimeout(() => {
          console.log("Refreshing replies...");
          loadReplies(ref, true);
        }, 1500);    
      } else {
        alert("Error sending reply: " + (data.error || "Unknown error"));
      }
    } catch (err) {
      console.error("Error sending reply:", err);  
      alert("An error occurred while sending the reply.");
    }
  });
});

let isLoading = false;

async function loadReplies(ref, forcereload = false) {

  if (isLoading && !forcereload) return; 
  isLoading = true;
  try {
    const response = await fetch(`../php/get_reply.php?ref=${encodeURIComponent(ref)}&t=${Date.now()}`);

    if (!response.ok) {
      console.error(`Failed to fetch replies: ${response.statusText}`);
      return;
    }

    const text = await response.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error("Invalid JSON in get_reply:", text);
      return;
    }

    const container = document.getElementById("repliesContainer");
    if (!container) return;

    container.innerHTML = "";

    if (!Array.isArray(data.replies) || data.replies.length === 0) {
      container.innerHTML = `<p class="text-gray-500 text-sm italic">No replies yet.</p>`;
      return;
    }

    // Filters duplicates and helps with reply loading issues
    if (Array.isArray(data.replies)) {
      const seen = new Set();
      data.replies = data.replies.filter(r => {
        const key = r.reply_id || `${r.replied_by}-${r.created_at}-${r.message}`;
        if (seen.has(key)) return false;
          seen.add(key);
          return true;
        });
    }

    data.replies.forEach((r) => {
      const div = document.createElement("div");
      const sender =
        r.replied_by === "technician"
          ? `<span class="text-blue-900 font-semibold">Technician</span>`
          : r.replied_by === "system"
          ? `<span class="text-gray-600 font-semibold">System</span>`
          : `<span class="text-green-800 font-semibold">User</span>`;

      div.classList.add("p-3", "border", "rounded-lg", "mb-2", "bg-gray-50");

      const messageElement = document.createElement("p");
      messageElement.classList.add("mt-1", "text-gray-700");
      messageElement.textContent = r.message;  

      div.innerHTML = `
        <div class="flex justify-between items-center">
          ${sender}
          <span class="text-xs text-gray-500">${r.created_at}</span>
        </div>
        <p class="mt-1 text-gray-700">${r.message}</p>
        ${
          r.attachment_path
            ? `<a href="${r.attachment_path}" class="text-blue-700 text-sm" target="_blank">View Attachment</a>`
            : ""
        }
      `;
      container.appendChild(div);
    });
  } catch (err) {
    console.error("Error loading replies:", err); // Catching errors here suddenly need to fix
  }
}
