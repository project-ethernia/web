<?php
const ADMIN_ROLES = [
    'owner' => [
        'name'   => 'OWNER',
        'power'  => 100,
        'color'  => '#a524fb',
        'bg'     => 'rgba(147, 0, 167, 0.84)',
        'border' => 'rgba(108, 0, 117, 0.89)'
    ],
    'admin' => [
        'name'   => 'ADMIN',
        'power'  => 50,
        'color'  => '#df0b0bd3',
        'bg'     => 'rgba(194, 8, 8, 0.84)',
        'border' => 'rgba(126, 3, 3, 0.89)'
    ],
    'mod' => [
        'name'   => 'MOD',
        'power'  => 10,
        'color'  => '#25f0ff',
        'bg'     => 'rgba(6, 205, 219, 0.84)',
        'border' => 'rgba(11, 111, 141, 0.89)'
    ]
];

function getRoleBadge($roleKey) {
    $roles = ADMIN_ROLES;
    
    if (!array_key_exists($roleKey, $roles)) {
        return '<span class="badge" style="color:#9ca3af; background:rgba(156,163,175,0.1); border:1px solid rgba(156,163,175,0.3);">ISMERETLEN</span>';
    }

    $role = $roles[$roleKey];
    $style = sprintf(
        'color: %s; background: %s; border: 1px solid %s;',
        $role['color'],
        $role['bg'],
        $role['border']
    );

    return sprintf('<span class="badge" style="%s">%s</span>', $style, mb_strtoupper($role['name'], 'UTF-8'));
}

function getRoleName($roleKey) {
    return ADMIN_ROLES[$roleKey]['name'] ?? 'Ismeretlen';
}
?>