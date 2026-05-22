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
        header('Location: ' . BASE_URL . 'admin/locations/add.php'); exit;
    }
    $city     = sanitize($_POST['city']     ?? '');
    $province = sanitize($_POST['province'] ?? '');
    $region   = sanitize($_POST['region']   ?? '');
    $country  = sanitize($_POST['country']  ?? 'Italia');
    $address  = sanitize($_POST['address']  ?? '');
    $lat      = $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $lng      = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

    if ($city === '') { setFlash('Città obbligatoria.', 'error'); header('Location: ' . BASE_URL . 'admin/locations/add.php'); exit; }
    execute("INSERT INTO locations (city, province, region, country, address, latitude, longitude) VALUES (?,?,?,?,?,?,?)",
        'sssssdd', $city, $province, $region, $country, $address, $lat, $lng);
    setFlash('Luogo aggiunto.', 'success');
    header('Location: ' . BASE_URL . 'admin/locations/list.php'); exit;
}

$tpl = new Template('html/admin/locations_form');
setAdminCommonContent($tpl);
$tpl->setContent('locf_page_title', 'Aggiungi luogo');
$tpl->setContent('locf_action',     BASE_URL . 'admin/locations/add.php');
$tpl->setContent('loc_id',          0);
$tpl->setContent('loc_city',        '');
$tpl->setContent('loc_province',    '');
$tpl->setContent('loc_region',      '');
$tpl->setContent('loc_country',     'Italia');
$tpl->setContent('loc_address',     '');
$tpl->setContent('loc_lat',         '');
$tpl->setContent('loc_lng',         '');
$tpl->setContent('csrf_token',      generateCsrfToken());
$tpl->close();
