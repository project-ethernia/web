"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    // Napszaknak megfelelő üdvözlés a fejlécben
    var greetingElement = document.getElementById("greeting-subtitle");
    if (greetingElement) {
        var hour = new Date().getHours();
        var greeting = "Üdvözlünk az Ethernia rendszerében!";
        if (hour >= 5 && hour < 10) {
            greeting = "Jó reggelt! Sikeres napot kívánunk.";
        }
        else if (hour >= 10 && hour < 18) {
            greeting = "Jó napot! Készen állsz a feladatokra?";
        }
        else if (hour >= 18 && hour < 22) {
            greeting = "Jó estét! Reméljük, produktív napod volt.";
        }
        else {
            greeting = "Jó éjszakát! A rendszer éberen figyel.";
        }
        greetingElement.textContent = greeting;
    }
    // Ha később animálni akarnád a kártyákat beúszással, ide írhatod a logikát!
});
