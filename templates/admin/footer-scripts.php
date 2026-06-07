<?php
// Shared toast + tab JS.
// Expects: $php_toasts array (each: ['type'=>..., 'msg'=>...])
?>

<!-- ── Toast container ── -->
<div id="toast-container" role="alert" aria-live="polite"></div>

<script>
/* ── Tab switcher ── */
function switchTab(tab) {
  document.getElementById('panel-projects').style.display    = (tab === 'projects')    ? '' : 'none';
  document.getElementById('panel-experiences').style.display = (tab === 'experiences') ? '' : 'none';
  const url = new URL(window.location);
  url.searchParams.set('tab', tab);
  url.searchParams.delete('edit_project');
  url.searchParams.delete('edit_exp');
  history.replaceState(null, '', url);
}

/* ── Toast system ── */
const ICONS = {
  error:   '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
  warn:    '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  success: '<svg class="toast-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
};

function showToast(type, msg, duration) {
  duration = duration || (type === 'error' ? 0 : type === 'warn' ? 12000 : 5000);
  const container = document.getElementById('toast-container');
  const t = document.createElement('div');
  t.className = 'toast toast-' + type;
  t.innerHTML = ICONS[type] +
    '<span style="flex:1">' + msg + '</span>' +
    '<button class="toast-close" aria-label="Dismiss">&times;</button>';

  const close = t.querySelector('.toast-close');
  let timer;
  const dismiss = () => {
    clearTimeout(timer);
    t.classList.add('dismissing');
    t.addEventListener('animationend', () => t.remove(), { once: true });
  };
  close.addEventListener('click', dismiss);
  if (duration > 0) timer = setTimeout(dismiss, duration);

  container.appendChild(t);
}

/* ── Client-side file validation — shows toast before form submits ── */
function attachFileValidation(inputSelector) {
  document.querySelectorAll(inputSelector).forEach(function(input) {
    input.addEventListener('change', function() {
      const file = this.files[0];
      if (!file) return;
      const allowed = ['image/jpeg','image/png','image/webp','image/gif'];
      if (!allowed.includes(file.type)) {
        showToast('error', 'Invalid file type: <strong>' + file.name + '</strong>. Only JPEG, PNG, WebP and GIF are allowed.');
        this.value = '';
        return;
      }
      const mb = file.size / 1048576;
      if (mb > 4) {
        showToast('error', 'File too large: <strong>' + mb.toFixed(1) + ' MB</strong>. Maximum allowed size is 4 MB.');
        this.value = '';
      }
    });
  });
}

/* ── Fire PHP-generated toasts on page load ── */
document.addEventListener('DOMContentLoaded', function() {
  attachFileValidation('input[type="file"]');

  var phpToasts = <?= json_encode($php_toasts ?? []) ?>;
  phpToasts.forEach(function(t) { showToast(t.type, t.msg); });
});
</script>


