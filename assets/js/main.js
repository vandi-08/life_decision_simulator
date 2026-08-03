/**
 * Life Decision Simulator Indonesia — core client JS
 */

document.addEventListener('DOMContentLoaded', function () {
  initThemeToggle();
  initFlashMessages();
  initAnimatedNumbers();
  initRupiahInputs();
  initPrioritySliders();
});

/* ---------- Dark mode (persisted via localStorage) ---------- */
function initThemeToggle() {
  var toggle = document.getElementById('themeToggle');
  if (!toggle) return;

  var icon = toggle.querySelector('i');
  var current = document.documentElement.getAttribute('data-theme') || 'light';
  updateIcon(icon, current);

  toggle.addEventListener('click', function () {
    var theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('ldsi_theme', theme);
    updateIcon(icon, theme);
  });
}
function updateIcon(icon, theme) {
  if (!icon) return;
  icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
}

/* ---------- Flash messages ---------- */
function initFlashMessages() {
  document.querySelectorAll('.flash-close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.flash').remove();
    });
  });
  document.querySelectorAll('.flash').forEach(function (flash) {
    setTimeout(function () { flash.style.display = 'none'; }, 6000);
  });
}

/* ---------- Animated numbers (data-animate-number="1234567") ---------- */
function initAnimatedNumbers() {
  document.querySelectorAll('[data-animate-number]').forEach(function (el) {
    var target = parseFloat(el.getAttribute('data-animate-number')) || 0;
    var prefix = el.getAttribute('data-prefix') || '';
    var duration = 900;
    var start = null;

    function step(ts) {
      if (!start) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      var value = Math.round(target * eased);
      el.textContent = prefix + value.toLocaleString('id-ID');
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });
}

/* ---------- Rupiah-formatted number inputs (data-rupiah-input) ---------- */
function initRupiahInputs() {
  document.querySelectorAll('[data-rupiah-input]').forEach(function (input) {
    var hidden = document.getElementById(input.dataset.rupiahTarget);

    function format() {
      var raw = input.value.replace(/[^0-9]/g, '');
      if (hidden) hidden.value = raw;
      input.value = raw ? Number(raw).toLocaleString('id-ID') : '';
    }
    input.addEventListener('input', format);
    format();
  });
}

/* ---------- Priority sliders that must total 100% ---------- */
function initPrioritySliders() {
  var sliders = document.querySelectorAll('.priority-slider input[type="range"]');
  var totalEl = document.getElementById('priorityTotal');
  if (!sliders.length || !totalEl) return;

  function recalc() {
    var total = 0;
    sliders.forEach(function (s) {
      total += parseInt(s.value, 10) || 0;
      var valEl = document.getElementById(s.id + '_value');
      if (valEl) valEl.textContent = s.value + '%';
    });
    totalEl.textContent = 'Total: ' + total + '%';
    totalEl.classList.toggle('invalid', total !== 100);

    var submitBtn = document.getElementById('simulatorSubmit');
    if (submitBtn) submitBtn.disabled = (total !== 100);
  }

  sliders.forEach(function (s) { s.addEventListener('input', recalc); });
  recalc();
}

/* ---------- Small confirm-dialog helper for delete actions ---------- */
function confirmAction(message) {
  return window.confirm(message || 'Apakah kamu yakin?');
}
