<?php
// Projects panel template
// Expects:
// - $projects (array)
// - $edit_project_id (int)
// - $active_tab (string)
// - $php_toasts not required here
// - csrf_token(), techs_to_string() functions available in parent
?>

<?php
// Prevent undefined-variable notices in IDE tooling.
$active_tab     = $active_tab ?? 'projects';
$projects    = $projects ?? [];
$edit_project_id    = $edit_project_id ?? 0;
?>

<!-- ══════════════════════════════════════════════
     PROJECTS TAB
══════════════════════════════════════════════ -->
<section id="panel-projects" <?= $active_tab !== 'projects' ? 'style="display:none"' : '' ?> >

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


