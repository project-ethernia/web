/// <reference lib="dom" />

const MC_SERVER: string = "play.ethernia.hu";
const DISCORD_GUILD_ID: string = "1322224781000577046";

interface MinecraftApiResponse {
  players?: {
    online?: number;
    max?: number;
  };
}

interface DiscordWidgetResponse {
  presence_count?: number;
  members?: unknown[];
}

document.addEventListener("DOMContentLoaded", () => {
  const yearEl = document.getElementById("year") as HTMLElement | null;
  if (yearEl) {
    yearEl.textContent = String(new Date().getFullYear());
  }

  updateMinecraftStats();
  updateDiscordStats();
  initNavbarStyles();
  initNewsModal();
});

async function updateMinecraftStats(): Promise<void> {
  const onlineEl = document.getElementById("mc-online") as HTMLElement | null;
  const maxEl = document.getElementById("mc-max") as HTMLElement | null;
  if (!onlineEl || !maxEl) return;

  try {
    const res = await fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`, {
      cache: "no-store"
    });

    if (!res.ok) throw new Error("HTTP error: " + res.status);
    const data: MinecraftApiResponse = await res.json();

    onlineEl.textContent =
      data && data.players && typeof data.players.online === "number"
        ? String(data.players.online)
        : "0";

    maxEl.textContent =
      data && data.players && typeof data.players.max === "number"
        ? String(data.players.max)
        : "?";
  } catch (e) {
    console.error("MC stat hiba:", e);
    onlineEl.textContent = "N/A";
    maxEl.textContent = "";
  }
}

async function updateDiscordStats(): Promise<void> {
  const onlineEl = document.getElementById("discord-online") as HTMLElement | null;
  if (!onlineEl) return;

  try {
    const url = `https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`;
    const res = await fetch(url, { cache: "no-store" });

    if (!res.ok) throw new Error("HTTP error: " + res.status);

    const data: DiscordWidgetResponse = await res.json();

    const count: number | null =
      typeof data.presence_count === "number"
        ? data.presence_count
        : Array.isArray(data.members)
        ? data.members.length
        : null;

    onlineEl.textContent = typeof count === "number" ? String(count) : "N/A";
  } catch (e) {
    console.error("Discord stat hiba:", e);
    onlineEl.textContent = "N/A";
  }
}

function initNavbarStyles(): void {
  const navbarItems = document.querySelectorAll(".main-nav-links li");
  const registrationButton = document.querySelector(".register-button");
  const loginButton = document.querySelector(".login-button");

  if (registrationButton) {
    registrationButton.remove();
  }
  if (loginButton) {
    loginButton.remove();
  }

  navbarItems.forEach((item) => {
    const element = item as HTMLElement;
    element.style.justifyContent = "center";
    if (element.classList.contains("active")) {
      element.style.border = "2px solid #A020F0";
      element.style.borderRadius = "5px";
    }
  });
}

function initNewsModal(): void {
  const modal = document.getElementById("news-modal") as HTMLElement | null;
  if (!modal) return;

  const modalEl: HTMLElement = modal;
  const contentInner = modal.querySelector(".news-modal-content-inner") as HTMLElement | null;
  const closeBtn = modal.querySelector(".news-modal-close") as HTMLElement | null;
  const backdrop = modal.querySelector(".news-modal-backdrop") as HTMLElement | null;
  const readMoreButtons = document.querySelectorAll<HTMLElement>(".news-readmore");

  function openFromCard(card: HTMLElement | null): void {
    if (!card || !contentInner) return;

    const tagEl = card.querySelector(".news-tag-pill") as HTMLElement | null;
    const dateEl = card.querySelector(".news-card-date") as HTMLElement | null;
    const titleEl = card.querySelector(".news-card-title") as HTMLElement | null;
    const shortEl = card.querySelector(".news-card-short") as HTMLElement | null;

    const tag: string = tagEl ? tagEl.textContent || "" : "";
    const date: string = dateEl ? dateEl.textContent || "" : "";
    const title: string = titleEl ? titleEl.textContent || "" : "";
    const textAttr: string = card.getAttribute("data-full") || "";
    const shortText: string = shortEl ? shortEl.textContent || "" : "";
    const text: string = textAttr || shortText;

    contentInner.innerHTML = `
      <div class="news-meta">
        <span class="news-tag-pill">${tag}</span>
        <span class="news-card-date">${date}</span>
      </div>
      <h3 class="news-modal-title">${title}</h3>
      <p class="news-modal-text">${text}</p>
    `;

    modalEl.classList.add("open");
  }

  function closeModal(): void {
    modalEl.classList.remove("open");
  }

  readMoreButtons.forEach((btn) => {
    btn.addEventListener("click", (e: Event) => {
      e.stopPropagation();
      const card = btn.closest(".news-card") as HTMLElement | null;
      openFromCard(card);
    });
  });

  if (closeBtn) {
    closeBtn.addEventListener("click", closeModal);
  }
  if (backdrop) {
    backdrop.addEventListener("click", closeModal);
  }

  document.addEventListener("keydown", (e: KeyboardEvent) => {
    if (e.key === "Escape") {
      closeModal();
    }
  });
}