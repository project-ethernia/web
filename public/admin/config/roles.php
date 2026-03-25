<?php
// === ETHERNIA ADMIN - SZEREPKÖRÖK ÉS JOGOSULTSÁGOK ===

$ADMIN_ROLES = [
    'superadmin' => [
        'name' => 'Tulajdonos',
        'color' => '#ef4444', // Piros
        'permissions' => ['all'] // Mindent is csinálhat
    ],
    'admin' => [
        'name' => 'Adminisztrátor',
        'color' => '#f59e0b', // Narancs
        'permissions' => ['manage_users', 'manage_tickets', 'manage_news']
    ],
    'moderator' => [
        'name' => 'Moderátor',
        'color' => '#3b82f6', // Kék
        'permissions' => ['manage_tickets', 'manage_news']
    ],
    'support' => [
        'name' => 'Ügyfélszolgálat',
        'color' => '#22c55e', // Zöld
        'permissions' => ['manage_tickets']
    ]
];

// Segédfüggvény: Jogosultság ellenőrzése
function hasPermission($role_key, $permission_name) {
    global $ADMIN_ROLES;
    
    // Ha nem létezik a rang, nincs joga
    if (!isset($ADMIN_ROLES[$role_key])) return false;
    
    $role_perms = $ADMIN_ROLES[$role_key]['permissions'];
    
    // Ha 'all' joga van (Tulaj), mindent szabad
    if (in_array('all', $role_perms)) return true;
    
    // Egyébként meg kell egyeznie a kért joggal
    return in_array($permission_name, $role_perms);
}
?>