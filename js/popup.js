// Auto-hide toast after 5 seconds
window.onload = function() {
  const toast = document.getElementById("toast");
  if (toast) {
    setTimeout(() => {
      toast.classList.add("hide");
    }, 5000); // 5 seconds
  }
}