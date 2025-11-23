// /admin/assets/js/players.js

document.addEventListener("DOMContentLoaded", () => {
  const table = document.querySelector(".players-table");
  if (!table) return;

  table.addEventListener("click", async (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;

    const row = btn.closest("tr");
    if (!row) return;

    const id   = row.dataset.id;
    const name = row.dataset.name || "ismeretlen";

    if (btn.classList.contains("btn-ban-toggle")) {
      const action = btn.dataset.action; // ban / unban
      const confirmText =
        action === "ban"
          ? `'${name}' játékost tényleg bannolod?`
          : `'${name}' unbannolása?`;

      if (!window.confirm(confirmText)) return;

      await sendPlayerAction(id, action);

    } else if (btn.classList.contains("btn-mute-toggle")) {
      const action = btn.dataset.action; // mute / unmute
      const confirmText =
        action === "mute"
          ? `'${name}' némítása (chat mute)?`
          : `'${name}' némításának feloldása?`;

      if (!window.confirm(confirmText)) return;

      await sendPlayerAction(id, action);
    }
  });
});

async function sendPlayerAction(id, action) {
  try {
    const formData = new FormData();
    formData.append("id", id);
    formData.append("action", action);

    const res = await fetch("/admin/players.php", {
      method: "POST",
      body: formData,
    });

    const data = await res.json();

    if (!data.ok) {
      alert("Hiba: " + (data.error || "ismeretlen hiba"));
      return;
    }

    // egyszerű megoldás: frissítjük az oldalt
    window.location.reload();
  } catch (err) {
    console.error(err);
    alert("Nem sikerült a művelet (hálózati hiba?).");
  }
}
