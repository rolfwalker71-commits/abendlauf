<?php snippet('header') ?>

<?php
$punkte     = $page->punkte()->toStructure();
$kategorien = $page->kategorien()->toStructure()->sortBy('startzeit', 'asc');
$dateien    = $page->files()->template('rangliste');
?>

<?php
$aktionen = '';
foreach ($dateien as $d) {
    $aktionen .= '<a class="btn btn--primary" href="' . $d->url() . '" download>'
               . ($d->titel()->or('Als PDF')) . '</a>';
}
?>

<?php snippet('pagehead', [
  'eyebrow'  => $kategorien->isNotEmpty() ? $kategorien->count() . ' Kategorien · Start ab ' . $kategorien->first()->startzeit() . ' Uhr' : null,
  'titel'    => $page->title(),
  'lead'     => $page->intro()->isNotEmpty() ? $page->intro()->html() : null,
  'aktionen' => $aktionen,
  'hintergrund' => $page->kopfHintergrund()->toFile(),
]) ?>

<?php if ($kategorien->isNotEmpty()): ?>
  <section class="section">
    <div class="wrap">
      <div class="section__head">
        <h2><span class="section__num">01</span> Kategorien und Startzeiten</h2>
        <p class="section__aside">An allen drei Abenden identisch</p>
      </div>
      <div class="results">
        <table>
          <thead>
            <tr>
              <th scope="col">Kategorie</th>
              <th scope="col">Zeit</th>
              <th scope="col">Kat.</th>
              <th scope="col">Jahrgang</th>
              <th scope="col">Runden</th>
              <th scope="col">Distanz</th>
              <th scope="col">Startgeld</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($kategorien as $k): ?>
              <tr>
                <?php /* Auf schmalen Bildschirmen wird jede Zeile zur Karte:
                        die Bezeichnung ist die Überschrift, die übrigen
                        Werte tragen ihre Spaltenbeschriftung selbst. */ ?>
                <th scope="row" class="results__rowhead"><?= $k->bezeichnung() ?></th>
                <td data-label="Startzeit"><strong><?= $k->startzeit() ?></strong></td>
                <td data-label="Kategorie"><?= $k->kuerzel() ?></td>
                <td data-label="Jahrgang"><?= $k->jahrgang() ?></td>
                <td data-label="Runden"><?= $k->runden() ?></td>
                <td data-label="Distanz"><?= $k->distanz() ?></td>
                <td data-label="Startgeld"><?= $k->startgeld() ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
<?php endif ?>

<?php if ($punkte->isNotEmpty()): ?>
  <section class="section section--tint">
    <div class="wrap">
      <h2><span class="section__num">02</span> Organisatorisches</h2>
      <div class="results">
        <table>
          <tbody>
            <?php foreach ($punkte as $p): ?>
              <tr>
                <th scope="row" style="width:11rem; vertical-align:top;"><?= $p->stichwort() ?></th>
                <td><?= $p->text()->kirbytextinline() ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
<?php endif ?>

<?php snippet('footer') ?>
