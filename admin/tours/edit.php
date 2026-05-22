<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$tourId = (int)($_GET['id'] ?? 0);
if ($tourId <= 0) {
    header('Location: ' . BASE_URL . 'admin/tours/list.php');
    exit;
}

$tour = queryOne("SELECT * FROM tours WHERE id = ?", 'i', $tourId);
if (!$tour) {
    setFlash('Tour non trovato.', 'error');
    header('Location: ' . BASE_URL . 'admin/tours/list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/tours/edit.php?id=' . $tourId);
        exit;
    }

    $title       = sanitize($_POST['title']            ?? '');
    $slug        = sanitize($_POST['slug']             ?? '');
    $shortDesc   = sanitize($_POST['short_description']?? '');
    $description = sanitize($_POST['description']      ?? '');
    $price       = (float)($_POST['price_per_person']  ?? 0);
    $duration    = (int)($_POST['duration_minutes']    ?? 0);
    $maxPart     = (int)($_POST['max_participants']    ?? 0);
    $difficulty  = sanitize($_POST['difficulty_level'] ?? 'facile');
    $catId       = (int)($_POST['categories_id']       ?? 0);
    $locId       = (int)($_POST['locations_id']        ?? 0);
    $meeting     = sanitize($_POST['meeting_point']    ?? '');
    $included    = sanitize($_POST['included_services']?? '');
    $tobring     = sanitize($_POST['what_to_bring']    ?? '');
    $isActive    = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '' || $price <= 0 || $duration <= 0 || $catId <= 0 || $locId <= 0 || $description === '') {
        setFlash('Compila tutti i campi obbligatori.', 'error');
        header('Location: ' . BASE_URL . 'admin/tours/edit.php?id=' . $tourId);
        exit;
    }

    $slug = ensureUniqueSlug(slugify($slug ?: $title), 'tours', 'slug', $tourId);

    execute(
        "UPDATE tours SET title=?, slug=?, short_description=?, description=?, price_per_person=?,
          duration_minutes=?, max_participants=?, difficulty_level=?, categories_id=?, locations_id=?,
          meeting_point=?, included_services=?, what_to_bring=?, is_active=? WHERE id=?",
        'ssssdiisiisssii',
        $title, $slug, $shortDesc, $description, $price, $duration,
        $maxPart, $difficulty, $catId, $locId, $meeting, $included, $tobring, $isActive, $tourId
    );

    // Update guides
    execute("DELETE FROM tours_has_guides WHERE tours_id = ?", 'i', $tourId);
    $guideIds = $_POST['guide_ids'] ?? [];
    $leadId   = (int)($_POST['lead_guide_id'] ?? 0);
    foreach ($guideIds as $gid) {
        $gid = (int)$gid;
        $isLead = ($gid === $leadId) ? 1 : 0;
        execute("INSERT INTO tours_has_guides (tours_id, guides_id, is_lead_guide) VALUES (?,?,?)", 'iii', $tourId, $gid, $isLead);
    }

    // Delete selected images
    $deleteIds = $_POST['delete_images'] ?? [];
    foreach ($deleteIds as $did) {
        $img = queryOne("SELECT image_path FROM tour_images WHERE id = ? AND tours_id = ?", 'ii', (int)$did, $tourId);
        if ($img) {
            $fullPath = __DIR__ . '/../../' . $img['image_path'];
            if (file_exists($fullPath)) unlink($fullPath);
            execute("DELETE FROM tour_images WHERE id = ?", 'i', (int)$did);
        }
    }

    // Cover assignment for existing images
    $coverExistingId = (int)($_POST['cover_image_existing'] ?? 0);
    if ($coverExistingId > 0) {
        execute("UPDATE tour_images SET is_cover = 0 WHERE tours_id = ?", 'i', $tourId);
        execute("UPDATE tour_images SET is_cover = 1 WHERE id = ? AND tours_id = ?", 'ii', $coverExistingId, $tourId);
    }

    // Upload new images
    if (!empty($_FILES['tour_images']['name'][0])) {
        $files = reformatFiles($_FILES['tour_images']);
        foreach ($files as $file) {
            $path = uploadImage($file, 'tours', 'tour_');
            if ($path) {
                $hasCover = queryOne("SELECT id FROM tour_images WHERE tours_id = ? AND is_cover = 1", 'i', $tourId);
                $isCover  = $hasCover ? 0 : 1;
                execute("INSERT INTO tour_images (tours_id, image_path, is_cover, sort_order) VALUES (?,?,?,0)", 'ssi', $tourId, $path, $isCover);
            }
        }
    }

    setFlash('Tour aggiornato.', 'success');
    header('Location: ' . BASE_URL . 'admin/tours/list.php');
    exit;
}

// Render form
$tpl = new Template('html/admin/tours_form');
setAdminCommonContent($tpl);

$diff = $tour['difficulty_level'];
$tpl->setContent('form_page_title',  'Modifica tour');
$tpl->setContent('form_action',      BASE_URL . 'admin/tours/edit.php?id=' . $tourId);
$tpl->setContent('t_id',             $tourId);
$tpl->setContent('t_title',          htmlspecialchars($tour['title']));
$tpl->setContent('t_slug',           htmlspecialchars($tour['slug']));
$tpl->setContent('t_short_desc',     htmlspecialchars($tour['short_description'] ?? ''));
$tpl->setContent('t_description',    htmlspecialchars($tour['description']));
$tpl->setContent('t_price',          $tour['price_per_person']);
$tpl->setContent('t_duration',       (int)$tour['duration_minutes']);
$tpl->setContent('t_max_part',       (int)$tour['max_participants']);
$tpl->setContent('t_diff_facile',    $diff === 'facile'    ? 'selected' : '');
$tpl->setContent('t_diff_medio',     $diff === 'medio'     ? 'selected' : '');
$tpl->setContent('t_diff_difficile', $diff === 'difficile' ? 'selected' : '');
$tpl->setContent('t_meeting',        htmlspecialchars($tour['meeting_point'] ?? ''));
$tpl->setContent('t_included',       htmlspecialchars($tour['included_services'] ?? ''));
$tpl->setContent('t_tobring',        htmlspecialchars($tour['what_to_bring'] ?? ''));
$tpl->setContent('t_active_checked', $tour['is_active'] ? 'checked' : '');
$tpl->setContent('csrf_token',       generateCsrfToken());

$cats = queryAll("SELECT id, name FROM categories ORDER BY name");
foreach ($cats as $co) {
    $tpl->setContent('co_id',       (int)$co['id']);
    $tpl->setContent('co_name',     htmlspecialchars($co['name']));
    $tpl->setContent('co_selected', (int)$co['id'] === (int)$tour['categories_id'] ? 'selected' : '');
}
$locs = queryAll("SELECT id, city FROM locations ORDER BY city");
foreach ($locs as $lo) {
    $tpl->setContent('lo_id',       (int)$lo['id']);
    $tpl->setContent('lo_city',     htmlspecialchars($lo['city']));
    $tpl->setContent('lo_selected', (int)$lo['id'] === (int)$tour['locations_id'] ? 'selected' : '');
}

$assignedGuides = queryAll("SELECT guides_id, is_lead_guide FROM tours_has_guides WHERE tours_id = ?", 'i', $tourId);
$assignedMap = array_column($assignedGuides, null, 'guides_id');
$guides = queryAll("SELECT id, first_name, last_name FROM guides WHERE is_active = 1 ORDER BY last_name");
foreach ($guides as $go) {
    $isAssigned = isset($assignedMap[$go['id']]);
    $tpl->setContent('go_id',      (int)$go['id']);
    $tpl->setContent('go_name',    htmlspecialchars($go['first_name'] . ' ' . $go['last_name']));
    $tpl->setContent('go_checked', $isAssigned ? 'checked' : '');
    $tpl->setContent('go_lead',    ($isAssigned && $assignedMap[$go['id']]['is_lead_guide']) ? 'checked' : '');
}

$images = queryAll("SELECT id, image_path, alt_text, is_cover FROM tour_images WHERE tours_id = ? ORDER BY is_cover DESC, sort_order ASC", 'i', $tourId);
$tpl->setContent('has_existing_images', !empty($images) ? '1' : '');
if (!empty($images)) {
    foreach ($images as $ei) {
        $tpl->setContent('ei_id',            (int)$ei['id']);
        $tpl->setContent('ei_path',          htmlspecialchars($ei['image_path']));
        $tpl->setContent('ei_alt',           htmlspecialchars($ei['alt_text'] ?? $tour['title']));
        $tpl->setContent('ei_cover_checked', $ei['is_cover'] ? 'checked' : '');
    }
} else {
    $tpl->setContent('ei_id', 0); $tpl->setContent('ei_path', ''); $tpl->setContent('ei_alt', ''); $tpl->setContent('ei_cover_checked', '');
}

$tpl->close();

function reformatFiles(array $files): array {
    $result = [];
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $result[] = ['name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
        }
    }
    return $result;
}
