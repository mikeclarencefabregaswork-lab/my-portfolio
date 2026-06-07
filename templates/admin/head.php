<?php
// Shared <head> for admin templates
?>
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

  /* ── Toast notifications ── */
  #toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: .6rem;
    pointer-events: none;
  }
  .toast {
    display: flex;
    align-items: flex-start;
    gap: .65rem;
    min-width: 280px;
    max-width: 420px;
    border-radius: .6rem;
    padding: .75rem 1rem;
    font-size: .8rem;
    line-height: 1.4;
    pointer-events: all;
    box-shadow: 0 4px 24px rgba(0,0,0,.5);
    animation: toastIn .25s ease both;
  }
  .toast.dismissing { animation: toastOut .3s ease forwards; }
  .toast-error   { background:#1a0a0a; border:1px solid rgba(239,68,68,.5);  color:#fca5a5; }
  .toast-warn    { background:#1a1000; border:1px solid rgba(234,179,8,.45); color:#fde68a; }
  .toast-success { background:#0a1a0a; border:1px solid rgba(34,197,94,.4);  color:#86efac; }
  .toast-icon { flex-shrink:0; margin-top:.05rem; }
  .toast-close {
    margin-left:auto; flex-shrink:0;
    background:none; border:none; cursor:pointer;
    color:inherit; opacity:.5; font-size:1rem; line-height:1;
    padding:0 0 0 .4rem;
    transition: opacity .15s;
  }
  .toast-close:hover { opacity:1; }
  @keyframes toastIn  { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:none; } }
  @keyframes toastOut { from { opacity:1; transform:none; } to { opacity:0; transform:translateY(8px); } }
</style>
</head>
<body class="min-h-screen">
