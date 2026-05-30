<?php
// ============================================================
//  admin.php  —  Protected admin dashboard (CRUD)
// ============================================================
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';

$pdo      = get_pdo();
$msg      = '';
$msg_type = 'green';

// ─────────────────────────────────────────────────────────────
//  HELPERS
// ─────────────────────────────────────────────────────────────
function handle_image_upload(array $file): string {
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true))
        throw new RuntimeException('Invalid file type. Only JPEG, PNG, WebP and GIF allowed.');
    if ($file['size'] > 4 * 1024 * 1024)
        throw new RuntimeException('File exceeds 4 MB limit.');

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename))
        throw new RuntimeException('Failed to save uploaded file.');

    return 'uploads/' . $filename;
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

            $image_path = 'uploads/placeholder.png';
            if (!empty($_FILES['image']['name']))
                $image_path = handle_image_upload($_FILES['image']);

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

            if ($id === 0 || $title === '' || $description === '')
                throw new RuntimeException('ID, title and description are required.');

            // Keep existing image unless a new one is uploaded
            $row = $pdo->prepare('SELECT image_path FROM projects WHERE id = ? LIMIT 1');
            $row->execute([$id]);
            $existing   = $row->fetch();
            $image_path = $existing['image_path'] ?? 'uploads/placeholder.png';

            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                if ($image_path && $image_path !== 'uploads/placeholder.png'
                    && file_exists(__DIR__ . '/' . $image_path))
                    @unlink(__DIR__ . '/' . $image_path);
                $image_path = handle_image_upload($_FILES['image']);
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
                && $proj['image_path'] !== 'uploads/placeholder.png'
                && file_exists(__DIR__ . '/' . $proj['image_path']))
                @unlink(__DIR__ . '/' . $proj['image_path']);
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
      <button class="text-xs text-red-500 hover:text-red-400 transition">Logout</button>
    </form>
  </div>
</header>

<!-- ── Flash message ── -->
<?php if ($msg): ?>
  <div class="mx-6 mt-5 flex items-center gap-2 rounded-lg border px-4 py-3 text-sm
    <?= $msg_type === 'green'
        ? 'border-green-800/50 bg-green-950/40 text-green-400'
        : 'border-red-800/50 bg-red-950/40 text-red-400' ?>">
    <?php if ($msg_type === 'green'): ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    <?php else: ?>
      <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?php endif; ?>
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

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
              $cat_map = ['web_app'=>['chip-web','Web/App'],'photo_editing'=>['chip-photo','Photo'],'video_editing'=>['chip-video','Video']];
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
                        <?php foreach (['web_app'=>'Web & App Development','photo_editing'=>'Photo Editing','video_editing'=>'Video Editing'] as $val => $lbl): ?>
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

<script>
function switchTab(tab) {
  document.getElementById('panel-projects').style.display    = (tab === 'projects')    ? '' : 'none';
  document.getElementById('panel-experiences').style.display = (tab === 'experiences') ? '' : 'none';
  // Update URL so refreshes preserve the tab
  const url = new URL(window.location);
  url.searchParams.set('tab', tab);
  // Clear any open edit panel when switching tabs
  url.searchParams.delete('edit_project');
  url.searchParams.delete('edit_exp');
  history.replaceState(null, '', url);
}
</script>
</body>
</html>
