<?php
// Tabs row template
// Expects: $active_tab, $projects_count, $experiences_count
?>

<!-- ── Tabs ── -->
<div class="px-6 mt-6 border-b border-neutral-800 flex gap-1">
  <button class="tab-btn <?= $active_tab === 'projects' ? 'active' : '' ?>" onclick="switchTab('projects')">
    Projects <span class="ml-1 text-xs text-neutral-600">(<?= (int)$projects_count ?>)</span>
  </button>
  <button class="tab-btn <?= $active_tab === 'experiences' ? 'active' : '' ?>" onclick="switchTab('experiences')">
    Experiences <span class="ml-1 text-xs text-neutral-600">(<?= (int)$experiences_count ?>)</span>
  </button>
</div>

