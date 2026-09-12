<?php snippet('header') ?>

<?php $mitglieder = $page->mitglieder()->toStructure() ?>

<?php snippet('pagehead', [
  'eyebrow' => $mitglieder->count() . ' Personen',
  'titel'   => $page->title(),
  'lead'    => $page->intro()->isNotEmpty() ? $page->intro()->html() : null,
  'hintergrund' => $page->kopfHintergrund()->toFile(),
]) ?>

<section class="section">
  <div class="wrap">
    <div class="grid grid--personen">
      <?php foreach ($mitglieder as $m):
        $foto = $m->portrait()->toFile();
      ?>
        <div class="person">
          <div class="person__img">
            <?php if ($foto): ?>
              <?= $foto->crop(440, 440)->html(['alt' => '', 'loading' => 'lazy']) ?>
            <?php endif ?>
          </div>
          <p class="person__name"><?= $m->name() ?></p>
          <p class="person__role"><?= $m->funktion() ?></p>
          <?php if ($m->email()->isNotEmpty()): ?>
            <p class="person__role"><a href="mailto:<?= $m->email() ?>"><?= $m->email() ?></a></p>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
  </div>
</section>

<?php snippet('footer') ?>
