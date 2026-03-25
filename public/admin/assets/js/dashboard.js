"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    // 1. Üdvözlés logika
    var greetingElement = document.getElementById("greeting-subtitle");
    if (greetingElement) {
        var hour = new Date().getHours();
        var greeting = "Üdvözlünk az Ethernia rendszerében!";
        if (hour >= 5 && hour < 10)
            greeting = "Jó reggelt! Sikeres napot kívánunk.";
        else if (hour >= 10 && hour < 18)
            greeting = "Jó napot! Készen állsz a feladatokra?";
        else if (hour >= 18 && hour < 22)
            greeting = "Jó estét! Reméljük, produktív napod volt.";
        else
            greeting = "Jó éjszakát! A rendszer éberen figyel.";
        greetingElement.textContent = greeting;
    }
    // 2. Chart.js Inicializálása
    var canvas = document.getElementById('activityChart');
    if (canvas && typeof Chart !== 'undefined') {
        var ctx = canvas.getContext('2d');
        // Csinálunk egy menő piros-fekete színátmenetet a grafikon alá
        var gradient = ctx === null || ctx === void 0 ? void 0 : ctx.createLinearGradient(0, 0, 0, 300);
        if (gradient) {
            gradient.addColorStop(0, 'rgba(239, 68, 68, 0.5)'); // Var(--admin-red)
            gradient.addColorStop(1, 'rgba(239, 68, 68, 0.0)');
        }
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                        label: 'Rendszer Események (Logok)',
                        data: chartData,
                        borderColor: '#ef4444',
                        backgroundColor: gradient || 'rgba(239, 68, 68, 0.2)',
                        borderWidth: 3,
                        pointBackgroundColor: '#0b0710',
                        pointBorderColor: '#ef4444',
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
                        backgroundColor: 'rgba(15, 10, 20, 0.9)',
                        titleFont: { family: 'Outfit' },
                        bodyFont: { family: 'Outfit' },
                        padding: 12,
                        borderColor: 'rgba(239, 68, 68, 0.3)',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8', stepSize: 1 }
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
