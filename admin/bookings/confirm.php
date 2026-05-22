<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . BASE_URL . 'admin/bookings/list.php'); exit;
}
$id = (int)($_POST['booking_id'] ?? 0);
if ($id > 0) {
    execute("UPDATE bookings SET status = 'confermata' WHERE id = ? AND status = 'in_attesa'", 'i', $id);
    setFlash('Prenotazione confermata.', 'success');
}
header('Location: ' . BASE_URL . 'admin/bookings/detail.php?id=' . $id);
exit;
