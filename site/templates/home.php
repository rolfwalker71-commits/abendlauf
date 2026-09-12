<?php snippet('header') ?>

<?php
$hero = $page->heroBild()->toFile();

// Die Austragungsnummer wird als eigenes Gestaltungselement gesetzt.
preg_match('/^(\d+)\.\s*(.*)$/u', $page->headline()->or($page->title())->value(), $teile);
$nummer     = $teile[1] ?? null;
$restTitel  = $teile[2] ?? $page->headline()->or($page->title())->value();

// Nur Termine ab heute, chronologisch, höchstens drei.
$heute = strtotime('today');
$naechste = $page->laufabende()->toStructure()
    ->filter(fn($t) => $t->datum()->toDate() >= $heute)
    ->sortBy(fn($t) => $t->datum()->toDate(), 'asc')
    ->limit(3);

$sponsoren = ($sp = $site->find('sponsoren')) ? $sp->sponsoren()->toStructure() : null;
// Auf der Startseite steht nur das Patronat — die vollständige Liste
// hat ihre eigene Seite.
$patronat = $sponsoren?->filterBy('kategorie', 'in', ['haupt', 'co']);

// Kennzahlen kommen aus der Ausschreibung, damit sie nie auseinanderlaufen.
$kennzahlen = [];
if ($aus = $site->find('ausschreibung')) {
    $kat = $aus->kategorien()->toStructure();
    if ($kat->isNotEmpty()) {
        $distanzen = $kat->pluck('distanz', null, true);
        $gelder = array_map(
            fn($g) => (float)filter_var($g, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            $kat->pluck('startgeld', null, true)
        );
        $gelder = array_filter($gelder);
        $kennzahlen = [
            ['Kleine Runde',  $kat->filterBy('runden', '*=', 'kleine')->first()?->distanz(), 'Rund ums Schloss A Pro'],
            ['Grosse Runde',  '2260 m', 'Weg der Schweiz'],
            ['Kategorien',    (string)$kat->count(), 'Vom Pfüderi bis zur Volksläuferin'],
            ['Startgeld',     $gelder ? 'CHF ' . (int)min($gelder) . '–' . (int)max($gelder) : null, 'Anmeldung vor Ort'],
        ];
        $kennzahlen = array_filter($kennzahlen, fn($k) => !empty((string)$k[1]));
    }
}

// Neueste Alben für den Fotostreifen
$alben = ($fo = $site->find('fotos')) ? $fo->children()->listed()->sortBy('jahr', 'desc')->limit(3) : null;
?>

<section class="hero <?= $hero ? '' : 'hero--plain' ?>">
  <div class="hero__text">
    <?php snippet('runlogo') ?>
    <p class="eyebrow"><?= $site->untertitel()->or('STV Altdorf') ?> · Seedorf am Urnersee</p>

    <?php if ($nummer): ?>
      <p class="hero__edition"><?= $nummer ?><sup>.</sup></p>
      <h1><?= $restTitel ?></h1>
    <?php else: ?>
      <h1><?= $restTitel ?></h1>
    <?php endif ?>

    <?php if ($page->subline()->isNotEmpty()): ?>
      <p class="hero__lead"><?= $page->subline() ?></p>
    <?php endif ?>

    <?php if ($naechste->isNotEmpty()):
      $naechster = $naechste->first();
      $ziel = $naechster->datum()->toDate();
    ?>
      <div class="countdown" data-countdown="<?= date('c', $ziel) ?>" aria-live="off">
        <div class="countdown__unit"><span class="countdown__value" data-unit="tage">–</span><span class="countdown__label">Tage</span></div>
        <div class="countdown__unit"><span class="countdown__value" data-unit="stunden">–</span><span class="countdown__label">Stunden</span></div>
        <div class="countdown__unit"><span class="countdown__value" data-unit="minuten">–</span><span class="countdown__label">Minuten</span></div>
      </div>
      <p class="countdown__note">
        bis zum Start: <?= $naechster->titel() ?>, <?= date('d.m.Y', $ziel) ?> um <?= date('H:i', $ziel) ?> Uhr
      </p>
    <?php endif ?>

    <div class="hero__actions">
      <?php if ($a = $site->find('ausschreibung')): ?>
        <a class="btn btn--primary" href="<?= $a->url() ?>">Ausschreibung</a>
      <?php endif ?>
      <?php if ($r = $site->find('ranglisten')): ?>
        <a class="btn btn--ghost" href="<?= $r->url() ?>">Ranglisten</a>
      <?php endif ?>
    </div>
  </div>

  <?php if ($hero): ?>
    <div class="hero__media"><?= $hero->crop(1100, 900)->html(['alt' => '', 'fetchpriority' => 'high']) ?></div>
  <?php endif ?>
</section>

<?php if ($page->aktuellAktiv()->toBool(true) && $page->aktuellText()->isNotEmpty()): ?>
  <section class="section section--overlap">
    <div class="wrap">
      <div class="notice-panel">
        <div class="notice-panel__head">
          <p class="eyebrow" style="margin:0;">Vom OK</p>
          <h2><span class="section__num">01</span> <?= $page->aktuellTitel()->or('Aktuell') ?></h2>
          <?php if ($page->aktuellStand()->isNotEmpty()): ?>
            <p class="notice-panel__date">Stand <?= $page->aktuellStand()->toDate('d.m.Y') ?></p>
          <?php endif ?>
        </div>
        <div class="notice-panel__body prose">
          <?= $page->aktuellText()->kirbytextExtern() ?>
        </div>
      </div>
    </div>
  </section>
<?php endif ?>

<?php if ($page->intro()->isNotEmpty()): ?>
  <section class="section">
    <div class="wrap prose" style="max-width: 46rem;"><?= $page->intro()->kirbytext() ?></div>
  </section>
<?php endif ?>

<?php if ($naechste->isNotEmpty()): ?>
  <section class="section">
    <div class="wrap">
      <div class="section__head">
        <h2><span class="section__num">02</span> Die nächsten Laufabende</h2>
        <p class="section__aside">Anmeldung jeweils ab 16.30 Uhr vor Ort</p>
      </div>
      <div class="grid grid--3 reveal">
        <?php foreach ($naechste as $termin) snippet('event', ['termin' => $termin]) ?>
      </div>
    </div>
  </section>
<?php endif ?>

<?php if ($kennzahlen): ?>
  <section class="section">
    <div class="wrap">
      <div class="section__head">
        <h2><span class="section__num">03</span> Strecke und Kategorien</h2>
        <?php if ($a = $site->find('ausschreibung')): ?>
          <p class="section__aside"><a href="<?= $a->url() ?>">Ganze Ausschreibung</a></p>
        <?php endif ?>
      </div>
      <div class="grid grid--4 reveal">
        <?php foreach ($kennzahlen as [$label, $wert, $notiz]): ?>
          <div class="fact">
            <p class="eyebrow" style="margin-bottom:.5rem;"><?= $label ?></p>
            <p class="fact__value"><?= $wert ?></p>
            <p class="fact__note"><?= $notiz ?></p>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  </section>
<?php endif ?>

<?php if ($alben && $alben->isNotEmpty()): ?>
  <section class="section">
    <div class="wrap">
      <div class="section__head">
        <h2><span class="section__num">04</span> Aus den Alben</h2>
        <p class="section__aside"><a href="<?= $fo->url() ?>">Alle Alben</a></p>
      </div>
      <div class="bleed-strip">
        <?php foreach ($alben as $album):
          $cover = $album->cover()->toFile() ?? $album->images()->sortBy('sort', 'asc')->first();
        ?>
          <a class="album-card" href="<?= $album->url() ?>">
            <div class="album-card__img">
              <?php if ($cover): ?>
                <?= $cover->crop(800, 534)->html(['alt' => '', 'loading' => 'lazy']) ?>
              <?php endif ?>
            </div>
            <h3 class="album-card__title"><?= $album->title() ?></h3>
            <p class="album-card__meta"><?= $album->images()->count() ?> Bilder</p>
          </a>
        <?php endforeach ?>
      </div>
    </div>
  </section>
<?php endif ?>

<?php if ($page->aktionAktiv()->toBool(true) && $page->aktionText()->isNotEmpty()):
  $aktionBilder = $page->aktionBilder()->toFiles();
?>
  <section class="section section--tint">
    <div class="wrap">
      <div class="offer">
        <div class="offer__text">
          <?php if ($page->aktionPartner()->isNotEmpty()): ?>
            <p class="eyebrow"><?= $page->aktionPartner() ?></p>
          <?php endif ?>
          <h2><span class="section__num">05</span> <?= $page->aktionTitel()->or('Aktion') ?></h2>
          <div class="prose"><?= $page->aktionText()->kirbytextExtern() ?></div>
          <?php if ($page->aktionLink()->isNotEmpty()): ?>
            <p style="margin-top:1.4rem;">
              <a class="btn btn--primary" href="<?= $page->aktionLink() ?>" target="_blank" rel="noopener">
                <?= $page->aktionLinkText()->or('Mehr erfahren') ?>
              </a>
            </p>
          <?php endif ?>
        </div>

        <?php if ($aktionBilder->isNotEmpty()): ?>
          <div class="offer__media">
            <?php foreach ($aktionBilder as $bild): ?>
              <a href="<?= $bild->url() ?>" data-lightbox data-caption="<?= $bild->alt()->escape('attr') ?>">
                <?= $bild->resize(700)->html(['alt' => $bild->alt()->escape('attr'), 'loading' => 'lazy']) ?>
              </a>
            <?php endforeach ?>
          </div>
        <?php endif ?>
      </div>
    </div>
  </section>
<?php endif ?>

<?php if ($patronat && $patronat->isNotEmpty()): ?>
  <section class="section section--tint">
    <div class="wrap">
      <div class="section__head">
        <h2><span class="section__num">06</span> Patronat</h2>
        <?php if ($sp): ?>
          <p class="section__aside">
            <a href="<?= $sp->url() ?>">Alle <?= $sponsoren->filterBy('aktiv', true)->count() ?> Sponsoren ansehen</a>
          </p>
        <?php endif ?>
      </div>
      <?php snippet('sponsors', ['sponsoren' => $patronat]) ?>
    </div>
  </section>
<?php endif ?>

<?php snippet('footer') ?>
