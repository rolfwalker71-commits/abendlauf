<?php snippet('header') ?>

<?php snippet('pagehead', [
  'titel' => $page->title(),
  'lead'  => $page->intro()->isNotEmpty() ? $page->intro()->html() : null,
  'hintergrund' => $page->kopfHintergrund()->toFile(),
]) ?>

<section class="section">
  <div class="wrap prose" style="max-width: 46rem;">
    <?= $page->text()->kirbytext() ?>

    <?php $dateien = $page->files()->template('rangliste') ?>
    <?php if ($dateien->isNotEmpty()): ?>
      <h2>Dateien</h2>
      <ul class="file-list">
        <?php foreach ($dateien as $d): ?>
          <li>
            <a href="<?= $d->url() ?>" download><?= $d->titel()->or($d->filename()) ?></a>
            <span class="file-list__meta"><?= strtoupper($d->extension()) ?> · <?= $d->niceSize() ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </div>
</section>

<?php snippet('footer') ?>
