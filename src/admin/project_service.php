<?php
// ============================================================
//  src/admin/project_service.php — projects CRUD logic
// ============================================================

declare(strict_types=1);

require_once __DIR__ . '/../..//db.php';
require_once __DIR__ . '/upload.php';

function admin_add_project(PDO $pdo, array $post, array $files): void
{
    $title       = trim($post['title'] ?? '');
    $description = trim($post['description'] ?? '');
    $url         = trim($post['project_url'] ?? '#');
    $category    = normalize_admin_category((string)($post['category'] ?? 'web_app'));
    $tech_json   = admin_techs_to_json((string)($post['technologies'] ?? ''));

    if ($title === '' || $description === '') {
        throw new RuntimeException('Title and description are required.');
    }

    $image_path = 'projects/web/placeholder.png';
    if (!empty($files['image']['name'])) {
        $image_path = handle_admin_image_upload($files['image'], $category);
    }

    $pdo->prepare(
        'INSERT INTO projects (title,description,technologies,project_url,image_path,category)
         VALUES (?,?,?,?,?,?)'
    )->execute([$title, $description, $tech_json, $url, $image_path, $category]);
}

function admin_update_project(PDO $pdo, array $post, array $files): void
{
    $id          = (int)($post['id'] ?? 0);
    $title       = trim($post['title'] ?? '');
    $description = trim($post['description'] ?? '');
    $url         = trim($post['project_url'] ?? '#');
    $category    = normalize_admin_category((string)($post['category'] ?? 'web_app'));
    $tech_json   = admin_techs_to_json((string)($post['technologies'] ?? ''));

    if ($id === 0 || $title === '' || $description === '') {
        throw new RuntimeException('ID, title and description are required.');
    }

    // Keep existing image unless a new one is uploaded
    $row = $pdo->prepare('SELECT image_path FROM projects WHERE id = ? LIMIT 1');
    $row->execute([$id]);
    $existing   = $row->fetch();
    $image_path = $existing['image_path'] ?? 'uploads/placeholder.png';

    if (!empty($files['image']['name']) && ($files['image']['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK) {
        // Delete old file if it exists and is not the placeholder
        if (
            $image_path
            && strpos($image_path, 'placeholder') === false
            && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $image_path)
        ) {
            @unlink(__DIR__ . DIRECTORY_SEPARATOR . $image_path);
        }

        $image_path = handle_admin_image_upload($files['image'], $category);
    }

    $pdo->prepare(
        'UPDATE projects
         SET title=?, description=?, technologies=?, project_url=?, image_path=?, category=?
         WHERE id=?'
    )->execute([$title, $description, $tech_json, $url, $image_path, $category, $id]);
}

function admin_delete_project(PDO $pdo, array $post): void
{
    $id  = (int)($post['id'] ?? 0);

    $row = $pdo->prepare('SELECT image_path FROM projects WHERE id = ? LIMIT 1');
    $row->execute([$id]);
    $proj = $row->fetch();

    if (
        $proj
        && !empty($proj['image_path'])
        && strpos($proj['image_path'], 'placeholder') === false
        && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $proj['image_path'])
    ) {
        @unlink(__DIR__ . DIRECTORY_SEPARATOR . $proj['image_path']);
    }

    $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
}

function admin_techs_to_json(string $raw): string
{
    $arr = array_values(array_filter(array_map('trim', explode(',', $raw))));
    return json_encode($arr, JSON_UNESCAPED_UNICODE);
}


