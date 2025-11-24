const MC_SERVER = "play.ethernia.hu";
const DISCORD_GUILD_ID = "1322224781000577046";

document.addEventListener("DOMContentLoaded", () => {
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  updateMinecraftStats();
  updateDiscordStats();
  initNewsSlider();
  initNewsModal();
});

async function updateMinecraftStats() {
  const onlineEl = document.getElementById("mc-online");
  const maxEl = document.getElementById("mc-max");
  if (!onlineEl || !maxEl) return;

  try {
    const res = await fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`, { cache: "no-store" });
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();

    const online =
      data && data.players && typeof data.players.online === "number"
        ? data.players.online
        : 0;
    const max =
      data && data.players && typeof data.players.max === "number"
        ? data.players.max
        : "?";

    onlineEl.textContent = online;
    maxEl.textContent = max;
  } catch (e) {
    console.error("MC stat hiba:", e);
    onlineEl.textContent = "N/A";
    maxEl.textContent = "";
  }
}

async function updateDiscordStats() {
  const onlineEl = document.getElementById("discord-online");
  if (!onlineEl) return;

  try {
    const url = `https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`;
    const res = await fetch(url, { cache: "no-store" });
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();

    let count = null;
    if (typeof data.presence_count === "number") {
      count = data.presence_count;
    } else if (Array.isArray(data.members)) {
      count = data.members.length;
    }

    onlineEl.textContent = typeof count === "number" ? count : "N/A";
  } catch (e) {
    console.error("Discord stat hiba:", e);
    onlineEl.textContent = "N/A";
  }
}

function initNewsSlider() {
  const list = document.getElementById("news-list");
  const prevBtn = document.getElementById("news-prev");
  const nextBtn = document.getElementById("news-next");
  if (!list || !prevBtn || !nextBtn) return;

  function scrollByAmount(dir) {
    const card = list.querySelector(".news-card");
    if (!card) return;
    const style = window.getComputedStyle(card);
    const gap = parseFloat(style.marginRight || "0");
    const width = card.getBoundingClientRect().width + gap;
    const amount = width * 1.3 * dir;
    list.scrollBy({ left: amount, behavior: "smooth" });
  }

  prevBtn.addEventListener("click", () => scrollByAmount(-1));
  nextBtn.addEventListener("click", () => scrollByAmount(1));
}

function initNewsModal() {
  const modal = document.getElementById("news-modal");
  const tagEl = document.getElementById("news-modal-tag");
  const dateEl = document.getElementById("news-modal-date");
  const titleEl = document.getElementById("news-modal-title");
  const textEl = document.getElementById("news-modal-text");
  const closeBtn = document.getElementById("news-modal-close");
  const backdrop = document.getElementById("news-modal-backdrop");
  if (!modal || !tagEl || !dateEl || !titleEl || !textEl) return;

  function openFromCard(card) {
    if (!card) return;
    const tag = card.getAttribute("data-tag") || "";
    const date = card.getAttribute("data-date") || "";
    const title = card.getAttribute("data-title") || "";
    const text = card.getAttribute("data-text") || "";

    tagEl.textContent = tag;
    dateEl.textContent = date;
    titleEl.textContent = title;
    textEl.textContent = text;

    modal.classList.add("open");
  }

  function close() {
    modal.classList.remove("open");
  }

  const buttons = document.querySelectorAll(".news-detail-btn");
  buttons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const card = btn.closest(".news-card");
      openFromCard(card);
    });
  });

  if (closeBtn) closeBtn.addEventListener("click", close);
  if (backdrop) backdrop.addEventListener("click", close);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") close();
  });
}