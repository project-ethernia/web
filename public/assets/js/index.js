const MC_SERVER = "play.ethernia.hu";
const DISCORD_GUILD_ID = "1322224781000577046";

document.addEventListener("DOMContentLoaded", () => {
  const yearEl = document.getElementById("year");
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

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
    const res = await fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`, {
      cache: "no-store"
    });
    if (!res.ok) throw new Error("HTTP " + res.status);
    const data = await res.json();

    onlineEl.textContent =
      data && data.players && typeof data.players.online === "number"
        ? data.players.online
        : 0;

    maxEl.textContent =
      data && data.players && typeof data.players.max === "number"
        ? data.players.max
        : "?";
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

    const count =
      typeof data.presence_count === "number"
        ? data.presence_count
        : Array.isArray(data.members)
        ? data.members.length
        : null;

    onlineEl.textContent = typeof count === "number" ? count : "N/A";
  } catch (e) {
    console.error("Discord stat hiba:", e);
    onlineEl.textContent = "N/A";
  }
}

function initNewsSlider() {
  const track = document.getElementById("news-track");
  const prevBtn = document.getElementById("news-prev");
  const nextBtn = document.getElementById("news-next");
  if (!track || !prevBtn || !nextBtn) return;

  const cards = Array.from(track.querySelectorAll(".news-card"));
  if (!cards.length) return;

  const gap = 16;
  let index = 0;

  function cardWidth() {
    const first = cards[0];
    const rect = first.getBoundingClientRect();
    return rect.width + gap;
  }

  function update() {
    const w = cardWidth();
    const maxIndex = Math.max(0, cards.length - Math.floor(track.parentElement.offsetWidth / w));
    if (index < 0) index = 0;
    if (index > maxIndex) index = maxIndex;
    track.style.transform = `translateX(${-index * w}px)`;
  }

  prevBtn.addEventListener("click", () => {
    index -= 1;
    update();
  });

  nextBtn.addEventListener("click", () => {
    index += 1;
    update();
  });

  window.addEventListener("resize", () => {
    update();
  });

  update();
}

function initNewsModal() {
  const modal = document.getElementById("news-modal");
  if (!modal) return;

  const contentInner = modal.querySelector(".news-modal-content-inner");
  const closeBtn = modal.querySelector(".news-modal-close");
  const backdrop = modal.querySelector(".news-modal-backdrop");
  const readMoreButtons = document.querySelectorAll(".news-readmore");

  function openFromCard(card) {
    if (!card || !contentInner) return;

    const tagEl = card.querySelector(".news-tag");
    const dateEl = card.querySelector(".news-date");
    const titleEl = card.querySelector(".news-headline");
    const shortEl = card.querySelector(".news-text");

    const tag = tagEl ? tagEl.textContent : "";
    const date = dateEl ? dateEl.textContent : "";
    const title = titleEl ? titleEl.textContent : "";
    const textAttr = card.getAttribute("data-full") || "";
    const shortText = shortEl ? shortEl.textContent : "";
    const text = textAttr || shortText;

    contentInner.innerHTML = `
      <div class="news-meta">
        <span class="news-tag">${tag}</span>
        <span class="news-date">${date}</span>
      </div>
      <h3 class="news-modal-title">${title}</h3>
      <p class="news-modal-text">${text}</p>
    `;

    modal.classList.add("open");
  }

  function closeModal() {
    modal.classList.remove("open");
  }

  readMoreButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const card = btn.closest(".news-card");
      openFromCard(card);
    });
  });

  if (closeBtn) {
    closeBtn.addEventListener("click", closeModal);
  }

  if (backdrop) {
    backdrop.addEventListener("click", closeModal);
  }

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal();
    }
  });
}
