document.getElementById("year").textContent = new Date().getFullYear();

const MC_SERVER = "play.ethernia.hu";
const DISCORD_GUILD_ID = "0000000000000"; // <-- ide a sajátod

// Minecraft stat
fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`)
  .then(r => r.json())
  .then(d => {
    document.getElementById("mc-online").textContent = d?.players?.online ?? "0";
    document.getElementById("mc-max").textContent = d?.players?.max ?? "?";
  });

// Discord stat
fetch(`https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`)
  .then(r => r.json())
  .then(d => {
    const count = d.presence_count || d.members?.length || 0;
    document.getElementById("discord-online").textContent = count;
  })
  .catch(() => {
    document.getElementById("discord-online").textContent = "N/A";
  });
