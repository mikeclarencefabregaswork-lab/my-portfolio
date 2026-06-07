<?php
// ============================================================
//  src/admin/experience_service.php — experiences CRUD logic
// ============================================================

declare(strict_types=1);

function admin_techs_to_json_ex(string $raw): string
{
    $arr = array_values(array_filter(array_map('trim', explode(',', $raw))));
    return json_encode($arr, JSON_UNESCAPED_UNICODE);
}

function admin_add_experience(PDO $pdo, array $post): void
{
    $year        = trim($post['year'] ?? '');
    $role        = trim($post['role'] ?? '');
    $company     = trim($post['company'] ?? '');
    $description = trim($post['description'] ?? '');
    $tech_json   = admin_techs_to_json_ex((string)($post['technologies'] ?? ''));

    if ($year === '' || $role === '' || $company === '') {
        throw new RuntimeException('Year, role and company are required.');
    }

    $pdo->prepare(
        'INSERT INTO experiences (year,role,company,description,technologies)
         VALUES (?,?,?,?,?)'
    )->execute([$year, $role, $company, $description, $tech_json]);
}

function admin_update_experience(PDO $pdo, array $post): void
{
    $id          = (int)($post['id'] ?? 0);
    $year        = trim($post['year'] ?? '');
    $role        = trim($post['role'] ?? '');
    $company     = trim($post['company'] ?? '');
    $description = trim($post['description'] ?? '');
    $tech_json   = admin_techs_to_json_ex((string)($post['technologies'] ?? ''));

    if ($id === 0 || $year === '' || $role === '' || $company === '') {
        throw new RuntimeException('ID, year, role and company are required.');
    }

    $pdo->prepare(
        'UPDATE experiences
         SET year=?, role=?, company=?, description=?, technologies=?
         WHERE id=?'
    )->execute([$year, $role, $company, $description, $tech_json, $id]);
}

function admin_delete_experience(PDO $pdo, array $post): void
{
    $id = (int)($post['id'] ?? 0);
    $pdo->prepare('DELETE FROM experiences WHERE id = ?')->execute([$id]);
}

