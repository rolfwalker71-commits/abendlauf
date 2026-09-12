<?php snippet('header') ?>

<?php
/**
 * Die Ranglisten liegen als Dateien an dieser Seite, jede mit Jahr und Lauf.
 * Daraus wird eine Matrix: eine Zeile pro Jahr, eine Spalte pro Abend.
 */
$listen = $page->files()->template('rangliste')->filterBy('art', 'rangliste');

$jahre = [];
foreach ($listen as $datei) {
    $jahr = (int)$datei->jahr()->value();
    if (!$jahr) continue;
    $jahre[$jahr][$datei->lauf()->or('gesamt')->value()] = $datei;
}
krsort($jahre);

$aktuell = $jahre ? array_key_first($jahre) : null;
// Die Abendläufe starteten 1995 — daraus ergibt sich die Austragungsnummer.
$nummer = fn(int $jahr) => $jahr - 1994;
$spalten = ['1' => '1. Abend', '2' => '2. Abend', '3' => '3. Abend', 'gesamt' => 'Gesamt'];
?>

<?php snippet('pagehead', [
  'eyebrow' => $jahre ? 'Resultate ' . array_key_last($jahre) . ' bis ' . $aktuell : 'Resultate',
  'titel'   => $page->title(),
  'lead'    => $page->intro()->isNotEmpty() ? $page->intro()->html() : null,
  'hintergrund' => $page->kopfHintergrund()->toFile(),
]) ?>

<?php if ($aktuell && isset($jahre[$aktuell]['gesamt'])): ?>
  <section class="section" style="padding-bottom:0;">
    <div class="wrap">
      <a class="result-highlight" href="<?= $jahre[$aktuell]['gesamt']->url() ?>" download>
        <span>
          <h3>Gesamtrangliste <?= $aktuell ?></h3>
          <p>Alle Kategorien über alle Abende · PDF, <?= $jahre[$aktuell]['gesamt']->niceSize() ?></p>
        </span>
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M12 3v13" stroke-linecap="round"/>
          <path d="m6.5 11.5 5.5 5.5 5.5-5.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M4 20h16" stroke-linecap="round"/>
        </svg>
      </a>
    </div>
  </section>
<?php endif ?>

<section class="section">
  <div class="wrap">
    <?php if (empty($jahre)): ?>
      <p class="section__aside">Noch keine Ranglisten hochgeladen.</p>
    <?php else: ?>
      <div class="results">
        <table>
          <caption class="visually-hidden">Ranglisten nach Jahrgang und Laufabend</caption>
          <thead>
            <tr>
              <th scope="col">Jahrgang</th>
              <?php foreach ($spalten as $titel): ?>
                <th scope="col" class="is-center"><?= $titel ?></th>
              <?php endforeach ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jahre as $jahr => $dateien): ?>
              <tr<?= $jahr === $aktuell ? ' class="is-current"' : '' ?>>
                <th scope="row" class="results__year">
                  <?= $jahr ?> <small><?= $nummer($jahr) ?>. Abendläufe</small>
                </th>
                <?php foreach ($spalten as $schluessel => $spaltenTitel): ?>
                  <?php /* data-label trägt die Spaltenüberschrift, damit die
                          Tabelle auf schmalen Bildschirmen ohne Kopfzeile
                          verständlich bleibt. */ ?>
                  <td class="is-center" data-label="<?= $spaltenTitel ?>">
                    <?php snippet('pdf-link', ['datei' => $dateien[$schluessel] ?? null]) ?>
                  </td>
                <?php endforeach ?>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php endif ?>

    <?php $weitere = $page->files()->template('rangliste')->filterBy('art', '!=', 'rangliste') ?>
    <?php if ($weitere->isNotEmpty()): ?>
      <h2 style="margin-top:3rem;">Weitere Dokumente</h2>
      <ul class="prose">
        <?php foreach ($weitere as $d): ?>
          <li><a href="<?= $d->url() ?>" download><?= $d->titel()->or($d->filename()) ?></a>
              <span class="section__aside">· <?= $d->niceSize() ?></span></li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </div>
</section>

<?php snippet('footer') ?>
