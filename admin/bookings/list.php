<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$filterStatus = sanitize($_GET['status'] ?? '');
$searchCode   = sanitize($_GET['code']   ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

$tpl = new Template('html/admin/bookings_list');
setAdminCommonContent($tpl);
$tpl->setContent('search_code',               htmlspecialchars($searchCode));
$tpl->setContent('filter_status_confermata',  $filterStatus === 'confermata' ? 'selected' : '');
$tpl->setContent('filter_status_attesa',      $filterStatus === 'in_attesa'  ? 'selected' : '');
$tpl->setContent('filter_status_cancellata',  $filterStatus === 'cancellata' ? 'selected' : '');

$where = ['1=1']; $params = []; $types = '';
if ($filterStatus !== '') { $where[] = 'b.status = ?'; $params[] = $filterStatus; $types .= 's'; }
if ($searchCode   !== '') { $where[] = 'b.booking_code LIKE ?'; $params[] = '%' . $searchCode . '%'; $types .= 's'; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countRow = queryOne("SELECT COUNT(*) AS cnt FROM bookings b $whereSQL", $types, ...$params);
$total = (int)($countRow['cnt'] ?? 0);

$offset = ($page - 1) * $perPage;
$bookings = queryAll(
    "SELECT b.id, b.booking_code, b.total_participants, b.total_price, b.status,
            t.title, u.username, s.slot_date
     FROM bookings b
     JOIN time_slots s ON s.id = b.time_slots_id
     JOIN tours t ON t.id = s.tours_id
     JOIN users u ON u.id = b.users_id
     $whereSQL
     ORDER BY b.created_at DESC LIMIT ? OFFSET ?",
    $types . 'ii', ...[...$params, $perPage, $offset]
);

foreach ($bookings as $ba) {
    $tpl->setContent('ba_id',           (int)$ba['id']);
    $tpl->setContent('ba_code',         htmlspecialchars($ba['booking_code']));
    $tpl->setContent('ba_tour',         htmlspecialchars($ba['title']));
    $tpl->setContent('ba_user',         htmlspecialchars($ba['username']));
    $tpl->setContent('ba_date',         formatDate($ba['slot_date']));
    $tpl->setContent('ba_participants', (int)$ba['total_participants']);
    $tpl->setContent('ba_price',        number_format($ba['total_price'], 2, ',', '.'));
    $tpl->setContent('ba_status_badge', statusLabel($ba['status']));
}

$tpl->setContent('bookings_pagination', buildPagination($page, $total, $perPage, $_GET));
$tpl->close();
