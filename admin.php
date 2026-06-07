<?php
// ============================================================
//  admin.php  —  Protected admin dashboard (CRUD)
// ============================================================
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function validate_csrf_from_request(): void {
    if (empty($_POST['csrf_token']) || !hash_equals(csrf_token(), (string)$_POST['csrf_token'])) {
        http_response_code(403);
        exit('CSRF validation failed.');
    }
}

require_once __DIR__ . '/db.php';

$pdo      = get_pdo();
$msg      = '';
$msg_type = 'green';

// ─────────────────────────────────────────────────────────────
//  HELPERS
// ─────────────────────────────────────────────────────────────
// ── Category → subfolder map ─────────────────────────────────────────────────
//  Physical paths under D:\xampp\htdocs\my-portfolio\projects\
//  The web-accessible relative path stored in the DB is  projects/web/file.jpg  etc.
define('UPLOAD_DIRS', [
    'web_app'       => 'projects' . DIRECTORY_SEPARATOR . 'web',
    'photo_editing' => 'projects' . DIRECTORY_SEPARATOR . 'photo',
    'video_editing' => 'projects' . DIRECTORY_SEPARATOR . 'video',
]);

// Web-relative equivalents (used for src= paths stored in DB)
define('UPLOAD_WEB', [
    'web_app'       => 'projects/web/',
    'photo_editing' => 'projects/photo/',
    'video_editing' => 'projects/video/',
]);

function get_upload_dir(string $category): string {
    $dirs = UPLOAD_DIRS;
    $sub  = $dirs[$category] ?? ('projects' . DIRECTORY_SEPARATOR . 'web');
    // rtrim then re-add ensures exactly one trailing separator — no double-slash on Windows
    return rtrim(__DIR__ . DIRECTORY_SEPARATOR . $sub, '/\\') . DIRECTORY_SEPARATOR;
}

function get_upload_web(string $category): string {
    $map = UPLOAD_WEB;
    return $map[$category] ?? 'projects/web/';
}

function ensure_upload_dir(string $category): void {
    $dir      = get_upload_dir($category);
    // Windows: is_dir() and is_writable() are unreliable with trailing backslash
    // Use rtrim for the check, keep trailing sep for move_uploaded_file destination
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

function check_upload_error(int $code): void {
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

function normalize_category(string $category): string {
    $allowed = ['web_app', 'photo_editing', 'video_editing'];
    return in_array($category, $allowed, true) ? $category : 'web_app';
}

function handle_image_upload(array $file, string $category): string {
    $category = normalize_category($category);

    // Step 1 — catch PHP-level errors
    check_upload_error($file['error']);

    // Step 2 — ensure the category subfolder exists and is writable
    ensure_upload_dir($category);
    $upload_dir = get_upload_dir($category);
    $upload_web = get_upload_web($category);

    // Step 3 — validate real MIME type from actual file bytes
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException("Invalid file type ($mime). Only JPEG, PNG, WebP and GIF are allowed.");
    }

    // Step 4 — 4 MB hard cap
    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Image is ' . round($file['size'] / 1048576, 1) . ' MB — maximum is 4 MB.');
    }

    // Step 5 — safe random filename
    $ext_map  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $filename = bin2hex(random_bytes(12)) . '.' . $ext_map[$mime];
    // rtrim + one separator = exactly one backslash/slash before filename on any OS
    $dest = rtrim($upload_dir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    // Step 6 — move from PHP temp to the correct category folder
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException(
            "move_uploaded_file() failed saving to: {$dest} — " .
            "Check that the folder exists, is writable, and that open_basedir in php.ini is not restricting this path."
        );
    }

    // Return a clean forward-slash web path stored in the DB and used in src= attributes
    return rtrim($upload_web, '/') . '/' . $filename;
}

function techs_to_json(string $raw): string {
    $arr = array_values(array_filter(array_map('trim', explode(',', $raw))));
    return json_encode($arr, JSON_UNESCAPED_UNICODE);
}

function techs_to_string(string $json): string {
    $arr = json_decode($json, true);
    return is_array($arr) ? implode(', ', $arr) : '';
}

// ─────────────────────────────────────────────────────────────
//  POST HANDLER
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_from_request();
    $action = $_POST['action'] ?? '';

    try {

        // ── ADD PROJECT ──────────────────────────────────────
        if ($action === 'add_project') {
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $url         = trim($_POST['project_url'] ?? '#');
            $category    = $_POST['category'] ?? 'web_app';
            $tech_json   = techs_to_json($_POST['technologies'] ?? '');

            if ($title === '' || $description === '')
                throw new RuntimeException('Title and description are required.');

            $category = normalize_category($category);

            $image_path = 'projects/web/placeholder.png';
            if (!empty($_FILES['image']['name']))
                $image_path = handle_image_upload($_FILES['image'], $category);

            $pdo->prepare('INSERT INTO projects (title,description,technologies,project_url,image_path,category)
                           VALUES (?,?,?,?,?,?)')
                ->execute([$title, $description, $tech_json, $url, $image_path, $category]);
            $msg = 'Project added successfully.';
        }

        // ── UPDATE PROJECT ───────────────────────────────────
        elseif ($action === 'update_project') {
            $id          = (int)($_POST['id'] ?? 0);
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $url         = trim($_POST['project_url'] ?? '#');
            $category    = $_POST['category'] ?? 'web_app';
            $tech_json   = techs_to_json($_POST['technologies'] ?? '');

            $category = normalize_category($category);

            if ($id === 0 || $title === '' || $description === '')
                throw new RuntimeException('ID, title and description are required.');

            // Keep existing image unless a new one is uploaded
            $row = $pdo->prepare('SELECT image_path FROM projects WHERE id = ? LIMIT 1');
            $row->execute([$id]);
            $existing   = $row->fetch();
            $image_path = $existing['image_path'] ?? 'uploads/placeholder.png';

            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Delete old file if it exists and is not the placeholder
                if ($image_path && strpos($image_path, 'placeholder') === false
                    && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $image_path))
                    @unlink(__DIR__ . DIRECTORY_SEPARATOR . $image_path);
                $image_path = handle_image_upload($_FILES['image'], $category);
            }

            $pdo->prepare('UPDATE projects
                           SET title=?, description=?, technologies=?, project_url=?, image_path=?, category=?
                           WHERE id=?')
                ->execute([$title, $description, $tech_json, $url, $image_path, $category, $id]);
            $msg = 'Project updated successfully.';
        }

        // ── DELETE PROJECT ───────────────────────────────────
        elseif ($action === 'delete_project') {
            $id  = (int)($_POST['id'] ?? 0);
            $row = $pdo->prepare('SELECT image_path FROM projects WHERE id = ? LIMIT 1');
            $row->execute([$id]);
            $proj = $row->fetch();
            if ($proj && !empty($proj['image_path'])
                && strpos($proj['image_path'], 'placeholder') === false
                && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $proj['image_path']))
                @unlink(__DIR__ . DIRECTORY_SEPARATOR . $proj['image_path']);
            $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
            $msg = 'Project deleted.';
        }

        // ── ADD EXPERIENCE ───────────────────────────────────
        elseif ($action === 'add_experience') {
            $year        = trim($_POST['year'] ?? '');
            $role        = trim($_POST['role'] ?? '');
            $company     = trim($_POST['company'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tech_json   = techs_to_json($_POST['technologies'] ?? '');

            if ($year === '' || $role === '' || $company === '')
                throw new RuntimeException('Year, role and company are required.');

            $pdo->prepare('INSERT INTO experiences (year,role,company,description,technologies)
                           VALUES (?,?,?,?,?)')
                ->execute([$year, $role, $company, $description, $tech_json]);
            $msg = 'Experience added.';
        }

        // ── UPDATE EXPERIENCE ────────────────────────────────
        elseif ($action === 'update_experience') {
            $id          = (int)($_POST['id'] ?? 0);
            $year        = trim($_POST['year'] ?? '');
            $role        = trim($_POST['role'] ?? '');
            $company     = trim($_POST['company'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tech_json   = techs_to_json($_POST['technologies'] ?? '');

            if ($id === 0 || $year === '' || $role === '' || $company === '')
                throw new RuntimeException('ID, year, role and company are required.');

            $pdo->prepare('UPDATE experiences
                           SET year=?, role=?, company=?, description=?, technologies=?
                           WHERE id=?')
                ->execute([$year, $role, $company, $description, $tech_json, $id]);
            $msg = 'Experience updated successfully.';
        }

        // ── DELETE EXPERIENCE ────────────────────────────────
        elseif ($action === 'delete_experience') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM experiences WHERE id = ?')->execute([$id]);
            $msg = 'Experience deleted.';
        }

        // ── LOGOUT ───────────────────────────────────────────
        elseif ($action === 'logout') {
            session_destroy();
            header('Location: login.php');
            exit;
        }

    } catch (RuntimeException $e) {
        $msg      = $e->getMessage();
        $msg_type = 'red';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $msg      = 'Database error — check server logs.';
        $msg_type = 'red';
    }
}

// ─────────────────────────────────────────────────────────────
//  Which edit panels are open?  (driven by GET params so the
//  page reloads after POST and the correct row stays open)
// ─────────────────────────────────────────────────────────────
$edit_project_id = (int)($_GET['edit_project'] ?? 0);
$edit_exp_id     = (int)($_GET['edit_exp']     ?? 0);
$active_tab      = $_GET['tab'] ?? 'projects';

// ─────────────────────────────────────────────────────────────
//  FETCH DATA
// ─────────────────────────────────────────────────────────────
$projects    = $pdo->query('SELECT * FROM projects    ORDER BY category, sort_order, id')->fetchAll();
$experiences = $pdo->query('SELECT * FROM experiences ORDER BY sort_order, id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel — MCF Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'DM Sans', sans-serif; background: #0a0a0a; color: #e5e5e5; }

    /* ── Tabs ── */
    .tab-btn        { border-bottom: 2px solid transparent; color: #737373; padding: .6rem 1.25rem; font-size:.875rem; font-weight:500; cursor:pointer; transition:color .15s,border-color .15s; }
    .tab-btn.active { border-color: rgb(168,85,247); color: #fff; }
    .tab-btn:hover  { color: #d4d4d4; }

    /* ── Form controls ── */
    .fi {
      display:block; width:100%;
      background:rgba(23,23,23,.8);
      border:1px solid #404040;
      border-radius:.5rem;
      padding:.6rem 1rem;
      color:#e5e5e5;
      font-size:.875rem;
      transition:border-color .15s, box-shadow .15s;
    }
    .fi:focus { outline:none; border-color:rgba(168,85,247,.7); box-shadow:0 0 0 2px rgba(168,85,247,.18); }
    label.lbl { display:block; font-size:.7rem; letter-spacing:.08em; text-transform:uppercase; color:#737373; margin-bottom:.35rem; }

    /* ── Buttons ── */
    .btn-save {
      background: linear-gradient(135deg,rgb(236,72,153),rgb(168,85,247));
      color:#fff; border-radius:.5rem; padding:.5rem 1.25rem;
      font-size:.8rem; font-weight:500; cursor:pointer;
      transition:opacity .15s,transform .1s; white-space:nowrap;
    }
    .btn-save:hover  { opacity:.88; }
    .btn-save:active { transform:scale(.97); }

    .btn-cancel {
      background:transparent; border:1px solid #404040; color:#a3a3a3;
      border-radius:.5rem; padding:.5rem 1.1rem;
      font-size:.8rem; cursor:pointer; transition:background .15s,color .15s;
    }
    .btn-cancel:hover { background:#1a1a1a; color:#e5e5e5; }

    .btn-edit {
      background:rgba(23,37,84,.6); border:1px solid rgb(37,99,235);
      color:rgb(147,197,253); border-radius:.4rem; padding:.28rem .65rem;
      font-size:.72rem; cursor:pointer; transition:background .15s; white-space:nowrap;
    }
    .btn-edit:hover { background:rgba(37,99,235,.3); }

    .btn-del {
      background:rgba(127,29,29,.6); border:1px solid rgb(185,28,28);
      color:rgb(252,165,165); border-radius:.4rem; padding:.28rem .65rem;
      font-size:.72rem; cursor:pointer; transition:background .15s; white-space:nowrap;
    }
    .btn-del:hover { background:rgba(185,28,28,.55); }

    /* ── Inline edit panel ── */
    .edit-panel {
      background:#111;
      border:1px solid #2a2a2a;
      border-radius:.75rem;
      padding:1.25rem 1.5rem;
      margin-top:.5rem;
      margin-bottom:.75rem;
    }

    /* ── Table ── */
    .data-table { width:100%; font-size:.85rem; border-collapse:collapse; }
    .data-table th {
      text-align:left; padding:.65rem 1rem;
      font-size:.68rem; letter-spacing:.08em; text-transform:uppercase;
      color:#737373; font-weight:500;
      border-bottom:1px solid #262626; background:rgba(23,23,23,.7);
    }
    .data-table td { padding:.7rem 1rem; border-bottom:1px solid #1a1a1a; vertical-align:top; }
    .data-table tr:last-child td { border-bottom:none; }
    .data-table tbody tr:hover { background:rgba(255,255,255,.02); }

    /* ── Tech badge ── */
    .badge { display:inline-block; background:#1a1a1a; border:1px solid #2a2a2a; border-radius:.3rem; padding:.15rem .5rem; font-size:.7rem; color:#a78bfa; margin:.1rem .1rem 0 0; }

    /* ── Category chip ── */
    .chip-web   { background:rgba(8,51,68,.5);   color:#67e8f9; border:1px solid rgba(8,145,178,.4); }
    .chip-photo { background:rgba(59,7,100,.5);  color:#d8b4fe; border:1px solid rgba(126,34,206,.4); }
    .chip-video { background:rgba(76,5,25,.5);   color:#fda4af; border:1px solid rgba(190,18,60,.4); }
    .chip { border-radius:9999px; padding:.2rem .6rem; font-size:.68rem; font-weight:500; white-space:nowrap; }
    /* ── Toast notifications ── */
    #toast-container {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: .6rem;
      pointer-events: none;
    }
    .toast {
      display: flex;
      align-items: flex-start;
      gap: .65rem;
      min-width: 280px;
      max-width: 420px;
      border-radius: .6rem;
      padding: .75rem 1rem;
      font-size: .8rem;
      line-height: 1.4;
      pointer-events: all;
      box-shadow: 0 4px 24px rgba(0,0,0,.5);
      animation: toastIn .25s ease both;
    }
    .toast.dismissing { animation: toastOut .3s ease forwards; }
    .toast-error   { background:#1a0a0a; border:1px solid rgba(239,68,68,.5);  color:#fca5a5; }
    .toast-warn    { background:#1a1000; border:1px solid rgba(234,179,8,.45); color:#fde68a; }
    .toast-success { background:#0a1a0a; border:1px solid rgba(34,197,94,.4);  color:#86efac; }
    .toast-icon { flex-shrink:0; margin-top:.05rem; }
    .toast-close {
      margin-left:auto; flex-shrink:0;
      background:none; border:none; cursor:pointer;
      color:inherit; opacity:.5; font-size:1rem; line-height:1;
      padding:0 0 0 .4rem;
      transition: opacity .15s;
    }
    .toast-close:hover { opacity:1; }
    @keyframes toastIn  { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
    @keyframes toastOut { from { opacity:1; transform:none; } to { opacity:0; transform:translateY(8px); } }
  </style>
</head>
<body class="min-h-screen">

<!-- ── Top bar ── -->
<header class="sticky top-0 z-40 border-b border-neutral-800 bg-neutral-950/85 backdrop-blur-md px-6 py-3 flex items-center justify-between gap-4">
  <div class="flex items-center gap-3">
    <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center text-xs font-semibold text-white">MC</span>
    <span class="text-sm font-light text-neutral-300 hidden sm:block">Admin Panel</span>
  </div>
  <div class="flex items-center gap-5">
    <span class="text-sm text-neutral-500">Hello, <span class="text-purple-400"><?= htmlspecialchars($_SESSION['admin_user']) ?></span></span>
    <a href="index.php" class="text-xs text-neutral-500 hover:text-white transition">← View Site</a>
    <form method="POST"><input type="hidden" name="action" value="logout" />
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
      <button class="text-xs text-red-500 hover:text-red-400 transition">Logout</button>
    </form>
  </div>
</header>

<?php
// ── Silently auto-create upload folders; collect problems for toast only ──
$folder_checks = [
    'web_app'       => 'projects' . DIRECTORY_SEPARATOR . 'web',
    'photo_editing' => 'projects' . DIRECTORY_SEPARATOR . 'photo',
    'video_editing' => 'projects' . DIRECTORY_SEPARATOR . 'video',
];
$folder_problems = [];
foreach ($folder_checks as $key => $rel) {
    $abs = rtrim(__DIR__ . DIRECTORY_SEPARATOR . $rel, '/\\');
    if (!is_dir($abs)) @mkdir($abs, 0755, true);
    if (!is_dir($abs))         $folder_problems[] = $rel . ' — missing, could not auto-create';
    elseif (!is_writable($abs)) $folder_problems[] = $rel . ' — not writable';
}

// Build PHP-side toast queue (shown via JS on load)
$php_toasts = [];
if (!empty($folder_problems)) {
    $php_toasts[] = ['type' => 'warn',
                     'msg'  => 'Upload folder issue: ' . implode(' | ', $folder_problems)];
}
if ($msg !== '') {
    $php_toasts[] = ['type' => ($msg_type === 'green' ? 'success' : 'error'), 'msg' => $msg];
}
?>

<!-- ── Tabs ── -->
<div class="px-6 mt-6 border-b border-neutral-800 flex gap-1">
  <button class="tab-btn <?= $active_tab === 'projects'    ? 'active' : '' ?>" onclick="switchTab('projects')">
    Projects <span class="ml-1 text-xs text-neutral-600">(<?= count($projects) ?>)</span>
  </button>
  <button class="tab-btn <?= $active_tab === 'experiences' ? 'active' : '' ?>" onclick="switchTab('experiences')">
    Experiences <span class="ml-1 text-xs text-neutral-600">(<?= count($experiences) ?>)</span>
  </button>
</div>

<main class="px-4 sm:px-6 py-8 max-w-6xl mx-auto">

<!-- ══════════════════════════════════════════════
     PROJECTS TAB
══════════════════════════════════════════════ -->
<section id="panel-projects" <?= $active_tab !== 'projects' ? 'style="display:none"' : '' ?>>

  <!-- Add Project -->
  <div class="border border-neutral-800 rounded-2xl p-6 mb-8 bg-neutral-900/30">
    <h2 class="text-sm font-medium text-white mb-5 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Add New Project
    </h2>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_project" />
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="lbl">Title *</label>
          <input name="title" type="text" class="fi" placeholder="Project name" required />
        </div>
        <div>
          <label class="lbl">Category *</label>
          <select name="category" class="fi">
            <option value="web_app">Web & App Development</option>
            <option value="photo_editing">Photo Editing</option>
            <option value="video_editing">Video Editing</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="lbl">Description *</label>
          <textarea name="description" rows="2" class="fi" required></textarea>
        </div>
        <div>
          <label class="lbl">Technologies <span class="normal-case text-neutral-700">(comma-separated)</span></label>
          <input name="technologies" type="text" class="fi" placeholder="React, Node.js, MySQL" />
        </div>
        <div>
          <label class="lbl">Project URL</label>
          <input name="project_url" type="url" class="fi" placeholder="https://github.com/…" />
        </div>
        <div class="sm:col-span-2">
          <label class="lbl">Image <span class="normal-case text-neutral-700">(max 4 MB)</span></label>
          <input name="image" type="file" accept="image/*" class="fi" style="padding:.4rem .75rem" />
        </div>
      </div>
      <div class="flex justify-end">
        <button type="submit" class="btn-save">Add Project</button>
      </div>
    </form>
  </div>

  <!-- Projects list -->
  <div class="border border-neutral-800 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:70px">Image</th>
            <th>Title</th>
            <th class="hidden md:table-cell">Category</th>
            <th class="hidden lg:table-cell">Technologies</th>
            <th style="width:130px; text-align:center">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($projects)): ?>
          <tr><td colspan="5" class="text-center text-neutral-600 py-10 text-sm">No projects yet.</td></tr>
        <?php endif; ?>

        <?php foreach ($projects as $p):
          $p_techs     = techs_to_string($p['technologies']);
          $is_editing  = ($edit_project_id === (int)$p['id']);
        ?>
          <!-- ── Normal row ── -->
          <tr>
            <td>
              <img src="<?= htmlspecialchars($p['image_path']) ?>"
                   onerror="this.src='https://placehold.co/60x40/111/333?text=img'"
                   class="w-14 h-10 object-cover rounded-md border border-neutral-800" />
            </td>
            <td class="text-neutral-200 font-medium max-w-[180px]">
              <div class="truncate"><?= htmlspecialchars($p['title']) ?></div>
            </td>
            <td class="hidden md:table-cell">
              <?php
              $cat_map = ['web_app' => ['chip-web','Web / App'], 'photo_editing' => ['chip-photo','Photo'], 'video_editing' => ['chip-video','Video']];
              [$chip_cls, $chip_lbl] = $cat_map[$p['category']] ?? ['',''];
              ?>
              <span class="chip <?= $chip_cls ?>"><?= $chip_lbl ?></span>
            </td>
            <td class="hidden lg:table-cell">
              <div class="flex flex-wrap">
                <?php foreach (json_decode($p['technologies'], true) ?? [] as $t): ?>
                  <span class="badge"><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
              </div>
            </td>
            <td>
              <div class="flex items-center justify-center gap-2">
                <a href="?tab=projects&edit_project=<?= $is_editing ? 0 : $p['id'] ?>#ep-<?= $p['id'] ?>"
                   class="btn-edit"><?= $is_editing ? 'Close' : 'Edit' ?></a>
                <form method="POST" onsubmit="return confirm('Delete «<?= htmlspecialchars(addslashes($p['title'])) ?>»?')">
                  <input type="hidden" name="action" value="delete_project" />
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                  <input type="hidden" name="id"     value="<?= $p['id'] ?>" />
                  <button type="submit" class="btn-del">Delete</button>
                </form>
              </div>
            </td>
          </tr>

          <!-- ── Inline edit panel (shown when this row is being edited) ── -->
          <?php if ($is_editing): ?>
          <tr id="ep-<?= $p['id'] ?>">
            <td colspan="5" style="padding:.25rem 1rem .75rem">
              <div class="edit-panel">
                <p class="text-xs text-purple-400 font-medium uppercase tracking-widest mb-4">Editing: <?= htmlspecialchars($p['title']) ?></p>
                <form method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="update_project" />
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                  <input type="hidden" name="id"     value="<?= $p['id'] ?>" />
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label class="lbl">Title *</label>
                      <input name="title" type="text" class="fi"
                             value="<?= htmlspecialchars($p['title']) ?>" required />
                    </div>
                    <div>
                      <label class="lbl">Category *</label>
                      <select name="category" class="fi">
                        <?php foreach (['web_app' => 'Web & App Development', 'photo_editing' => 'Photo Editing', 'video_editing' => 'Video Editing'] as $val => $lbl): ?>
                          <option value="<?= $val ?>" <?= $p['category'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="sm:col-span-2">
                      <label class="lbl">Description *</label>
                      <textarea name="description" rows="3" class="fi" required><?= htmlspecialchars($p['description']) ?></textarea>
                    </div>
                    <div>
                      <label class="lbl">Technologies</label>
                      <input name="technologies" type="text" class="fi"
                             value="<?= htmlspecialchars($p_techs) ?>" />
                    </div>
                    <div>
                      <label class="lbl">Project URL</label>
                      <input name="project_url" type="url" class="fi"
                             value="<?= htmlspecialchars($p['project_url']) ?>" />
                    </div>
                    <div class="sm:col-span-2">
                      <label class="lbl">Replace Image <span class="normal-case text-neutral-700">(leave blank to keep current)</span></label>
                      <input name="image" type="file" accept="image/*" class="fi" style="padding:.4rem .75rem" />
                      <?php if ($p['image_path']): ?>
                        <p class="mt-1 text-xs text-neutral-600">Current: <?= htmlspecialchars($p['image_path']) ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="flex gap-3 justify-end">
                    <a href="?tab=projects" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-save">Save Changes</button>
                  </div>
                </form>
              </div>
            </td>
          </tr>
          <?php endif; ?>

        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════
     EXPERIENCES TAB
══════════════════════════════════════════════ -->
<section id="panel-experiences" <?= $active_tab !== 'experiences' ? 'style="display:none"' : '' ?>>

  <!-- Add Experience -->
  <div class="border border-neutral-800 rounded-2xl p-6 mb-8 bg-neutral-900/30">
    <h2 class="text-sm font-medium text-white mb-5 flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Add New Experience
    </h2>
    <form method="POST">
      <input type="hidden" name="action" value="add_experience" />
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="lbl">Year / Period *</label>
          <input name="year" type="text" class="fi" placeholder="2024 — Present" required />
        </div>
        <div>
          <label class="lbl">Role *</label>
          <input name="role" type="text" class="fi" placeholder="Fullstack Developer" required />
        </div>
        <div>
          <label class="lbl">Company *</label>
          <input name="company" type="text" class="fi" placeholder="Company Name" required />
        </div>
        <div>
          <label class="lbl">Technologies</label>
          <input name="technologies" type="text" class="fi" placeholder="React, Node.js, MySQL" />
        </div>
        <div class="sm:col-span-2">
          <label class="lbl">Description</label>
          <textarea name="description" rows="2" class="fi"></textarea>
        </div>
      </div>
      <div class="flex justify-end">
        <button type="submit" class="btn-save">Add Experience</button>
      </div>
    </form>
  </div>

  <!-- Experiences list -->
  <div class="border border-neutral-800 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:120px">Year</th>
            <th>Role</th>
            <th class="hidden md:table-cell">Company</th>
            <th class="hidden lg:table-cell">Technologies</th>
            <th style="width:130px; text-align:center">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($experiences)): ?>
          <tr><td colspan="5" class="text-center text-neutral-600 py-10 text-sm">No experiences yet.</td></tr>
        <?php endif; ?>

        <?php foreach ($experiences as $e):
          $e_techs    = techs_to_string($e['technologies']);
          $is_editing = ($edit_exp_id === (int)$e['id']);
        ?>
          <!-- ── Normal row ── -->
          <tr>
            <td class="text-neutral-500 text-xs whitespace-nowrap"><?= htmlspecialchars($e['year']) ?></td>
            <td class="text-neutral-200 font-medium">
              <div class="truncate max-w-[180px]"><?= htmlspecialchars($e['role']) ?></div>
            </td>
            <td class="hidden md:table-cell text-neutral-400">
              <div class="truncate max-w-[150px]"><?= htmlspecialchars($e['company']) ?></div>
            </td>
            <td class="hidden lg:table-cell">
              <div class="flex flex-wrap">
                <?php foreach (json_decode($e['technologies'], true) ?? [] as $t): ?>
                  <span class="badge"><?= htmlspecialchars($t) ?></span>
                <?php endforeach; ?>
              </div>
            </td>
            <td>
              <div class="flex items-center justify-center gap-2">
                <a href="?tab=experiences&edit_exp=<?= $is_editing ? 0 : $e['id'] ?>#ee-<?= $e['id'] ?>"
                   class="btn-edit"><?= $is_editing ? 'Close' : 'Edit' ?></a>
                <form method="POST" onsubmit="return confirm('Delete this experience?')">
                  <input type="hidden" name="action" value="delete_experience" />
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                  <input type="hidden" name="id"     value="<?= $e['id'] ?>" />
                  <button type="submit" class="btn-del">Delete</button>
                </form>
              </div>
            </td>
          </tr>

          <!-- ── Inline edit panel ── -->
          <?php if ($is_editing): ?>
          <tr id="ee-<?= $e['id'] ?>">
            <td colspan="5" style="padding:.25rem 1rem .75rem">
              <div class="edit-panel">
                <p class="text-xs text-purple-400 font-medium uppercase tracking-widest mb-4">Editing: <?= htmlspecialchars($e['role']) ?> @ <?= htmlspecialchars($e['company']) ?></p>
                <form method="POST">
                  <input type="hidden" name="action" value="update_experience" />
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
                  <input type="hidden" name="id"     value="<?= $e['id'] ?>" />
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                      <label class="lbl">Year / Period *</label>
                      <input name="year" type="text" class="fi"
                             value="<?= htmlspecialchars($e['year']) ?>" required />
                    </div>
                    <div>
                      <label class="lbl">Role *</label>
                      <input name="role" type="text" class="fi"
                             value="<?= htmlspecialchars($e['role']) ?>" required />
                    </div>
                    <div>
                      <label class="lbl">Company *</label>
                      <input name="company" type="text" class="fi"
                             value="<?= htmlspecialchars($e['company']) ?>" required />
                    </div>
                    <div>
                      <label class="lbl">Technologies</label>
                      <input name="technologies" type="text" class="fi"
                             value="<?= htmlspecialchars($e_techs) ?>" />
                    </div>
                    <div class="sm:col-span-2">
                      <label class="lbl">Description</label>
                      <textarea name="description" rows="3" class="fi"><?= htmlspecialchars($e['description']) ?></textarea>
                    </div>
                  </div>
                  <div class="flex gap-3 justify-end">
                    <a href="?tab=experiences" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-save">Save Changes</button>
                  </div>
                </form>
              </div>
            </td>
          </tr>
          <?php endif; ?>

        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

</main>

<!-- ── Toast container ── -->
<div id="toast-container" role="alert" aria-live="polite"></div>

<script>
/* ── Tab switcher ── */
function switchTab(tab) {
  document.getElementById('panel-projects').style.display    = (tab === 'projects')    ? '' : 'none';
  document.getElementById('panel-experiences').style.display = (tab === 'experiences') ? '' : 'none';
  const url = new URL(window.location);
  url.searchParams.set('tab', tab);
  url.searchParams.delete('edit_project');
  url.searchParams.delete('edit_exp');
  history.replaceState(null, '', url);
}

/* ── Toast system ── */
const ICONS = {
  error:   '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  warn:    '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  success: '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
};

function showToast(type, msg, duration) {
  duration = duration || (type === 'error' ? 0 : type === 'warn' ? 12000 : 5000);
  const container = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast toast-' + type;
  t.innerHTML = ICONS[type] +
    '<span style="flex:1">' + msg + '</span>' +
    '<button class="toast-close" aria-label="Dismiss">&times;</button>';

  const close = t.querySelector('.toast-close');
  let timer;
  const dismiss = () => {
    clearTimeout(timer);
    t.classList.add('dismissing');
    t.addEventListener('animationend', () => t.remove(), { once: true });
  };
  close.addEventListener('click', dismiss);
  if (duration > 0) timer = setTimeout(dismiss, duration);

  container.appendChild(t);
}

/* ── Client-side file validation — shows toast before form submits ── */
function attachFileValidation(inputSelector) {
  document.querySelectorAll(inputSelector).forEach(function(input) {
    input.addEventListener('change', function() {
      const file = this.files[0];
      if (!file) return;
      const allowed = ['image/jpeg','image/png','image/webp','image/gif'];
      if (!allowed.includes(file.type)) {
        showToast('error', 'Invalid file type: <strong>' + file.name + '</strong>. Only JPEG, PNG, WebP and GIF are allowed.');
        this.value = '';
        return;
      }
      const mb = file.size / 1048576;
      if (mb > 4) {
        showToast('error', 'File too large: <strong>' + mb.toFixed(1) + ' MB</strong>. Maximum allowed size is 4 MB.');
        this.value = '';
      }
    });
  });
}

/* ── Fire PHP-generated toasts on page load ── */
document.addEventListener('DOMContentLoaded', function() {
  attachFileValidation('input[type="file"]');

  var phpToasts = <?= json_encode($php_toasts) ?>;
  phpToasts.forEach(function(t) { showToast(t.type, t.msg); });
});
</script>
</body>
</html>
