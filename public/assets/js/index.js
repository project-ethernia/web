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
    const res = await fetch(
      `https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`,
      { cache: "no-store" }
    );
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
  const prevBtn = document.querySelector(".news-nav-prev");
  const nextBtn = document.querySelector(".news-nav-next");
  if (!track || !prevBtn || !nextBtn) return;

  const cards = Array.from(track.querySelectorAll(".news-card"));
  if (!cards.length) return;

  let index = 0;
  const visibleDesktop = 4;
  const visibleMobile = 1;
  const gapPx = 16;

  function visibleCount() {
    return window.innerWidth <= 900 ? visibleMobile : visibleDesktop;
  }

  function cardWidth() {
    const first = cards[0];
    const rect = first.getBoundingClientRect();
    return rect.width;
  }

  function clampIndex() {
    const maxIndex = Math.max(0, cards.length - visibleCount());
    if (index < 0) index = 0;
    if (index > maxIndex) index = maxIndex;
  }

  function update() {
    clampIndex();
    const w = cardWidth() + gapPx;
    const offset = -(index * w);
    track.style.transform = `translateX(${offset}px)`;
  }

  prevBtn.addEventListener("click", () => {
    index -= visibleCount();
    update();
  });

  nextBtn.addEventListener("click", () => {
    index += visibleCount();
    update();
  });

  window.addEventListener("resize", () => {
    update();
  });

  track.style.transition = "transform 0.3s ease";
  update();
}

function initNewsModal() {
  const modal = document.getElementById("news-modal");
  if (!modal) return;

  const contentInner = modal.querySelector(".news-modal-content-inner");
  const closeBtn = modal.querySelector(".news-modal-close");
  const backdrop = modal.querySelector(".news-modal-backdrop");
  const buttons = document.querySelectorAll(".news-readmore");

  function openFromCard(card) {
    if (!card || !contentInner) return;

    const tagEl = card.querySelector(".news-tag-pill");
    const dateEl = card.querySelector(".news-card-date");
    const titleEl = card.querySelector(".news-card-title");

    const tag = tagEl ? tagEl.textContent : "";
    const date = dateEl ? dateEl.textContent : "";
    const title = titleEl ? titleEl.textContent : "";
    const text = card.getAttribute("data-full") || "";

    contentInner.innerHTML = `
      <div class="news-card-header">
        <span class="news-tag-pill">${tag}</span>
        <span class="news-card-date">${date}</span>
      </div>
      <h3 class="news-card-title">${title}</h3>
      <p class="news-card-short">${text}</p>
    `;

    modal.classList.add("open");
  }

  function closeModal() {
    modal.classList.remove("open");
  }

  buttons.forEach(btn => {
    btn.addEventListener("click", e => {
      e.stopPropagation();
      const card = btn.closest(".news-card");
      openFromCard(card);
    });
  });

  if (closeBtn) closeBtn.addEventListener("click", closeModal);
  if (backdrop) backdrop.addEventListener("click", closeModal);

  document.addEventListener("keydown", e => {
    if (e.key === "Escape") closeModal();
  });
}
