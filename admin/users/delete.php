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
    try {
        $conn = getConnection();
        $conn->begin_transaction();
        
        execute("DELETE FROM reviews WHERE users_id = ?", 'i', $id);
        execute("DELETE FROM bookings WHERE users_id = ?", 'i', $id);
        execute("DELETE FROM users_has_groups WHERE users_id = ?", 'i', $id);
        execute("DELETE FROM users WHERE id = ?", 'i', $id);
        
        $conn->commit();
        setFlash('Utente eliminato definitivamente.', 'success');
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('Errore durante l\'eliminazione.', 'error');
    }
} else {
    setFlash('Impossibile eliminare questo account.', 'error');
}
header('Location: ' . BASE_URL . 'admin/users/list.php');
exit;
