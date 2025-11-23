<?php
// /admin/_log.php

if (!function_exists('get_client_ip')) {
    function get_client_ip(): string {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ipList = explode(',', $_SERVER[$key]);
                return trim($ipList[0]);
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('get_user_agent')) {
    function get_user_agent(): string {
        return isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 250) : 'unknown';
    }
}

if (!function_exists('log_admin_action')) {
    /**
     * @param PDO    $pdo       már létező PDO kapcsolat
     * @param int    $adminId   admin_users.id vagy 0/NULL
     * @param string $username  admin felhasználónév (vagy "Ismeretlen")
     * @param string $action    rövid leírás pl. "Új hír létrehozása: 'Season 3'"
     * @param array  $context   extra adatok (assoc tömb) – JSON-ként mentjük
     */
    function log_admin_action(PDO $pdo, ?int $adminId, string $username, string $action, array $context = []): void {
        $ip  = get_client_ip();
        $ua  = get_user_agent();
        $ctx = $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, username, action, context, ip_address, user_agent)
            VALUES (:admin_id, :username, :action, :context, :ip, :ua)
        ");
        $stmt->execute([
            ':admin_id' => $adminId,
            ':username' => $username,
            ':action'   => $action,
            ':context'  => $ctx,
            ':ip'       => $ip,
            ':ua'       => $ua,
        ]);
    }
}
