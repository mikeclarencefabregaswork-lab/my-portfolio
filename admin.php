<?php
// ============================================================
//  admin.php  —  Protected admin dashboard (CRUD)
// ============================================================
session_start();

// --- Auth guard ---
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';

$pdo      = get_pdo();
$msg      = '';
$msg_type = 'green'; // green | red

// ─────────────────────────────────────────────────────────────
//  UPLOAD HELPER
// ─────────────────────────────────────────────────────────────
function handle_image_upload(array $file): string {
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mime, true)) {
        throw new RuntimeException('Invalid file type. Only JPEG, PNG, WebP and GIF are allowed.');
    }

    if ($file['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('File exceeds 4 MB limit.');
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(12)) . '.' . strtolower($ext);
    $dest     = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return 'uploads/' . $filename;
}

// ─────────────────────────────────────────────────────────────
//  POST HANDLER
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // ---------- INSERT PROJECT ----------
        if ($action === 'add_project') {
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tech_raw    = trim($_POST['technologies'] ?? '');
            $url         = trim($_POST['project_url'] ?? '#');
            $category    = $_POST['category'] ?? 'web_app';

            if ($title === '' || $description === '') {
                throw new RuntimeException('Title and description are required.');
            }

            // Parse comma-separated technologies → JSON array
            $techs = array_filter(array_map('trim', explode(',', $tech_raw)));
            $tech_json = json_encode(array_values($techs));

            // Handle image upload
            $image_path = 'uploads/placeholder.png';
            if (!empty($_FILES['image']['name'])) {
                $image_path = handle_image_upload($_FILES['image']);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO projects (title, description, technologies, project_url, image_path, category)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$title, $description, $tech_json, $url, $image_path, $category]);
            $msg = 'Project added successfully.';
        }

        // ---------- DELETE PROJECT ----------
        elseif ($action === 'delete_project') {
            $id = (int)($_POST['id'] ?? 0);
            // Optionally delete image file
            $row = $pdo->prepare('SELECT image_path FROM projects WHERE id = ?');
            $row->execute([$id]);
            $proj = $row->fetch();
            if ($proj && $proj['image_path'] && file_exists(__DIR__ . '/' . $proj['image_path'])) {
                @unlink(__DIR__ . '/' . $proj['image_path']);
            }
            $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
            $msg = 'Project deleted.';
        }

        // ---------- UPDATE PROJECT ----------
        elseif ($action === 'update_project') {
            $id          = (int)($_POST['id'] ?? 0);
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tech_raw    = trim($_POST['technologies'] ?? '');
            $url         = trim($_POST['project_url'] ?? '#');
            $category    = $_POST['category'] ?? 'web_app';

            $techs     = array_filter(array_map('trim', explode(',', $tech_raw)));
            $tech_json = json_encode(array_values($techs));

            // Check if new image uploaded
            $row = $pdo->prepare('SELECT image_path FROM projects WHERE id = ?');
            $row->execute([$id]);
            $existing = $row->fetch();
            $image_path = $existing['image_path'] ?? 'uploads/placeholder.png';

            if (!empty($_FILES['image']['name'])) {
                // Delete old file
                if ($image_path && file_exists(__DIR__ . '/' . $image_path)) {
                    @unlink(__DIR__ . '/' . $image_path);
                }
                $image_path = handle_image_upload($_FILES['image']);
            }

            $stmt = $pdo->prepare(
                'UPDATE projects SET title=?, description=?, technologies=?, project_url=?, image_path=?, category=?
                 WHERE id=?'
            );
            $stmt->execute([$title, $description, $tech_json, $url, $image_path, $category, $id]);
            $msg = 'Project updated.';
        }

        // ---------- INSERT EXPERIENCE ----------
        elseif ($action === 'add_experience') {
            $year        = trim($_POST['year'] ?? '');
            $role        = trim($_POST['role'] ?? '');
            $company     = trim($_POST['company'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tech_raw    = trim($_POST['technologies'] ?? '');

            if ($year === '' || $role === '' || $company === '') {
                throw new RuntimeException('Year, role and company are required.');
            }

            $techs     = array_filter(array_map('trim', explode(',', $tech_raw)));
            $tech_json = json_encode(array_values($techs));

            $stmt = $pdo->prepare(
                'INSERT INTO experiences (year, role, company, description, technologies)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$year, $role, $company, $description, $tech_json]);
            $msg = 'Experience added.';
        }

        // ---------- DELETE EXPERIENCE ----------
        elseif ($action === 'delete_experience') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM experiences WHERE id = ?')->execute([$id]);
            $msg = 'Experience deleted.';
        }

        // ---------- UPDATE EXPERIENCE ----------
        elseif ($action === 'update_experience') {
            $id          = (int)($_POST['id'] ?? 0);
            $year        = trim($_POST['year'] ?? '');
            $role        = trim($_POST['role'] ?? '');
            $company     = trim($_POST['company'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $tech_raw    = trim($_POST['technologies'] ?? '');

            $techs     = array_filter(array_map('trim', explode(',', $tech_raw)));
            $tech_json = json_encode(array_values($techs));

            $stmt = $pdo->prepare(
                'UPDATE experiences SET year=?, role=?, company=?, description=?, technologies=? WHERE id=?'
            );
            $stmt->execute([$year, $role, $company, $description, $tech_json, $id]);
            $msg = 'Experience updated.';
        }

        // ---------- LOGOUT ----------
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
        $msg      = 'Database error. Check server logs.';
        $msg_type = 'red';
    }
}

// ─────────────────────────────────────────────────────────────
//  FETCH DATA for display
// ─────────────────────────────────────────────────────────────
$projects    = $pdo->query('SELECT * FROM projects    ORDER BY category, sort_order, id')->fetchAll();
$experiences = $pdo->query('SELECT * FROM experiences ORDER BY sort_order, id')->fetchAll();

// Decode JSON techs for display
$decode_tech = fn(string $json): string =>
    implode(', ', json_decode($json, true) ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel — MCF Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap');
    body { font-family: 'DM Sans', sans-serif; background: #0a0a0a; color: #e5e5e5; }

    .tab-btn.active { border-color: rgb(168 85 247); color: white; }
    .tab-btn        { border-color: transparent; color: #737373; }

    .form-input {
      width: 100%;
      background: rgba(23,23,23,.8);
      border: 1px solid #404040;
      border-radius: .5rem;
      padding: .6rem 1rem;
      color: #e5e5e5;
      font-size: .875rem;
      transition: border-color .15s, box-shadow .15s;
    }
    .form-input:focus {
      outline: none;
      border-color: rgb(168 85 247 / .7);
      box-shadow: 0 0 0 2px rgb(168 85 247 / .2);
    }
    .form-label {
      display: block;
      font-size: .7rem;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: #737373;
      margin-bottom: .4rem;
    }
    .btn-primary {
      background: linear-gradient(135deg, rgb(236,72,153), rgb(168,85,247));
      color: white;
      border-radius: .5rem;
      padding: .55rem 1.4rem;
      font-size: .875rem;
      font-weight: 500;
      cursor: pointer;
      transition: opacity .15s, transform .1s;
    }
    .btn-primary:hover   { opacity: .9; }
    .btn-primary:active  { transform: scale(.97); }

    .btn-danger {
      background: rgba(127,29,29,.6);
      border: 1px solid rgb(185,28,28);
      color: rgb(252,165,165);
      border-radius: .4rem;
      padding: .3rem .7rem;
      font-size: .75rem;
      cursor: pointer;
      transition: background .15s;
    }
    .btn-danger:hover { background: rgba(185,28,28,.6); }

    .btn-edit {
      background: rgba(23,37,84,.6);
      border: 1px solid rgb(37,99,235);
      color: rgb(147,197,253);
      border-radius: .4rem;
      padding: .3rem .7rem;
      font-size: .75rem;
      cursor: pointer;
      transition: background .15s;
    }
    .btn-edit:hover { background: rgba(37,99,235,.3); }

    /* modal */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,.75);
      backdrop-filter: blur(4px);
      z-index: 50;
      display: flex; align-items: center; justify-content: center;
      padding: 1rem;
    }
    .modal-box {
      background: #111;
      border: 1px solid #262626;
      border-radius: 1rem;
      width: 100%;
      max-width: 560px;
      max-height: 90vh;
      overflow-y: auto;
      padding: 2rem;
    }
  </style>
</head>
<body class="min-h-screen">

<!-- ── Top Bar ── -->
<header class="sticky top-0 z-40 border-b border-neutral-800 bg-neutral-950/80 backdrop-blur-md px-6 py-4 flex items-center justify-between">
  <div>
    <span class="text-lg font-light tracking-widest text-white">MCF</span>
    <span class="ml-3 text-xs text-neutral-500 tracking-wider uppercase">Admin Panel</span>
  </div>
  <div class="flex items-center gap-4">
    <span class="text-sm text-neutral-500">Hello, <span class="text-purple-400"><?= htmlspecialchars($_SESSION['admin_user']) ?></span></span>
    <a href="index.php" class="text-xs text-neutral-500 hover:text-white transition">← View Site</a>
    <form method="POST" class="inline">
      <input type="hidden" name="action" value="logout" />
      <button type="submit" class="text-xs text-red-500 hover:text-red-400 transition">Logout</button>
    </form>
  </div>
</header>

<!-- ── Flash Message ── -->
<?php if ($msg): ?>
  <div class="mx-6 mt-5 rounded-lg border px-4 py-3 text-sm
    <?= $msg_type === 'green'
        ? 'border-green-800/60 bg-green-950/40 text-green-400'
        : 'border-red-800/60 bg-red-950/40 text-red-400' ?>">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<!-- ── Tabs ── -->
<div class="mx-6 mt-6 flex gap-2 border-b border-neutral-800 pb-0" id="tabs">
  <button onclick="switchTab('projects')"  id="tab-projects"
    class="tab-btn active border-b-2 px-5 py-2.5 text-sm font-medium transition">Projects</button>
  <button onclick="switchTab('experiences')" id="tab-experiences"
    class="tab-btn border-b-2 px-5 py-2.5 text-sm font-medium transition">Experiences</button>
</div>

<main class="px-6 py-8 max-w-6xl mx-auto">

<!-- ══════════════ PROJECTS PANEL ══════════════ -->
<section id="panel-projects">

  <!-- Add Project Form -->
  <div class="border border-neutral-800 rounded-2xl p-6 mb-8 bg-neutral-900/30">
    <h2 class="text-base font-medium text-white mb-6">Add New Project</h2>
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <input type="hidden" name="action" value="add_project" />

      <div>
        <label class="form-label">Title *</label>
        <input name="title" type="text" class="form-input" placeholder="Project name" required />
      </div>
      <div>
        <label class="form-label">Category *</label>
        <select name="category" class="form-input">
          <option value="web_app">Web & App Development</option>
          <option value="photo_editing">Photo Editing</option>
          <option value="video_editing">Video Editing</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="form-label">Description *</label>
        <textarea name="description" rows="3" class="form-input" required></textarea>
      </div>
      <div>
        <label class="form-label">Technologies (comma-separated)</label>
        <input name="technologies" type="text" class="form-input" placeholder="React, Node.js, MySQL" />
      </div>
      <div>
        <label class="form-label">Project URL</label>
        <input name="project_url" type="url" class="form-input" placeholder="https://github.com/…" />
      </div>
      <div class="md:col-span-2">
        <label class="form-label">Project Image</label>
        <input name="image" type="file" accept="image/*" class="form-input" style="padding:.45rem .75rem;" />
        <p class="mt-1 text-xs text-neutral-600">Max 4 MB — JPEG, PNG, WebP, GIF</p>
      </div>
      <div class="md:col-span-2 flex justify-end">
        <button type="submit" class="btn-primary">Add Project</button>
      </div>
    </form>
  </div>

  <!-- Projects Table -->
  <div class="border border-neutral-800 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
      <thead class="border-b border-neutral-800 bg-neutral-900/60">
        <tr>
          <th class="text-left px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium">Image</th>
          <th class="text-left px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium">Title</th>
          <th class="text-left px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium hidden md:table-cell">Category</th>
          <th class="text-left px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium hidden lg:table-cell">Technologies</th>
          <th class="px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-800">
        <?php foreach ($projects as $p): ?>
          <?php $techs_str = $decode_tech($p['technologies']); ?>
          <tr class="hover:bg-neutral-900/40 transition">
            <td class="px-5 py-3">
              <img src="<?= htmlspecialchars($p['image_path']) ?>"
                   onerror="this.src='https://placehold.co/60x40/1a1a1a/555?text=img'"
                   class="w-16 h-10 object-cover rounded-md bg-neutral-800" />
            </td>
            <td class="px-5 py-3 text-neutral-200 font-medium max-w-[180px] truncate">
              <?= htmlspecialchars($p['title']) ?>
            </td>
            <td class="px-5 py-3 hidden md:table-cell">
              <span class="rounded-full px-2.5 py-0.5 text-xs font-medium
                <?php echo match($p['category']) {
                  'web_app'       => 'bg-cyan-950 text-cyan-400',
                  'photo_editing' => 'bg-purple-950 text-purple-400',
                  'video_editing' => 'bg-rose-950 text-rose-400',
                  default         => 'bg-neutral-800 text-neutral-400',
                }; ?>">
                <?= match($p['category']) {
                  'web_app'       => 'Web / App',
                  'photo_editing' => 'Photo',
                  'video_editing' => 'Video',
                  default         => $p['category'],
                } ?>
              </span>
            </td>
            <td class="px-5 py-3 text-neutral-400 hidden lg:table-cell max-w-[200px] truncate text-xs">
              <?= htmlspecialchars($techs_str) ?>
            </td>
            <td class="px-5 py-3">
              <div class="flex items-center gap-2 justify-center">
                <!-- Edit trigger -->
                <button class="btn-edit"
                  onclick="openEditProject(
                    <?= $p['id'] ?>,
                    <?= json_encode($p['title']) ?>,
                    <?= json_encode($p['description']) ?>,
                    <?= json_encode($techs_str) ?>,
                    <?= json_encode($p['project_url']) ?>,
                    <?= json_encode($p['category']) ?>
                  )">Edit</button>
                <!-- Delete -->
                <form method="POST" onsubmit="return confirm('Delete this project?')">
                  <input type="hidden" name="action" value="delete_project" />
                  <input type="hidden" name="id" value="<?= $p['id'] ?>" />
                  <button type="submit" class="btn-danger">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($projects)): ?>
          <tr><td colspan="5" class="px-5 py-8 text-center text-neutral-600 text-sm">No projects yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ══════════════ EXPERIENCES PANEL ══════════════ -->
<section id="panel-experiences" class="hidden">

  <!-- Add Experience Form -->
  <div class="border border-neutral-800 rounded-2xl p-6 mb-8 bg-neutral-900/30">
    <h2 class="text-base font-medium text-white mb-6">Add New Experience</h2>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <input type="hidden" name="action" value="add_experience" />

      <div>
        <label class="form-label">Year / Period *</label>
        <input name="year" type="text" class="form-input" placeholder="2024 — Present" required />
      </div>
      <div>
        <label class="form-label">Role *</label>
        <input name="role" type="text" class="form-input" placeholder="Fullstack Developer" required />
      </div>
      <div>
        <label class="form-label">Company *</label>
        <input name="company" type="text" class="form-input" placeholder="Company Name" required />
      </div>
      <div>
        <label class="form-label">Technologies (comma-separated)</label>
        <input name="technologies" type="text" class="form-input" placeholder="React, Node.js, MySQL" />
      </div>
      <div class="md:col-span-2">
        <label class="form-label">Description</label>
        <textarea name="description" rows="3" class="form-input"></textarea>
      </div>
      <div class="md:col-span-2 flex justify-end">
        <button type="submit" class="btn-primary">Add Experience</button>
      </div>
    </form>
  </div>

  <!-- Experiences Table -->
  <div class="border border-neutral-800 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
      <thead class="border-b border-neutral-800 bg-neutral-900/60">
        <tr>
          <th class="text-left px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium">Year</th>
          <th class="text-left px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium">Role</th>
          <th class="text-left px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium hidden md:table-cell">Company</th>
          <th class="text-left px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium hidden lg:table-cell">Tech</th>
          <th class="px-5 py-3 text-xs uppercase tracking-wider text-neutral-500 font-medium">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-neutral-800">
        <?php foreach ($experiences as $e): ?>
          <?php $etechs = $decode_tech($e['technologies']); ?>
          <tr class="hover:bg-neutral-900/40 transition">
            <td class="px-5 py-3 text-neutral-400 text-xs whitespace-nowrap"><?= htmlspecialchars($e['year']) ?></td>
            <td class="px-5 py-3 text-neutral-200 font-medium max-w-[180px] truncate"><?= htmlspecialchars($e['role']) ?></td>
            <td class="px-5 py-3 text-neutral-400 hidden md:table-cell max-w-[150px] truncate"><?= htmlspecialchars($e['company']) ?></td>
            <td class="px-5 py-3 text-neutral-500 hidden lg:table-cell max-w-[200px] truncate text-xs"><?= htmlspecialchars($etechs) ?></td>
            <td class="px-5 py-3">
              <div class="flex items-center gap-2 justify-center">
                <button class="btn-edit"
                  onclick="openEditExp(
                    <?= $e['id'] ?>,
                    <?= json_encode($e['year']) ?>,
                    <?= json_encode($e['role']) ?>,
                    <?= json_encode($e['company']) ?>,
                    <?= json_encode($e['description']) ?>,
                    <?= json_encode($etechs) ?>
                  )">Edit</button>
                <form method="POST" onsubmit="return confirm('Delete this experience?')">
                  <input type="hidden" name="action" value="delete_experience" />
                  <input type="hidden" name="id" value="<?= $e['id'] ?>" />
                  <button type="submit" class="btn-danger">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($experiences)): ?>
          <tr><td colspan="5" class="px-5 py-8 text-center text-neutral-600 text-sm">No experiences yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

</main>

<!-- ══════ EDIT PROJECT MODAL ══════ -->
<div id="modal-project" class="modal-overlay hidden">
  <div class="modal-box">
    <h3 class="text-base font-medium text-white mb-6">Edit Project</h3>
    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 gap-4">
      <input type="hidden" name="action" value="update_project" />
      <input type="hidden" name="id" id="ep-id" />
      <div>
        <label class="form-label">Title</label>
        <input name="title" id="ep-title" type="text" class="form-input" required />
      </div>
      <div>
        <label class="form-label">Category</label>
        <select name="category" id="ep-category" class="form-input">
          <option value="web_app">Web & App Development</option>
          <option value="photo_editing">Photo Editing</option>
          <option value="video_editing">Video Editing</option>
        </select>
      </div>
      <div>
        <label class="form-label">Description</label>
        <textarea name="description" id="ep-description" rows="3" class="form-input"></textarea>
      </div>
      <div>
        <label class="form-label">Technologies (comma-separated)</label>
        <input name="technologies" id="ep-technologies" type="text" class="form-input" />
      </div>
      <div>
        <label class="form-label">Project URL</label>
        <input name="project_url" id="ep-url" type="url" class="form-input" />
      </div>
      <div>
        <label class="form-label">Replace Image (optional)</label>
        <input name="image" type="file" accept="image/*" class="form-input" style="padding:.45rem .75rem;" />
      </div>
      <div class="flex justify-end gap-3 mt-2">
        <button type="button" onclick="closeModal('modal-project')"
          class="px-5 py-2 rounded-lg border border-neutral-700 text-neutral-400 text-sm hover:bg-neutral-800 transition">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ══════ EDIT EXPERIENCE MODAL ══════ -->
<div id="modal-exp" class="modal-overlay hidden">
  <div class="modal-box">
    <h3 class="text-base font-medium text-white mb-6">Edit Experience</h3>
    <form method="POST" class="grid grid-cols-1 gap-4">
      <input type="hidden" name="action" value="update_experience" />
      <input type="hidden" name="id" id="ee-id" />
      <div>
        <label class="form-label">Year / Period</label>
        <input name="year" id="ee-year" type="text" class="form-input" required />
      </div>
      <div>
        <label class="form-label">Role</label>
        <input name="role" id="ee-role" type="text" class="form-input" required />
      </div>
      <div>
        <label class="form-label">Company</label>
        <input name="company" id="ee-company" type="text" class="form-input" required />
      </div>
      <div>
        <label class="form-label">Description</label>
        <textarea name="description" id="ee-description" rows="3" class="form-input"></textarea>
      </div>
      <div>
        <label class="form-label">Technologies (comma-separated)</label>
        <input name="technologies" id="ee-technologies" type="text" class="form-input" />
      </div>
      <div class="flex justify-end gap-3 mt-2">
        <button type="button" onclick="closeModal('modal-exp')"
          class="px-5 py-2 rounded-lg border border-neutral-700 text-neutral-400 text-sm hover:bg-neutral-800 transition">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(tab) {
  ['projects','experiences'].forEach(t => {
    document.getElementById('panel-' + t).classList.toggle('hidden', t !== tab);
    const btn = document.getElementById('tab-' + t);
    btn.classList.toggle('active', t === tab);
  });
}

function openEditProject(id, title, desc, techs, url, category) {
  document.getElementById('ep-id').value          = id;
  document.getElementById('ep-title').value       = title;
  document.getElementById('ep-description').value = desc;
  document.getElementById('ep-technologies').value= techs;
  document.getElementById('ep-url').value         = url;
  document.getElementById('ep-category').value    = category;
  document.getElementById('modal-project').classList.remove('hidden');
}

function openEditExp(id, year, role, company, desc, techs) {
  document.getElementById('ee-id').value          = id;
  document.getElementById('ee-year').value        = year;
  document.getElementById('ee-role').value        = role;
  document.getElementById('ee-company').value     = company;
  document.getElementById('ee-description').value = desc;
  document.getElementById('ee-technologies').value= techs;
  document.getElementById('modal-exp').classList.remove('hidden');
}

function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => {
    if (e.target === el) closeModal(el.id);
  });
});
</script>
</body>
</html>
