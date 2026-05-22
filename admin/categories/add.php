<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/categories/add.php'); exit;
    }
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $desc = sanitize($_POST['description'] ?? '');
    $icon = sanitize($_POST['icon'] ?? '');

    if ($name === '') { setFlash('Nome obbligatorio.', 'error'); header('Location: ' . BASE_URL . 'admin/categories/add.php'); exit; }
    $slug = ensureUniqueSlug(slugify($slug ?: $name), 'categories', 'slug');
    execute("INSERT INTO categories (name, slug, description, icon) VALUES (?,?,?,?)", 'ssss', $name, $slug, $desc, $icon);
    setFlash('Categoria creata.', 'success');
    header('Location: ' . BASE_URL . 'admin/categories/list.php'); exit;
}

$tpl = new Template('html/admin/categories_form');
setAdminCommonContent($tpl);
$tpl->setContent('catf_page_title', 'Aggiungi categoria');
$tpl->setContent('catf_action',     BASE_URL . 'admin/categories/add.php');
$tpl->setContent('cat_id',          0);
$tpl->setContent('cat_name',        '');
$tpl->setContent('cat_slug_val',    '');
$tpl->setContent('cat_desc',        '');
$tpl->setContent('cat_icon_val',    '');
$tpl->setContent('csrf_token',      generateCsrfToken());
$tpl->close();
