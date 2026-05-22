<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$guideId = (int)($_GET['id'] ?? 0);
if ($guideId <= 0) { header('Location: ' . BASE_URL . 'admin/guides/list.php'); exit; }

$guide = queryOne("SELECT * FROM guides WHERE id = ?", 'i', $guideId);
if (!$guide) { setFlash('Guida non trovata.', 'error'); header('Location: ' . BASE_URL . 'admin/guides/list.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/guides/edit.php?id=' . $guideId);
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
        header('Location: ' . BASE_URL . 'admin/guides/edit.php?id=' . $guideId);
        exit;
    }

    $photo = $guide['profile_photo'];
    if (!empty($_FILES['profile_photo']['name'])) {
        $newPhoto = uploadImage($_FILES['profile_photo'], 'guides', 'guide_');
        if ($newPhoto) {
            if ($photo && file_exists(__DIR__ . '/../../' . $photo)) unlink(__DIR__ . '/../../' . $photo);
            $photo = $newPhoto;
        }
    }

    execute(
        "UPDATE guides SET first_name=?, last_name=?, email=?, phone=?, specialization=?, languages=?, bio=?, profile_photo=?, is_active=? WHERE id=?",
        'ssssssssii', $first, $last, $email, $phone, $spec, $langs, $bio, $photo, $active, $guideId
    );
    setFlash('Guida aggiornata.', 'success');
    header('Location: ' . BASE_URL . 'admin/guides/list.php');
    exit;
}

$tpl = new Template('html/admin/guides_form');
setAdminCommonContent($tpl);
$tpl->setContent('gf_page_title',    'Modifica guida');
$tpl->setContent('gf_action',        BASE_URL . 'admin/guides/edit.php?id=' . $guideId);
$tpl->setContent('g_id',             $guideId);
$tpl->setContent('g_first',          htmlspecialchars($guide['first_name']));
$tpl->setContent('g_last',           htmlspecialchars($guide['last_name']));
$tpl->setContent('g_email',          htmlspecialchars($guide['email'] ?? ''));
$tpl->setContent('g_phone',          htmlspecialchars($guide['phone'] ?? ''));
$tpl->setContent('g_spec',           htmlspecialchars($guide['specialization'] ?? ''));
$tpl->setContent('g_langs',          htmlspecialchars($guide['languages'] ?? ''));
$tpl->setContent('g_bio',            htmlspecialchars($guide['bio'] ?? ''));
$tpl->setContent('g_photo',          htmlspecialchars($guide['profile_photo'] ?? ''));
$tpl->setContent('g_active_checked', $guide['is_active'] ? 'checked' : '');
$tpl->setContent('csrf_token',       generateCsrfToken());
$tpl->close();
