"use strict";
/// <reference lib="dom" />
document.addEventListener("DOMContentLoaded", function () {
    // 1. Avatar betöltése név beírása után
    var userInp = document.getElementById('username-input');
    var avatarImg = document.getElementById('dynamic-avatar');
    if (userInp && avatarImg) {
        userInp.addEventListener('blur', function () {
            var username = userInp.value.trim();
            if (username.length > 2) {
                avatarImg.src = "https://minotar.net/helm/".concat(username, "/80.png");
            }
        });
    }
    // 2. Visszaszámláló óra (Timer) ha le van tiltva a fiók
    var timerEl = document.getElementById('countdown');
    if (timerEl) {
        var endData = timerEl.dataset.end;
        if (endData) {
            var endTime_1 = parseInt(endData, 10) * 1000;
            var timerInterval_1 = setInterval(function () {
                var now = new Date().getTime();
                var distance = endTime_1 - now;
                if (distance <= 0) {
                    clearInterval(timerInterval_1);
                    // Ha lejárt, frissítsük az oldalt, hogy visszajöjjön a login form
                    window.location.href = '/admin/login.php';
                }
                else {
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    timerEl.innerHTML = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
                }
            }, 1000);
        }
    }
});
