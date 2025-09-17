document.addEventListener("DOMContentLoaded", function () {
  console.log("Heartbeat script loaded");
  
  function sendHeartbeatPing() {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", heartbeat_object.ajax_url, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function () {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          console.log(response.data);
        } catch (e) {
          console.error("Error parsing server response:", e);
        }
      } else {
        console.error("Failed to send heartbeat ping. Status:", xhr.status);
      }
    };
    
    const data = "action=session_heartbeat";
    xhr.send(data);
  }
  
  setInterval(sendHeartbeatPing, 60000);
});
