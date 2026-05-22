<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$bookingId = (int)($_GET['id'] ?? 0);
if ($bookingId <= 0) { header('Location: ' . BASE_URL . 'admin/bookings/list.php'); exit; }

$booking = queryOne(
    "SELECT b.*, t.title, l.city, s.slot_date, s.start_time, s.end_time,
            u.username, u.email AS user_email
     FROM bookings b
     JOIN time_slots s  ON s.id = b.time_slots_id
     JOIN tours t  ON t.id = s.tours_id
     JOIN locations l ON l.id = t.locations_id
     JOIN users u  ON u.id = b.users_id
     WHERE b.id = ?",
    'i', $bookingId
);
if (!$booking) { setFlash('Prenotazione non trovata.', 'error'); header('Location: ' . BASE_URL . 'admin/bookings/list.php'); exit; }

$participants = queryAll("SELECT first_name, last_name, is_primary_contact FROM booking_participants WHERE bookings_id = ? ORDER BY is_primary_contact DESC, id ASC", 'i', $bookingId);

$tpl = new Template('html/admin/bookings_detail');
setAdminCommonContent($tpl);

$tpl->setContent('bk_id',               $bookingId);
$tpl->setContent('bk_code',             htmlspecialchars($booking['booking_code']));
$tpl->setContent('bk_status_badge',     statusLabel($booking['status']));
$tpl->setContent('bk_created',          formatDate($booking['created_at']));
$tpl->setContent('bk_tour',             htmlspecialchars($booking['title']));
$tpl->setContent('bk_city',             htmlspecialchars($booking['city']));
$tpl->setContent('bk_slot_date',        formatDateLong($booking['slot_date']));
$tpl->setContent('bk_slot_time',        formatTime($booking['start_time']) . ' – ' . formatTime($booking['end_time']));
$tpl->setContent('bk_user',             htmlspecialchars($booking['username']));
$tpl->setContent('bk_user_email',       htmlspecialchars($booking['user_email']));
$tpl->setContent('bk_participants_count',(int)$booking['total_participants']);
$tpl->setContent('bk_price',            number_format($booking['total_price'], 2, ',', '.'));
$tpl->setContent('bk_notes',            htmlspecialchars($booking['additional_notes'] ?? ''));
$tpl->setContent('csrf_token',          generateCsrfToken());

$tpl->setContent('can_cancel_booking',  $booking['status'] === 'confermata' ? '1' : '');
$tpl->setContent('can_confirm_booking', $booking['status'] === 'in_attesa'  ? '1' : '');

foreach ($participants as $idx => $bp) {
    $primaryLabel = $bp['is_primary_contact'] ? '<span class="badge badge-success">Principale</span>' : '';
    $tpl->setContent('bp_num',     $idx + 1);
    $tpl->setContent('bp_name',    htmlspecialchars($bp['first_name'] . ' ' . $bp['last_name']));
    $tpl->setContent('bp_primary', $primaryLabel);
}

$tpl->close();
