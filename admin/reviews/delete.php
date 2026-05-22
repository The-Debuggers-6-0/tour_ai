<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . BASE_URL . 'admin/reviews/list.php'); exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    execute("DELETE FROM reviews WHERE id = ?", 'i', $id);
    setFlash('Recensione eliminata.', 'success');
}
header('Location: ' . BASE_URL . 'admin/reviews/list.php');
exit;
