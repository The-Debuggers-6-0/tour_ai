<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$slotId = (int)($_GET['id'] ?? 0);
if ($slotId <= 0) { header('Location: ' . BASE_URL . 'admin/slots/list.php'); exit; }

$slot = queryOne("SELECT * FROM time_slots WHERE id = ?", 'i', $slotId);
if (!$slot) { setFlash('Slot non trovato.', 'error'); header('Location: ' . BASE_URL . 'admin/slots/list.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/slots/edit.php?id=' . $slotId); exit;
    }
    $tourId = (int)($_POST['tours_id']        ?? 0);
    $date   = sanitize($_POST['slot_date']    ?? '');
    $start  = sanitize($_POST['start_time']   ?? '');
    $end    = sanitize($_POST['end_time']     ?? '');
    $seats  = (int)($_POST['available_seats'] ?? 0);
    $status = sanitize($_POST['status']       ?? 'disponibile');
    $notes  = sanitize($_POST['notes']        ?? '');

    if ($tourId <= 0 || $date === '' || $start === '' || $end === '' || $seats < 1) {
        setFlash('Compila tutti i campi obbligatori.', 'error');
        header('Location: ' . BASE_URL . 'admin/slots/edit.php?id=' . $slotId); exit;
    }
    execute(
        "UPDATE time_slots SET tours_id=?, slot_date=?, start_time=?, end_time=?, available_seats=?, status=?, notes=? WHERE id=?",
        'isssissi', $tourId, $date, $start, $end, $seats, $status, $notes, $slotId
    );
    setFlash('Slot aggiornato.', 'success');
    header('Location: ' . BASE_URL . 'admin/slots/list.php'); exit;
}

$s = $slot['status'];
$tpl = new Template('html/admin/slots_form');
setAdminCommonContent($tpl);
$tpl->setContent('slf_page_title',  'Modifica slot');
$tpl->setContent('slf_action',      BASE_URL . 'admin/slots/edit.php?id=' . $slotId);
$tpl->setContent('sl_form_id',      $slotId);
$tpl->setContent('sl_form_date',    htmlspecialchars($slot['slot_date']));
$tpl->setContent('sl_form_start',   substr($slot['start_time'], 0, 5));
$tpl->setContent('sl_form_end',     substr($slot['end_time'], 0, 5));
$tpl->setContent('sl_form_seats',   (int)$slot['available_seats']);
$tpl->setContent('sl_form_notes',   htmlspecialchars($slot['notes'] ?? ''));
$tpl->setContent('sl_status_disp',  $s === 'disponibile' ? 'selected' : '');
$tpl->setContent('sl_status_pieno', $s === 'pieno'       ? 'selected' : '');
$tpl->setContent('sl_status_canc',  $s === 'cancellato'  ? 'selected' : '');
$tpl->setContent('csrf_token',      generateCsrfToken());

$tours = queryAll("SELECT id, title FROM tours WHERE is_active = 1 ORDER BY title");
foreach ($tours as $sto) {
    $tpl->setContent('sto_id',       (int)$sto['id']);
    $tpl->setContent('sto_title',    htmlspecialchars($sto['title']));
    $tpl->setContent('sto_selected', (int)$sto['id'] === (int)$slot['tours_id'] ? 'selected' : '');
}
$tpl->close();
