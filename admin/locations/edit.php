<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$locId = (int)($_GET['id'] ?? 0);
if ($locId <= 0) { header('Location: ' . BASE_URL . 'admin/locations/list.php'); exit; }
$loc = queryOne("SELECT * FROM locations WHERE id = ?", 'i', $locId);
if (!$loc) { setFlash('Luogo non trovato.', 'error'); header('Location: ' . BASE_URL . 'admin/locations/list.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/locations/edit.php?id=' . $locId); exit;
    }
    $city     = sanitize($_POST['city']     ?? '');
    $province = sanitize($_POST['province'] ?? '');
    $region   = sanitize($_POST['region']   ?? '');
    $country  = sanitize($_POST['country']  ?? 'Italia');
    $address  = sanitize($_POST['address']  ?? '');
    $lat      = $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $lng      = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

    if ($city === '') { setFlash('Città obbligatoria.', 'error'); header('Location: ' . BASE_URL . 'admin/locations/edit.php?id=' . $locId); exit; }
    execute("UPDATE locations SET city=?, province=?, region=?, country=?, address=?, latitude=?, longitude=? WHERE id=?",
        'sssssdddi', $city, $province, $region, $country, $address, (float)$lat, (float)$lng, $locId);
    setFlash('Luogo aggiornato.', 'success');
    header('Location: ' . BASE_URL . 'admin/locations/list.php'); exit;
}

$tpl = new Template('html/admin/locations_form');
setAdminCommonContent($tpl);
$tpl->setContent('locf_page_title', 'Modifica luogo');
$tpl->setContent('locf_action',     BASE_URL . 'admin/locations/edit.php?id=' . $locId);
$tpl->setContent('loc_id',          $locId);
$tpl->setContent('loc_city',        htmlspecialchars($loc['city']));
$tpl->setContent('loc_province',    htmlspecialchars($loc['province'] ?? ''));
$tpl->setContent('loc_region',      htmlspecialchars($loc['region']   ?? ''));
$tpl->setContent('loc_country',     htmlspecialchars($loc['country']  ?? 'Italia'));
$tpl->setContent('loc_address',     htmlspecialchars($loc['address']  ?? ''));
$tpl->setContent('loc_lat',         $loc['latitude']  ?? '');
$tpl->setContent('loc_lng',         $loc['longitude'] ?? '');
$tpl->setContent('csrf_token',      generateCsrfToken());
$tpl->close();
