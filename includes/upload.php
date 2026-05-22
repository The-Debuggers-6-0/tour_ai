<?php
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_EXTS',  ['jpg', 'jpeg', 'png', 'webp']);

/**
 * Upload a single image file.
 * Returns the relative path (e.g. 'uploads/tours/abc123.jpg') or false on failure.
 */
function uploadImage(array $file, string $subdir, string $prefix = ''): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        setFlash('Il file supera la dimensione massima consentita (5 MB).', 'error');
        return false;
    }

    // Validate MIME via finfo
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_TYPES)) {
        setFlash('Tipo di file non consentito. Usa JPG, PNG o WEBP.', 'error');
        return false;
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTS)) {
        setFlash('Estensione non consentita.', 'error');
        return false;
    }

    $dir = UPLOAD_DIR . $subdir . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = ($prefix ? $prefix . '_' : '') . uniqid() . '.' . $ext;
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        setFlash('Errore durante il salvataggio del file.', 'error');
        return false;
    }

    return 'uploads/' . $subdir . '/' . $filename;
}

/**
 * Delete an uploaded image file (relative path).
 */
function deleteImage(string $path): void {
    $full = __DIR__ . '/../' . ltrim($path, '/');
    if (file_exists($full)) {
        unlink($full);
    }
}
