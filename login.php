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

$returnUrl    = sanitize($_GET['return'] ?? '');
$flashMessage = '';
$flashType    = '';
$loginUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token di sicurezza non valido.', 'error');
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginUsername = $username;

    if ($username === '' || $password === '') {
        setFlash('Inserisci username e password.', 'error');
    } else {
        $user = queryOne(
            "SELECT u.id, u.username, u.password_hash, u.first_name, u.last_name, u.is_active,
                    g.name AS group_name
             FROM users u
             JOIN users_has_groups ug ON ug.users_id = u.id
             JOIN groups g ON g.id = ug.groups_id
             WHERE u.username = ?
             LIMIT 1",
            's', $username
        );

        if ($user && $user['is_active'] && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            if ($_SESSION['role'] === 'admin') {
                $redirect = BASE_URL . 'admin/index.php';
            } else {
                $redirect = ($returnUrl !== '' && strpos($returnUrl, BASE_URL) === 0) ? $returnUrl : BASE_URL;
            }
            header('Location: ' . $redirect);
            exit;
        } else {
            setFlash('Credenziali non valide o account disattivato.', 'error');
        }
    }
}

[$flashMessage, $flashType] = getFlash();

$tpl = new Template('html/login');
$tpl->setContent('site_name',     SITE_NAME);
$tpl->setContent('base_url',      BASE_URL);
$tpl->setContent('flash_message', $flashMessage);
$tpl->setContent('flash_type',    $flashType ?: 'info');
$tpl->setContent('csrf_token',    generateCsrfToken());
$tpl->setContent('return_url',    htmlspecialchars($returnUrl));
$tpl->setContent('login_username',htmlspecialchars($loginUsername));
$tpl->close();
