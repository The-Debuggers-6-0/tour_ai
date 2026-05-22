<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/template2.inc.php';

requireLogin();

if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Handle cancel POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token di sicurezza non valido.', 'error');
        header('Location: ' . BASE_URL . 'my_bookings.php');
        exit;
    }
    $cancelId = (int)$_POST['cancel_booking_id'];
    $row = queryOne(
        "SELECT b.id, s.slot_date FROM bookings b JOIN time_slots s ON s.id = b.time_slots_id
         WHERE b.id = ? AND b.users_id = ? AND b.status = 'confermata'",
        'ii', $cancelId, $userId
    );
    if ($row && strtotime($row['slot_date']) > time() + 86400) {
        execute("UPDATE bookings SET status = 'cancellata' WHERE id = ?", 'i', $cancelId);
        setFlash('Prenotazione cancellata.', 'success');
    } else {
        setFlash('Impossibile cancellare la prenotazione.', 'error');
    }
    header('Location: ' . BASE_URL . 'my_bookings.php');
    exit;
}

$bookings = queryAll(
    "SELECT b.id, b.booking_code, b.total_participants, b.total_price, b.status, b.created_at,
            t.title, l.city,
            s.slot_date, s.start_time, s.end_time
     FROM bookings b
     JOIN time_slots s  ON s.id = b.time_slots_id
     JOIN tours t  ON t.id = s.tours_id
     JOIN locations l ON l.id = t.locations_id
     WHERE b.users_id = ? AND b.status != 'cancellata'
     ORDER BY b.created_at DESC",
    'i', $userId
);

$tpl = new Template('html/my_bookings');
setCommonContent($tpl);

$csrfToken = generateCsrfToken();
$tpl->setContent('mb_csrf',     $csrfToken);
$tpl->setContent('no_bookings', empty($bookings) ? '1' : '');

foreach ($bookings as $bk) {
    $statusBadge = statusLabel($bk['status']);
    $canCancel   = ($bk['status'] === 'confermata' && strtotime($bk['slot_date']) > time() + 86400) ? '1' : '';

    $tpl->setContent('mb_code',             htmlspecialchars($bk['booking_code']));
    $tpl->setContent('mb_tour',             htmlspecialchars($bk['title']));
    $tpl->setContent('mb_city',             htmlspecialchars($bk['city']));
    $tpl->setContent('mb_date',             formatDate($bk['slot_date']));
    $tpl->setContent('mb_time',             formatTime($bk['start_time']));
    $tpl->setContent('mb_participants',     (int)$bk['total_participants']);
    $tpl->setContent('mb_price',            number_format($bk['total_price'], 2, ',', '.'));
    $tpl->setContent('mb_status_badge',     $statusBadge);
    // Detail accordion fields (same booking, detail view)
    $tpl->setContent('mb_code_detail',         htmlspecialchars($bk['booking_code']));
    $tpl->setContent('mb_date_detail',         formatDateLong($bk['slot_date']));
    $tpl->setContent('mb_time_detail',         formatTime($bk['start_time']) . ' – ' . formatTime($bk['end_time']));
    $tpl->setContent('mb_participants_detail', (int)$bk['total_participants']);
    $tpl->setContent('mb_price_detail',        number_format($bk['total_price'], 2, ',', '.'));
    $tpl->setContent('mb_status_detail',       $statusBadge);
    $tpl->setContent('mb_can_cancel',          $canCancel);
    $tpl->setContent('mb_id',                  (int)$bk['id']);
}

$tpl->close();
