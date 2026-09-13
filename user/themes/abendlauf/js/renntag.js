/* Urner Abendläufe — Laufabend heute.
   Leiste „Jetzt am Start“ unten auf jeder Seite und Zeitplan auf der
   Startseite. Die Seiten liegen bis zu 15 Minuten im Cache, deshalb rechnet
   der Browser mit seiner eigenen Uhr. Daten: #renntag-daten (Laufabende der
   Startseite, Startzeiten der Ausschreibung) aus partials/base.html.twig. */

(function () {
  'use strict';

  var quelle = document.getElementById('renntag-daten');
  if (!quelle) return;
  var daten;
  try { daten = JSON.parse(quelle.textContent); } catch (e) { return; }
  if (!daten.abende || !daten.abende.length) return;

  var MIN = 60 * 1000;
  var STUNDE = 60 * MIN;
  var VORLAUF = 90 * MIN;      // Leiste ab 90 Minuten vor dem ersten Start …
  var NACHLAUF = 60 * MIN;     // … bis eine Stunde nach dem letzten
  var SPEICHER = 'renntag-ausgeblendet';

  var leiste = document.getElementById('renntag-leiste');
  var plan = document.getElementById('renntag-plan');

  function minuten(hhmm) {
    var t = String(hhmm).split(':');
    return (parseInt(t[0], 10) || 0) * 60 + (parseInt(t[1], 10) || 0);
  }

  /* Startblöcke eines Abends als feste Zeitpunkte. Gerechnet wird ab der
     Startzeit des Laufabends (mit Zeitzone), damit auch ein Browser in
     einer anderen Zeitzone richtig liegt. Gleiche Zeiten ergeben einen Block. */
  function bloeckeVon(abend) {
    var basis = new Date(abend.start).getTime();
    var basisMinuten = minuten(abend.start.substr(11, 5));
    var nachZeit = {};
    var liste = [];
    (daten.starts || []).forEach(function (k) {
      if (!nachZeit[k.zeit]) {
        nachZeit[k.zeit] = { zeit: k.zeit, um: basis + (minuten(k.zeit) - basisMinuten) * MIN, kategorien: [] };
        liste.push(nachZeit[k.zeit]);
      }
      nachZeit[k.zeit].kategorien.push(k);
    });
    return liste.sort(function (a, b) { return a.um - b.um; });
  }

  /* Was gilt jetzt? null = nichts anzeigen */
  function lage(jetzt) {
    for (var i = 0; i < daten.abende.length; i++) {
      var abend = daten.abende[i];
      var start = new Date(abend.start).getTime();
      var amTag = jetzt > start - 12 * STUNDE && jetzt < start + 6 * STUNDE;
      if (abend.status !== 'findet-statt') {
        if (amTag) return { art: 'warnung', abend: abend, amTag: true };
        continue;
      }
      var bloecke = bloeckeVon(abend);
      if (!bloecke.length) continue;
      var aktuell = null, naechster = null;
      bloecke.forEach(function (b) {
        if (b.um <= jetzt) aktuell = b;
        else if (!naechster) naechster = b;
      });
      var inLeiste = jetzt >= bloecke[0].um - VORLAUF && jetzt <= bloecke[bloecke.length - 1].um + NACHLAUF;
      if (amTag) {
        return { art: 'lauf', abend: abend, bloecke: bloecke, aktuell: aktuell, naechster: naechster, inLeiste: inLeiste, amTag: true };
      }
    }
    return null;
  }

  function namen(block) {
    var n = block.kategorien.map(function (k) { return k.name; });
    if (n.length > 3) return n.slice(0, 2).join(', ') + ' und ' + (n.length - 2) + ' weitere';
    if (n.length > 1) return n.slice(0, -1).join(', ') + ' und ' + n[n.length - 1];
    return n[0] + (block.kategorien[0].distanz ? ' (' + block.kategorien[0].distanz + ')' : '');
  }

  function bis(ms) {
    var m = Math.max(1, Math.ceil(ms / MIN));
    return m === 1 ? 'in 1 Minute' : 'in ' + m + ' Minuten';
  }

  function setze(el, text) {
    if (el && el.textContent !== text) el.textContent = text;
  }

  /* ---- Leiste unten ------------------------------------------------- */

  function zeigeLeiste(l, jetzt) {
    if (!leiste) return;
    var marke = leiste.querySelector('[data-renntag="marke"]');
    var text = leiste.querySelector('[data-renntag="text"]');
    var ausgeblendet = null;
    try { ausgeblendet = sessionStorage.getItem(SPEICHER); } catch (e) {}

    var sichtbar = l && (l.art === 'warnung' || l.inLeiste) && ausgeblendet !== l.abend.start;
    leiste.hidden = !sichtbar;
    document.documentElement.classList.toggle('mit-renntag-leiste', !!sichtbar);
    if (!sichtbar) return;

    leiste.classList.toggle('renntag-leiste--warnung', l.art === 'warnung');
    if (l.art === 'warnung') {
      var abgesagt = l.abend.status === 'abgesagt';
      setze(marke, abgesagt ? 'Abgesagt' : 'Verschoben');
      setze(text, l.abend.titel + ': ' + (l.abend.hinweis ||
        (abgesagt ? 'Der Laufabend findet heute nicht statt.' : 'Der Laufabend findet heute nicht wie geplant statt.')));
    } else if (!l.aktuell) {
      setze(marke, 'Heute');
      setze(text, 'Erster Start ' + l.naechster.zeit + ' Uhr: ' + namen(l.naechster) + ' – ' + bis(l.naechster.um - jetzt));
    } else if (l.naechster) {
      setze(marke, 'Jetzt am Start');
      setze(text, l.aktuell.zeit + ' Uhr ' + namen(l.aktuell) + ' · Als Nächstes ' + l.naechster.zeit + ' Uhr ' +
        namen(l.naechster) + ', ' + bis(l.naechster.um - jetzt));
    } else {
      setze(marke, 'Unterwegs');
      setze(text, 'Seit ' + l.aktuell.zeit + ' Uhr: ' + namen(l.aktuell) + ' · letzter Start des Abends');
    }
  }

  if (leiste) {
    var zu = leiste.querySelector('[data-renntag="zu"]');
    if (zu) {
      zu.addEventListener('click', function () {
        var l = lage(Date.now());
        try { if (l) sessionStorage.setItem(SPEICHER, l.abend.start); } catch (e) {}
        leiste.hidden = true;
        document.documentElement.classList.remove('mit-renntag-leiste');
      });
    }
  }

  /* ---- Zeitplan auf der Startseite ---------------------------------- */

  function zeigePlan(l, jetzt) {
    if (!plan) return;
    var gilt = l && l.amTag && l.abend.start === plan.getAttribute('data-start');
    plan.hidden = !gilt;
    if (!gilt || l.art !== 'lauf') return;

    var zeilen = plan.querySelectorAll('[data-startzeit]');
    Array.prototype.forEach.call(zeilen, function (zeile) {
      var zeit = zeile.getAttribute('data-startzeit');
      var istJetzt = !!(l.aktuell && l.aktuell.zeit === zeit && (l.naechster || jetzt <= l.aktuell.um + NACHLAUF));
      var vorbei = !istJetzt && l.bloecke.some(function (b) { return b.zeit === zeit && b.um <= jetzt; });
      zeile.classList.toggle('is-jetzt', istJetzt);
      zeile.classList.toggle('is-vorbei', vorbei);
      if (istJetzt) zeile.setAttribute('aria-current', 'time');
      else zeile.removeAttribute('aria-current');
    });
  }

  function aktualisiere() {
    var jetzt = Date.now();
    var l = lage(jetzt);
    zeigeLeiste(l, jetzt);
    zeigePlan(l, jetzt);
  }

  aktualisiere();
  setInterval(aktualisiere, 30 * 1000);
})();
