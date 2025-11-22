const MC_SERVER = "play.ethernia.hu";
const DISCORD_GUILD_ID = "1322224781000577046"; // ETHERNIA guild ID

document.addEventListener("DOMContentLoaded", () => {
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  updateMinecraftStats();
  updateDiscordStats();
  initNewsCarousel();
  initNewsModal();
});

// ---------- Minecraft stat ----------

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
    console.error("MC stat hiba:", e);
    onlineEl.textContent = "N/A";
    maxEl.textContent = "";
  }
}

// ---------- Discord stat ----------

async function updateDiscordStats() {
  const onlineEl = document.getElementById("discord-online");
  if (!onlineEl) return;

  try {
    const url = `https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`;
    const res = await fetch(url, { cache: "no-store" });
    if (!res.ok) throw new Error("HTTP error: " + res.status);

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

// ---------- HÍR KARUSSZEL ----------

function initNewsCarousel() {
  const container = document.querySelector(".news-strip");
  const cards = Array.from(document.querySelectorAll(".news-card"));
  if (!container || !cards.length) return;

  let index = 0;
  const intervalMs = 5000;

  function setActive(i) {
    index = (i + cards.length) % cards.length;

    cards.forEach((card, idx) => {
      card.classList.toggle("active", idx === index);
    });

    const activeCard = cards[index];
    const containerRect = container.getBoundingClientRect();
    const cardRect = activeCard.getBoundingClientRect();

    const targetLeft = container.scrollLeft +
      (cardRect.left - (containerRect.left + (containerRect.width - cardRect.width) / 2));

    container.scrollTo({
      left: targetLeft,
      behavior: "smooth",
    });
  }

  // első aktív
  setActive(index);

  // automatikus léptetés
  setInterval(() => {
    setActive(index + 1);
  }, intervalMs);

  // kattintásra is lehessen középre húzni
  cards.forEach((card, idx) => {
    card.addEventListener("click", (e) => {
      // ha a gombra kattintott, a modal majd kezeli, de a card is középre kerül
      if (!(e.target instanceof HTMLButtonElement)) {
        setActive(idx);
      }
    });
  });
}

// ---------- HÍR MODAL ----------

function initNewsModal() {
  const modal = document.getElementById("news-modal");
  if (!modal) return;

  const contentInner = modal.querySelector(".news-modal-content-inner");
  const closeBtn = modal.querySelector(".news-modal-close");
  const backdrop = modal.querySelector(".news-modal-backdrop");
  const readMoreButtons = document.querySelectorAll(".news-readmore");

  function openFromCard(card) {
    if (!card || !contentInner) return;

    const tag = card.querySelector(".news-tag")?.textContent || "";
    const date = card.querySelector(".news-date")?.textContent || "";
    const title = card.querySelector(".news-headline")?.textContent || "";
    const textAttr = card.getAttribute("data-full") || "";
    const shortText = card.querySelector(".news-text")?.textContent || "";
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
      e.stopPropagation(); // ne triggelje a kártya clickjét
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
