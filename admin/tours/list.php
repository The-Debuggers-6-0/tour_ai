<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$searchQ   = sanitize($_GET['q']        ?? '');
$filterCat = sanitize($_GET['category'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 15;

$tpl = new Template('html/admin/tours_list');
setAdminCommonContent($tpl);
$tpl->setContent('search_query', htmlspecialchars($searchQ));

// Category filter options
$cats = queryAll("SELECT id, slug, name FROM categories ORDER BY name");
foreach ($cats as $cf) {
    $tpl->setContent('tfa_slug',     htmlspecialchars($cf['slug']));
    $tpl->setContent('tfa_name',     htmlspecialchars($cf['name']));
    $tpl->setContent('tfa_selected', $filterCat === $cf['slug'] ? 'selected' : '');
}

// Build query
$where = ['t.is_active IN (0,1)']; $params = []; $types = '';
if ($searchQ !== '') {
    $where[] = 't.title LIKE ?'; $params[] = '%' . $searchQ . '%'; $types .= 's';
}
if ($filterCat !== '') {
    $where[] = 'c.slug = ?'; $params[] = $filterCat; $types .= 's';
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$countRow = queryOne("SELECT COUNT(*) AS cnt FROM tours t JOIN categories c ON c.id = t.categories_id JOIN locations l ON l.id = t.locations_id $whereSQL", $types, ...$params);
$total = (int)($countRow['cnt'] ?? 0);
$tpl->setContent('total_tours', $total);

$offset = ($page - 1) * $perPage;
$tours = queryAll(
    "SELECT t.id, t.title, t.slug, t.price_per_person, t.is_active,
            c.name AS cat_name, l.city,
            COALESCE(AVG(r.rating),0) AS avg_rating,
            (SELECT ti.image_path FROM tour_images ti WHERE ti.tours_id = t.id AND ti.is_cover = 1 LIMIT 1) AS cover
     FROM tours t
     JOIN categories c ON c.id = t.categories_id
     JOIN locations l  ON l.id = t.locations_id
     LEFT JOIN reviews r ON r.tours_id = t.id AND r.is_approved = 1
     $whereSQL
     GROUP BY t.id ORDER BY t.id DESC LIMIT ? OFFSET ?",
    $types . 'ii', ...[...$params, $perPage, $offset]
);

foreach ($tours as $ta) {
    $cover = $ta['cover'] ? '<img src="' . BASE_URL . htmlspecialchars($ta['cover']) . '" style="width:48px;height:36px;object-fit:cover;border-radius:4px;">' : '';
    $activeBadge = $ta['is_active'] ? '<span class="badge badge-success">Attivo</span>' : '<span class="badge badge-secondary">Inattivo</span>';
    $tpl->setContent('ta_id',          (int)$ta['id']);
    $tpl->setContent('ta_title',       htmlspecialchars($ta['title']));
    $tpl->setContent('ta_category',    htmlspecialchars($ta['cat_name']));
    $tpl->setContent('ta_city',        htmlspecialchars($ta['city']));
    $tpl->setContent('ta_price',       number_format($ta['price_per_person'], 2, ',', '.'));
    $tpl->setContent('ta_rating',      number_format($ta['avg_rating'], 1));
    $tpl->setContent('ta_image',       $cover);
    $tpl->setContent('ta_active_badge',$activeBadge);
    $tpl->setContent('ta_slug',        htmlspecialchars($ta['slug']));
    $tpl->setContent('loop_base_url',       BASE_URL);
}

$tpl->setContent('has_tours', count($tours) > 0 ? '1' : '');

$tpl->setContent('tours_pagination', buildPagination($page, $total, $perPage, $_GET));
$tpl->setContent('csrf_token',       generateCsrfToken());
$tpl->close();
