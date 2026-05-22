<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$tpl = new Template('html/admin/categories_list');
setAdminCommonContent($tpl);
$tpl->setContent('csrf_token', generateCsrfToken());

$cats = queryAll(
    "SELECT c.id, c.name, c.slug, c.icon, COUNT(t.id) AS tours_count
     FROM categories c LEFT JOIN tours t ON t.categories_id = c.id AND t.is_active = 1
     GROUP BY c.id ORDER BY c.name"
);
foreach ($cats as $cad) {
    $tpl->setContent('cad_id',          (int)$cad['id']);
    $tpl->setContent('cad_icon',        htmlspecialchars($cad['icon'] ?? ''));
    $tpl->setContent('cad_name',        htmlspecialchars($cad['name']));
    $tpl->setContent('cad_slug',        htmlspecialchars($cad['slug']));
    $tpl->setContent('cad_tours_count', (int)$cad['tours_count']);
}
$tpl->close();
