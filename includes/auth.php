<?php

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(?string $returnUrl = null): void {
    if (!isLoggedIn()) {
        $redirect = $returnUrl ?? ($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . BASE_URL . 'login.php?return=' . urlencode($redirect));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!userHasGroup((int)$_SESSION['user_id'], 'admin')) {
        header('Location: ' . BASE_URL);
        exit;
    }
}

function userHasGroup(int $userId, string $groupName): bool {
    $row = queryOne(
        "SELECT 1 FROM users_has_groups ug JOIN groups g ON g.id = ug.groups_id WHERE ug.users_id = ? AND g.name = ?",
        'is', $userId, $groupName
    );
    return $row !== null;
}

function loginUser(array $user): void {
    session_regenerate_id(true);

    $groupRow = queryOne(
        "SELECT g.name FROM groups g JOIN users_has_groups ug ON ug.groups_id = g.id WHERE ug.users_id = ? ORDER BY g.id ASC LIMIT 1",
        'i', $user['id']
    );
    $group = $groupRow['name'] ?? 'registered_user';

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $group;
    $_SESSION['user']     = [
        'id'       => $user['id'],
        'username' => $user['username'],
        'name'     => $user['first_name'] ?? '',
        'surname'  => $user['last_name']  ?? '',
        'role'     => $group,
    ];
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setCommonContent($tpl): void {
    $tpl->setContent('site_name',        SITE_NAME);
    $tpl->setContent('base_url',         BASE_URL);
    $tpl->setContent('user_logged',      isLoggedIn() ? '1' : '');
    $tpl->setContent('is_admin',         (isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin') ? '1' : '');
    $tpl->setContent('admin_dashboard_btn', (isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin') ? '<a href="'.BASE_URL.'admin/index.php" class="btn btn-sm btn-primary">Dashboard Admin</a>' : '');

    $displayName = '';
    $initial     = '';
    if (isLoggedIn()) {
        $displayName = htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['username'] ?? '');
        $initial     = strtoupper(substr($displayName, 0, 1));
    }
    $tpl->setContent('username_display', $displayName);
    $tpl->setContent('username_initial', $initial);

    [$flashMsg, $flashType] = getFlash();
    $tpl->setContent('flash_message', htmlspecialchars($flashMsg));
    $tpl->setContent('flash_type',    $flashType ?: 'info');
}

function setAdminCommonContent($tpl): void {
    $tpl->setContent('site_name', SITE_NAME);
    $tpl->setContent('base_url',  BASE_URL);

    $name    = htmlspecialchars($_SESSION['user']['name'] ?? 'Admin');
    $initial = strtoupper(substr($name, 0, 1));
    $tpl->setContent('admin_name',    $name);
    $tpl->setContent('admin_initial', $initial);

    [$flashMsg, $flashType] = getFlash();
    $tpl->setContent('flash_message', htmlspecialchars($flashMsg));
    $tpl->setContent('flash_type',    $flashType ?: 'info');
}
