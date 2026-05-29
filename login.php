<?php
// ============================================================
//  login.php  —  Admin authentication
// ============================================================
session_start();

require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $pdo  = get_pdo();
        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_user'] = $user['username'];
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Already logged in?
if (isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login — MCF Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap');
    body { font-family: 'DM Sans', sans-serif; background: #0a0a0a; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(30px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp .55s ease both; }

    .glow-border:focus {
      outline: none;
      box-shadow: 0 0 0 2px rgba(168,85,247,.45);
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4">

  <div class="fade-up w-full max-w-md">
    <!-- Logo / Title -->
    <div class="text-center mb-10">
      <span class="text-4xl font-thin tracking-widest text-white">MCF</span>
      <p class="mt-2 text-sm text-neutral-500 tracking-wider uppercase">Admin Portal</p>
    </div>

    <!-- Card -->
    <div class="border border-neutral-800 rounded-2xl p-8 bg-neutral-900/40 backdrop-blur-sm">
      <h1 class="text-xl font-light text-white mb-6">Sign in to continue</h1>

      <?php if ($error): ?>
        <div class="mb-5 rounded-lg border border-red-800/60 bg-red-950/40 px-4 py-3 text-sm text-red-400">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>
        <div class="mb-4">
          <label class="block text-xs text-neutral-500 tracking-wider uppercase mb-2" for="username">Username</label>
          <input
            id="username" name="username" type="text"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            class="glow-border w-full rounded-lg border border-neutral-700 bg-neutral-800/60 px-4 py-3 text-white text-sm placeholder-neutral-600 transition"
            placeholder="admin"
            autocomplete="username"
            required
          />
        </div>

        <div class="mb-7">
          <label class="block text-xs text-neutral-500 tracking-wider uppercase mb-2" for="password">Password</label>
          <input
            id="password" name="password" type="password"
            class="glow-border w-full rounded-lg border border-neutral-700 bg-neutral-800/60 px-4 py-3 text-white text-sm placeholder-neutral-600 transition"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          />
        </div>

        <button
          type="submit"
          class="w-full rounded-lg bg-gradient-to-r from-pink-500 via-purple-600 to-purple-700 py-3 text-sm font-medium text-white tracking-wide hover:opacity-90 active:scale-[.98] transition-all duration-150"
        >
          Sign In
        </button>
      </form>
    </div>

    <p class="mt-6 text-center text-xs text-neutral-700">
      &copy; <?= date('Y') ?> Mike Clarence Fabregas
    </p>
  </div>

</body>
</html>
