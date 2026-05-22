<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/template2.inc.php';

requireLogin();
$userId = (int)$_SESSION['user_id'];

$user = queryOne("SELECT * FROM users WHERE id = ?", 'i', $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token di sicurezza non valido.', 'error');
        header('Location: ' . BASE_URL . 'profile.php');
        exit;
    }

    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName  = sanitize($_POST['last_name']  ?? '');
    $email     = sanitize($_POST['email']      ?? '');
    $phone     = sanitize($_POST['phone']      ?? '');
    $newPwd    = $_POST['new_password']  ?? '';
    $newPwd2   = $_POST['new_password2'] ?? '';
    $curPwd    = $_POST['current_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('Email non valida.', 'error');
        header('Location: ' . BASE_URL . 'profile.php');
        exit;
    }

    $dupEmail = queryOne("SELECT id FROM users WHERE email = ? AND id != ?", 'si', $email, $userId);
    if ($dupEmail) {
        setFlash('Email già in uso.', 'error');
        header('Location: ' . BASE_URL . 'profile.php');
        exit;
    }

    if ($newPwd !== '') {
        if (!password_verify($curPwd, $user['password_hash'])) {
            setFlash('Password attuale non corretta.', 'error');
            header('Location: ' . BASE_URL . 'profile.php');
            exit;
        }
        if (strlen($newPwd) < 8 || $newPwd !== $newPwd2) {
            setFlash('La nuova password deve essere di almeno 8 caratteri e coincidere.', 'error');
            header('Location: ' . BASE_URL . 'profile.php');
            exit;
        }
        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        execute("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, password_hash=? WHERE id=?",
            'sssssi', $firstName, $lastName, $email, $phone, $hash, $userId);
    } else {
        execute("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=?",
            'ssssi', $firstName, $lastName, $email, $phone, $userId);
    }

    $_SESSION['username'] = $user['username'];
    setFlash('Profilo aggiornato.', 'success');
    header('Location: ' . BASE_URL . 'profile.php');
    exit;
}

[$flashMsg, $flashType] = getFlash();

$tpl = new Template('html/profile');
setCommonContent($tpl);
$tpl->setContent('prof_first',    htmlspecialchars($user['first_name'] ?? ''));
$tpl->setContent('prof_last',     htmlspecialchars($user['last_name']  ?? ''));
$tpl->setContent('prof_username', htmlspecialchars($user['username']));
$tpl->setContent('prof_email',    htmlspecialchars($user['email']));
$tpl->setContent('prof_phone',    htmlspecialchars($user['phone'] ?? ''));
$tpl->setContent('csrf_token',    generateCsrfToken());
$tpl->close();
