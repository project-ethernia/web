"use strict";
/// <reference lib="dom" />
const MC_SERVER = "play.ethernia.hu";
const DISCORD_GUILD_ID = "1322224781000577046";
document.addEventListener("DOMContentLoaded", () => {
    const yearEl = document.getElementById("year");
    if (yearEl) {
        yearEl.textContent = String(new Date().getFullYear());
    }
    updateMinecraftStats();
    updateDiscordStats();
    initNavbarStyles();
    initNewsModal();
});
async function updateMinecraftStats() {
    const onlineEl = document.getElementById("mc-online");
    const maxEl = document.getElementById("mc-max");
    if (!onlineEl || !maxEl)
        return;
    try {
        const res = await fetch(`https://api.mcsrvstat.us/2/${MC_SERVER}`, {
            cache: "no-store"
        });
        if (!res.ok)
            throw new Error("HTTP error: " + res.status);
        const data = await res.json();
        onlineEl.textContent =
            data && data.players && typeof data.players.online === "number"
                ? String(data.players.online)
                : "0";
        maxEl.textContent =
            data && data.players && typeof data.players.max === "number"
                ? String(data.players.max)
                : "?";
    }
    catch (e) {
        console.error("MC stat hiba:", e);
        onlineEl.textContent = "N/A";
        maxEl.textContent = "";
    }
}
async function updateDiscordStats() {
    const onlineEl = document.getElementById("discord-online");
    if (!onlineEl)
        return;
    try {
        const url = `https://discord.com/api/guilds/${DISCORD_GUILD_ID}/widget.json`;
        const res = await fetch(url, { cache: "no-store" });
        if (!res.ok)
            throw new Error("HTTP error: " + res.status);
        const data = await res.json();
        const count = typeof data.presence_count === "number"
            ? data.presence_count
            : Array.isArray(data.members)
                ? data.members.length
                : null;
        onlineEl.textContent = typeof count === "number" ? String(count) : "N/A";
    }
    catch (e) {
        console.error("Discord stat hiba:", e);
        onlineEl.textContent = "N/A";
    }
}
function initNavbarStyles() {
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
        const element = item;
        element.style.justifyContent = "center";
        if (element.classList.contains("active")) {
            element.style.border = "2px solid #A020F0";
            element.style.borderRadius = "5px";
        }
    });
}
function initNewsModal() {
    const modal = document.getElementById("news-modal");
    if (!modal)
        return;
    const modalEl = modal;
    const contentInner = modal.querySelector(".news-modal-content-inner");
    const closeBtn = modal.querySelector(".news-modal-close");
    const backdrop = modal.querySelector(".news-modal-backdrop");
    const readMoreButtons = document.querySelectorAll(".news-readmore");
    function openFromCard(card) {
        if (!card || !contentInner)
            return;
        const tagEl = card.querySelector(".news-tag-pill");
        const dateEl = card.querySelector(".news-card-date");
        const titleEl = card.querySelector(".news-card-title");
        const shortEl = card.querySelector(".news-card-short");
        const tag = tagEl ? tagEl.textContent || "" : "";
        const date = dateEl ? dateEl.textContent || "" : "";
        const title = titleEl ? titleEl.textContent || "" : "";
        const textAttr = card.getAttribute("data-full") || "";
        const shortText = shortEl ? shortEl.textContent || "" : "";
        const text = textAttr || shortText;
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
    function closeModal() {
        modalEl.classList.remove("open");
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
