/* ============================================================
   IMMIGRATION DIVISION 2 — SHARED BEHAVIOUR
   สคริปต์กลาง: โหมดสีเข้ม, แท็บกรอง, จุดสไลด์
   ============================================================ */
(function () {
  'use strict';

  /* ---------- โหมดสีเข้ม ---------- */
  var root = document.documentElement;
  var saved = null;
  try { saved = localStorage.getItem('imm-theme'); } catch (e) {}
  var initial = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

  function applyTheme(mode) {
    root.setAttribute('data-theme', mode);
    document.querySelectorAll('.theme-btn').forEach(function (b) {
      b.textContent = mode === 'dark' ? '☀' : '🌙';
      b.setAttribute('aria-label', mode === 'dark' ? 'สลับเป็นโหมดสว่าง' : 'สลับเป็นโหมดสีเข้ม');
    });
  }
  applyTheme(initial);

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.theme-btn');
    if (!btn) return;
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    try { localStorage.setItem('imm-theme', next); } catch (err) {}
  });

  /* ---------- แท็บกรองเนื้อหา ---------- */
  document.querySelectorAll('.chips').forEach(function (group) {
    group.addEventListener('click', function (e) {
      var b = e.target.closest('button');
      if (!b || b.disabled) return;
      group.querySelectorAll('button').forEach(function (x) {
        x.setAttribute('aria-pressed', 'false');
      });
      b.setAttribute('aria-pressed', 'true');
    });
  });

  /* ---------- จุดสไลด์ ---------- */
  document.querySelectorAll('.carousel-dots').forEach(function (dots) {
    dots.addEventListener('click', function (e) {
      var b = e.target.closest('button');
      if (!b) return;
      dots.querySelectorAll('button').forEach(function (x) {
        x.setAttribute('aria-current', 'false');
      });
      b.setAttribute('aria-current', 'true');
    });
  });

  /* ---------- สลับภาษา ---------- */
  document.querySelectorAll('.lang').forEach(function (group) {
    group.addEventListener('click', function (e) {
      var b = e.target.closest('button');
      if (!b) return;
      group.querySelectorAll('button').forEach(function (x) {
        x.setAttribute('aria-pressed', 'false');
      });
      b.setAttribute('aria-pressed', 'true');
    });
  });
})();
