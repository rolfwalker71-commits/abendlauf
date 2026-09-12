<?php
/**
 * Gemeinsamer Seitenkopf aller Unterseiten.
 *
 * Erwartet: $titel
 * Optional: $eyebrow, $lead, $aktionen (HTML),
 *           $bild        — scharfes Bild rechts neben dem Text,
 *           $hintergrund — weichgezeichnetes Bild hinter dem Text.
 */
$bild        = $bild        ?? null;
$hintergrund = $hintergrund ?? null;
?>
<section class="hero hero--compact <?= $bild ? '' : 'hero--plain' ?> <?= $hintergrund ? 'hero--backdrop' : '' ?>">
  <?php if ($hintergrund): ?>
    <div class="hero__backdrop" aria-hidden="true">
      <?= $hintergrund->crop(1600, 700)->html(['alt' => '', 'loading' => 'eager']) ?>
    </div>
  <?php endif ?>

  <div class="hero__text">
    <?php if (!empty($eyebrow)): ?>
      <p class="eyebrow"><?= $eyebrow ?></p>
    <?php endif ?>
    <h1><?= $titel ?></h1>
    <?php if (!empty($lead)): ?>
      <p class="hero__lead"><?= $lead ?></p>
    <?php endif ?>
    <?php if (!empty($aktionen)): ?>
      <div class="hero__actions"><?= $aktionen ?></div>
    <?php endif ?>
  </div>

  <?php if ($bild): ?>
    <div class="hero__media"><?= $bild->crop(1100, 760)->html(['alt' => '', 'fetchpriority' => 'high']) ?></div>
  <?php endif ?>
</section>
