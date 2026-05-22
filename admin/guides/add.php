<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/guides/add.php');
        exit;
    }
    $first  = sanitize($_POST['first_name']    ?? '');
    $last   = sanitize($_POST['last_name']     ?? '');
    $email  = sanitize($_POST['email']         ?? '');
    $phone  = sanitize($_POST['phone']         ?? '');
    $spec   = sanitize($_POST['specialization']?? '');
    $langs  = sanitize($_POST['languages']     ?? '');
    $bio    = sanitize($_POST['bio']           ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($first === '' || $last === '') {
        setFlash('Nome e cognome obbligatori.', 'error');
        header('Location: ' . BASE_URL . 'admin/guides/add.php');
        exit;
    }

    $photo = null;
    if (!empty($_FILES['profile_photo']['name'])) {
        $photo = uploadImage($_FILES['profile_photo'], 'guides', 'guide_');
    }

    execute(
        "INSERT INTO guides (first_name, last_name, email, phone, specialization, languages, bio, profile_photo, is_active, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())",
        'ssssssssi', $first, $last, $email, $phone, $spec, $langs, $bio, $photo, $active
    );
    setFlash('Guida aggiunta.', 'success');
    header('Location: ' . BASE_URL . 'admin/guides/list.php');
    exit;
}

$tpl = new Template('html/admin/guides_form');
setAdminCommonContent($tpl);
$tpl->setContent('gf_page_title',    'Aggiungi guida');
$tpl->setContent('gf_action',        BASE_URL . 'admin/guides/add.php');
$tpl->setContent('g_id',             0);
$tpl->setContent('g_first',          '');
$tpl->setContent('g_last',           '');
$tpl->setContent('g_email',          '');
$tpl->setContent('g_phone',          '');
$tpl->setContent('g_spec',           '');
$tpl->setContent('g_langs',          '');
$tpl->setContent('g_bio',            '');
$tpl->setContent('g_photo',          '');
$tpl->setContent('g_active_checked', 'checked');
$tpl->setContent('csrf_token',       generateCsrfToken());
$tpl->close();
