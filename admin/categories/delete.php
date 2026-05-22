<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: ' . BASE_URL . 'admin/categories/list.php'); exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $inUse = queryOne("SELECT id FROM tours WHERE categories_id = ? AND is_active = 1 LIMIT 1", 'i', $id);
    if ($inUse) {
        setFlash('Impossibile eliminare: ci sono tour attivi in questa categoria.', 'error');
    } else {
        execute("DELETE FROM categories WHERE id = ?", 'i', $id);
        setFlash('Categoria eliminata.', 'success');
    }
}
header('Location: ' . BASE_URL . 'admin/categories/list.php');
exit;
