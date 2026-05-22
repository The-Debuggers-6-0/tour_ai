<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/slots/add.php'); exit;
    }
    $tourId    = (int)($_POST['tours_id']        ?? 0);
    $date      = sanitize($_POST['slot_date']    ?? '');
    $start     = sanitize($_POST['start_time']   ?? '');
    $end       = sanitize($_POST['end_time']     ?? '');
    $seats     = (int)($_POST['available_seats'] ?? 0);
    $status    = sanitize($_POST['status']       ?? 'disponibile');
    $notes     = sanitize($_POST['notes']        ?? '');

    if ($tourId <= 0 || $date === '' || $start === '' || $end === '' || $seats < 1) {
        setFlash('Compila tutti i campi obbligatori.', 'error');
        header('Location: ' . BASE_URL . 'admin/slots/add.php'); exit;
    }
    execute(
        "INSERT INTO time_slots (tours_id, slot_date, start_time, end_time, available_seats, status, notes) VALUES (?,?,?,?,?,?,?)",
        'isssiss', $tourId, $date, $start, $end, $seats, $status, $notes
    );
    setFlash('Slot creato.', 'success');
    header('Location: ' . BASE_URL . 'admin/slots/list.php'); exit;
}

$tpl = new Template('html/admin/slots_form');
setAdminCommonContent($tpl);
$tpl->setContent('slf_page_title',  'Aggiungi slot');
$tpl->setContent('slf_action',      BASE_URL . 'admin/slots/add.php');
$tpl->setContent('sl_form_id',      0);
$tpl->setContent('sl_form_date',    '');
$tpl->setContent('sl_form_start',   '');
$tpl->setContent('sl_form_end',     '');
$tpl->setContent('sl_form_seats',   '');
$tpl->setContent('sl_form_notes',   '');
$tpl->setContent('sl_status_disp',  'selected');
$tpl->setContent('sl_status_pieno', '');
$tpl->setContent('sl_status_canc',  '');
$tpl->setContent('csrf_token',      generateCsrfToken());

$tours = queryAll("SELECT id, title FROM tours WHERE is_active = 1 ORDER BY title");
foreach ($tours as $sto) {
    $tpl->setContent('sto_id',       (int)$sto['id']);
    $tpl->setContent('sto_title',    htmlspecialchars($sto['title']));
    $tpl->setContent('sto_selected', '');
}
$tpl->close();
