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

    // 2. Chart.js Inicializálása (Most Játékos Aktivitásra szabva, Kék-Lila témával)
    const canvas = document.getElementById('activityChart') as HTMLCanvasElement | null;
    if (canvas && typeof Chart !== 'undefined') {
        const ctx = canvas.getContext('2d');
        
        // Szép kék-lila átmenet a grafikon kitöltéséhez
        let gradient = ctx?.createLinearGradient(0, 0, 0, 300);
        if (gradient) {
            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)'); // Kék
            gradient.addColorStop(1, 'rgba(168, 85, 247, 0.0)'); // Lila eltűnő
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Egyedi Játékos Belépések',
                    data: chartData,
                    borderColor: '#3b82f6', // Kék vonal
                    backgroundColor: gradient || 'rgba(59, 130, 246, 0.2)',
                    borderWidth: 3,
                    pointBackgroundColor: '#0b0710',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4 // Szép, görbülő vonal
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
                        ticks: { color: '#94a3b8', stepSize: 50 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
    }

    // 3. Élő Adat Szimuláció (Hogy vizuálisan mozgásban legyen a műszerfal)
    // Ezt később cseréld le igazi WebSocket vagy AJAX hívásokra!
    setInterval(() => {
        const cpuEl = document.getElementById('live-cpu');
        const cpuBar = document.getElementById('live-cpu-bar');
        const tpsEl = document.getElementById('live-tps');
        const playersEl = document.getElementById('live-players');

        if (cpuEl && cpuBar) {
            let currentCpu = parseInt(cpuEl.innerText);
            // +/- 5% mozgás
            let newCpu = currentCpu + (Math.floor(Math.random() * 11) - 5);
            if (newCpu < 5) newCpu = 5;
            if (newCpu > 100) newCpu = 100;
            
            cpuEl.innerHTML = `${newCpu}%`;
            cpuBar.style.width = `${newCpu}%`;
            
            // Színváltás a terhelés alapján
            if (newCpu > 80) cpuBar.style.background = 'var(--admin-red)';
            else if (newCpu > 60) cpuBar.style.background = 'var(--admin-warning)';
            else cpuBar.style.background = 'var(--admin-info)';
        }

        if (tpsEl) {
            // TPS 19.5 és 20.0 között ugrál
            let newTps = (19.5 + Math.random() * 0.5).toFixed(1);
            tpsEl.innerHTML = `${newTps} <small>/ 20.0</small>`;
        }

        if (playersEl) {
            // Néha be/kilép valaki (+/- 1 ember)
            if (Math.random() > 0.7) {
                let currentP = parseInt(playersEl.innerText);
                let shift = Math.random() > 0.5 ? 1 : -1;
                playersEl.innerText = (currentP + shift).toString();
            }
        }
    }, 3000); // 3 másodpercenként frissít
});