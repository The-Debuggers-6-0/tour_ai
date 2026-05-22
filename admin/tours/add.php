<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token non valido.', 'error');
        header('Location: ' . BASE_URL . 'admin/tours/add.php');
        exit;
    }
    saveTour(0);
}

renderTourForm(0);

function saveTour(int $tourId): void {
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
        header('Location: ' . BASE_URL . 'admin/tours/add.php');
        exit;
    }

    $slug = ensureUniqueSlug(slugify($slug ?: $title), 'tours', 'slug', $tourId ?: null);

    execute(
        "INSERT INTO tours (title, slug, short_description, description, price_per_person, duration_minutes,
          max_participants, difficulty_level, categories_id, locations_id, meeting_point, included_services,
          what_to_bring, is_active, created_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
        'ssssdiisiisssi',
        $title, $slug, $shortDesc, $description, $price, $duration,
        $maxPart, $difficulty, $catId, $locId, $meeting, $included, $tobring, $isActive
    );

    $conn   = getConnection();
    $newId  = $conn->insert_id;

    // Guides
    $guideIds = $_POST['guide_ids'] ?? [];
    $leadId   = (int)($_POST['lead_guide_id'] ?? 0);
    foreach ($guideIds as $gid) {
        $gid = (int)$gid;
        $isLead = ($gid === $leadId) ? 1 : 0;
        execute("INSERT INTO tours_has_guides (tours_id, guides_id, is_lead_guide) VALUES (?,?,?)", 'iii', $newId, $gid, $isLead);
    }

    // Images
    if (!empty($_FILES['tour_images']['name'][0])) {
        $files = reformatFiles($_FILES['tour_images']);
        $coverSet = false;
        foreach ($files as $file) {
            $path = uploadImage($file, 'tours', 'tour_');
            if ($path) {
                $isCover = $coverSet ? 0 : 1;
                $coverSet = true;
                execute("INSERT INTO tour_images (tours_id, image_path, is_cover, sort_order) VALUES (?,?,?,0)", 'ssi', $newId, $path, $isCover);
            }
        }
    }

    setFlash('Tour creato con successo.', 'success');
    header('Location: ' . BASE_URL . 'admin/tours/list.php');
    exit;
}

function renderTourForm(int $tourId): void {
    $tpl = new Template('html/admin/tours_form');
    setAdminCommonContent($tpl);

    $tpl->setContent('form_page_title', 'Aggiungi tour');
    $tpl->setContent('form_action',     BASE_URL . 'admin/tours/add.php');
    $tpl->setContent('t_id',            0);
    $tpl->setContent('t_title',         '');
    $tpl->setContent('t_slug',          '');
    $tpl->setContent('t_short_desc',    '');
    $tpl->setContent('t_description',   '');
    $tpl->setContent('t_price',         '');
    $tpl->setContent('t_duration',      '');
    $tpl->setContent('t_max_part',      '');
    $tpl->setContent('t_diff_facile',   'selected');
    $tpl->setContent('t_diff_medio',    '');
    $tpl->setContent('t_diff_difficile','');
    $tpl->setContent('t_meeting',       '');
    $tpl->setContent('t_included',      '');
    $tpl->setContent('t_tobring',       '');
    $tpl->setContent('t_active_checked','checked');
    $tpl->setContent('has_existing_images', '');
    $tpl->setContent('csrf_token',      generateCsrfToken());

    $cats = queryAll("SELECT id, name FROM categories ORDER BY name");
    foreach ($cats as $co) {
        $tpl->setContent('co_id',       (int)$co['id']);
        $tpl->setContent('co_name',     htmlspecialchars($co['name']));
        $tpl->setContent('co_selected', '');
    }
    $locs = queryAll("SELECT id, city FROM locations ORDER BY city");
    foreach ($locs as $lo) {
        $tpl->setContent('lo_id',       (int)$lo['id']);
        $tpl->setContent('lo_city',     htmlspecialchars($lo['city']));
        $tpl->setContent('lo_selected', '');
    }
    $guides = queryAll("SELECT id, first_name, last_name FROM guides WHERE is_active = 1 ORDER BY last_name");
    foreach ($guides as $go) {
        $tpl->setContent('go_id',      (int)$go['id']);
        $tpl->setContent('go_name',    htmlspecialchars($go['first_name'] . ' ' . $go['last_name']));
        $tpl->setContent('go_checked', '');
        $tpl->setContent('go_lead',    '');
    }
    // Existing images placeholder (none for new tour)
    $tpl->setContent('ei_id',            0);
    $tpl->setContent('ei_path',          '');
    $tpl->setContent('ei_alt',           '');
    $tpl->setContent('ei_cover_checked', '');

    $tpl->close();
}

function reformatFiles(array $files): array {
    $result = [];
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $result[] = [
                'name'     => $name,
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }
    }
    return $result;
}
