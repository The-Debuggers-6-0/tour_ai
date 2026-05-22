<?php
define('DB_HOST',       'localhost');
define('DB_USER',       'root');
define('DB_PASS',       '');
define('DB_NAME',       'tour_guidati');
define('BASE_URL',      'http://localhost/tour_ai/');
define('SITE_NAME',     'Tour Guidati');
define('UPLOAD_DIR',    __DIR__ . '/uploads/');
define('UPLOAD_URL',    BASE_URL . 'uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB

session_start();

// Ensure $_SESSION['user'] is always an array (required by template2.inc.php)
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [];
}
