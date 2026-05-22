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

    try {
        $conn = getConnection();
        $conn->begin_transaction();

        // Elimina le recensioni collegate a questo tour
        execute("DELETE FROM reviews WHERE tours_id = ?", 'i', $tourId);

        // Trova tutti gli slot associati a questo tour
        $slots = queryAll("SELECT id FROM time_slots WHERE tours_id = ?", 'i', $tourId);
        if (!empty($slots)) {
            $slotIds = array_column($slots, 'id');
            $inClause = implode(',', array_fill(0, count($slotIds), '?'));
            $types = str_repeat('i', count($slotIds));

            // Trova tutte le prenotazioni per quegli slot
            $bookings = queryAll("SELECT id FROM bookings WHERE time_slots_id IN ($inClause)", $types, ...$slotIds);
            if (!empty($bookings)) {
                $bookingIds = array_column($bookings, 'id');
                $bInClause = implode(',', array_fill(0, count($bookingIds), '?'));
                $bTypes = str_repeat('i', count($bookingIds));

                // Elimina i partecipanti alle prenotazioni
                execute("DELETE FROM booking_participants WHERE bookings_id IN ($bInClause)", $bTypes, ...$bookingIds);
                
                // Elimina le recensioni collegate alle prenotazioni (dovrebbero essere già state eliminate sopra, ma per sicurezza)
                execute("DELETE FROM reviews WHERE bookings_id IN ($bInClause)", $bTypes, ...$bookingIds);

                // Elimina le prenotazioni
                execute("DELETE FROM bookings WHERE time_slots_id IN ($inClause)", $types, ...$slotIds);
            }
        }

        // Le tabelle time_slots, tour_images e tours_has_guides hanno ON DELETE CASCADE, 
        // quindi verranno eliminate automaticamente quando eliminiamo il tour.
        execute("DELETE FROM tours WHERE id = ?", 'i', $tourId);

        $conn->commit();
        setFlash('Tour eliminato definitivamente.', 'success');
    } catch (Exception $e) {
        $conn->rollback();
        setFlash('Errore durante l\'eliminazione: ' . $e->getMessage(), 'error');
    }
header('Location: ' . BASE_URL . 'admin/tours/list.php');
exit;
