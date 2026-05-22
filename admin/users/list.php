<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$searchQ = sanitize($_GET['q']    ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$tpl = new Template('html/admin/users_list');
setAdminCommonContent($tpl);
$tpl->setContent('users_search_query', htmlspecialchars($searchQ));
$tpl->setContent('csrf_token',         generateCsrfToken());

$where = ['1=1']; $params = []; $types = '';
if ($searchQ !== '') {
    $where[] = '(u.username LIKE ? OR u.email LIKE ?)';
    $like = '%' . $searchQ . '%';
    $params[] = $like; $params[] = $like; $types .= 'ss';
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countRow = queryOne("SELECT COUNT(*) AS cnt FROM users u $whereSQL", $types, ...$params);
$total = (int)($countRow['cnt'] ?? 0);

$offset = ($page - 1) * $perPage;
$users = queryAll(
    "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.is_active, u.created_at,
            g.name AS group_name
     FROM users u
     LEFT JOIN users_has_groups ug ON ug.users_id = u.id
     LEFT JOIN groups g ON g.id = ug.groups_id
     $whereSQL
     ORDER BY u.created_at DESC LIMIT ? OFFSET ?",
    $types . 'ii', ...[...$params, $perPage, $offset]
);

foreach ($users as $ua) {
    $activeBadge = $ua['is_active']
        ? '<span class="badge badge-success">Attivo</span>'
        : '<span class="badge badge-secondary">Inattivo</span>';
    $tpl->setContent('ua_id',          (int)$ua['id']);
    $tpl->setContent('ua_username',    htmlspecialchars($ua['username']));
    $tpl->setContent('ua_email',       htmlspecialchars($ua['email']));
    $tpl->setContent('ua_name',        htmlspecialchars(trim(($ua['first_name'] ?? '') . ' ' . ($ua['last_name'] ?? ''))));
    $tpl->setContent('ua_group',       htmlspecialchars($ua['group_name'] ?? '—'));
    $tpl->setContent('ua_active_badge',$activeBadge);
    $tpl->setContent('ua_created',     formatDate($ua['created_at']));
}

$tpl->setContent('users_pagination', buildPagination($page, $total, $perPage, $_GET));
$tpl->close();
