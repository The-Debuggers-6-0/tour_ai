<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'admin/tours/list.php');
    exit;
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlash('Token non valido.', 'error');
    header('Location: ' . BASE_URL . 'admin/tours/list.php');
    exit;
}

$tourId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($tourId <= 0) {
    header('Location: ' . BASE_URL . 'admin/tours/list.php');
    exit;
}

// Soft delete
execute("UPDATE tours SET is_active = 0 WHERE id = ?", 'i', $tourId);
setFlash('Tour disattivato.', 'success');
header('Location: ' . BASE_URL . 'admin/tours/list.php');
exit;
