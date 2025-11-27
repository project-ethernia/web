document.addEventListener("DOMContentLoaded", function () {
  var errorBox = document.getElementById("wait-error");
  var waitMessage = document.getElementById("wait-message");

  function handleResult(data) {
    if (!data || !data.status) return;

    if (data.status === "approved") {
      if (data.redirect) {
        window.location.href = data.redirect;
      } else {
        window.location.href = "/admin/index.php";
      }
      return;
    }

    if (data.status === "rejected" || data.status === "expired") {
      if (waitMessage) {
        waitMessage.style.display = "none";
      }
      if (errorBox) {
        errorBox.style.display = "block";
        errorBox.textContent =
          data.message ||
          (data.status === "rejected"
            ? "A bejelentkezési kérelmet elutasítottad Discordon."
            : "A bejelentkezési kérés lejárt.");
      }
      setTimeout(function () {
        window.location.href = "/admin/login.php";
      }, 4000);
    }
  }

  function checkStatus() {
    fetch("/admin/login_status.php", { cache: "no-store" })
      .then(function (res) {
        if (!res.ok) return null;
        return res.json();
      })
      .then(function (data) {
        if (!data) return;
        handleResult(data);
      })
      .catch(function () {});
  }

  setInterval(checkStatus, 3000);
});
