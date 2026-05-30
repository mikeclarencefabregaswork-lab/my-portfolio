<?php
// ============================================================
//  index.php  —  Portfolio Frontend
// ============================================================
require_once __DIR__ . '/db.php';

$pdo = get_pdo();

// Fetch data
$experiences = $pdo->query('SELECT * FROM experiences ORDER BY sort_order, id')->fetchAll();
$projects    = $pdo->query('SELECT * FROM projects    ORDER BY sort_order, id')->fetchAll();

// Group projects by category
$grouped = ['web_app' => [], 'photo_editing' => [], 'video_editing' => []];
foreach ($projects as $p) {
    $grouped[$p['category']][] = $p;
}

function decode_tech(string $json): array {
    return json_decode($json, true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mike Clarence Fabregas — Fullstack Developer</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,200;0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />

  <style>
    /* ─── Base ──────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; }

    :root {
      --bg:         #0a0a0a;
      --border:     #1a1a1a;
      --text-muted: #737373;
      --purple:     rgb(168,85,247);
    }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: #e5e5e5;
      font-family: 'DM Sans', sans-serif;
      font-weight: 300;
      overflow-x: hidden;
    }

    /* ─── Subtle noise grain overlay ────────────────── */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
      opacity: .025;
      pointer-events: none;
      z-index: 0;
    }

    /* ─── Gradient text helper ───────────────────────── */
    .grad-text {
      background: linear-gradient(90deg, #f9a8d4, #64748b, #a855f7);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* ─── Floating icons (Technology cards) ─────────── */
    @keyframes float {
      0%, 100% { transform: translateY(-10px); }
      50%       { transform: translateY(10px);  }
    }
    .float-1 { animation: float 3s   ease-in-out infinite; }
    .float-2 { animation: float 2.5s ease-in-out infinite; }
    .float-3 { animation: float 4s   ease-in-out infinite; }
    .float-4 { animation: float 6s   ease-in-out infinite; }
    .float-5 { animation: float 5s   ease-in-out infinite; }

    /* ─── Scroll-reveal (IntersectionObserver driven) ── */
    .reveal {
      opacity: 0;
      transition: opacity .6s ease, transform .6s ease;
    }
    .reveal.from-left  { transform: translateX(-60px); }
    .reveal.from-right { transform: translateX(60px);  }
    .reveal.from-top   { transform: translateY(-40px); }
    .reveal.from-bot   { transform: translateY(40px);  }
    .reveal.visible    { opacity: 1; transform: none;  }

    /* ─── Hero entrance (CSS-only, no JS needed) ─────── */
    @keyframes slideInLeft {
      from { opacity: 0; transform: translateX(-80px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes slideInRight {
      from { opacity: 0; transform: translateX(80px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    .hero-title  { animation: slideInLeft .6s ease both; }
    .hero-sub    { animation: slideInLeft .6s .3s ease both; }
    .hero-body   { animation: slideInLeft .6s .5s ease both; }
    .hero-img    { animation: slideInRight .7s .8s ease both; }

    /* ─── Nav link hover underline ───────────────────── */
    .nav-link {
      position: relative;
      color: var(--text-muted);
      font-size: .85rem;
      letter-spacing: .06em;
      text-decoration: none;
      transition: color .2s;
    }
    .nav-link::after {
      content: '';
      position: absolute;
      bottom: -2px; left: 0;
      width: 0; height: 1px;
      background: var(--purple);
      transition: width .25s ease;
    }
    .nav-link:hover { color: #e5e5e5; }
    .nav-link:hover::after { width: 100%; }

    /* ─── Tech icon cards ────────────────────────────── */
    .tech-card {
      width: 80px;
      height: 80px;
      border-radius: 16px;
      border: 1.5px solid #2a2a2a;
      background: #111111;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: border-color .2s, transform .25s;
    }
    .tech-card:hover {
      border-color: var(--purple);
      transform: translateY(-5px);
    }
    .tech-card img {
      width: 40px;
      height: 40px;
      object-fit: contain;
    }
    .tech-card span {
      font-size: 9px;
      color: #525252;
      font-family: monospace;
      letter-spacing: .04em;
    }

    /* ─── Project card ───────────────────────────────── */
    .project-card {
      display: flex;
      flex-wrap: wrap;
      gap: 1.5rem;
      margin-bottom: 2rem;
      cursor: pointer;
      padding: 1.25rem;
      border-radius: 1rem;
      border: 1px solid transparent;
      transition: border-color .2s, background .2s, transform .2s;
      text-decoration: none;
      color: inherit;
    }
    .project-card:hover {
      border-color: #2a2a2a;
      background: rgba(168,85,247,.04);
      transform: translateY(-2px);
    }

    /* ─── Category section heading pill ─────────────── */
    .cat-pill {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      border-radius: 9999px;
      padding: .25rem .9rem;
      font-size: .7rem;
      font-weight: 500;
      letter-spacing: .1em;
      text-transform: uppercase;
      margin-bottom: 1.5rem;
    }

    /* ─── Social icon hover ──────────────────────────── */
    .social-link {
      color: var(--text-muted);
      font-size: 1.5rem;
      transition: color .2s, transform .2s;
    }
    .social-link:hover { color: #e5e5e5; transform: translateY(-2px); }

    /* ─── Section divider ────────────────────────────── */
    .section-divider {
      border-bottom: 1px solid var(--border);
      padding-bottom: 1.5rem;
    }

    /* ─── SVG icons (inline) ─────────────────────────── */
    svg { display: inline-block; vertical-align: middle; }
  </style>
</head>
<body class="relative">

<!-- ══════════════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════════════ -->
<nav class="sticky top-0 z-50 border-b border-neutral-900 bg-neutral-950/70 backdrop-blur-md">
  <div class="mx-auto max-w-6xl px-6 py-4 flex items-center justify-between">

    <!-- Logo -->
    <a href="#hero" class="flex items-center gap-2">
      <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center text-xs font-semibold text-white tracking-wider">MC</span>
      <span class="text-sm font-light tracking-widest text-neutral-300 hidden sm:block">Mike Clarence</span>
    </a>

    <!-- Nav links -->
    <div class="hidden md:flex items-center gap-7">
      <a href="#about"       class="nav-link">About</a>
      <a href="#technologies" class="nav-link">Tech</a>
      <a href="#experience"  class="nav-link">Experience</a>
      <a href="#projects"    class="nav-link">Projects</a>
      <a href="#contact"     class="nav-link">Contact</a>
    </div>

    <!-- Social icons (desktop) -->
    <div class="flex items-center gap-3">
      <a href="https://www.facebook.com/mikeclarencef" target="_blank" class="social-link" aria-label="Facebook">
        <!-- Facebook SVG -->
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.971h-1.513c-1.491 0-1.956.93-1.956 1.886v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
      </a>
      <a href="https://github.com/mikeclarencefabregaswork-lab" target="_blank" class="social-link" aria-label="GitHub">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
      </a>
      <a href="https://www.instagram.com/mikeclarencef/" target="_blank" class="social-link" aria-label="Instagram">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
      </a>
      <a href="https://www.linkedin.com/in/mike-clarence-fabregas-487b38277/" target="_blank" class="social-link" aria-label="LinkedIn">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
      </a>
      <a href="https://x.com/mikeclarencef" target="_blank" class="social-link" aria-label="X / Twitter">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
      </a>
    </div>

  </div>
</nav>

<div class="mx-auto max-w-6xl px-6 relative z-10">

<!-- ══════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════ -->
<section id="hero" class="section-divider py-20 lg:py-28">
  <div class="flex flex-wrap items-center gap-10">

    <div class="w-full lg:w-1/2">
      <h1 class="hero-title pb-6 text-5xl font-thin tracking-tight lg:text-7xl leading-tight text-white">
        Mike Clarence<br/>Fabregas
      </h1>
      <span class="hero-sub grad-text text-2xl tracking-tight font-light">
        Fullstack Developer
      </span>
      <p class="hero-body my-6 max-w-xl font-light tracking-tight text-neutral-400 leading-relaxed">
        I craft performant, accessible, and visually refined web applications — from database architecture to polished user interfaces. Passionate about clean code, thoughtful design, and shipping things that matter.
      </p>
      <div class="hero-body flex gap-4 mt-8">
        <a href="#projects" class="rounded-lg bg-gradient-to-r from-pink-500 to-purple-600 px-6 py-2.5 text-sm text-white font-medium hover:opacity-90 transition">
          View Work
        </a>
        <a href="#contact" class="rounded-lg border border-neutral-700 px-6 py-2.5 text-sm text-neutral-300 hover:bg-neutral-800 transition">
          Contact
        </a>
      </div>
    </div>

    <div class="w-full lg:w-5/12 flex justify-center">
      <div class="hero-img relative">
        <!-- Gradient blob behind photo -->
        <div class="absolute inset-0 -m-6 rounded-full bg-gradient-to-br from-pink-600/20 via-purple-700/15 to-transparent blur-3xl"></div>
        <img
          src="uploads/IMG_0828.JPG"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
          alt="Mike Clarence Fabregas"
          class="relative rounded-2xl w-64 h-64 lg:w-80 lg:h-80 object-cover border border-neutral-800 shadow-2xl"
        />
        <!-- Fallback avatar if photo not uploaded -->
        <div style="display:none" class="relative rounded-2xl w-64 h-64 lg:w-80 lg:h-80 border border-neutral-800 bg-gradient-to-br from-neutral-900 to-neutral-800 items-center justify-center">
          <span class="text-6xl font-thin text-neutral-600">MCF</span>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     ABOUT
══════════════════════════════════════════════════════════ -->
<section id="about" class="section-divider py-20">
  <h2 class="reveal from-top my-10 text-center text-4xl font-thin text-white">
    About <span class="text-neutral-500">Me</span>
  </h2>
  <div class="flex flex-wrap items-center gap-10">
    <div class="reveal from-left w-full lg:w-5/12 flex justify-center">
      <img
        src="uploads/IMG_0830.JPG"
        onerror="this.src='https://placehold.co/340x400/111/333?text=MCF'"
        alt="About Mike"
        class="rounded-2xl w-72 object-cover border border-neutral-800 shadow-xl"
      />
    </div>
    <div class="reveal from-right w-full lg:w-1/2">
      <p class="max-w-xl text-neutral-400 leading-relaxed font-light tracking-tight">
        I'm a Fullstack Developer based in Lipa City, Batangas, Philippines with a strong command of modern JavaScript, PHP, Python, and their surrounding ecosystems. My work spans e-commerce platforms, productivity tools, REST APIs, and creative digital content.
      </p>
      <p class="max-w-xl mt-4 text-neutral-400 leading-relaxed font-light tracking-tight">
        Beyond code, I enjoy photo and video editing — combining technical precision with creative storytelling. I believe that great software is both functional and beautiful, and I strive to bring that philosophy to every project I touch.
      </p>
      <div class="mt-8 flex flex-wrap gap-3">
        <?php foreach (['PHP','React','Node.js','MySQL','Python','Tailwind CSS', 'Capcut'] as $skill): ?>
          <span class="rounded-full border border-neutral-700 px-3 py-1 text-xs text-neutral-400 tracking-wider"><?= htmlspecialchars($skill) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     TECHNOLOGIES
══════════════════════════════════════════════════════════ -->
<section id="technologies" class="section-divider py-20">
  <h2 class="reveal from-top my-10 text-center text-4xl font-thin text-white">
    Technologies
  </h2>
  <div class="reveal from-left flex flex-wrap items-center justify-center gap-5">

    <?php
    $tech_icons = [
      ['name' => 'React',      'file' => 'icons/react.png',      'float' => 'float-1'],
      ['name' => 'Python',     'file' => 'icons/python.png',     'float' => 'float-2'],
      ['name' => 'HTML5',      'file' => 'icons/HTML5.png',      'float' => 'float-3'],
      ['name' => 'CSS3',       'file' => 'icons/CSS3.png',       'float' => 'float-4'],
      ['name' => 'JavaScript', 'file' => 'icons/JavaScript.png', 'float' => 'float-5'],
      ['name' => 'PHP',        'file' => 'icons/PHP.png',        'float' => 'float-1'],
      ['name' => 'MySQL',      'file' => 'icons/mySQL.png',      'float' => 'float-3'],
      ['name' => 'Capcut',     'file' => 'icons/Capcut.png',     'float' => 'float-2'],
    ];
    foreach ($tech_icons as $icon): ?>
      <div class="tech-card <?= $icon['float'] ?>">
        <img
          src="<?= htmlspecialchars($icon['file']) ?>"
          alt="<?= htmlspecialchars($icon['name']) ?>"
          width="40" height="40"
          onerror="this.style.opacity='.3'"
        />
        <span><?= htmlspecialchars($icon['name']) ?></span>
      </div>
    <?php endforeach; ?>

  </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     EXPERIENCE
══════════════════════════════════════════════════════════ -->
<section id="experience" class="section-divider py-20">
  <h2 class="reveal from-top my-10 text-center text-4xl font-thin text-white">
    Experience
  </h2>

  <?php foreach ($experiences as $exp):
    $techs = decode_tech($exp['technologies']);
  ?>
  <div class="mb-10 flex flex-wrap lg:justify-center gap-4">
    <div class="reveal from-left w-full lg:w-1/4">
      <p class="text-sm text-neutral-500 tracking-wide"><?= htmlspecialchars($exp['year']) ?></p>
    </div>
    <div class="reveal from-right w-full lg:w-2/4">
      <h3 class="mb-1 font-medium text-white">
        <?= htmlspecialchars($exp['role']) ?>
        <span class="text-sm font-light text-purple-300 ml-1">— <?= htmlspecialchars($exp['company']) ?></span>
      </h3>
      <p class="mb-4 text-sm text-neutral-400 leading-relaxed"><?= htmlspecialchars($exp['description']) ?></p>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($techs as $tech): ?>
          <span class="rounded bg-neutral-900 border border-neutral-800 px-2.5 py-1 text-xs font-medium text-purple-300">
            <?= htmlspecialchars($tech) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($experiences)): ?>
    <p class="text-center text-neutral-600 text-sm">No experience entries yet.</p>
  <?php endif; ?>
</section>

<!-- ══════════════════════════════════════════════════════════
     PROJECTS
══════════════════════════════════════════════════════════ -->
<section id="projects" class="section-divider py-20">
  <h2 class="reveal from-top my-10 text-center text-4xl font-thin text-white">
    Projects
  </h2>

  <?php
  $categories = [
    'web_app'       => ['label' => 'Web & App Development', 'dot' => 'bg-cyan-400',   'pill' => 'bg-cyan-950/60 text-cyan-400 border-cyan-900',   'icon' => '⟨/⟩'],
    'photo_editing' => ['label' => 'Photo Editing',          'dot' => 'bg-purple-400', 'pill' => 'bg-purple-950/60 text-purple-400 border-purple-900', 'icon' => '◈'],
    'video_editing' => ['label' => 'Video Editing',          'dot' => 'bg-rose-400',   'pill' => 'bg-rose-950/60 text-rose-400 border-rose-900',   'icon' => '▷'],
  ];

  foreach ($categories as $cat_key => $cat):
    if (empty($grouped[$cat_key])) continue;
  ?>

  <!-- Category Heading -->
  <div class="reveal from-left mb-6 mt-14">
    <span class="cat-pill border <?= $cat['pill'] ?>">
      <span><?= $cat['icon'] ?></span>
      <?= htmlspecialchars($cat['label']) ?>
    </span>
    <div class="h-px bg-gradient-to-r from-neutral-800 to-transparent"></div>
  </div>

  <!-- Projects in this category -->
  <?php foreach ($grouped[$cat_key] as $proj):
    $techs       = decode_tech($proj['technologies']);
    $safe_url    = filter_var($proj['project_url'], FILTER_VALIDATE_URL) ? $proj['project_url'] : '#';
    $target      = ($safe_url !== '#') ? '_blank' : '_self';
    $rel         = ($target === '_blank') ? ' rel="noopener noreferrer"' : '';
  ?>
  <a href="<?= htmlspecialchars($safe_url) ?>"
     target="<?= $target ?>"
     <?= $rel ?>
     class="project-card reveal from-bot">

    <div class="w-full lg:w-1/4 flex-shrink-0">
      <img
        src="<?= htmlspecialchars($proj['image_path']) ?>"
        onerror="this.src='https://placehold.co/250x160/111/333?text=Project'"
        alt="<?= htmlspecialchars($proj['title']) ?>"
        width="250"
        height="160"
        class="rounded-lg object-cover w-full max-w-[260px] border border-neutral-800"
        loading="lazy"
      />
    </div>

    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2 mb-2">
        <h3 class="font-medium text-white"><?= htmlspecialchars($proj['title']) ?></h3>
        <?php if ($safe_url !== '#'): ?>
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#737373" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        <?php endif; ?>
      </div>
      <p class="mb-4 text-sm text-neutral-400 leading-relaxed"><?= htmlspecialchars($proj['description']) ?></p>
      <div class="flex flex-wrap gap-2">
        <?php foreach ($techs as $tech): ?>
          <span class="rounded bg-neutral-900 border border-neutral-800 px-2.5 py-1 text-xs font-medium text-neutral-400">
            <?= htmlspecialchars($tech) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>

  </a>
  <?php endforeach; ?>

  <?php endforeach; ?>

  <?php if (array_sum(array_map('count', $grouped)) === 0): ?>
    <p class="text-center text-neutral-600 text-sm">No projects yet.</p>
  <?php endif; ?>

</section>

<!-- ══════════════════════════════════════════════════════════
     CONTACT
══════════════════════════════════════════════════════════ -->
<section id="contact" class="py-20">
  <h2 class="reveal from-top my-10 text-center text-4xl font-thin text-white">
    Get in Touch
  </h2>
  <div class="text-center tracking-tighter space-y-4">
    <p class="reveal from-left text-neutral-400">Lipa City, Batangas, Philippines</p>
    <p class="reveal from-left text-neutral-400">0955-405-9911</p>
    <a class="reveal from-left inline-block border-b border-neutral-600 text-neutral-300 hover:text-white hover:border-white transition"
       href="mailto:mikeclarencefabregas@gmail.com">
      mikeclarencefabregas.work@gmail.com
    </a>
  </div>

  <!-- Social row -->
  <div class="reveal from-bot mt-12 flex justify-center gap-6">
    <a href="https://www.facebook.com/mikeclarencef" target="_blank" class="social-link">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.971h-1.513c-1.491 0-1.956.93-1.956 1.886v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
    </a>
    <a href="https://github.com/mikeclarencefabregaswork-lab" target="_blank" class="social-link">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
    </a>
    <a href="https://www.instagram.com/mikeclarencef/" target="_blank" class="social-link">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
    </a>
    <a href="https://www.linkedin.com/in/mike-clarence-fabregas-487b38277/" target="_blank" class="social-link">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
    </a>
    <a href="https://x.com/mikeclarencef" target="_blank" class="social-link">
      <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
    </a>
  </div>
</section>

</div><!-- /max-w-6xl -->

<!-- ══════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════ -->
<footer class="border-t border-neutral-900 mt-10 py-6 text-center text-xs text-neutral-700">
  &copy; <?= date('Y') ?> Mike Clarence Fabregas &mdash; Lipa City, Batangas
  <span class="mx-3 text-neutral-800">|</span>
  <a href="login.php" class="hover:text-neutral-500 transition">Admin</a>
</footer>

<!-- ══════════════════════════════════════════════════════════
     SCROLL REVEAL  (lightweight IntersectionObserver)
══════════════════════════════════════════════════════════ -->
<script>
(function () {
  const els = document.querySelectorAll('.reveal');
  if (!els.length) return;

  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });

  els.forEach(el => io.observe(el));
})();
</script>

</body>
</html>
