<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) { header('Location: ' . BASE_URL . 'admin/users/list.php'); exit; }

$user = queryOne("SELECT * FROM users WHERE id = ?", 'i', $userId);
if (!$user) { setFlash('Utente non trovato.', 'error'); header('Location: ' . BASE_URL . 'admin/users/list.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/users/edit.php?id=' . $userId); exit;
    }
    $username  = sanitize($_POST['username']   ?? '');
    $email     = sanitize($_POST['email']      ?? '');
    $first     = sanitize($_POST['first_name'] ?? '');
    $last      = sanitize($_POST['last_name']  ?? '');
    $phone     = sanitize($_POST['phone']      ?? '');
    $groupId   = (int)($_POST['group_id']      ?? 0);
    $isActive  = isset($_POST['is_active']) ? 1 : 0;
    $newPwd    = $_POST['new_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $username === '') {
        setFlash('Dati non validi.', 'error');
        header('Location: ' . BASE_URL . 'admin/users/edit.php?id=' . $userId); exit;
    }

    $dupUser = queryOne("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?", 'ssi', $username, $email, $userId);
    if ($dupUser) { setFlash('Username o email già in uso.', 'error'); header('Location: ' . BASE_URL . 'admin/users/edit.php?id=' . $userId); exit; }

    if ($newPwd !== '') {
        if (strlen($newPwd) < 8) { setFlash('Password troppo corta.', 'error'); header('Location: ' . BASE_URL . 'admin/users/edit.php?id=' . $userId); exit; }
        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        execute("UPDATE users SET username=?, email=?, first_name=?, last_name=?, phone=?, is_active=?, password_hash=? WHERE id=?",
            'sssssisi', $username, $email, $first, $last, $phone, $isActive, $hash, $userId);
    } else {
        execute("UPDATE users SET username=?, email=?, first_name=?, last_name=?, phone=?, is_active=? WHERE id=?",
            'sssssii', $username, $email, $first, $last, $phone, $isActive, $userId);
    }

    if ($groupId > 0) {
        execute("DELETE FROM users_has_groups WHERE users_id = ?", 'i', $userId);
        execute("INSERT INTO users_has_groups (users_id, groups_id) VALUES (?,?)", 'ii', $userId, $groupId);
    }

    setFlash('Utente aggiornato.', 'success');
    header('Location: ' . BASE_URL . 'admin/users/list.php'); exit;
}

$userGroup = queryOne("SELECT groups_id FROM users_has_groups WHERE users_id = ?", 'i', $userId);
$currentGroupId = (int)($userGroup['groups_id'] ?? 0);

$tpl = new Template('html/admin/users_form');
setAdminCommonContent($tpl);
$tpl->setContent('u_id',             $userId);
$tpl->setContent('u_username',       htmlspecialchars($user['username']));
$tpl->setContent('u_email',          htmlspecialchars($user['email']));
$tpl->setContent('u_first',          htmlspecialchars($user['first_name'] ?? ''));
$tpl->setContent('u_last',           htmlspecialchars($user['last_name']  ?? ''));
$tpl->setContent('u_phone',          htmlspecialchars($user['phone']      ?? ''));
$tpl->setContent('u_active_checked', $user['is_active'] ? 'checked' : '');
$tpl->setContent('csrf_token',       generateCsrfToken());

$groups = queryAll("SELECT id, name FROM groups ORDER BY name");
foreach ($groups as $grp) {
    $tpl->setContent('grp_id',       (int)$grp['id']);
    $tpl->setContent('grp_name',     htmlspecialchars($grp['name']));
    $tpl->setContent('grp_selected', (int)$grp['id'] === $currentGroupId ? 'selected' : '');
}
$tpl->close();
