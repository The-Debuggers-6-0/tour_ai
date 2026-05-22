<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . BASE_URL . 'admin/guides/list.php'); exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $guide = queryOne("SELECT profile_photo FROM guides WHERE id = ?", 'i', $id);
    if ($guide && $guide['profile_photo'] && file_exists(__DIR__ . '/../../' . $guide['profile_photo'])) {
        unlink(__DIR__ . '/../../' . $guide['profile_photo']);
    }
    execute("DELETE FROM guides WHERE id = ?", 'i', $id);
    setFlash('Guida eliminata.', 'success');
}
header('Location: ' . BASE_URL . 'admin/guides/list.php');
exit;
