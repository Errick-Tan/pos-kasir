/* Halaman kasir: keranjang, pencarian cepat, pembayaran, hapus pesanan + urungkan. */
(() => {
  'use strict';

  const $ = (s, r = document) => r.querySelector(s);
  const KEY = 'pos_cart_v1';
  const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const rp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

  const ICON = (d) => `<svg class="ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${d}</svg>`;
  const I = {
    minus: ICON('<path d="M5 12h14"/>'),
    plus: ICON('<path d="M12 5v14M5 12h14"/>'),
    trash: ICON('<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>'),
  };

  /* ---------- Data ---------- */
  const produk = new Map(JSON.parse($('#produk-data').textContent).map(p => [p.kode, p]));
  const cart = new Map();                         // kode -> jumlah
  const rows = new Map();                         // kode -> <li>
  const tiles = new Map([...document.querySelectorAll('.pcard')].map(el => [el.dataset.kode, el]));

  const el = {
    list: $('#cartList'), empty: $('#cartEmpty'), count: $('#cCount'), total: $('#cTotal'),
    clear: $('#btnClear'), bayar: $('#bayar'), bayarHidden: $('#bayarHidden'), cartJson: $('#cartJson'),
    chips: $('#chips'), result: $('#payResult'), btn: $('#btnBayar'), form: $('#formBayar'),
    barCount: $('#barCount'), barTotal: $('#barTotal'), cart: $('#cart'), backdrop: $('#backdrop'),
    toggle: $('#cartToggle'), close: $('#cartClose'), q: $('#q'), noResult: $('#noResult'), grid: $('#pgrid'),
  };

  // Pulihkan keranjang yang tersimpan (disesuaikan dengan stok terbaru)
  try {
    JSON.parse(localStorage.getItem(KEY) || '[]').forEach(([k, q]) => {
      const p = produk.get(k);
      if (p && p.stok > 0) cart.set(k, Math.min(Math.max(1, q | 0), p.stok));
    });
  } catch (_) { /* abaikan */ }

  const save = () => { try { localStorage.setItem(KEY, JSON.stringify([...cart])); } catch (_) { /* abaikan */ } };
  const pulse = node => { node.classList.remove('bump'); void node.offsetWidth; node.classList.add('bump'); };

  const totals = () => {
    let total = 0, count = 0;
    cart.forEach((q, k) => { total += produk.get(k).harga * q; count += q; });
    return { total, count };
  };

  /* ---------- Aksi keranjang ---------- */
  function add(kode, n = 1) {
    const p = produk.get(kode);
    if (!p || p.stok < 1) return;
    const cur = cart.get(kode) || 0;
    if (cur + n > p.stok) { toast(`Stok ${p.nama} hanya ${p.stok}.`, { type: 'warn' }); return; }
    cart.set(kode, cur + n);
    render(kode);
  }

  function setQty(kode, q) {
    if (q <= 0) return remove(kode);
    const p = produk.get(kode);
    if (q > p.stok) { toast(`Stok ${p.nama} hanya ${p.stok}.`, { type: 'warn' }); return; }
    cart.set(kode, q);
    render(kode);
  }

  // Hapus satu pesanan dari keranjang — bisa diurungkan.
  function remove(kode) {
    const qty = cart.get(kode);
    if (!qty) return;
    const p = produk.get(kode);
    cart.delete(kode);
    render();
    toast(`${p.nama} dihapus dari pesanan.`, {
      action: { label: 'Urungkan', fn: () => { cart.set(kode, Math.min(qty, p.stok)); render(kode); } },
    });
  }

  // Kosongkan seluruh pesanan — juga bisa diurungkan.
  function clearAll() {
    if (!cart.size) return;
    const snap = new Map(cart);
    cart.clear();
    render();
    toast('Semua pesanan dikosongkan.', {
      action: { label: 'Urungkan', fn: () => { snap.forEach((q, k) => cart.set(k, q)); render(); } },
    });
  }

  /* ---------- Render ---------- */
  const rowTemplate = `
    <div class="ci-inner"><div class="ci-body">
      <div class="ci-name"></div><div class="ci-sub"></div>
      <div class="ci-ctl">
        <div class="stepper">
          <button type="button" data-act="dec" aria-label="Kurangi jumlah">${I.minus}</button>
          <span class="q" aria-live="polite"></span>
          <button type="button" data-act="inc" aria-label="Tambah jumlah">${I.plus}</button>
        </div>
        <span class="ci-unit"></span>
      </div>
      <button type="button" class="icon-btn danger ci-remove" data-act="del" aria-label="Hapus dari pesanan" title="Hapus dari pesanan">${I.trash}</button>
    </div></div>`;

  let shown = 0, raf = 0;
  function tween(node, from, to) {
    cancelAnimationFrame(raf);
    if (reduceMotion || from === to) { node.textContent = rp(to); return; }
    const t0 = performance.now(), dur = 380;
    const step = now => {
      const k = Math.min(1, (now - t0) / dur), e = 1 - Math.pow(1 - k, 3);
      node.textContent = rp(from + (to - from) * e);
      if (k < 1) raf = requestAnimationFrame(step);
    };
    raf = requestAnimationFrame(step);
  }

  function render(touched) {
    // baris yang sudah tidak ada -> animasi ciut lalu dilepas
    rows.forEach((li, k) => {
      if (cart.has(k)) return;
      rows.delete(k);
      li.classList.add('removing');
      setTimeout(() => li.remove(), reduceMotion ? 0 : 360);
    });

    // baris baru / diperbarui (item terbaru tampil paling atas)
    cart.forEach((q, k) => {
      const p = produk.get(k);
      let li = rows.get(k);
      if (!li) {
        li = document.createElement('li');
        li.className = 'ci';
        li.dataset.kode = k;
        li.innerHTML = rowTemplate;
        $('.ci-name', li).textContent = p.nama;
        $('.ci-unit', li).textContent = `@ ${rp(p.harga)}`;
        el.list.prepend(li);
        el.list.scrollTop = 0;
        rows.set(k, li);
      }
      const qEl = $('.q', li);
      if (qEl.textContent !== String(q)) { qEl.textContent = q; if (touched === k) pulse(qEl); }
      $('.ci-sub', li).textContent = rp(p.harga * q);
      $('[data-act="inc"]', li).disabled = q >= p.stok;
    });

    // kartu produk
    tiles.forEach((tile, k) => {
      const q = cart.get(k) || 0;
      const badge = $('.pcard-qty', tile);
      tile.classList.toggle('in-cart', q > 0);
      if (badge.textContent !== String(q || '')) { badge.textContent = q || ''; if (touched === k) pulse(badge); }
    });

    const { total, count } = totals();
    el.empty.hidden = cart.size > 0;
    el.clear.hidden = cart.size === 0;
    el.count.textContent = count;
    el.barCount.textContent = count;
    el.barTotal.textContent = rp(total);
    el.toggle.hidden = false;
    tween(el.total, shown, total);
    shown = total;

    updatePay();
    save();
  }

  /* ---------- Pembayaran ---------- */
  const getBayar = () => parseInt((el.bayar.value || '').replace(/\D/g, ''), 10) || 0;
  const setBayar = n => { el.bayar.value = n ? n.toLocaleString('id-ID') : ''; updatePay(); };

  function suggestions(total) {
    const out = new Set();
    [5000, 10000, 20000, 50000, 100000].forEach(d => { const v = Math.ceil(total / d) * d; if (v > total) out.add(v); });
    return [...out].sort((a, b) => a - b).slice(0, 3);
  }

  let chipsFor = -1;
  function updatePay() {
    const { total } = totals();
    const bayar = getBayar();

    if (chipsFor !== total) {
      chipsFor = total;
      el.chips.replaceChildren();
      if (total > 0) {
        [['Uang pas', total], ...suggestions(total).map(v => [rp(v), v])].forEach(([label, v]) => {
          const b = document.createElement('button');
          b.type = 'button'; b.className = 'chip'; b.textContent = label;
          b.addEventListener('click', () => { setBayar(v); });
          el.chips.append(b);
        });
      }
    }

    if (total === 0) {
      el.result.className = 'pay-result'; el.result.textContent = '';
    } else if (bayar >= total) {
      el.result.className = 'pay-result ok'; el.result.innerHTML = `Kembalian <b>${rp(bayar - total)}</b>`;
    } else if (bayar > 0) {
      el.result.className = 'pay-result low'; el.result.innerHTML = `Kurang <b>${rp(total - bayar)}</b>`;
    } else {
      el.result.className = 'pay-result'; el.result.textContent = 'Masukkan uang yang diterima.';
    }
    el.btn.disabled = !(total > 0 && bayar >= total);
  }

  el.bayar.addEventListener('input', () => setBayar(getBayar()));

  el.form.addEventListener('submit', e => {
    const { total } = totals();
    if (el.btn.disabled || !total) { e.preventDefault(); return; }
    el.cartJson.value = JSON.stringify([...cart].map(([kode, qty]) => ({ kode, qty })));
    el.bayarHidden.value = String(getBayar());
    el.btn.disabled = true;
    $('span', el.btn).textContent = 'Memproses…';
  });
  addEventListener('pageshow', () => { $('span', el.btn).textContent = 'Bayar & cetak struk'; updatePay(); });

  /* ---------- Interaksi ---------- */
  el.grid.addEventListener('click', e => {
    const tile = e.target.closest('.pcard');
    if (tile) add(tile.dataset.kode);
  });

  el.list.addEventListener('click', e => {
    const btn = e.target.closest('[data-act]');
    if (!btn) return;
    const kode = btn.closest('.ci').dataset.kode;
    const act = btn.dataset.act;
    if (act === 'inc') setQty(kode, (cart.get(kode) || 0) + 1);
    if (act === 'dec') setQty(kode, (cart.get(kode) || 0) - 1);
    if (act === 'del') remove(kode);
  });

  el.clear.addEventListener('click', clearAll);

  // Pencarian instan; Enter menambah produk (cocok untuk pemindai barcode)
  el.q.addEventListener('input', () => {
    const t = el.q.value.trim().toLowerCase();
    let hits = 0;
    tiles.forEach((tile, k) => {
      const p = produk.get(k);
      const hit = !t || p.nama.toLowerCase().includes(t) || k.toLowerCase().includes(t);
      tile.hidden = !hit;
      if (hit) hits++;
    });
    el.noResult.hidden = hits > 0 || tiles.size === 0;
  });

  el.q.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const t = el.q.value.trim().toLowerCase();
    if (!t) return;
    const exact = [...produk.values()].find(p => p.kode.toLowerCase() === t);
    const visible = [...tiles].filter(([, tile]) => !tile.hidden);
    const pick = exact || (visible.length === 1 ? produk.get(visible[0][0]) : null);
    if (!pick) { toast('Ketik kode lengkap atau persempit pencarian dulu.'); return; }
    if (pick.stok < 1) { toast(`${pick.nama} sedang habis.`, { type: 'warn' }); return; }
    add(pick.kode);
    el.q.value = '';
    el.q.dispatchEvent(new Event('input'));
  });

  addEventListener('keydown', e => {
    if (e.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)) {
      e.preventDefault();
      el.q.focus();
    }
    if (e.key === 'Escape') closeSheet();
  });

  /* ---------- Lembar pesanan (mobile) ---------- */
  const openSheet = () => { el.cart.classList.add('open'); el.backdrop.classList.add('open'); document.body.classList.add('sheet-open'); };
  function closeSheet() { el.cart.classList.remove('open'); el.backdrop.classList.remove('open'); document.body.classList.remove('sheet-open'); }
  el.toggle.addEventListener('click', openSheet);
  el.close.addEventListener('click', closeSheet);
  el.backdrop.addEventListener('click', closeSheet);

  /* ---------- Mulai ---------- */
  render();
})();
