<?php
// ============================================================
//  register.php  —  Create a new admin account
//
//  SECURITY NOTE:
//  Once you have created your account, either:
//    (a) Delete this file from your server, OR
//    (b) Set REGISTRATION_OPEN = false below.
//  Leaving an open registration page on a live server is a
//  security risk — anyone could create an admin account.
// ============================================================
session_start();

// ── Set to false after you create your account ──
define('REGISTRATION_OPEN', true);

if (!REGISTRATION_OPEN) {
    http_response_code(403);
    die('
<!DOCTYPE html><html><head><title>Closed</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-[#0a0a0a] min-h-screen flex items-center justify-center text-neutral-400 font-light" style="font-family:DM Sans,sans-serif">
  <div class="text-center">
    <p class="text-4xl mb-4">🔒</p>
    <p class="text-lg text-white mb-2">Registration is closed.</p>
    <a href="login.php" class="text-purple-400 hover:text-purple-300 text-sm transition">← Back to Login</a>
  </div>
</body></html>');
}

require_once __DIR__ . '/db.php';

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $password  = $_POST['password']       ?? '';
    $confirm   = $_POST['confirm']        ?? '';

    // ── Validation ──────────────────────────────────────────
    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = 'Username must be between 3 and 30 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.\-]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, underscores, hyphens and dots.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($confirm !== $password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = get_pdo();

        // Check for duplicate username
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $check->execute([$username]);
        if ($check->fetch()) {
            $errors[] = 'That username is already taken. Choose a different one.';
        } else {
            // Hash with PHP's built-in bcrypt — this is what password_verify() expects
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register — MCF Portfolio</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'DM Sans', sans-serif; background: #0a0a0a; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(28px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp .5s ease both; }
    .field {
      width: 100%;
      border-radius: .5rem;
      border: 1px solid #404040;
      background: rgba(38,38,38,.6);
      padding: .75rem 1rem;
      color: #e5e5e5;
      font-size: .875rem;
      transition: border-color .15s, box-shadow .15s;
    }
    .field:focus {
      outline: none;
      border-color: rgb(168 85 247 / .7);
      box-shadow: 0 0 0 2px rgb(168 85 247 / .2);
    }
    .field.error-field { border-color: rgb(239 68 68 / .7); }

    /* Password strength bar */
    #strength-bar { height: 3px; border-radius: 9999px; transition: width .3s, background .3s; }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10">

  <div class="fade-up w-full max-w-md">

    <!-- Heading -->
    <div class="text-center mb-10">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 text-white font-semibold text-sm mb-4">MC</div>
      <p class="text-xs text-neutral-500 tracking-widest uppercase">Admin Portal</p>
    </div>

    <?php if ($success): ?>
    <!-- ── Success state ── -->
    <div class="border border-neutral-800 rounded-2xl p-8 bg-neutral-900/40 text-center">
      <div class="w-14 h-14 rounded-full bg-green-950/60 border border-green-800/50 flex items-center justify-center mx-auto mb-5">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <h2 class="text-lg font-medium text-white mb-2">Account Created!</h2>
      <p class="text-sm text-neutral-400 mb-6">
        You can now sign in with <span class="text-white font-medium"><?= htmlspecialchars($_POST['username']) ?></span>.
      </p>
      <div class="rounded-lg border border-yellow-800/40 bg-yellow-950/30 px-4 py-3 text-xs text-yellow-500 text-left mb-6">
        <strong class="block mb-1">⚠ Security reminder</strong>
        Set <code>REGISTRATION_OPEN = false</code> in <code>register.php</code> or delete this file
        from your server so nobody else can create an admin account.
      </div>
      <a href="login.php"
         class="inline-block w-full rounded-lg bg-gradient-to-r from-pink-500 via-purple-600 to-purple-700 py-3 text-sm font-medium text-white text-center hover:opacity-90 transition">
        Go to Login →
      </a>
    </div>

    <?php else: ?>
    <!-- ── Registration form ── -->
    <div class="border border-neutral-800 rounded-2xl p-8 bg-neutral-900/40">
      <h1 class="text-xl font-light text-white mb-1">Create account</h1>
      <p class="text-sm text-neutral-500 mb-7">Add a new admin to your portfolio.</p>

      <!-- Error list -->
      <?php if (!empty($errors)): ?>
        <div class="mb-6 rounded-lg border border-red-800/50 bg-red-950/40 px-4 py-3 space-y-1">
          <?php foreach ($errors as $err): ?>
            <p class="flex items-start gap-2 text-sm text-red-400">
              <svg class="mt-0.5 shrink-0" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <?= htmlspecialchars($err) ?>
            </p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="register.php" novalidate id="reg-form">

        <!-- Username -->
        <div class="mb-4">
          <label class="block text-xs text-neutral-500 tracking-widest uppercase mb-2" for="username">Username</label>
          <input
            id="username" name="username" type="text"
            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
            class="field <?= in_array('Username is required.', $errors) || in_array('That username is already taken. Choose a different one.', $errors) ? 'error-field' : '' ?>"
            placeholder="e.g. mike_admin"
            autocomplete="username"
            maxlength="30"
            required
          />
          <p class="mt-1.5 text-xs text-neutral-600">Letters, numbers, underscores, hyphens, dots. 3–30 chars.</p>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label class="block text-xs text-neutral-500 tracking-widest uppercase mb-2" for="password">Password</label>
          <div class="relative">
            <input
              id="password" name="password" type="password"
              class="field pr-10"
              placeholder="Min. 8 characters"
              autocomplete="new-password"
              oninput="checkStrength(this.value)"
              required
            />
            <button type="button" onclick="toggleVis('password', this)"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-500 hover:text-neutral-300 transition"
              tabindex="-1" aria-label="Toggle password visibility">
              <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <!-- Strength bar -->
          <div class="mt-2 h-1 w-full rounded-full bg-neutral-800 overflow-hidden">
            <div id="strength-bar" style="width:0%"></div>
          </div>
          <p id="strength-label" class="mt-1 text-xs text-neutral-600"></p>
        </div>

        <!-- Confirm Password -->
        <div class="mb-7">
          <label class="block text-xs text-neutral-500 tracking-widest uppercase mb-2" for="confirm">Confirm Password</label>
          <div class="relative">
            <input
              id="confirm" name="confirm" type="password"
              class="field pr-10"
              placeholder="Repeat your password"
              autocomplete="new-password"
              required
            />
            <button type="button" onclick="toggleVis('confirm', this)"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-500 hover:text-neutral-300 transition"
              tabindex="-1" aria-label="Toggle confirm visibility">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button
          type="submit"
          class="w-full rounded-lg bg-gradient-to-r from-pink-500 via-purple-600 to-purple-700 py-3 text-sm font-medium text-white tracking-wide hover:opacity-90 active:scale-[.98] transition-all"
        >
          Create Account
        </button>
      </form>
    </div>

    <?php endif; ?>

    <p class="mt-6 text-center text-xs text-neutral-700">
      Already have an account?
      <a href="login.php" class="text-neutral-500 hover:text-white transition ml-1">Sign in →</a>
    </p>
    <p class="mt-2 text-center text-xs text-neutral-700">&copy; <?= date('Y') ?> Mike Clarence Fabregas</p>

  </div><!-- /fade-up -->

<script>
function toggleVis(fieldId, btn) {
  const input = document.getElementById(fieldId);
  input.type = (input.type === 'password') ? 'text' : 'password';
  // Swap eye icon opacity as a simple visual cue
  btn.style.opacity = (input.type === 'text') ? '1' : '0.5';
}

function checkStrength(pw) {
  const bar   = document.getElementById('strength-bar');
  const label = document.getElementById('strength-label');
  let score = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  const levels = [
    { pct: '0%',   color: 'transparent',       text: '' },
    { pct: '20%',  color: 'rgb(239,68,68)',    text: 'Very weak' },
    { pct: '40%',  color: 'rgb(249,115,22)',   text: 'Weak' },
    { pct: '60%',  color: 'rgb(234,179,8)',    text: 'Fair' },
    { pct: '80%',  color: 'rgb(34,197,94)',    text: 'Strong' },
    { pct: '100%', color: 'rgb(168,85,247)',   text: 'Very strong' },
  ];

  const lvl = levels[Math.min(score, 5)];
  bar.style.width      = lvl.pct;
  bar.style.background = lvl.color;
  label.textContent    = lvl.text;
  label.style.color    = lvl.color;
}
</script>

</body>
</html>
