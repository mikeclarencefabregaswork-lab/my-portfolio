<?php
// Shared admin header/topbar
?>

<!-- ── Top bar ── -->
<header class="sticky top-0 z-40 border-b border-neutral-800 bg-neutral-950/85 backdrop-blur-md px-6 py-3 flex items-center justify-between gap-4">
  <div class="flex items-center gap-3">
    <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center text-xs font-semibold text-white">MC</span>
    <span class="text-sm font-light text-neutral-300 hidden sm:block">Admin Panel</span>
  </div>
  <div class="flex items-center gap-5">
    <span class="text-sm text-neutral-500">Hello, <span class="text-purple-400"><?= htmlspecialchars($_SESSION['admin_user'] ?? '') ?></span></span>
    <a href="index.php" class="text-xs text-neutral-500 hover:text-white transition">← View Site</a>
    <form method="POST">
      <input type="hidden" name="action" value="logout" />
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>" />
      <button class="text-xs text-red-500 hover:text-red-400 transition">Logout</button>
    </form>
  </div>
</header>

