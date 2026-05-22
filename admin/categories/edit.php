<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$catId = (int)($_GET['id'] ?? 0);
if ($catId <= 0) { header('Location: ' . BASE_URL . 'admin/categories/list.php'); exit; }
$cat = queryOne("SELECT * FROM categories WHERE id = ?", 'i', $catId);
if (!$cat) { setFlash('Categoria non trovata.', 'error'); header('Location: ' . BASE_URL . 'admin/categories/list.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/categories/edit.php?id=' . $catId); exit;
    }
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');
    $icon = sanitize($_POST['icon'] ?? '');
    if ($name === '') { setFlash('Nome obbligatorio.', 'error'); header('Location: ' . BASE_URL . 'admin/categories/edit.php?id=' . $catId); exit; }
    $slug = ensureUniqueSlug(slugify($slug ?: $name), 'categories', 'slug', $catId);
    execute("UPDATE categories SET name=?, slug=?, description=?, icon=? WHERE id=?", 'ssssi', $name, $slug, $desc, $icon, $catId);
    setFlash('Categoria aggiornata.', 'success');
    header('Location: ' . BASE_URL . 'admin/categories/list.php'); exit;
}

$tpl = new Template('html/admin/categories_form');
setAdminCommonContent($tpl);
$tpl->setContent('catf_page_title', 'Modifica categoria');
$tpl->setContent('catf_action',     BASE_URL . 'admin/categories/edit.php?id=' . $catId);
$tpl->setContent('cat_id',          $catId);
$tpl->setContent('cat_name',        htmlspecialchars($cat['name']));
$tpl->setContent('cat_slug_val',    htmlspecialchars($cat['slug']));
$tpl->setContent('cat_desc',        htmlspecialchars($cat['description'] ?? ''));
$tpl->setContent('cat_icon_val',    htmlspecialchars($cat['icon'] ?? ''));
$tpl->setContent('csrf_token',      generateCsrfToken());
$tpl->close();
