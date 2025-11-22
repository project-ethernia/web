const MC_SERVER = "play.ethernia.hu";
const DISCORD_GUILD_ID = "1322224781000577046"; // ETHERNIA guild ID

document.addEventListener("DOMContentLoaded", () => {
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  updateMinecraftStats();
  updateDiscordStats();
  loadNewsFromApi(); // <-- hírek betöltése DB-ből
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
    onlineEl.textContent = data && data.players && typeof data.players.online === "number"
      ? data.players.online
      : 0;
    maxEl.textContent = data && data.players && typeof data.players.max === "number"
      ? data.players.max
      : "?";
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

// ---------- HÍREK BETÖLTÉSE API-BÓL ----------

function loadNewsFromApi() {
  const container = document.getElementById("news-strip-inner");
  if (!container) return;

  fetch("/api/news.php", { cache: "no-store" })
    .then((res) => res.json())
    .then((data) => {
      if (!data.ok || !Array.isArray(data.news) || data.news.length === 0) {
        console.warn("Nincs megjeleníthető hír.");
        return;
      }

      container.innerHTML = "";

      data.news.forEach((item) => {
        const card = document.createElement("article");
        card.className = "news-card";

        // meta (tag + dátum)
        const metaDiv = document.createElement("div");
        metaDiv.className = "news-meta";

        const tagSpan = document.createElement("span");
        tagSpan.className = "news-tag";
        const tagText = item.tag || "Info";
        tagSpan.textContent = tagText;

        const lowered = tagText.toLowerCase();
        if (lowered.indexOf("event") !== -1) {
          tagSpan.classList.add("news-tag-event");
        } else if (lowered.indexOf("info") !== -1) {
          tagSpan.classList.add("news-tag-info");
        }

        const dateSpan = document.createElement("span");
        dateSpan.className = "news-date";
        dateSpan.textContent = item.date_display || "";

        metaDiv.appendChild(tagSpan);
        metaDiv.appendChild(dateSpan);

        // cím
        const h3 = document.createElement("h3");
        h3.className = "news-headline";
        h3.textContent = item.title || "";

        // rövid szöveg
        const p = document.createElement("p");
        p.className = "news-text";
        p.textContent = item.short_text || "";

        // gomb
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "news-readmore";
        btn.textContent = "Részletek";

        // hosszú szöveget data attribútumba tesszük
        if (item.full_text) {
          card.setAttribute("data-full", item.full_text);
        }

        card.appendChild(metaDiv);
        card.appendChild(h3);
        card.appendChild(p);
        card.appendChild(btn);

        container.appendChild(card);
      });

      // ha megvannak a kártyák, indulhat a coverflow + modal
      initNewsCarousel();
      initNewsModal();
    })
    .catch((err) => {
      console.error("Hír API hiba:", err);
    });
}

// ---------- HÍR COVERFLOW KARUSSZEL ----------

function initNewsCarousel() {
  const cards = Array.from(document.querySelectorAll(".news-card"));
  if (!cards.length) return;

  let index = 0;
  const intervalMs = 5000;
  let intervalId;

  function updatePositions() {
    const n = cards.length;

    cards.forEach((card, i) => {
      let offset = i - index;

      // wrap körbe
      if (offset > n / 2) offset -= n;
      if (offset < -n / 2) offset += n;

      card.classList.remove("pos-2", "pos-1", "pos0", "pos1", "pos2", "hidden");

      if (offset === 0) {
        card.classList.add("pos0");
      } else if (offset === -1) {
        card.classList.add("pos-1");
      } else if (offset === -2) {
        card.classList.add("pos-2");
      } else if (offset === 1) {
        card.classList.add("pos1");
      } else if (offset === 2) {
        card.classList.add("pos2");
      } else {
        card.classList.add("hidden");
      }
    });
  }

  function setActive(i) {
    const n = cards.length;
    index = ((i % n) + n) % n;
    updatePositions();
  }

  function startAuto() {
    stopAuto();
    intervalId = setInterval(() => {
      setActive(index + 1);
    }, intervalMs);
  }

  function stopAuto() {
    if (intervalId) clearInterval(intervalId);
  }

  // első állapot
  setActive(index);
  startAuto();

  // card click → legyen középen
  cards.forEach((card, i) => {
    card.addEventListener("click", () => {
      setActive(i);
      startAuto();
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
