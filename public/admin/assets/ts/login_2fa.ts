document.addEventListener("DOMContentLoaded", () => {
  const errorBox = document.getElementById("wait-error") as HTMLElement | null;
  const waitMessage = document.getElementById("wait-message") as HTMLElement | null;

  interface LoginStatusResponse {
    status?: string;
    redirect?: string;
    message?: string;
  }

  const handleResult = (data: LoginStatusResponse | null): void => {
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
      setTimeout(() => {
        window.location.href = "/admin/login.php";
      }, 4000);
    }
  };

  const checkStatus = (): void => {
    fetch("/admin/login_status.php", { cache: "no-store" })
      .then((res) => {
        if (!res.ok) return null;
        return res.json() as Promise<LoginStatusResponse>;
      })
      .then((data) => {
        if (!data) return;
        handleResult(data);
      })
      .catch(() => {
        /* szándékosan elnyeljük */
      });
  };

  setInterval(checkStatus, 3000);
});
