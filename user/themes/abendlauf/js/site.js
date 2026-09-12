/* Urner Abendläufe — alles an Javascript, was die Seite braucht.
   Ohne Framework, ohne externe Abhängigkeit. */

(function () {
  'use strict';

  /* ---- Hell / Dunkel ------------------------------------------------ */

  var root   = document.documentElement;
  var toggle = document.getElementById('theme-toggle');

  function systemPrefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  if (toggle) {
    toggle.addEventListener('click', function () {
      var jetztDunkel = root.dataset.theme
        ? root.dataset.theme === 'dark'
        : systemPrefersDark();
      var neu = jetztDunkel ? 'light' : 'dark';
      root.dataset.theme = neu;
      try { localStorage.setItem('theme', neu); } catch (e) {}
    });
  }

  /* ---- Menü auf kleinen Bildschirmen -------------------------------- */

  var navToggle = document.getElementById('nav-toggle');
  var nav       = document.getElementById('nav');

  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      var offen = nav.dataset.open === 'true';
      nav.dataset.open = offen ? 'false' : 'true';
      navToggle.setAttribute('aria-expanded', offen ? 'false' : 'true');
      navToggle.setAttribute('aria-label', offen ? 'Menü öffnen' : 'Menü schliessen');
    });
  }


  /* ---- Countdown auf den nächsten Laufabend ------------------------- */

  var uhr = document.querySelector('[data-countdown]');
  if (uhr) {
    var ziel = new Date(uhr.getAttribute('data-countdown')).getTime();
    var felder = {
      tage:     uhr.querySelector('[data-unit="tage"]'),
      stunden:  uhr.querySelector('[data-unit="stunden"]'),
      minuten:  uhr.querySelector('[data-unit="minuten"]')
    };

    function tick() {
      var rest = ziel - Date.now();
      if (rest <= 0) {
        uhr.hidden = true;
        return;
      }
      var minute = 60 * 1000, stunde = 60 * minute, tag = 24 * stunde;
      felder.tage.textContent    = Math.floor(rest / tag);
      felder.stunden.textContent = Math.floor((rest % tag) / stunde);
      felder.minuten.textContent = Math.floor((rest % stunde) / minute);
    }
    tick();
    setInterval(tick, 30000);
  }

  /* ---- Sponsorenband: Inhalt verdoppeln für den Endlosumlauf ------- */

  /* Die CSS-Animation verschiebt das Band um 50 % — damit das lückenlos
     aussieht, muss der Inhalt genau zweimal vorhanden sein. */
  document.querySelectorAll('[data-marquee] .marquee__track').forEach(function (track) {
    var inhalt = track.querySelector('.sponsors');
    if (!inhalt) return;
    var kopie = inhalt.cloneNode(true);
    kopie.setAttribute('aria-hidden', 'true');
    track.appendChild(kopie);
  });

  /* ---- Abschnitte beim Scrollen einblenden -------------------------- */

  // Weitere Blöcke automatisch zum Einblenden anmelden, damit das
  // nicht in jedem Template einzeln ausgezeichnet werden muss.
  document.querySelectorAll(
    '.section .grid, .notice-panel, .results, .sponsors, .bleed-strip, .gallery'
  ).forEach(function (el) {
    // Das laufende Sponsorenband ausnehmen: sein Inhalt wird geklont,
    // und die Kopie würde sonst dauerhaft unsichtbar bleiben.
    if (el.closest('[data-marquee]')) return;
    el.classList.add('reveal');
  });

  var zuZeigen = document.querySelectorAll('.reveal');
  if (zuZeigen.length && 'IntersectionObserver' in window) {
    var beobachter = new IntersectionObserver(function (eintraege) {
      eintraege.forEach(function (e) {
        if (!e.isIntersecting) return;
        e.target.classList.add('is-visible');
        beobachter.unobserve(e.target);
      });
    }, { rootMargin: '0px 0px -12% 0px' });
    zuZeigen.forEach(function (el) { beobachter.observe(el); });
  } else {
    zuZeigen.forEach(function (el) { el.classList.add('is-visible'); });
  }


  /* ---- Bildbetrachter für die Alben --------------------------------- */

  var bilder = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
  if (!bilder.length) return;

  var box, bild, beschriftung, zaehler, index = 0;

  function aufbauen() {
    box = document.createElement('div');
    box.className = 'lightbox';
    box.setAttribute('role', 'dialog');
    box.setAttribute('aria-modal', 'true');
    box.setAttribute('aria-label', 'Bildansicht');
    box.innerHTML =
      '<button class="lightbox__close" aria-label="Schliessen">&times;</button>' +
      '<button class="lightbox__nav lightbox__nav--prev" aria-label="Vorheriges Bild">&#8249;</button>' +
      '<figure class="lightbox__figure"><img alt=""><figcaption></figcaption></figure>' +
      '<button class="lightbox__nav lightbox__nav--next" aria-label="Nächstes Bild">&#8250;</button>' +
      '<p class="lightbox__count"></p>';
    document.body.appendChild(box);

    bild         = box.querySelector('img');
    beschriftung = box.querySelector('figcaption');
    zaehler      = box.querySelector('.lightbox__count');

    box.querySelector('.lightbox__close').addEventListener('click', schliessen);
    box.querySelector('.lightbox__nav--prev').addEventListener('click', function (e) { e.stopPropagation(); blaettern(-1); });
    box.querySelector('.lightbox__nav--next').addEventListener('click', function (e) { e.stopPropagation(); blaettern(1); });
    box.addEventListener('click', function (e) { if (e.target === box) schliessen(); });
  }

  function zeigen(i) {
    index = (i + bilder.length) % bilder.length;
    var a = bilder[index];
    bild.src = a.getAttribute('href');
    bild.alt = a.querySelector('img') ? a.querySelector('img').alt : '';
    var text = a.getAttribute('data-caption') || '';
    beschriftung.textContent = text;
    beschriftung.hidden = text === '';
    zaehler.textContent = (index + 1) + ' von ' + bilder.length;
  }

  function oeffnen(i) {
    if (!box) aufbauen();
    zeigen(i);
    box.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    box.querySelector('.lightbox__close').focus();
  }

  function schliessen() {
    box.classList.remove('is-open');
    document.body.style.overflow = '';
    bilder[index].focus();
  }

  function blaettern(richtung) { zeigen(index + richtung); }

  bilder.forEach(function (a, i) {
    a.addEventListener('click', function (e) { e.preventDefault(); oeffnen(i); });
  });

  document.addEventListener('keydown', function (e) {
    if (!box || !box.classList.contains('is-open')) return;
    if (e.key === 'Escape')     schliessen();
    if (e.key === 'ArrowLeft')  blaettern(-1);
    if (e.key === 'ArrowRight') blaettern(1);
  });
})();
