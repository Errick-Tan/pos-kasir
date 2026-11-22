/* Perilaku umum: toast, pencarian otomatis, dialog hapus, cetak. */
(() => {
  'use strict';

  /* ---------- Tema terang/gelap ---------- */
  const root = document.documentElement;
  const syncThemeButtons = () => {
    const dark = root.dataset.theme === 'dark';
    document.querySelectorAll('[data-theme-toggle]').forEach(b => {
      b.setAttribute('aria-label', dark ? 'Ganti ke mode terang' : 'Ganti ke mode gelap');
      b.title = dark ? 'Mode terang' : 'Mode gelap';
    });
  };
  const applyTheme = (t, animate = true) => {
    if (animate && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
      root.classList.add('theme-anim');
      setTimeout(() => root.classList.remove('theme-anim'), 450);
    }
    root.dataset.theme = t;
    syncThemeButtons();
  };
  document.querySelectorAll('[data-theme-toggle]').forEach(b => b.addEventListener('click', () => {
    const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('pos_theme', next); } catch (_) { /* abaikan */ }
    applyTheme(next);
  }));
  // Ikuti perubahan tema sistem selama pengguna belum memilih sendiri
  matchMedia('(prefers-color-scheme: dark)').addEventListener?.('change', e => {
    let saved = null;
    try { saved = localStorage.getItem('pos_theme'); } catch (_) { /* abaikan */ }
    if (!saved) applyTheme(e.matches ? 'dark' : 'light');
  });
  syncThemeButtons();

  /* ---------- Toast ---------- */
  const box = document.getElementById('toasts');
  window.toast = (msg, { type = 'info', action = null, ttl = 4500 } = {}) => {
    if (!box) return;
    const el = document.createElement('div');
    el.className = 'toast t-' + type;
    const span = document.createElement('span');
    span.textContent = msg;
    el.append(span);

    const close = () => {
      if (!el.isConnected || el.classList.contains('leaving')) return;
      el.classList.add('leaving');
      setTimeout(() => el.remove(), 300);
    };
    if (action) {
      const b = document.createElement('button');
      b.type = 'button';
      b.textContent = action.label;
      b.addEventListener('click', () => { action.fn(); close(); });
      el.append(b);
    }
    box.append(el);
    // batasi jumlah toast yang tampil bersamaan
    while (box.children.length > 3) box.firstElementChild.remove();
    setTimeout(close, action ? Math.max(ttl, 6000) : ttl);
  };

  const flash = document.getElementById('flash-data');
  if (flash && flash.dataset.flash) {
    try { JSON.parse(flash.dataset.flash).forEach(f => toast(f.msg, { type: f.type === 'ok' ? 'info' : f.type, ttl: 5500 })); } catch (_) { /* abaikan */ }
  }

  /* ---------- Form pencarian/filter otomatis ---------- */
  document.querySelectorAll('form[data-autosubmit]').forEach(form => {
    let t;
    form.addEventListener('input', e => {
      if (e.target.matches('input[type="search"], input[type="text"]')) {
        clearTimeout(t);
        t = setTimeout(() => form.requestSubmit(), 380);
      } else if (e.target.matches('input[type="date"], select')) {
        form.requestSubmit();
      }
    });
  });
  const refocus = document.querySelector('[data-refocus]');
  if (refocus && refocus.value) {
    refocus.focus();
    refocus.setSelectionRange(refocus.value.length, refocus.value.length);
  }

  /* ---------- Dialog konfirmasi hapus ---------- */
  const dlg = document.getElementById('dlg-hapus');
  if (dlg) {
    document.querySelectorAll('[data-delete]').forEach(btn => btn.addEventListener('click', () => {
      document.getElementById('hapus-kode').value = btn.dataset.kode;
      document.getElementById('hapus-nama').textContent = btn.dataset.nama;
      dlg.showModal();
    }));
    dlg.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => dlg.close()));
    dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); }); // klik di luar kotak
  }

  /* ---------- Cetak & bersihkan keranjang setelah bayar ---------- */
  document.querySelectorAll('[data-print]').forEach(b => b.addEventListener('click', () => window.print()));
  if (document.body.hasAttribute('data-clear-cart')) {
    try { localStorage.removeItem('pos_cart_v1'); } catch (_) { /* abaikan */ }
  }
})();
