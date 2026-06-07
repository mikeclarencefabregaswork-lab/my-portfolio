<?php
// Experiences panel template
// Expects:
// - $experiences (array)
// - $edit_exp_id (int)
// - $active_tab (string)
?>

<?php
// Prevent undefined-variable notices in IDE tooling.
$active_tab     = $active_tab ?? 'experiences';
$experiences    = $experiences ?? [];
$edit_exp_id    = $edit_exp_id ?? 0;
?>


<!-- ══════════════════════════════════════════════
     EXPERIENCES TAB
══════════════════════════════════════════════ -->
<section id="panel-experiences" <?= ($active_tab ?? 'experiences') !== 'experiences' ? 'style="display:none"' : '' ?> >


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

