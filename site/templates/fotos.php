<?php snippet('header') ?>

<?php
$alben  = $page->children()->listed()->sortBy('jahr', 'desc', 'lauf', 'desc');
$bilder = $alben->images()->count();
$titelbild = $alben->first()?->cover()->toFile()
          ?? $alben->first()?->images()->sortBy('sort', 'asc')->first();
?>

<?php snippet('pagehead', [
  'eyebrow' => $alben->isNotEmpty() ? $alben->count() . ' Alben · ' . $bilder . ' Bilder' : null,
  'titel'   => $page->title(),
  'lead'    => $page->intro()->isNotEmpty() ? $page->intro()->html() : null,
  'bild'    => $titelbild,
]) ?>

<section class="section">
  <div class="wrap">
    <?php if ($alben->isEmpty()): ?>
      <p class="section__aside">Noch keine Alben veröffentlicht.</p>
    <?php else: ?>
      <?php
      // Nach Jahrgang gruppieren — so bleibt das Archiv übersichtlich,
      // auch wenn jedes Jahr drei Alben dazukommen.
      $nachJahr = [];
      foreach ($alben as $album) $nachJahr[(string)$album->jahr()][] = $album;
      ?>
      <?php foreach ($nachJahr as $jahr => $gruppe): ?>
        <div class="year-block">
          <div class="section__head">
            <h2><span class="section__num"><?= $jahr ?></span> <?= count($gruppe) ?> Alben</h2>
          </div>
          <div class="grid grid--3 reveal">
            <?php foreach ($gruppe as $album):
              $cover = $album->cover()->toFile() ?? $album->images()->sortBy('sort', 'asc')->first();
            ?>
              <a class="album-card" href="<?= $album->url() ?>">
                <div class="album-card__img">
                  <?php if ($cover): ?>
                    <?= $cover->crop(800, 534)->html(['alt' => '', 'loading' => 'lazy']) ?>
                  <?php endif ?>
                  <span class="album-card__count"><?= $album->images()->count() ?></span>
                </div>
                <h3 class="album-card__title"><?= $album->title() ?></h3>
                <p class="album-card__meta"><?= $album->images()->count() ?> Bilder</p>
              </a>
            <?php endforeach ?>
          </div>
        </div>
      <?php endforeach ?>
    <?php endif ?>
  </div>
</section>

<?php snippet('footer') ?>
