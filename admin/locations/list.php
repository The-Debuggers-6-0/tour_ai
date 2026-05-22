<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$tpl = new Template('html/admin/locations_list');
setAdminCommonContent($tpl);
$tpl->setContent('csrf_token', generateCsrfToken());

$locs = queryAll(
    "SELECT l.id, l.city, l.province, l.region, l.country, COUNT(t.id) AS tours_count
     FROM locations l LEFT JOIN tours t ON t.locations_id = l.id AND t.is_active = 1
     GROUP BY l.id ORDER BY l.city"
);
foreach ($locs as $lad) {
    $tpl->setContent('lad_id',          (int)$lad['id']);
    $tpl->setContent('lad_city',        htmlspecialchars($lad['city']));
    $tpl->setContent('lad_province',    htmlspecialchars($lad['province'] ?? ''));
    $tpl->setContent('lad_region',      htmlspecialchars($lad['region'] ?? ''));
    $tpl->setContent('lad_country',     htmlspecialchars($lad['country'] ?? 'Italia'));
    $tpl->setContent('lad_tours_count', (int)$lad['tours_count']);
    $tpl->setContent('loop_base_url',   BASE_URL);
}

$tpl->setContent('has_locations', count($locs) > 0 ? '1' : '');
$tpl->close();
