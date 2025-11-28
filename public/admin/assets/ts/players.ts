document.addEventListener("DOMContentLoaded", () => {
  const table = document.querySelector(".players-table") as HTMLElement | null;
  if (!table) return;

  const doPlayerAction = async (id: string, action: string): Promise<void> => {
    try {
      const formData = new FormData();
      formData.append("id", id);
      formData.append("action", action);

      const res = await fetch("/admin/players.php", {
        method: "POST",
        body: formData
      });

      const data = await res.json();

      if (!data.ok) {
        alert("Hiba: " + (data.error || "ismeretlen hiba"));
        return;
      }

      window.location.reload();
    } catch (err) {
      console.error(err);
      alert("Nem sikerült a művelet (hálózati hiba?).");
    }
  };

  table.addEventListener("click", async (e: MouseEvent) => {
    const target = e.target as HTMLElement | null;
    if (!target) return;

    const btn = target.closest("button") as HTMLButtonElement | null;
    if (!btn) return;

    const row = btn.closest("tr") as HTMLTableRowElement | null;
    if (!row) return;

    const id = row.dataset.id || "";
    const name = row.dataset.name || "ismeretlen";

    if (btn.classList.contains("btn-ban-toggle")) {
      const action = btn.dataset.action || "";
      const confirmText =
        action === "ban"
          ? `'${name}' játékost tényleg bannolod?`
          : `'${name}' unbannolása?`;

      if (!window.confirm(confirmText)) return;
      await doPlayerAction(id, action);
    } else if (btn.classList.contains("btn-mute-toggle")) {
      const action = btn.dataset.action || "";
      const confirmText =
        action === "mute"
          ? `'${name}' némítása (chat mute)?`
          : `'${name}' némításának feloldása?`;

      if (!window.confirm(confirmText)) return;
      await doPlayerAction(id, action);
    }
  });
});
