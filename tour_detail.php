<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/template2.inc.php';

$slug = sanitize($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: ' . BASE_URL . 'tours.php');
    exit;
}

$tour = queryOne(
    "SELECT t.*, c.name AS category_name, l.city, l.region, l.address
     FROM tours t
     JOIN categories c ON c.id = t.categories_id
     JOIN locations l  ON l.id = t.locations_id
     WHERE t.slug = ? AND t.is_active = 1",
    's', $slug
);
if (!$tour) {
    header('Location: ' . BASE_URL . 'tours.php');
    exit;
}

// Handle review POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    requireLogin();
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token di sicurezza non valido.', 'error');
        header('Location: ' . BASE_URL . 'tour_detail.php?slug=' . urlencode($slug));
        exit;
    }
    $userId  = (int)$_SESSION['user_id'];
    $rating  = (int)($_POST['rating'] ?? 0);
    $title   = sanitize($_POST['review_title'] ?? '');
    $comment = sanitize($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5 || $title === '') {
        setFlash('Dati recensione non validi.', 'error');
    } else {
        // Check user has completed booking for this tour
        $hasBooked = queryOne(
            "SELECT b.id FROM bookings b
             JOIN time_slots s ON s.id = b.time_slots_id
             WHERE s.tours_id = ? AND b.users_id = ? AND b.status = 'confermata'",
            'ii', $tour['id'], $userId
        );
        if (!$hasBooked) {
            setFlash('Puoi recensire solo tour che hai prenotato e completato.', 'error');
        } else {
            $existing = queryOne(
                "SELECT id FROM reviews WHERE tours_id = ? AND users_id = ?",
                'ii', $tour['id'], $userId
            );
            if ($existing) {
                setFlash('Hai già lasciato una recensione per questo tour.', 'error');
            } else {
                execute(
                    "INSERT INTO reviews (tours_id, users_id, rating, title, comment, is_approved, created_at) VALUES (?,?,?,?,?,0,NOW())",
                    'iiiss', $tour['id'], $userId, $rating, $title, $comment
                );
                setFlash('Recensione inviata! Sarà visibile dopo approvazione.', 'success');
            }
        }
    }
    header('Location: ' . BASE_URL . 'tour_detail.php?slug=' . urlencode($slug));
    exit;
}

$tpl = new Template('html/tour_detail');
setCommonContent($tpl);

// Ratings aggregate
$ratingRow = queryOne(
    "SELECT COALESCE(AVG(rating),0) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE tours_id = ? AND is_approved = 1",
    'i', $tour['id']
);
$avgRating    = (float)$ratingRow['avg_r'];
$reviewsCount = (int)$ratingRow['cnt'];

$tpl->setContent('tour_title',           htmlspecialchars($tour['title']));
$tpl->setContent('tour_slug',            htmlspecialchars($tour['slug']));
$tpl->setContent('tour_price',           number_format($tour['price_per_person'], 2, ',', '.'));
$tpl->setContent('tour_duration_fmt',    formatDuration((int)$tour['duration_minutes']));
$tpl->setContent('tour_max_part',        (int)$tour['max_participants']);
$tpl->setContent('tour_city',            htmlspecialchars($tour['city']));
$tpl->setContent('tour_region',          htmlspecialchars($tour['region'] ?? ''));
$tpl->setContent('tour_meeting_point',   htmlspecialchars($tour['meeting_point'] ?? ''));
$tpl->setContent('tour_difficulty_badge', difficultyLabel($tour['difficulty_level']));
$tpl->setContent('tour_category',        htmlspecialchars($tour['category_name']));
$tpl->setContent('tour_rating',          number_format($avgRating, 1));
$tpl->setContent('tour_stars',           renderStars($avgRating));
$tpl->setContent('tour_reviews_count',   $reviewsCount);
// La descrizione è un campo rich-text che può contenere HTML (<p>, <br>, ...).
// Se contiene tag la mostriamo renderizzata; se è testo semplice manteniamo
// l'escape + i ritorni a capo per non introdurre rischi XSS.
$desc = $tour['description'] ?? '';
$tpl->setContent('tour_description',     $desc !== strip_tags($desc) ? $desc : nl2br(htmlspecialchars($desc)));

// Included services / what to bring as HTML lists
$included = $tour['included_services'] ?? '';
$tpl->setContent('tour_included_html', buildListHtml($included));
$tobring = $tour['what_to_bring'] ?? '';
$tpl->setContent('tour_tobring_html', buildListHtml($tobring));

$tpl->setContent('csrf_token',        generateCsrfToken());
$tpl->setContent('review_tour_id',    (int)$tour['id']);
$tpl->setContent('current_url_encoded', urlencode($_SERVER['REQUEST_URI']));

// Tour images
$images = queryAll("SELECT image_path, alt_text, is_cover FROM tour_images WHERE tours_id = ? ORDER BY is_cover DESC, sort_order ASC", 'i', $tour['id']);
$coverSet = false;
foreach ($images as $idx => $img) {
    if (!file_exists(__DIR__ . '/' . $img['image_path'])) {
        $img['image_path'] = 'css/placeholder.jpg';
    }
    $active = (!$coverSet && $img['is_cover']) || ($idx === 0 && !$coverSet) ? 'active' : '';
    if ($active) $coverSet = true;
    $tpl->setContent('img_path',   BASE_URL . htmlspecialchars($img['image_path']));
    $tpl->setContent('img_alt',    htmlspecialchars($img['alt_text'] ?? $tour['title']));
    $tpl->setContent('img_active', $active);
}
$cover = ($images && file_exists(__DIR__ . '/' . $images[0]['image_path'])) ? BASE_URL . $images[0]['image_path'] : BASE_URL . 'css/placeholder.jpg';
$tpl->setContent('cover_image', $cover);

// If no images, add one placeholder iteration
if (empty($images)) {
    $tpl->setContent('img_path',   BASE_URL . 'css/placeholder.jpg');
    $tpl->setContent('img_alt',    htmlspecialchars($tour['title']));
    $tpl->setContent('img_active', 'active');
}

// Guides
$guides = queryAll(
    "SELECT g.first_name, g.last_name, g.profile_photo, g.bio, g.languages, g.specialization, tg.is_lead_guide
     FROM tours_has_guides tg
     JOIN guides g ON g.id = tg.guides_id
     WHERE tg.tours_id = ? AND g.is_active = 1",
    'i', $tour['id']
);
foreach ($guides as $gd) {
    $name    = htmlspecialchars($gd['first_name'] . ' ' . $gd['last_name']);
    $initial = strtoupper(substr($gd['first_name'], 0, 1));
    $tpl->setContent('gd_name',      $name);
    $tpl->setContent('gd_photo',     $gd['profile_photo'] ? BASE_URL . htmlspecialchars($gd['profile_photo']) : '');
    $tpl->setContent('gd_bio',       htmlspecialchars($gd['bio'] ?? ''));
    $tpl->setContent('gd_langs',     htmlspecialchars($gd['languages'] ?? ''));
    $tpl->setContent('gd_lead_badge',$gd['is_lead_guide'] ? '<span class="badge badge-lead">Lead</span>' : '');
    $tpl->setContent('gd_spec',      htmlspecialchars($gd['specialization'] ?? ''));
    $tpl->setContent('gd_initial',   $initial);
}

// Slots (future, available)
$slots = queryAll(
    "SELECT s.id, s.slot_date, s.start_time, s.end_time,
            s.available_seats - COALESCE(SUM(CASE WHEN b.status='confermata' THEN b.total_participants ELSE 0 END),0) AS seats_left
     FROM time_slots s
     LEFT JOIN bookings b ON b.time_slots_id = s.id
     WHERE s.tours_id = ? AND s.status = 'disponibile' AND s.slot_date >= CURDATE()
     GROUP BY s.id
     HAVING seats_left > 0
     ORDER BY s.slot_date ASC, s.start_time ASC
     LIMIT 10",
    'i', $tour['id']
);
$tpl->setContent('no_slots', empty($slots) ? '1' : '');
$dayNames = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
foreach ($slots as $sl) {
    $dayName = $dayNames[(int)date('w', strtotime($sl['slot_date']))];
    $tpl->setContent('sl_id',         (int)$sl['id']);
    $tpl->setContent('sl_date',       formatDate($sl['slot_date']));
    $tpl->setContent('sl_day_name',   ucfirst($dayName));
    $tpl->setContent('sl_start',      formatTime($sl['start_time']));
    $tpl->setContent('sl_end',        formatTime($sl['end_time']));
    $tpl->setContent('sl_available',  (int)$sl['seats_left']);
    $tpl->setContent('sl_full_class', (int)$sl['seats_left'] <= 3 ? 'slot-card--low' : '');

    if ((int)$sl['seats_left'] <= 0) {
        $badge = '<span class="badge badge-danger">Pieno</span>';
    } elseif ((int)$sl['seats_left'] <= 3) {
        $badge = '<span class="badge badge-warning">Ultimi ' . (int)$sl['seats_left'] . ' posti</span>';
    } else {
        $badge = '<span style="color:#2E8B57;">✓ ' . (int)$sl['seats_left'] . ' posti liberi</span>';
    }
    $tpl->setContent('sl_badge', $badge);
}

// Reviews
$reviews = queryAll(
    "SELECT u.username, r.rating, r.title, r.comment, r.created_at
     FROM reviews r JOIN users u ON u.id = r.users_id
     WHERE r.tours_id = ? AND r.is_approved = 1
     ORDER BY r.created_at DESC",
    'i', $tour['id']
);
$tpl->setContent('no_reviews', empty($reviews) ? '1' : '');
foreach ($reviews as $rv) {
    $tpl->setContent('rv_author',  htmlspecialchars($rv['username']));
    $tpl->setContent('rv_stars',   renderStars($rv['rating']));
    $tpl->setContent('rv_title',   htmlspecialchars($rv['title']));
    $tpl->setContent('rv_comment', htmlspecialchars($rv['comment']));
    $tpl->setContent('rv_date',    formatDate($rv['created_at']));
}

// Can review: logged in + has confirmed booking + hasn't reviewed yet
$canReview = '';
if (isLoggedIn()) {
    $userId = (int)$_SESSION['user_id'];
    $hasBooked = queryOne(
        "SELECT b.id FROM bookings b JOIN time_slots s ON s.id = b.time_slots_id
         WHERE s.tours_id = ? AND b.users_id = ? AND b.status = 'confermata'",
        'ii', $tour['id'], $userId
    );
    $alreadyReviewed = queryOne("SELECT id FROM reviews WHERE tours_id = ? AND users_id = ?", 'ii', $tour['id'], $userId);
    if ($hasBooked && !$alreadyReviewed) $canReview = '1';
}
$tpl->setContent('can_review', $canReview);

$tpl->close();

function formatDuration(int $minutes): string {
    if ($minutes < 60) return $minutes . ' min';
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $m > 0 ? $h . 'h ' . $m . 'min' : $h . 'h';
}

function buildListHtml(string $text): string {
    if (trim($text) === '') return '';
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    if (empty($lines)) return '';
    return '<ul>' . implode('', array_map(fn($l) => '<li>' . htmlspecialchars($l) . '</li>', $lines)) . '</ul>';
}
