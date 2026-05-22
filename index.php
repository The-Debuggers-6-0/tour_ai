<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/template2.inc.php';

$tpl = new Template('html/index');
setCommonContent($tpl);

// Stats
$statsRow = queryOne("SELECT COUNT(*) AS cnt FROM tours WHERE is_active = 1");
$tpl->setContent('total_tours', $statsRow['cnt'] ?? 0);

// Hero categories dropdown
$heroCategories = queryAll("SELECT slug, name FROM categories ORDER BY name");
foreach ($heroCategories as $hc) {
    $tpl->setContent('hcat_slug', htmlspecialchars($hc['slug']));
    $tpl->setContent('hcat_name', htmlspecialchars($hc['name']));
}

// Featured tours (top 6 by rating)
$featuredTours = queryAll(
    "SELECT t.slug, t.title, t.price_per_person, t.duration_minutes,
            c.name AS category_name, l.city,
            COALESCE(AVG(r.rating),0) AS avg_rating,
            COUNT(r.id) AS review_count,
            t.difficulty_level,
            (SELECT ti.image_path FROM tour_images ti WHERE ti.tours_id = t.id AND ti.is_cover = 1 LIMIT 1) AS cover
     FROM tours t
     JOIN categories c ON c.id = t.categories_id
     JOIN locations l  ON l.id = t.locations_id
     LEFT JOIN reviews r ON r.tours_id = t.id AND r.is_approved = 1
     WHERE t.is_active = 1
     GROUP BY t.id
     ORDER BY avg_rating DESC, t.id DESC
     LIMIT 6"
);
foreach ($featuredTours as $ft) {
    $cover = ($ft['cover'] && file_exists(__DIR__ . '/' . $ft['cover'])) ? BASE_URL . $ft['cover'] : BASE_URL . 'css/placeholder.jpg';
    $tpl->setContent('ft_slug',       htmlspecialchars($ft['slug']));
    $tpl->setContent('ft_title',      htmlspecialchars($ft['title']));
    $tpl->setContent('ft_image',      $cover);
    $tpl->setContent('ft_city',       htmlspecialchars($ft['city']));
    $tpl->setContent('ft_duration',   formatDuration($ft['duration_minutes']));
    $tpl->setContent('ft_price',      number_format($ft['price_per_person'], 2, ',', '.'));
    $tpl->setContent('ft_rating',     number_format($ft['avg_rating'], 1));
    $tpl->setContent('ft_reviews',    (int)$ft['review_count']);
    $tpl->setContent('ft_category',   htmlspecialchars($ft['category_name']));
    $tpl->setContent('ft_difficulty', difficultyLabel($ft['difficulty_level']));
}

// Categories with count
$categories = queryAll(
    "SELECT c.slug, c.name, c.icon, COUNT(t.id) AS tour_count
     FROM categories c
     LEFT JOIN tours t ON t.categories_id = c.id AND t.is_active = 1
     GROUP BY c.id ORDER BY tour_count DESC"
);
foreach ($categories as $cat) {
    $tpl->setContent('cat_slug',  htmlspecialchars($cat['slug']));
    $tpl->setContent('cat_name',  htmlspecialchars($cat['name']));
    $tpl->setContent('cat_icon',  htmlspecialchars($cat['icon'] ?? '🗺'));
    $tpl->setContent('cat_count', (int)$cat['tour_count']);
}

// Featured guides
$guides = queryAll(
    "SELECT g.first_name, g.last_name, g.profile_photo, g.specialization, g.languages,
            COALESCE(AVG(r.rating),0) AS avg_rating
     FROM guides g
     LEFT JOIN tours_has_guides tg ON tg.guides_id = g.id
     LEFT JOIN reviews r ON r.tours_id = tg.tours_id AND r.is_approved = 1
     WHERE g.is_active = 1
     GROUP BY g.id
     ORDER BY avg_rating DESC
     LIMIT 4"
);
foreach ($guides as $g) {
    $name  = htmlspecialchars($g['first_name'] . ' ' . $g['last_name']);
    $tpl->setContent('gp_name',    $name);
    $photo = ($g['profile_photo'] && file_exists(__DIR__ . '/' . $g['profile_photo'])) ? BASE_URL . htmlspecialchars($g['profile_photo']) : '';
    $tpl->setContent('gp_photo',   $photo);
    $tpl->setContent('gp_spec',    htmlspecialchars($g['specialization'] ?? ''));
    $tpl->setContent('gp_rating',  number_format($g['avg_rating'], 1));
    $tpl->setContent('gp_initial', strtoupper(substr($g['first_name'], 0, 1)));
    $tpl->setContent('gp_langs',   htmlspecialchars($g['languages'] ?? ''));
}

// Recent approved reviews
$reviews = queryAll(
    "SELECT u.username, r.rating, r.title, r.comment, r.created_at, t.title AS tour_title
     FROM reviews r
     JOIN users u ON u.id = r.users_id
     JOIN tours t ON t.id = r.tours_id
     WHERE r.is_approved = 1
     ORDER BY r.created_at DESC
     LIMIT 6"
);
foreach ($reviews as $rv) {
    $tpl->setContent('hr_author',     htmlspecialchars($rv['username']));
    $tpl->setContent('hr_rating',     (int)$rv['rating']);
    $tpl->setContent('hr_comment',    htmlspecialchars($rv['comment']));
    $tpl->setContent('hr_tour_title', htmlspecialchars($rv['tour_title']));
    $tpl->setContent('hr_date',       formatDate($rv['created_at']));
    $tpl->setContent('hr_stars',      renderStars($rv['rating']));
}

$tpl->close();

function formatDuration(int $minutes): string {
    if ($minutes < 60) return $minutes . ' min';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $m > 0 ? $h . 'h ' . $m . 'min' : $h . 'h';
}
