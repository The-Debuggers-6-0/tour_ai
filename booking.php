<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/template2.inc.php';

requireLogin();

if (($_SESSION['role'] ?? '') === 'admin') {
    setFlash('Gli amministratori non possono effettuare prenotazioni. Gestisci le prenotazioni dalla dashboard.', 'info');
    header('Location: ' . BASE_URL . 'admin/index.php');
    exit;
}

$slotId = (int)($_GET['slot_id'] ?? $_POST['slot_id'] ?? 0);
if ($slotId <= 0) {
    header('Location: ' . BASE_URL . 'tours.php');
    exit;
}

// Load slot + tour
$slot = queryOne(
    "SELECT s.*, t.title, t.price_per_person, t.slug AS tour_slug, t.max_participants,
            l.city,
            s.available_seats - COALESCE(SUM(CASE WHEN b.status='confermata' THEN b.total_participants ELSE 0 END),0) AS seats_left
     FROM time_slots s
     JOIN tours t ON t.id = s.tours_id
     JOIN locations l ON l.id = t.locations_id
     LEFT JOIN bookings b ON b.time_slots_id = s.id
     WHERE s.id = ? AND s.status = 'disponibile' AND s.slot_date >= CURDATE() AND t.is_active = 1
     GROUP BY s.id",
    'i', $slotId
);

if (!$slot || (int)$slot['seats_left'] <= 0) {
    setFlash('Slot non disponibile o esaurito.', 'error');
    header('Location: ' . BASE_URL . 'tours.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token di sicurezza non valido.', 'error');
        header('Location: ' . BASE_URL . 'booking.php?slot_id=' . $slotId);
        exit;
    }

    $participants = $_POST['participants'] ?? [];
    $participantsCount = count($participants);
    if ($participantsCount < 1 || $participantsCount > (int)$slot['seats_left']) {
        setFlash('Numero partecipanti non valido.', 'error');
        header('Location: ' . BASE_URL . 'booking.php?slot_id=' . $slotId);
        exit;
    }

    $totalPrice = round($slot['price_per_person'] * $participantsCount, 2);
    $userId     = (int)$_SESSION['user_id'];
    $bookingCode = generateBookingCode();

    // Transaction: lock + insert
    $conn = getConnection();
    $conn->begin_transaction();
    try {
        // Re-check availability with lock
        $checkStmt = $conn->prepare(
            "SELECT s.available_seats - COALESCE(SUM(CASE WHEN b.status='confermata' THEN b.total_participants ELSE 0 END),0) AS seats_left
             FROM time_slots s LEFT JOIN bookings b ON b.time_slots_id = s.id
             WHERE s.id = ? FOR UPDATE"
        );
        $checkStmt->bind_param('i', $slotId);
        $checkStmt->execute();
        $checkRow = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ((int)$checkRow['seats_left'] < $participantsCount) {
            $conn->rollback();
            setFlash('Posti esauriti. Scegli un altro slot.', 'error');
            header('Location: ' . BASE_URL . 'tour_detail.php?slug=' . urlencode($slot['tour_slug']));
            exit;
        }

        $notes = sanitize($_POST['additional_notes'] ?? '');
        $stmt = $conn->prepare(
            "INSERT INTO bookings (booking_code, time_slots_id, users_id, total_participants, total_price, additional_notes, status, created_at)
             VALUES (?,?,?,?,?,?,'confermata',NOW())"
        );
        $stmt->bind_param('siiids', $bookingCode, $slotId, $userId, $participantsCount, $totalPrice, $notes);
        $stmt->execute();
        $bookingId = $conn->insert_id;
        $stmt->close();

        foreach (array_slice($participants, 0, $participantsCount) as $idx => $p) {
            $pFirst    = sanitize($p['first_name'] ?? '');
            $pLast     = sanitize($p['last_name']  ?? '');
            if ($pFirst === '') $pFirst = 'N/A';
            $isPrimary = ($idx === 0) ? 1 : 0;
            $ps = $conn->prepare("INSERT INTO booking_participants (bookings_id, first_name, last_name, is_primary_contact) VALUES (?,?,?,?)");
            $ps->bind_param('issi', $bookingId, $pFirst, $pLast, $isPrimary);
            $ps->execute();
            $ps->close();
        }

        $conn->commit();
        header('Location: ' . BASE_URL . 'booking_confirm.php?code=' . urlencode($bookingCode));
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('Errore durante la prenotazione. Riprova.', 'error');
        header('Location: ' . BASE_URL . 'booking.php?slot_id=' . $slotId);
        exit;
    }
}

$tpl = new Template('html/booking');
setCommonContent($tpl);

$tpl->setContent('tour_title',      htmlspecialchars($slot['title']));
$tpl->setContent('tour_city',       htmlspecialchars($slot['city']));
$tpl->setContent('tour_price',      number_format($slot['price_per_person'], 2, ',', '.'));
$tpl->setContent('tour_price_raw',  $slot['price_per_person']);
$tpl->setContent('tour_slug',       htmlspecialchars($slot['tour_slug']));
$tpl->setContent('slot_id',         $slotId);
$tpl->setContent('slot_date_fmt',   formatDateLong($slot['slot_date']));
$tpl->setContent('slot_start',      formatTime($slot['start_time']));
$tpl->setContent('slot_end',        formatTime($slot['end_time']));
$tpl->setContent('slot_available',  (int)$slot['seats_left']);
$tpl->setContent('csrf_token',      generateCsrfToken());

$tpl->close();
