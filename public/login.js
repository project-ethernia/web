// KONFIG – EZEKET ÍRD ÁT A SAJÁT ADATAIDRA
const MC_SERVER = "play.ethernia.hu";          // pl. "play.ethernia.hu"
const DISCORD_GUILD_ID = "1322224781000577046"; // pl. "123456789012345678"

document.addEventListener("DOMContentLoaded", () => {
  // év a láblécben
  const yearEl = document.getElementById("year");
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  updateMinecraftStatus();
  updateDiscordStatus();
});

// Minecraft server stat (mcsrvstat.us API)
async function updateMinecraftStatus() {
  const onlineEl = document.getElementById("mc-online");
  const maxEl = document.getElementById("mc-max");
  if (!onlineEl || !maxEl) return;

  try {
    const res = await fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`, {
      cache: "no-store"
    });

    if (!res.ok) throw new Error("HTTP error");

    const data = await res.json();

    if (data && data.players) {
      onlineEl.textContent = data.players.online ?? 0;
      maxEl.textContent = data.players.max ?? "?";
    } else {
      onlineEl.textContent = "0";
      maxEl.textContent = "?";
    }
  } catch (err) {
    console.error("Hiba a Minecraft stat lekérésekor:", err);
    onlineEl.textContent = "N/A";
    maxEl.textContent = "";
  }
}

// Discord stat (Discord guild widget – engedélyezve kell legyen!)
async function updateDiscordStatus() {
  const onlineEl = document.getElementById("discord-online");
  if (!onlineEl) return;

  try {
    const res = await fetch(
      `https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`,
      { cache: "no-store" }
    );

    if (!res.ok) throw new Error("HTTP error");

    const data = await res.json();

    // presence_count ha van, különben members.length
    const count =
      typeof data.presence_count === "number"
        ? data.presence_count
        : Array.isArray(data.members)
        ? data.members.length
        : null;

    if (typeof count === "number") {
      onlineEl.textContent = count;
    } else {
      onlineEl.textContent = "N/A";
    }
  } catch (err) {
    console.error("Hiba a Discord stat lekérésekor:", err);
    onlineEl.textContent = "N/A";
  }
}
