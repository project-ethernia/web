const MC_SERVER = "play.ethernia.hu";
const DISCORD_GUILD_ID = "1322224781000577046"; // <-- saját Discord szerver ID

document.addEventListener("DOMContentLoaded", () => {
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  updateMinecraftStats();
  updateDiscordStats();
});

async function updateMinecraftStats() {
  const onlineEl = document.getElementById("mc-online");
  const maxEl = document.getElementById("mc-max");
  if (!onlineEl || !maxEl) return;

  try {
    const res = await fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`, { cache: "no-store" });
    if (!res.ok) throw new Error("HTTP error");
    const data = await res.json();
    onlineEl.textContent = data?.players?.online ?? 0;
    maxEl.textContent  = data?.players?.max ?? "?";
  } catch (e) {
    onlineEl.textContent = "N/A";
    maxEl.textContent = "";
  }
}

async function updateDiscordStats() {
  const onlineEl = document.getElementById("discord-online");
  if (!onlineEl) return;

  try {
    const res = await fetch(
      `https://discord.com/api/guilds/${1322224781000577046}/widget.json`,
      { cache: "no-store" }
    );

    if (!res.ok) throw new Error("HTTP error");

    const data = await res.json();

    const count =
      typeof data.presence_count === "number"
        ? data.presence_count
        : Array.isArray(data.members)
        ? data.members.length
        : null;

    onlineEl.textContent = typeof count === "number" ? count : "N/A";
  } catch (e) {
    onlineEl.textContent = "N/A";
  }
}
