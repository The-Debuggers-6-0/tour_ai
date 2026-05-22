<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . BASE_URL . 'admin/slots/list.php'); exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $hasBookings = queryOne("SELECT id FROM bookings WHERE time_slots_id = ? AND status = 'confermata' LIMIT 1", 'i', $id);
    if ($hasBookings) {
        setFlash('Impossibile eliminare: ci sono prenotazioni confermate per questo slot.', 'error');
    } else {
        execute("DELETE FROM time_slots WHERE id = ?", 'i', $id);
        setFlash('Slot eliminato.', 'success');
    }
}
header('Location: ' . BASE_URL . 'admin/slots/list.php');
exit;
