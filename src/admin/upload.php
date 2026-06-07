<?php
// ============================================================
//  src/admin/upload.php — safe image upload handling
// ============================================================

declare(strict_types=1);

// Physical upload paths under the project root:
//   projects/<category>/
define('ADMIN_UPLOAD_DIRS', [
    'web_app'       => 'projects' . DIRECTORY_SEPARATOR . 'web',
    'photo_editing' => 'projects' . DIRECTORY_SEPARATOR . 'photo',
    'video_editing' => 'projects' . DIRECTORY_SEPARATOR . 'video',
]);

// Web-relative equivalents (used as DB + src= paths)
define('ADMIN_UPLOAD_WEB', [
    'web_app'       => 'projects/web/',
    'photo_editing' => 'projects/photo/',
    'video_editing' => 'projects/video/',
]);

function normalize_admin_category(string $category): string
{
    $allowed = ['web_app', 'photo_editing', 'video_editing'];
    return in_array($category, $allowed, true) ? $category : 'web_app';
}

function get_admin_upload_dir(string $category): string
{
    $dirs = ADMIN_UPLOAD_DIRS;
    $sub  = $dirs[$category] ?? ('projects' . DIRECTORY_SEPARATOR . 'web');
    return rtrim(__DIR__ . DIRECTORY_SEPARATOR . $sub, '/\\') . DIRECTORY_SEPARATOR;
}

function get_admin_upload_web(string $category): string
{
    $map = ADMIN_UPLOAD_WEB;
    return $map[$category] ?? 'projects/web/';
}

function ensure_admin_upload_dir(string $category): void
{
    $dir       = get_admin_upload_dir($category);
    $dir_check = rtrim($dir, '/\\');

    if (!is_dir($dir_check)) {
        if (!mkdir($dir_check, 0755, true)) {
            throw new RuntimeException(
                "Could not create folder: {$dir_check} — " .
                "Please create it manually in your XAMPP htdocs project folder."
            );
        }
    }

    if (!is_writable($dir_check)) {
        throw new RuntimeException(
            "Folder is not writable: {$dir_check} — " .
            "On Windows right-click the folder → Properties → Security and give write access."
        );
    }
}

function check_admin_upload_error(int $code): void
{
    $map = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini (' . ini_get('upload_max_filesize') . '). Open php.ini and raise that value.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form MAX_FILE_SIZE limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded. Try again.',
        UPLOAD_ERR_NO_FILE    => 'No file was sent.',
        UPLOAD_ERR_NO_TMP_DIR => 'PHP temp directory is missing. Check upload_tmp_dir in php.ini.',
        UPLOAD_ERR_CANT_WRITE => 'PHP could not write the temp file to disk. Check disk space.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
    ];

    if ($code !== UPLOAD_ERR_OK) {
        throw new RuntimeException($map[$code] ?? "Upload failed with error code $code.");
    }
}

function handle_admin_image_upload(array $file, string $category): string
{
    $category = normalize_admin_category($category);

    // Step 1 — PHP-level upload errors
    check_admin_upload_error($file['error']);

    // Step 2 — category folder exists + writable
    ensure_admin_upload_dir($category);
    $upload_dir = get_admin_upload_dir($category);
    $upload_web = get_admin_upload_web($category);

    // Step 3 — validate MIME from file bytes
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException("Invalid file type ($mime). Only JPEG, PNG, WebP and GIF are allowed.");
    }

    // Step 4 — 4MB hard cap
    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Image is ' . round($file['size'] / 1048576, 1) . ' MB — maximum is 4 MB.');
    }

    // Step 5 — random filename
    $ext_map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $filename = bin2hex(random_bytes(12)) . '.' . $ext_map[$mime];

    $dest = rtrim($upload_dir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    // Step 6 — safe move from temp
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException(
            "move_uploaded_file() failed saving to: {$dest} — " .
            "Check that the folder exists, is writable, and that open_basedir in php.ini is not restricting this path."
        );
    }

    // Return clean forward-slash web path (DB + src)
    return rtrim($upload_web, '/') . '/' . $filename;
}

