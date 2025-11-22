// KONFIG – EZEKET ÁLLÍTSD BE
const MC_SERVER = "play.ethernia.hu";          // a te MC szervered hostja
const DISCORD_GUILD_ID = "000000000000000000"; // a te Discord szerver ID-je

document.addEventListener("DOMContentLoaded", () => {
  // év a footerben
  const yearEl = document.getElementById("year");
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  updateMinecraftStats();
  updateDiscordStats();
});

// Minecraft stat (api.mcsrvstat.us)
async function updateMinecraftStats() {
  const onlineEl = document.getElementById("mc-online");
  const maxEl = document.getElementById("mc-max");
  if (!onlineEl || !maxEl) return;

  try {
    const res = await fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`, {
      cache: "no-store",
    });

    if (!res.ok) throw new Error("HTTP error");

    const data = await res.json();

    onlineEl.textContent = data?.players?.online ?? 0;
    maxEl.textContent = data?.players?.max ?? "?";
  } catch (err) {
    console.error("Minecraft stat hiba:", err);
    onlineEl.textContent = "N/A";
    maxEl.textContent = "";
  }
}

// Discord stat (guild widget – engedélyezni kell a szerver beállításainál!)
async function updateDiscordStats() {
  const onlineEl = document.getElementById("discord-online");
  if (!onlineEl) return;

  try {
    const res = await fetch(
      `https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`,
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
  } catch (err) {
    console.error("Discord stat hiba:", err);
    onlineEl.textContent = "N/A";
  }
}
