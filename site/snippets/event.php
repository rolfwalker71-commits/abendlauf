<?php
/**
 * Eine Laufabend-Kachel.
 * Erwartet: $termin (StructureObject aus dem Feld "laufabende" der Startseite)
 */
$datum  = $termin->datum()->toDate();
$bild   = $termin->bild()->toFile();
$status = $termin->status()->or('findet-statt')->value();
$abgesagt = $status !== 'findet-statt';
?>
<article class="event <?= $bild ? '' : 'event--plain' ?> <?= $abgesagt ? 'event--abgesagt' : '' ?>">
  <?php if ($bild): ?>
    <div class="event__media">
      <?= $bild->crop(800, 600)->html(['alt' => '', 'loading' => 'lazy']) ?>
    </div>
  <?php endif ?>

  <div class="event__top">
    <span class="event__date"><?= date('d.m.Y', $datum) ?></span>
    <span class="event__time">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2" stroke-linecap="round"/>
      </svg>
      <?= date('H:i', $datum) ?>
    </span>
  </div>

  <div>
    <?php if ($termin->ort()->isNotEmpty()): ?>
      <p class="event__meta"><?= $termin->ort() ?></p>
    <?php endif ?>
    <h3 class="event__title"><?= $termin->titel() ?></h3>
    <?php if ($abgesagt): ?>
      <p class="event__status">
        <?= $status === 'abgesagt' ? 'Abgesagt' : 'Verschoben' ?><?= $termin->statusHinweis()->isNotEmpty() ? ' · ' . $termin->statusHinweis() : '' ?>
      </p>
    <?php endif ?>
  </div>
</article>
