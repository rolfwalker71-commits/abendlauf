<?php snippet('header') ?>

<?php
$bilder = $page->images()->sortBy('sort', 'asc');
$cover  = $page->cover()->toFile() ?? $bilder->first();
$laufTitel = ['1' => '1. Abend', '2' => '2. Abend', '3' => '3. Abend', 'gesamt' => 'Schlussfeier'];

$meta = [$bilder->count() . ' Bilder'];
if ($page->datum()->isNotEmpty())    $meta[] = $page->datum()->toDate('d.m.Y');
if ($page->fotograf()->isNotEmpty()) $meta[] = 'Fotos: ' . $page->fotograf();

$geschwister = $page->siblings(false)->listed()->sortBy('jahr', 'desc', 'lauf', 'desc');
?>

<?php snippet('pagehead', [
  'eyebrow' => 'Album · ' . $page->jahr() . ($page->lauf()->isNotEmpty() ? ' · ' . ($laufTitel[$page->lauf()->value()] ?? '') : ''),
  'titel'   => $page->title(),
  'lead'    => implode(' · ', $meta) . ($page->beschreibung()->isNotEmpty() ? '<br>' . $page->beschreibung()->html() : ''),
  'bild'    => $cover,
]) ?>

<?php if ($geschwister->isNotEmpty()): ?>
  <div class="chips-bar">
    <div class="wrap chips">
      <span class="chip chip--active"><?= $page->title() ?></span>
      <?php foreach ($geschwister as $a): ?>
        <a class="chip" href="<?= $a->url() ?>"><?= $a->title() ?></a>
      <?php endforeach ?>
    </div>
  </div>
<?php endif ?>

<section class="section">
  <div class="wrap">
    <?php if ($bilder->isEmpty()): ?>
      <p class="section__aside">In diesem Album sind noch keine Bilder.</p>
    <?php else: ?>
      <div class="gallery">
        <?php foreach ($bilder as $bild): ?>
          <a href="<?= $bild->url() ?>" data-lightbox data-caption="<?= $bild->bildtext()->escape('attr') ?>">
            <?= $bild->crop(500, 500)->html([
                  'alt'     => $bild->alt()->or($bild->bildtext())->escape('attr'),
                  'loading' => 'lazy',
                ]) ?>
          </a>
        <?php endforeach ?>
      </div>
      <p class="gallery__hint">Bild anklicken für die grosse Ansicht — dort mit den Pfeiltasten blättern.</p>
    <?php endif ?>

    <p style="margin-top:2.5rem;">
      <a class="btn btn--ghost" href="<?= $page->parent()->url() ?>">Alle Alben</a>
    </p>
  </div>
</section>

<?php snippet('footer') ?>
