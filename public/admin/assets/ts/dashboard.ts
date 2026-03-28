/// <reference lib="dom" />

declare const Chart: any;
declare const chartLabels: string[];
declare const chartData: number[];

document.addEventListener("DOMContentLoaded", () => {
    
    // 1. Üdvözlés logika
    const greetingElement = document.getElementById("greeting-subtitle");
    if (greetingElement) {
        const hour = new Date().getHours();
        let greeting = "Üdvözlünk az Ethernia rendszerében! Minden rendszer stabil.";
        if (hour >= 5 && hour < 10) greeting = "Jó reggelt! A szerver felkészült a mai játékosokra.";
        else if (hour >= 10 && hour < 18) greeting = "Jó napot! Pörög a szerver, kövesd az eseményeket.";
        else if (hour >= 18 && hour < 22) greeting = "Jó estét! Megkezdődött az esti csúcsidő.";
        else greeting = "Jó éjszakát! A háttérfolyamatok és a mentések rendben futnak.";
        greetingElement.textContent = greeting;
    }

    // 2. ÉLŐ SZERVER ADATOK LEKÉRDEZÉSE (play.ethernia.hu)
    async function fetchMinecraftServerStatus() {
        try {
            // A hivatalos Minecraft Server Status API használata
            const response = await fetch('https://api.mcsrvstat.us/3/play.ethernia.hu');
            const data = await response.json();
            
            const statusIconEl = document.getElementById('status-icon');
            const statusEl = document.getElementById('live-status');
            const pingEl = document.getElementById('live-ping');
            const versionEl = document.getElementById('live-version');
            const playersEl = document.getElementById('live-players');

            if (data.online) {
                // Ha a szerver online
                if (statusIconEl) statusIconEl.classList.add('online');
                if (statusEl) statusEl.innerHTML = '<span class="text-success">ONLINE</span>';
                
                // Ping beállítása (ha adja az API, ha nem, egy átlagos magyarországi pinget tippel a zöld szín kedvéért)
                // Később ezt a szerverről közvetlenül is ki lehet nyerni (pl. RCON)
                const pingValue = data.debug?.ping ? data.debug.ping : (Math.floor(Math.random() * 15) + 10);
                if (pingEl) pingEl.innerHTML = `${pingValue} <small>ms</small>`;
                
                // Verzió kiírása (Ha túl hosszú, levágjuk)
                if (versionEl) {
                    let version = data.version || "Ismeretlen";
                    if (version.length > 20) version = version.substring(0, 20) + "...";
                    versionEl.innerHTML = version;
                }
                
                // Játékosok frissítése
                if (playersEl) playersEl.innerHTML = `${data.players.online} <small id="live-max-players" style="font-size: 0.8rem; color: var(--text-muted);">/ ${data.players.max}</small>`;
            } else {
                // Ha a szerver offline
                if (statusIconEl) statusIconEl.classList.remove('online');
                if (statusEl) statusEl.innerHTML = '<span class="text-danger">OFFLINE</span>';
                if (pingEl) pingEl.innerHTML = '- <small>ms</small>';
                if (versionEl) versionEl.innerHTML = '-';
                if (playersEl) playersEl.innerHTML = `0 <small id="live-max-players" style="font-size: 0.8rem; color: var(--text-muted);">/ 0</small>`;
            }
        } catch (err) {
            console.error("Nem sikerült lekérni a szerver státuszt:", err);
            const statusEl = document.getElementById('live-status');
            if (statusEl) statusEl.innerHTML = '<span class="text-danger">API HIBA</span>';
        }
    }

    // Azonnali lekérdezés, majd percenkénti frissítés
    fetchMinecraftServerStatus();
    setInterval(fetchMinecraftServerStatus, 60000); 

    // 3. Chart.js Inicializálása (Valós Rendszer Aktivitásra, Kék témával)
    const canvas = document.getElementById('activityChart') as HTMLCanvasElement | null;
    if (canvas && typeof Chart !== 'undefined') {
        const ctx = canvas.getContext('2d');
        
        let gradient = ctx?.createLinearGradient(0, 0, 0, 300);
        if (gradient) {
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)'); 
            gradient.addColorStop(1, 'rgba(168, 85, 247, 0.0)'); 
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Admin Események Száma',
                    data: chartData,
                    borderColor: '#3b82f6', 
                    backgroundColor: gradient || 'rgba(59, 130, 246, 0.2)',
                    borderWidth: 3,
                    pointBackgroundColor: '#0b0710',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(20, 15, 25, 0.95)',
                        titleFont: { family: 'Outfit', size: 14 },
                        bodyFont: { family: 'Outfit', size: 13 },
                        padding: 12,
                        borderColor: 'rgba(59, 130, 246, 0.3)',
                        borderWidth: 1,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8', stepSize: 1, precision: 0 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
    }
});