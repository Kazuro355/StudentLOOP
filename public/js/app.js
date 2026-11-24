/* StudentLOOP — JS ringan (tanpa build step). */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var rp = function (n) { return 'Rp' + Math.round(n).toLocaleString('id-ID'); };

  /* Tab: tombol [data-tab="grup:nama"] <-> panel [data-tabpanel="grup:nama"] */
  function showTab(key) {
    var group = key.split(':')[0];
    $$('[data-tab^="' + group + ':"]').forEach(function (b) { b.classList.toggle('active', b.dataset.tab === key); });
    $$('[data-tabpanel^="' + group + ':"]').forEach(function (p) { p.hidden = p.dataset.tabpanel !== key; });
  }
  $$('[data-tab]').forEach(function (b) {
    b.addEventListener('click', function () { showTab(b.dataset.tab); });
  });
  if (location.hash === '#tanya' && $('[data-tab="detail:tanya"]')) showTab('detail:tanya');
  if (location.hash === '#ulasan' && $('[data-tab="detail:ulasan"]')) showTab('detail:ulasan');

  /* Galeri foto barang */
  $$('[data-gallery]').forEach(function (g) {
    var main = $('[data-gallery-main]', g);
    $$('[data-src]', g).forEach(function (t) {
      t.addEventListener('click', function () {
        if (main) main.innerHTML = '<img src="' + t.dataset.src + '" alt="">';
        $$('[data-src]', g).forEach(function (x) { x.classList.toggle('active', x === t); });
      });
    });
  });

  /* Pilihan metode pembayaran: tampilkan field sesuai metode */
  function syncMethods() {
    var checked = $('input[name="method"]:checked');
    $$('[data-method-fields]').forEach(function (el) {
      var on = checked && el.dataset.methodFields === checked.value;
      el.hidden = !on;
      $$('input,select', el).forEach(function (i) { i.disabled = !on; });
    });
  }
  $$('input[name="method"]').forEach(function (r) { r.addEventListener('change', syncMethods); });
  if ($('input[name="method"]')) syncMethods();

  /* Hitung mundur batas pembayaran */
  $$('[data-countdown]').forEach(function (el) {
    var end = new Date(el.dataset.countdown).getTime();
    function tick() {
      var s = Math.max(0, Math.floor((end - Date.now()) / 1000));
      var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = s % 60;
      el.textContent = [h, m, x].map(function (v) { return String(v).padStart(2, '0'); }).join(':');
      if (s === 0) { clearInterval(t); el.textContent = 'Waktu habis'; }
    }
    var t = setInterval(tick, 1000); tick();
  });

  /* Salin teks (nomor VA, kode referensi) */
  $$('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var text = b.dataset.copy;
      var done = function () { var old = b.textContent; b.textContent = 'Tersalin'; setTimeout(function () { b.textContent = old; }, 1500); };
      if (navigator.clipboard) navigator.clipboard.writeText(text).then(done); else done();
    });
  });

  /* Chat: scroll ke pesan terbaru */
  var log = $('.chat-log');
  if (log) log.scrollTop = log.scrollHeight;

  /* Cegah kirim ganda */
  $$('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function () {
      $$('button[type=submit]', f).forEach(function (b) { b.disabled = true; });
    });
  });

  /* Konfirmasi aksi berisiko */
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); });
  });

  /* Kalender pilih tanggal sewa */
  var cal = $('#rent-cal');
  if (cal) {
    var booked = JSON.parse(cal.dataset.booked || '[]');
    var min = +cal.dataset.min, max = +cal.dataset.max;
    var price = +cal.dataset.price, deposit = +cal.dataset.deposit;
    var startIn = $('#start_date'), endIn = $('#end_date'), msg = $('#cal-msg');
    var pref = +cal.dataset.days || min;
    var start = startIn.value || null, end = endIn.value || null;
    var view = new Date(); view.setDate(1);
    var pad = function (n) { return String(n).padStart(2, '0'); };
    var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };
    var parse = function (s) { var p = s.split('-').map(Number); return new Date(p[0], p[1] - 1, p[2]); };
    var addDays = function (s, n) { var d = parse(s); d.setDate(d.getDate() + n); return iso(d); };
    var diff = function (a, b) { return Math.round((parse(b) - parse(a)) / 86400000) + 1; };
    var today = iso(new Date());
    var isBooked = function (s) { return booked.some(function (r) { return s >= r[0] && s <= r[1]; }); };
    var rangeBooked = function (a, b) { return booked.some(function (r) { return a <= r[1] && b >= r[0]; }); };
    var say = function (t) { if (msg) msg.textContent = t || ''; };

    function summary() {
      var days = start && end ? diff(start, end) : 0;
      $$('[data-sum="days"]').forEach(function (e) { e.textContent = days ? days + ' hari' : '–'; });
      $$('[data-sum="subtotal"]').forEach(function (e) { e.textContent = days ? rp(price * days) : '–'; });
      $$('[data-sum="total"]').forEach(function (e) { e.textContent = days ? rp(price * days + deposit) : '–'; });
      $$('[data-sum="range"]').forEach(function (e) {
        e.textContent = start && end ? start.split('-').reverse().join('/') + ' – ' + end.split('-').reverse().join('/') : 'Pilih tanggal di kalender';
      });
      $$('[data-days]').forEach(function (c) { c.classList.toggle('active', +c.dataset.days === (days || pref)); });
      var btn = $('#rent-submit'); if (btn) btn.disabled = !(start && end);
      startIn.value = start || ''; endIn.value = end || '';
    }

    function render() {
      var y = view.getFullYear(), m = view.getMonth();
      var first = new Date(y, m, 1).getDay(), count = new Date(y, m + 1, 0).getDate();
      var html = '<div class="cal-head"><button type="button" data-nav="-1" aria-label="Bulan sebelumnya">&lsaquo;</button><b>' +
        view.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) +
        '</b><button type="button" data-nav="1" aria-label="Bulan berikutnya">&rsaquo;</button></div><div class="cal-grid">';
      ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'].forEach(function (d) { html += '<div class="cal-dow">' + d + '</div>'; });
      for (var i = 0; i < first; i++) html += '<div></div>';
      for (var d = 1; d <= count; d++) {
        var s = y + '-' + pad(m + 1) + '-' + pad(d);
        var cls = 'cal-day';
        if (s === today) cls += ' today';
        if (start && (s === start || s === end)) cls += ' edge';
        else if (start && end && s > start && s < end) cls += ' in-range';
        var dis = s < today || isBooked(s);
        html += '<button type="button" class="' + cls + '" data-date="' + s + '"' + (dis ? ' disabled' : '') + '>' + d + '</button>';
      }
      cal.innerHTML = html + '</div>';
      $$('[data-nav]', cal).forEach(function (b) {
        b.addEventListener('click', function () { view.setMonth(view.getMonth() + (+b.dataset.nav)); render(); });
      });
      $$('[data-date]', cal).forEach(function (b) { b.addEventListener('click', function () { pick(b.dataset.date); }); });
      summary();
    }

    function setRange(s, n) {
      var e = addDays(s, n - 1);
      if (rangeBooked(s, e)) { say('Sebagian tanggal itu sudah terpakai. Pilih durasi lebih pendek atau tanggal lain.'); start = s; end = null; return; }
      start = s; end = e; say('');
    }

    function pick(s) {
      say('');
      if (!start || end || s < start) {
        setRange(s, pref);   // klik tanggal mulai: isi otomatis sesuai durasi pilihan
      } else {
        if (rangeBooked(start, s)) { say('Rentang itu melewati tanggal yang sudah terpakai.'); return; }
        var n = diff(start, s);
        if (n > max) { say('Maksimal sewa ' + max + ' hari.'); return; }
        if (n < min) { say('Minimal sewa ' + min + ' hari.'); return; }
        end = s; pref = n;
      }
      render();
    }

    $$('[data-days]').forEach(function (c) {
      c.addEventListener('click', function () {
        pref = Math.min(Math.max(+c.dataset.days, min), max);
        if (start) setRange(start, pref);
        render();
      });
    });

    if (start) view = new Date(parse(start).getFullYear(), parse(start).getMonth(), 1);
    render();
  }
})();
