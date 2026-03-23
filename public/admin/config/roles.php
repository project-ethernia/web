<?php
const ADMIN_ROLES = [
    'owner' => [
        'name'   => 'Tulajdonos',
        'power'  => 100,
        'color'  => '#fbbf24',
        'bg'     => 'rgba(245, 158, 11, 0.15)',
        'border' => 'rgba(245, 158, 11, 0.3)'
    ],
    'admin' => [
        'name'   => 'Adminisztrátor',
        'power'  => 50,
        'color'  => '#fca5a5',
        'bg'     => 'rgba(239, 68, 68, 0.15)',
        'border' => 'rgba(239, 68, 68, 0.3)'
    ],
    'mod' => [
        'name'   => 'Moderátor',
        'power'  => 10,
        'color'  => '#86efac',
        'bg'     => 'rgba(34, 197, 94, 0.15)',
        'border' => 'rgba(34, 197, 94, 0.3)'
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