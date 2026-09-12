<?php snippet('header') ?>

<?php
$alle = $page->sponsoren()->toStructure()->filterBy('aktiv', true);
$gruppen = [
  'haupt'   => 'Hauptsponsoren',
  'co'      => 'Co-Sponsoren',
  'sponsor' => 'Sponsoren',
  'goenner' => 'Gönner',
];
?>

<?php snippet('pagehead', [
  'eyebrow' => $alle->count() . ' Firmen und Institutionen',
  'titel'   => $page->title(),
  'lead'    => $page->intro()->isNotEmpty() ? $page->intro()->html() : null,
  'hintergrund' => $page->kopfHintergrund()->toFile(),
]) ?>

<?php foreach ($gruppen as $schluessel => $titel):
  $teil = $alle->filterBy('kategorie', $schluessel);
  if ($teil->isEmpty()) continue;
?>
  <section class="section<?= $schluessel === 'haupt' ? '' : ' section--tint' ?>">
    <div class="wrap">
      <h2><?= $titel ?></h2>
    </div>
    <?php if ($schluessel === 'sponsor' && $teil->count() > 8): ?>
      <?php /* Viele kleine Logos laufen als Band durch, statt ein Raster zu füllen. */ ?>
      <div class="marquee" data-marquee>
        <div class="marquee__track">
          <?php snippet('sponsors', ['sponsoren' => $teil]) ?>
        </div>
      </div>
    <?php else: ?>
      <div class="wrap">
        <?php snippet('sponsors', ['sponsoren' => $teil]) ?>
      </div>
    <?php endif ?>
  </section>
<?php endforeach ?>

<?php snippet('footer') ?>
