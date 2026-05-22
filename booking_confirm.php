<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/template2.inc.php';

requireLogin();

$code = sanitize($_GET['code'] ?? '');
if ($code === '') {
    header('Location: ' . BASE_URL);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$booking = queryOne(
    "SELECT b.booking_code, b.total_participants, b.total_price, b.status,
            t.title, l.city,
            s.slot_date, s.start_time, s.end_time
     FROM bookings b
     JOIN time_slots s  ON s.id = b.time_slots_id
     JOIN tours t  ON t.id = s.tours_id
     JOIN locations l ON l.id = t.locations_id
     WHERE b.booking_code = ? AND b.users_id = ?",
    'si', $code, $userId
);

if (!$booking) {
    header('Location: ' . BASE_URL);
    exit;
}

$tpl = new Template('html/booking_confirm');
setCommonContent($tpl);

$tpl->setContent('booking_code',        htmlspecialchars($booking['booking_code']));
$tpl->setContent('confirm_tour_title',  htmlspecialchars($booking['title']));
$tpl->setContent('confirm_tour_city',   htmlspecialchars($booking['city']));
$tpl->setContent('confirm_slot_date',   formatDateLong($booking['slot_date']));
$tpl->setContent('confirm_slot_time',   formatTime($booking['start_time']) . ' – ' . formatTime($booking['end_time']));
$tpl->setContent('confirm_participants',(int)$booking['total_participants']);
$tpl->setContent('confirm_total_price', number_format($booking['total_price'], 2, ',', '.'));

$tpl->close();
