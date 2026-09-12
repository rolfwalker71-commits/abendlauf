<?php
/** @var \Kirby\Cms\Site $site */
/** @var \Kirby\Cms\Page $page */
$logo = $site->logo()->toFile();
?>
<!DOCTYPE html>
<html lang="de-CH">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $page->isHomePage() ? $site->titel()->or($site->title()) : $page->title() . ' — ' . $site->titel()->or($site->title()) ?></title>
  <?php if ($page->intro()->isNotEmpty()): ?>
    <meta name="description" content="<?= $page->intro()->excerpt(160) ?>">
  <?php endif ?>

  <?php
  // Farbschema VOR dem ersten Zeichnen setzen, sonst blitzt beim Laden
  // kurz die falsche Variante auf.
  ?>
  <script>
    try {
      var t = localStorage.getItem('theme');
      if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t;
    } catch (e) {}
  </script>

  <?php
  // Die beiden Schnitte, die sofort sichtbar sind, mit Vorrang laden.
  // Ohne das zeichnet der Browser erst mit der Systemschrift und
  // tauscht sichtbar um (FOUT). crossorigin ist auch bei eigenen
  // Dateien nötig, weil Schriften immer per CORS geholt werden.
  ?>
  <link rel="preload" href="<?= url('assets/fonts/Oswald-600.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="<?= url('assets/fonts/SourceSans3-400.woff2') ?>" as="font" type="font/woff2" crossorigin>

  <link rel="stylesheet" href="<?= url('assets/css/main.css') ?>">
  <link rel="icon" href="<?= url('assets/img/logo.svg') ?>" type="image/svg+xml">
</head>
<body>

<?php /* Lesefortschritt — rein per CSS an das Scrollen gekoppelt. */ ?>
<div class="progress" aria-hidden="true"></div>

<?php if ($site->homePage()->hinweisAktiv()->toBool() && $site->homePage()->hinweisText()->isNotEmpty()): ?>
  <div class="notice">
    <?php if ($site->homePage()->hinweisLink()->isNotEmpty()): ?>
      <a href="<?= $site->homePage()->hinweisLink() ?>"><?= $site->homePage()->hinweisText() ?></a>
    <?php else: ?>
      <?= $site->homePage()->hinweisText() ?>
    <?php endif ?>
  </div>
<?php endif ?>

<header class="site-header">
  <div class="wrap site-header__inner">

    <a class="brand" href="<?= $site->url() ?>">
      <span class="brand__logo"><?= svg('assets/img/logo.svg') ?></span>
      <span class="brand__name">
        <?= $site->titel()->or($site->title()) ?>
        <?php if ($site->untertitel()->isNotEmpty()): ?>
          <span class="brand__sub"><?= $site->untertitel() ?></span>
        <?php endif ?>
      </span>
    </a>

    <nav class="nav" id="nav" aria-label="Hauptnavigation">
      <?php foreach ($site->children()->listed() as $item): ?>
        <a href="<?= $item->url() ?>"<?= $item->isOpen() ? ' aria-current="page"' : '' ?>><?= $item->title() ?></a>
      <?php endforeach ?>
    </nav>

    <div style="display:flex; gap:.45rem; align-items:center;">
      <button class="theme-toggle" type="button" id="theme-toggle"
              aria-label="Zwischen hellem und dunklem Erscheinungsbild wechseln">
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="12" cy="12" r="4.5"/>
          <path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.5 1.5M17.6 17.6l1.5 1.5M19.1 4.9l-1.5 1.5M6.4 17.6l-1.5 1.5" stroke-linecap="round"/>
        </svg>
        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a6.8 6.8 0 0 0 10.5 10.5z" stroke-linejoin="round"/>
        </svg>
      </button>

      <button class="nav-toggle" type="button" id="nav-toggle" aria-controls="nav" aria-expanded="false" aria-label="Menü öffnen">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

  </div>
</header>

<main>
