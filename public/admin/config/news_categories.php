<?php
const NEWS_CATEGORIES = [
    'INFO' => [
        'name'   => 'Információ',
        'color'  => '#38bdf8',
        'bg'     => 'rgba(56, 189, 248, 0.15)',
        'border' => 'rgba(56, 189, 248, 0.3)'
    ],
    'UPDATE' => [
        'name'   => 'Frissítés',
        'color'  => '#fbbf24',
        'bg'     => 'rgba(245, 158, 11, 0.15)',
        'border' => 'rgba(245, 158, 11, 0.3)'
    ],
    'EVENT' => [
        'name'   => 'Esemény',
        'color'  => '#c084fc',
        'bg'     => 'rgba(192, 132, 252, 0.15)',
        'border' => 'rgba(192, 132, 252, 0.3)'
    ],
    'MAINTENANCE' => [
        'name'   => 'Karbantartás',
        'color'  => '#fca5a5',
        'bg'     => 'rgba(239, 68, 68, 0.15)',
        'border' => 'rgba(239, 68, 68, 0.3)'
    ]
];

function getCategoryBadge($catKey) {
    $cats = NEWS_CATEGORIES;
    
    if (!array_key_exists($catKey, $cats)) {
        return '<span class="badge" style="color:#9ca3af; background:rgba(156,163,175,0.1); border:1px solid rgba(156,163,175,0.3);">EGYÉB</span>';
    }

    $cat = $cats[$catKey];
    $style = sprintf(
        'color: %s; background: %s; border: 1px solid %s;',
        $cat['color'],
        $cat['bg'],
        $cat['border']
    );

    return sprintf('<span class="badge" style="%s">%s</span>', $style, mb_strtoupper($catKey, 'UTF-8'));
}
?>