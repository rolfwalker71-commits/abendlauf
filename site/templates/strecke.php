<?php snippet('header') ?>

<?php $runden = $page->runden()->toStructure() ?>

<?php snippet('pagehead', [
  'eyebrow'     => $runden->isNotEmpty()
                    ? $runden->count() . ' Runden · ' . implode(' und ', $runden->pluck('distanz', null, true))
                    : null,
  'titel'       => $page->title(),
  'lead'        => $page->intro()->isNotEmpty() ? $page->intro()->html() : null,
  'hintergrund' => $page->kopfHintergrund()->toFile(),
]) ?>

<?php foreach ($runden as $i => $runde):
  $karte = $runde->karte()->toFile();
?>
  <section class="section<?= $i % 2 ? ' section--tint' : '' ?>">
    <div class="wrap route">

      <div class="route__text">
        <div class="section__head">
          <h2><span class="section__num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span> <?= $runde->titel() ?></h2>
        </div>

        <?php if ($runde->beschreibung()->isNotEmpty()): ?>
          <p><?= $runde->beschreibung() ?></p>
        <?php endif ?>

        <dl class="route__facts">
          <?php foreach ([
            'Distanz'     => $runde->distanz(),
            'Profil'      => $runde->hoehenmeter(),
            'Untergrund'  => $runde->untergrund(),
            'Kategorien'  => $runde->kategorien(),
          ] as $label => $wert): ?>
            <?php if ($wert->isNotEmpty()): ?>
              <div>
                <dt><?= $label ?></dt>
                <dd><?= $wert ?></dd>
              </div>
            <?php endif ?>
          <?php endforeach ?>
        </dl>
      </div>

      <?php if ($karte): ?>
        <figure class="route__map">
          <a href="<?= $karte->url() ?>" data-lightbox
             data-caption="<?= $runde->titel()->escape('attr') ?> · <?= $runde->distanz()->escape('attr') ?>">
            <?= $karte->resize(900)->html(['alt' => $karte->alt()->escape('attr'), 'loading' => 'lazy']) ?>
          </a>
          <figcaption>Karte anklicken für die grosse Ansicht</figcaption>
        </figure>
      <?php endif ?>

    </div>
  </section>
<?php endforeach ?>

<?php if ($page->text()->isNotEmpty()): ?>
  <section class="section">
    <div class="wrap prose" style="max-width: 46rem;">
      <?= $page->text()->kirbytext() ?>
    </div>
  </section>
<?php endif ?>

<?php snippet('footer') ?>
