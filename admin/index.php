<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../template2.inc.php';

requireAdmin();

$tpl = new Template('html/admin/dashboard');
setAdminCommonContent($tpl);

// Stats
$statTours   = queryOne("SELECT COUNT(*) AS cnt FROM tours WHERE is_active = 1")['cnt'] ?? 0;
$statBookings= queryOne("SELECT COUNT(*) AS cnt FROM bookings WHERE status = 'confermata'")['cnt'] ?? 0;
$statUsers   = queryOne("SELECT COUNT(*) AS cnt FROM users WHERE is_active = 1")['cnt'] ?? 0;
$statPending = queryOne("SELECT COUNT(*) AS cnt FROM reviews WHERE is_approved = 0")['cnt'] ?? 0;

$tpl->setContent('stat_tours',           (int)$statTours);
$tpl->setContent('stat_bookings',        (int)$statBookings);
$tpl->setContent('stat_users',           (int)$statUsers);
$tpl->setContent('stat_reviews_pending', (int)$statPending);

// Recent bookings
$recentBookings = queryAll(
    "SELECT b.booking_code, t.title, u.username, s.slot_date, b.status
     FROM bookings b
     JOIN time_slots s ON s.id = b.time_slots_id
     JOIN tours t ON t.id = s.tours_id
     JOIN users u ON u.id = b.users_id
     ORDER BY b.created_at DESC LIMIT 5"
);
foreach ($recentBookings as $rb) {
    $tpl->setContent('rbk_code',        htmlspecialchars($rb['booking_code']));
    $tpl->setContent('rbk_tour',        htmlspecialchars($rb['title']));
    $tpl->setContent('rbk_user',        htmlspecialchars($rb['username']));
    $tpl->setContent('rbk_date',        formatDate($rb['slot_date']));
    $tpl->setContent('rbk_status_badge',statusLabel($rb['status']));
}

// Upcoming slots
$upcomingSlots = queryAll(
    "SELECT t.title, s.slot_date, s.start_time,
            s.available_seats - COALESCE(SUM(CASE WHEN b.status='confermata' THEN b.total_participants ELSE 0 END),0) AS seats_left
     FROM time_slots s
     JOIN tours t ON t.id = s.tours_id
     LEFT JOIN bookings b ON b.time_slots_id = s.id
     WHERE s.slot_date >= CURDATE() AND s.status = 'disponibile'
     GROUP BY s.id
     ORDER BY s.slot_date ASC, s.start_time ASC
     LIMIT 5"
);
foreach ($upcomingSlots as $us) {
    $tpl->setContent('usl_tour',      htmlspecialchars($us['title']));
    $tpl->setContent('usl_date',      formatDate($us['slot_date']));
    $tpl->setContent('usl_time',      formatTime($us['start_time']));
    $tpl->setContent('usl_available', (int)$us['seats_left']);
}

$tpl->close();
