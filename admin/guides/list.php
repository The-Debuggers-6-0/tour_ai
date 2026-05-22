<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../template2.inc.php';

requireAdmin();

$tpl = new Template('html/admin/guides_list');
setAdminCommonContent($tpl);

$guides = queryAll(
    "SELECT g.id, g.first_name, g.last_name, g.profile_photo, g.specialization, g.languages, g.is_active,
            COALESCE(AVG(r.rating),0) AS avg_rating
     FROM guides g
     LEFT JOIN tours_has_guides tg ON tg.guides_id = g.id
     LEFT JOIN reviews r ON r.tours_id = tg.tours_id AND r.is_approved = 1
     GROUP BY g.id ORDER BY g.last_name"
);

foreach ($guides as $ga) {
    $name  = htmlspecialchars($ga['first_name'] . ' ' . $ga['last_name']);
    $initial = strtoupper(substr($ga['first_name'], 0, 1));
    $photo = $ga['profile_photo']
        ? '<img src="' . BASE_URL . htmlspecialchars($ga['profile_photo']) . '" alt="' . $name . '" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">'
        : '<div style="width:40px;height:40px;border-radius:50%;background:#8B4513;color:white;display:flex;align-items:center;justify-content:center;font-weight:700;">' . $initial . '</div>';
    $activeBadge = $ga['is_active']
        ? '<span class="badge badge-success">Attiva</span>'
        : '<span class="badge badge-secondary">Inattiva</span>';
    $tpl->setContent('ga_id',          (int)$ga['id']);
    $tpl->setContent('ga_photo_html',  $photo);
    $tpl->setContent('ga_name',        $name);
    $tpl->setContent('ga_spec',        htmlspecialchars($ga['specialization'] ?? ''));
    $tpl->setContent('ga_langs',       htmlspecialchars($ga['languages'] ?? ''));
    $tpl->setContent('ga_rating',      number_format($ga['avg_rating'], 1));
    $tpl->setContent('ga_active_badge',$activeBadge);
}

$tpl->setContent('csrf_token', generateCsrfToken());
$tpl->close();
