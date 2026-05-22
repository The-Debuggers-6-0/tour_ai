<?php

function setFlash(string $message, string $type = 'success'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
}

function getFlash(): array {
    if (isset($_SESSION['flash_message'])) {
        $msg  = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return [$msg, $type];
    }
    return ['', ''];
}

function sanitize(string $value): string {
    return trim($value);
}

function generateBookingCode(): string {
    return 'TG-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
}

function formatDate(string $date): string {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

function formatDateLong(string $date): string {
    if (!$date) return '';
    $days   = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
    $months = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    $ts     = strtotime($date);
    return $days[date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

function formatTime(string $time): string {
    if (!$time) return '';
    return substr($time, 0, 5);
}

function renderStars(float $rating, int $max = 5): string {
    $html = '<span class="stars" aria-label="' . $rating . ' su ' . $max . '">';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= floor($rating))            $html .= '<span class="star full">★</span>';
        elseif ($i - $rating < 1)            $html .= '<span class="star half">★</span>';
        else                                 $html .= '<span class="star empty">☆</span>';
    }
    return $html . '</span>';
}

function difficultyLabel(string $level): string {
    return match($level) {
        'facile'    => '<span class="badge badge-success">Facile</span>',
        'medio'     => '<span class="badge badge-warning">Medio</span>',
        'difficile' => '<span class="badge badge-danger">Difficile</span>',
        default     => '<span class="badge">' . htmlspecialchars($level) . '</span>',
    };
}

function statusLabel(string $status): string {
    return match($status) {
        'in_attesa'   => '<span class="badge badge-warning">In attesa</span>',
        'confermata'  => '<span class="badge badge-success">Confermata</span>',
        'cancellata'  => '<span class="badge badge-danger">Cancellata</span>',
        'completata'  => '<span class="badge badge-info">Completata</span>',
        'disponibile' => '<span class="badge badge-success">Disponibile</span>',
        'pieno'       => '<span class="badge badge-danger">Pieno</span>',
        default       => '<span class="badge">' . htmlspecialchars($status) . '</span>',
    };
}

function buildPagination(int $currentPage, int $total, int $perPage, array $queryParams = []): string {
    if ($total <= $perPage) return '';
    $pages = (int)ceil($total / $perPage);
    $base  = array_filter($queryParams, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY);
    $qs    = http_build_query($base);
    $sep   = $qs ? '&' : '';
    $html  = '<nav class="pagination"><ul>';
    if ($currentPage > 1) {
        $html .= '<li><a href="?' . $qs . $sep . 'page=' . ($currentPage - 1) . '">&laquo;</a></li>';
    }
    for ($i = 1; $i <= $pages; $i++) {
        $active = $i === $currentPage ? ' class="active"' : '';
        $html .= '<li' . $active . '><a href="?' . $qs . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    if ($currentPage < $pages) {
        $html .= '<li><a href="?' . $qs . $sep . 'page=' . ($currentPage + 1) . '">&raquo;</a></li>';
    }
    return $html . '</ul></nav>';
}

function slugify(string $text): string {
    $text = mb_strtolower(trim($text), 'UTF-8');
    $map  = ['à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
             'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
             'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

function ensureUniqueSlug(string $slug, string $table, string $column = 'slug', ?int $excludeId = null): string {
    $base  = $slug;
    $count = 0;
    do {
        $test = $count === 0 ? $base : $base . '-' . $count;
        $sql  = "SELECT id FROM `{$table}` WHERE `{$column}` = ?";
        $args = ['s', $test];
        if ($excludeId !== null) {
            $sql   .= ' AND id != ?';
            $args   = ['si', $test, $excludeId];
        }
        $row = queryOne($sql, ...$args);
        $count++;
    } while ($row !== null);
    return $test;
}
