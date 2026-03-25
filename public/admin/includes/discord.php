<?php
// === ETHERNIA ADMIN - DISCORD WEBHOOK INTEGRÁCIÓ ===

// FONTOS: IDE MÁSOLD BE A DISCORD SZERVERED WEBHOOK URL-JÉT!
// (Discord -> Szerver beállítások -> Integrációk -> Webhookok)
define('DISCORD_NEWS_WEBHOOK', 'https://discord.com/api/webhooks/TE_WEBHOOKOD_IDE_JON');

function send_discord_news($title, $short_text, $category, $author, $image_url = null) {
    $webhook_url = DISCORD_NEWS_WEBHOOK;
    
    // Ha még nem állítottad be, vagy üres, kilép (nem okoz hibát az oldalon)
    if (empty($webhook_url) || strpos($webhook_url, 'TE_WEBHOOKOD_IDE_JON') !== false) {
        return false; 
    }

    // Színek kódolása a Discordnak megfelelő Decimális formátumba
    $colors = [
        'INFO' => 3899990,   // Kék (#3b82f6)
        'UPDATE' => 2278750, // Zöld (#22c55e)
        'EVENT' => 16104971  // Narancs (#f59e0b)
    ];
    $color = $colors[$category] ?? 3899990;

    // Kategória ikonok az író mellé
    $cat_names = [
        'INFO' => 'ℹ️ Információ',
        'UPDATE' => '🔄 Frissítés',
        'EVENT' => '🎉 Esemény'
    ];
    $cat_name = $cat_names[$category] ?? 'Hír';

    // A gyönyörű Embed felépítése
    $embed = [
        "title" => $title,
        "description" => $short_text . "\n\n*További részletekért látogass el a weboldalra!*",
        "color" => $color,
        "author" => [
            "name" => $author . " - " . $cat_name,
            "icon_url" => "https://minotar.net/helm/" . urlencode($author) . "/32.png"
        ],
        "footer" => [
            "text" => "Ethernia Hálózat • " . date('Y.m.d H:i')
        ]
    ];

    // Ha adtál meg képet a hírhez, a Discord azt is kirakja nagyban!
    if (!empty($image_url)) {
        $embed["image"] = ["url" => $image_url];
    }

    $data = [
        "username" => "Ethernia Hírmondó",
        "avatar_url" => "https://play.ethernia.hu/assets/img/etherniareborn.png", // A szervered logója
        "embeds" => [$embed]
    ];

    // Adatok kilövése a Discord szerverére (cURL)
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
?>