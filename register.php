<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/template2.inc.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

$fields = ['reg_first' => '', 'reg_last' => '', 'reg_username' => '', 'reg_email' => '', 'reg_phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token di sicurezza non valido.', 'error');
        header('Location: ' . BASE_URL . 'register.php');
        exit;
    }

    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName  = sanitize($_POST['last_name']  ?? '');
    $username  = sanitize($_POST['username']   ?? '');
    $email     = sanitize($_POST['email']      ?? '');
    $phone     = sanitize($_POST['phone']      ?? '');
    $password  = $_POST['password']  ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $fields = ['reg_first' => $firstName, 'reg_last' => $lastName, 'reg_username' => $username, 'reg_email' => $email, 'reg_phone' => $phone];

    $errors = [];
    if ($firstName === '') $errors[] = 'Il nome è obbligatorio.';
    if ($lastName  === '') $errors[] = 'Il cognome è obbligatorio.';
    if ($username  === '' || strlen($username) < 3) $errors[] = 'Username minimo 3 caratteri.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email non valida.';
    if (strlen($password) < 8) $errors[] = 'Password minimo 8 caratteri.';
    if ($password !== $password_confirm) $errors[] = 'Le password non coincidono.';

    if (empty($errors)) {
        $existing = queryOne("SELECT id FROM users WHERE username = ? OR email = ?", 'ss', $username, $email);
        if ($existing) {
            $errors[] = 'Username o email già registrati.';
        }
    }

    if (!empty($errors)) {
        setFlash(implode(' ', $errors), 'error');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        execute(
            "INSERT INTO users (username, email, password_hash, first_name, last_name, phone, is_active, created_at) VALUES (?,?,?,?,?,?,1,NOW())",
            'ssssss', $username, $email, $hash, $firstName, $lastName, $phone
        );
        $newUser = queryOne("SELECT id FROM users WHERE username = ?", 's', $username);
        $registeredGroup = queryOne("SELECT id FROM groups WHERE name = 'registered_user'");
        if ($newUser && $registeredGroup) {
            execute("INSERT INTO users_has_groups (users_id, groups_id) VALUES (?,?)", 'ii', $newUser['id'], $registeredGroup['id']);
        }

        $user = queryOne(
            "SELECT u.id, u.username, u.first_name, u.last_name, u.is_active, g.name AS group_name
             FROM users u JOIN users_has_groups ug ON ug.users_id = u.id JOIN groups g ON g.id = ug.groups_id
             WHERE u.id = ? LIMIT 1",
            'i', $newUser['id']
        );
        loginUser($user);
        setFlash('Benvenuto, ' . htmlspecialchars($firstName) . '! Registrazione completata.', 'success');
        header('Location: ' . BASE_URL);
        exit;
    }
}

[$flashMessage, $flashType] = getFlash();

$tpl = new Template('html/register');
$tpl->setContent('site_name',    SITE_NAME);
$tpl->setContent('base_url',     BASE_URL);
$tpl->setContent('flash_message',$flashMessage);
$tpl->setContent('flash_type',   $flashType ?: 'info');
$tpl->setContent('csrf_token',   generateCsrfToken());
foreach ($fields as $k => $v) {
    $tpl->setContent($k, htmlspecialchars($v));
}
$tpl->close();
