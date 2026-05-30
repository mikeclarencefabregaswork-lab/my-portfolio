<?php
// ============================================================
//  login.php  —  Admin authentication
// ============================================================
session_start();

// Already logged in?
if (isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';   // do NOT trim password

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $pdo  = get_pdo();
        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // password_verify() works with hashes produced by PHP's password_hash()
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_user'] = $user['username'];
            header('Location: admin.php');
            exit;
        } else {
            // Deliberate generic message — don't reveal which field is wrong
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login — MCF Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'DM Sans', sans-serif; background: #0a0a0a; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(28px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp .5s ease both; }
    .field:focus {
      outline: none;
      border-color: rgb(168 85 247 / .7);
      box-shadow: 0 0 0 2px rgb(168 85 247 / .2);
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">

  <div class="fade-up w-full max-w-md">

    <!-- Heading -->
    <div class="text-center mb-10">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 text-white font-semibold text-sm mb-4">MC</div>
      <p class="text-xs text-neutral-500 tracking-widest uppercase">Admin Portal</p>
    </div>

    <!-- Card -->
    <div class="border border-neutral-800 rounded-2xl p-8 bg-neutral-900/40">
      <h1 class="text-xl font-light text-white mb-1">Welcome back</h1>
      <p class="text-sm text-neutral-500 mb-7">Sign in to manage your portfolio.</p>

      <?php if ($error): ?>
        <div class="mb-5 flex items-center gap-2 rounded-lg border border-red-800/50 bg-red-950/40 px-4 py-3 text-sm text-red-400">
          <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>

        <div class="mb-4">
          <label class="block text-xs text-neutral-500 tracking-widest uppercase mb-2" for="username">Username</label>
          <input
            id="username" name="username" type="text"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            class="field w-full rounded-lg border border-neutral-700 bg-neutral-800/60 px-4 py-3 text-white text-sm placeholder-neutral-600 transition"
            placeholder="your username"
            autocomplete="username"
            required
          />
        </div>

        <div class="mb-7">
          <label class="block text-xs text-neutral-500 tracking-widest uppercase mb-2" for="password">Password</label>
          <input
            id="password" name="password" type="password"
            class="field w-full rounded-lg border border-neutral-700 bg-neutral-800/60 px-4 py-3 text-white text-sm placeholder-neutral-600 transition"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
        </div>

        <button
          type="submit"
          class="w-full rounded-lg bg-gradient-to-r from-pink-500 via-purple-600 to-purple-700 py-3 text-sm font-medium text-white tracking-wide hover:opacity-90 active:scale-[.98] transition-all"
        >
          Sign In
        </button>
      </form>
    </div>

    <p class="mt-6 text-center text-xs text-neutral-700">
      Don't have an account?
      <a href="register.php" class="text-neutral-500 hover:text-white transition ml-1">Register →</a>
    </p>
    <p class="mt-3 text-center text-xs text-neutral-700">&copy; <?= date('Y') ?> Mike Clarence Fabregas</p>
  </div>

</body>
</html>
