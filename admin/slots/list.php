<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$filterTour  = (int)($_GET['tour_id'] ?? 0);
$filterMonth = sanitize($_GET['month'] ?? '');

$tpl = new Template('html/admin/slots_list');
setAdminCommonContent($tpl);
$tpl->setContent('filter_month', htmlspecialchars($filterMonth));

$tours = queryAll("SELECT id, title FROM tours WHERE is_active = 1 ORDER BY title");
foreach ($tours as $sf) {
    $tpl->setContent('sf_tour_id',       (int)$sf['id']);
    $tpl->setContent('sf_tour_title',    htmlspecialchars($sf['title']));
    $tpl->setContent('sf_tour_selected', (int)$sf['id'] === $filterTour ? 'selected' : '');
}

$where = ['1=1']; $params = []; $types = '';
if ($filterTour > 0) { $where[] = 's.tours_id = ?'; $params[] = $filterTour; $types .= 'i'; }
if ($filterMonth !== '') {
    $where[] = 'DATE_FORMAT(s.slot_date, "%Y-%m") = ?'; $params[] = $filterMonth; $types .= 's';
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$slots = queryAll(
    "SELECT s.id, t.title AS tour_title, s.slot_date, s.start_time, s.end_time, s.available_seats, s.status,
            COALESCE(SUM(CASE WHEN b.status='confermata' THEN b.total_participants ELSE 0 END),0) AS booked
     FROM time_slots s
     JOIN tours t ON t.id = s.tours_id
     LEFT JOIN bookings b ON b.time_slots_id = s.id
     $whereSQL
     GROUP BY s.id ORDER BY s.slot_date DESC, s.start_time DESC",
    $types, ...$params
);

foreach ($slots as $sla) {
    $statusMap = ['disponibile' => 'badge-success', 'pieno' => 'badge-warning', 'cancellato' => 'badge-danger'];
    $statusBadge = '<span class="badge ' . ($statusMap[$sla['status']] ?? 'badge-secondary') . '">' . htmlspecialchars($sla['status']) . '</span>';
    $tpl->setContent('sla_id',           (int)$sla['id']);
    $tpl->setContent('sla_tour',         htmlspecialchars($sla['tour_title']));
    $tpl->setContent('sla_date',         formatDate($sla['slot_date']));
    $tpl->setContent('sla_start',        formatTime($sla['start_time']));
    $tpl->setContent('sla_end',          formatTime($sla['end_time']));
    $tpl->setContent('sla_available',    (int)$sla['available_seats']);
    $tpl->setContent('sla_booked',       (int)$sla['booked']);
    $tpl->setContent('sla_status_badge', $statusBadge);
}

$tpl->setContent('csrf_token', generateCsrfToken());
$tpl->close();
