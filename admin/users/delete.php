<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . BASE_URL . 'admin/users/list.php'); exit;
}
$id = (int)($_GET['id'] ?? 0);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if ($id > 0 && $id !== $currentUserId) {
    execute("UPDATE users SET is_active = 0 WHERE id = ?", 'i', $id);
    setFlash('Utente disattivato.', 'success');
} else {
    setFlash('Impossibile eliminare questo account.', 'error');
}
header('Location: ' . BASE_URL . 'admin/users/list.php');
exit;
