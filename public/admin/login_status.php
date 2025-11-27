<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_pending_login_request_id']) || empty($_SESSION['admin_pending_admin_id'])) {
    echo json_encode(['status' => 'none']);
    exit;
}

$requestId = (int)$_SESSION['admin_pending_login_request_id'];
$adminId = (int)$_SESSION['admin_pending_admin_id'];

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/log.php';

try {
    $stmt = $pdo->prepare('SELECT * FROM admin_login_requests WHERE id = :id AND admin_id = :aid LIMIT 1');
    $stmt->execute([
        ':id' => $requestId,
        ':aid' => $adminId
    ]);
    $row = $stmt->fetch();

    if (!$row) {
        unset($_SESSION['admin_pending_login_request_id'], $_SESSION['admin_pending_admin_id'], $_SESSION['admin_pending_username']);
        echo json_encode(['status' => 'none']);
        exit;
    }

    $status = $row['status'];

    if ($status === 'pending') {
        $created = strtotime($row['created_at']);
        if ($created !== false && (time() - $created) > 300) {
            $upd = $pdo->prepare('UPDATE admin_login_requests SET status = :st WHERE id = :id');
            $upd->execute([
                ':st' => 'expired',
                ':id' => $requestId
            ]);
            unset($_SESSION['admin_pending_login_request_id'], $_SESSION['admin_pending_admin_id'], $_SESSION['admin_pending_username']);
            echo json_encode([
                'status' => 'expired',
                'message' => 'A bejelentkezési kérés lejárt.'
            ]);
            exit;
        }

        echo json_encode(['status' => 'pending']);
        exit;
    }

    if ($status === 'approved') {
        $stmtUser = $pdo->prepare('SELECT * FROM admin_users WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmtUser->execute([':id' => $adminId]);
        $user = $stmtUser->fetch();

        if (!$user) {
            unset($_SESSION['admin_pending_login_request_id'], $_SESSION['admin_pending_admin_id'], $_SESSION['admin_pending_username']);
            echo json_encode([
                'status' => 'expired',
                'message' => 'A felhasználó már nem aktív.'
            ]);
            exit;
        }

        $_SESSION['admin_id'] = (int)$user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['is_admin'] = true;

        unset($_SESSION['admin_pending_login_request_id'], $_SESSION['admin_pending_admin_id'], $_SESSION['admin_pending_username']);

        $updLogin = $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id');
        $updLogin->execute([':id' => $user['id']]);

        try {
            log_admin_action(
                $pdo,
                (int)$user['id'],
                (string)$user['username'],
                'Sikeres admin bejelentkezés (Discord jóváhagyással)',
                []
            );
        } catch (Throwable $e2) {
        }

        echo json_encode([
            'status' => 'approved',
            'redirect' => '/admin/index.php'
        ]);
        exit;
    }

    if ($status === 'rejected') {
        unset($_SESSION['admin_pending_login_request_id'], $_SESSION['admin_pending_admin_id'], $_SESSION['admin_pending_username']);
        echo json_encode([
            'status' => 'rejected',
            'message' => 'A bejelentkezési kérelmet elutasítottad Discordon.'
        ]);
        exit;
    }

    if ($status === 'expired') {
        unset($_SESSION['admin_pending_login_request_id'], $_SESSION['admin_pending_admin_id'], $_SESSION['admin_pending_username']);
        echo json_encode([
            'status' => 'expired',
            'message' => 'A bejelentkezési kérés lejárt.'
        ]);
        exit;
    }

    echo json_encode(['status' => 'none']);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Szerver hiba.'
    ]);
}
