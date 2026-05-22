<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$filterStatus = sanitize($_GET['status'] ?? '');

$tpl = new Template('html/admin/reviews_list');
setAdminCommonContent($tpl);
$tpl->setContent('filter_status_pending',  $filterStatus === 'pending'  ? 'selected' : '');
$tpl->setContent('filter_status_approved', $filterStatus === 'approved' ? 'selected' : '');
$tpl->setContent('csrf_token', generateCsrfToken());

$where = ['1=1']; $params = []; $types = '';
if ($filterStatus === 'pending')  { $where[] = 'r.is_approved = 0'; }
if ($filterStatus === 'approved') { $where[] = 'r.is_approved = 1'; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$reviews = queryAll(
    "SELECT r.id, r.rating, r.title, r.comment, r.is_approved, r.created_at,
            t.title AS tour_title, u.username
     FROM reviews r
     JOIN tours t ON t.id = r.tours_id
     JOIN users u ON u.id = r.users_id
     $whereSQL
     ORDER BY r.created_at DESC",
    $types, ...$params
);

foreach ($reviews as $ra) {
    $approvedBadge = $ra['is_approved']
        ? '<span class="badge badge-success">Approvata</span>'
        : '<span class="badge badge-warning">In attesa</span>';
    $tpl->setContent('ra_id',            (int)$ra['id']);
    $tpl->setContent('ra_tour',          htmlspecialchars($ra['tour_title']));
    $tpl->setContent('ra_user',          htmlspecialchars($ra['username']));
    $tpl->setContent('ra_rating',        (int)$ra['rating']);
    $tpl->setContent('ra_title',         htmlspecialchars($ra['title']));
    $tpl->setContent('ra_comment',       htmlspecialchars($ra['comment']));
    $tpl->setContent('ra_date',          formatDate($ra['created_at']));
    $tpl->setContent('ra_approved_badge',$approvedBadge);
    $tpl->setContent('ra_can_approve',   !$ra['is_approved'] ? '1' : '');
    $tpl->setContent('loop_base_url',    BASE_URL);
    $tpl->setContent('loop_csrf_token',  generateCsrfToken());
}

$tpl->setContent('has_reviews', count($reviews) > 0 ? '1' : '');

$tpl->close();
