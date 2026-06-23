<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/users/add.php'); exit;
    }
    $username  = sanitize($_POST['username']   ?? '');
    $email     = sanitize($_POST['email']      ?? '');
    $first     = sanitize($_POST['first_name'] ?? '');
    $last      = sanitize($_POST['last_name']  ?? '');
    $phone     = sanitize($_POST['phone']      ?? '');
    $groupId   = (int)($_POST['group_id']      ?? 0);
    $isActive  = isset($_POST['is_active']) ? 1 : 0;
    $newPwd    = $_POST['new_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $username === '' || $newPwd === '') {
        setFlash('Dati non validi o password mancante.', 'error');
        header('Location: ' . BASE_URL . 'admin/users/add.php'); exit;
    }

    $dupUser = queryOne("SELECT id FROM users WHERE username = ? OR email = ?", 'ss', $username, $email);
    if ($dupUser) { setFlash('Username o email già in uso.', 'error'); header('Location: ' . BASE_URL . 'admin/users/add.php'); exit; }

    if (strlen($newPwd) < 8) { setFlash('Password troppo corta (min 8 caratteri).', 'error'); header('Location: ' . BASE_URL . 'admin/users/add.php'); exit; }
    
    $hash = password_hash($newPwd, PASSWORD_DEFAULT);
    
    execute("INSERT INTO users (username, email, first_name, last_name, phone, is_active, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)",
        'sssssis', $username, $email, $first, $last, $phone, $isActive, $hash);
        
    $newUserId = getConnection()->insert_id;

    // Solo "admin" o utente normale: da qui non si possono creare le guide.
    $allowedGroups = array_map('intval', array_column(
        queryAll("SELECT id FROM groups WHERE name IN ('admin','registered_user')"), 'id'));
    if ($groupId > 0 && in_array($groupId, $allowedGroups, true)) {
        execute("INSERT INTO users_has_groups (users_id, groups_id) VALUES (?,?)", 'ii', $newUserId, $groupId);
    }

    setFlash('Utente creato con successo.', 'success');
    header('Location: ' . BASE_URL . 'admin/users/list.php'); exit;
}

$tpl = new Template('html/admin/users_form');
setAdminCommonContent($tpl);
$tpl->setContent('form_action',      BASE_URL . 'admin/users/add.php');
$tpl->setContent('u_username',       '');
$tpl->setContent('u_email',          '');
$tpl->setContent('u_first',          '');
$tpl->setContent('u_last',           '');
$tpl->setContent('u_phone',          '');
$tpl->setContent('u_active_checked', 'checked');
$tpl->setContent('password_label',   'Password');
$tpl->setContent('password_required','required');
$tpl->setContent('csrf_token',       generateCsrfToken());
$tpl->setContent('grp_is_user',      '1');

// L'admin può creare solo "admin" o utente normale ("registered_user").
// Le guide NON si creano da qui: hanno una sezione dedicata.
$groupLabels = ['admin' => 'Admin', 'registered_user' => 'Utente'];
$groups = queryAll("SELECT id, name FROM groups WHERE name IN ('admin', 'registered_user') ORDER BY name");
foreach ($groups as $grp) {
    $tpl->setContent('grp_id',       (int)$grp['id']);
    $tpl->setContent('grp_name',     htmlspecialchars($groupLabels[$grp['name']] ?? $grp['name']));
    $tpl->setContent('grp_selected', '');
}
$tpl->close();
