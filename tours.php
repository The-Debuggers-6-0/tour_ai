<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/template2.inc.php';

$tpl = new Template('html/tours');
setCommonContent($tpl);

// Filters from GET
$filterQ    = sanitize($_GET['q']        ?? '');
$filterCat  = sanitize($_GET['category'] ?? '');
$filterLoc  = sanitize($_GET['location'] ?? '');
$filterDiff = sanitize($_GET['difficulty'] ?? '');
$filterMin  = (float)($_GET['min_price'] ?? 0);
$filterMax  = (float)($_GET['max_price'] ?? 0);
$filterSort = sanitize($_GET['sort'] ?? 'rating');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 9;

$tpl->setContent('filter_q',             htmlspecialchars($filterQ));
$tpl->setContent('filter_min_price',     $filterMin > 0 ? $filterMin : '');
$tpl->setContent('filter_max_price',     $filterMax > 0 ? $filterMax : '');
$tpl->setContent('filter_diff_facile',   $filterDiff === 'facile'    ? 'selected' : '');
$tpl->setContent('filter_diff_medio',    $filterDiff === 'medio'     ? 'selected' : '');
$tpl->setContent('filter_diff_difficile',$filterDiff === 'difficile' ? 'selected' : '');
$tpl->setContent('filter_sort_rating',   $filterSort === 'rating'    ? 'selected' : '');
$tpl->setContent('filter_sort_price_asc',$filterSort === 'price_asc' ? 'selected' : '');
$tpl->setContent('filter_sort_price_desc',$filterSort === 'price_desc'? 'selected' : '');
$tpl->setContent('filter_sort_duration', $filterSort === 'duration'  ? 'selected' : '');

// Categories filter
$cats = queryAll("SELECT slug, name FROM categories ORDER BY name");
foreach ($cats as $cf) {
    $tpl->setContent('cf_slug',     htmlspecialchars($cf['slug']));
    $tpl->setContent('cf_name',     htmlspecialchars($cf['name']));
    $tpl->setContent('cf_selected', $filterCat === $cf['slug'] ? 'selected' : '');
}

// Locations filter
$locs = queryAll("SELECT city FROM locations ORDER BY city");
foreach ($locs as $lf) {
    $tpl->setContent('lf_city',     htmlspecialchars($lf['city']));
    $tpl->setContent('lf_selected', $filterLoc === $lf['city'] ? 'selected' : '');
}

// Build WHERE
$where  = ['t.is_active = 1'];
$params = [];
$types  = '';
if ($filterQ !== '') {
    $where[] = '(t.title LIKE ? OR t.short_description LIKE ?)';
    $like = '%' . $filterQ . '%';
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($filterCat !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $filterCat; $types .= 's';
}
if ($filterLoc !== '') {
    $where[] = 'l.city = ?';
    $params[] = $filterLoc; $types .= 's';
}
if ($filterDiff !== '') {
    $where[] = 't.difficulty_level = ?';
    $params[] = $filterDiff; $types .= 's';
}
if ($filterMin > 0) {
    $where[] = 't.price_per_person >= ?';
    $params[] = $filterMin; $types .= 'd';
}
if ($filterMax > 0) {
    $where[] = 't.price_per_person <= ?';
    $params[] = $filterMax; $types .= 'd';
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$orderMap = [
    'rating'     => 'avg_rating DESC',
    'price_asc'  => 't.price_per_person ASC',
    'price_desc' => 't.price_per_person DESC',
    'duration'   => 't.duration_minutes ASC',
];
$orderSQL = $orderMap[$filterSort] ?? 'avg_rating DESC';

// Count
$countRow = queryOne(
    "SELECT COUNT(DISTINCT t.id) AS cnt
     FROM tours t
     JOIN categories c ON c.id = t.categories_id
     JOIN locations l  ON l.id = t.locations_id
     $whereSQL",
    $types, ...$params
);
$total = (int)($countRow['cnt'] ?? 0);
$tpl->setContent('total_results', $total);
$tpl->setContent('no_results',    $total === 0 ? '1' : '');

// Tours list
$offset = ($page - 1) * $perPage;
$tours = queryAll(
    "SELECT t.slug, t.title, t.price_per_person, t.duration_minutes,
            t.short_description, t.difficulty_level,
            c.name AS category_name, l.city,
            COALESCE(AVG(r.rating),0) AS avg_rating,
            COUNT(DISTINCT r.id) AS review_count,
            (SELECT ti.image_path FROM tour_images ti WHERE ti.tours_id = t.id AND ti.is_cover = 1 LIMIT 1) AS cover
     FROM tours t
     JOIN categories c ON c.id = t.categories_id
     JOIN locations l  ON l.id = t.locations_id
     LEFT JOIN reviews r ON r.tours_id = t.id AND r.is_approved = 1
     $whereSQL
     GROUP BY t.id
     ORDER BY $orderSQL
     LIMIT ? OFFSET ?",
    $types . 'ii', ...[...$params, $perPage, $offset]
);

foreach ($tours as $tl) {
    $cover = ($tl['cover'] && file_exists(__DIR__ . '/' . $tl['cover'])) ? BASE_URL . $tl['cover'] : BASE_URL . 'css/placeholder.jpg';
    $diff  = $tl['difficulty_level'];
    $diffBadge = difficultyLabel($diff);
    $tpl->setContent('tl_slug',            htmlspecialchars($tl['slug']));
    $tpl->setContent('tl_title',           htmlspecialchars($tl['title']));
    $tpl->setContent('tl_image',           $cover);
    $tpl->setContent('tl_city',            htmlspecialchars($tl['city']));
    $tpl->setContent('tl_duration',        formatDuration((int)$tl['duration_minutes']));
    $tpl->setContent('tl_price',           number_format($tl['price_per_person'], 2, ',', '.'));
    $tpl->setContent('tl_rating',          number_format($tl['avg_rating'], 1));
    $tpl->setContent('tl_reviews',         (int)$tl['review_count']);
    $tpl->setContent('tl_category',        htmlspecialchars($tl['category_name']));
    $tpl->setContent('tl_difficulty_badge',$diffBadge);
    $tpl->setContent('tl_short_desc',      htmlspecialchars($tl['short_description'] ?? ''));
}

$tpl->setContent('current_page',   $page);
$tpl->setContent('tl_pagination',  buildPagination($page, $total, $perPage, $_GET));

$tpl->close();

function formatDuration(int $minutes): string {
    if ($minutes < 60) return $minutes . ' min';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $m > 0 ? $h . 'h ' . $m . 'min' : $h . 'h';
}
