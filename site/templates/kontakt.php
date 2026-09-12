<?php snippet('header') ?>

<?php snippet('pagehead', [
  'titel' => $page->title(),
  'lead'  => $page->intro()->isNotEmpty() ? $page->intro()->html() : null,
  'hintergrund' => $page->kopfHintergrund()->toFile(),
]) ?>

<section class="section">
  <div class="wrap" style="display:grid; gap:2.5rem; grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">

    <div class="prose">
      <?= $page->text()->kirbytext() ?>
      <?php if ($site->adresse()->isNotEmpty()): ?>
        <h3>Adresse</h3>
        <p><?= nl2br($site->adresse()->escape()) ?></p>
      <?php endif ?>
      <?php if ($site->email()->isNotEmpty()): ?>
        <p><a href="mailto:<?= $site->email() ?>"><?= $site->email() ?></a></p>
      <?php endif ?>
    </div>

    <?php if ($page->formularAktiv()->toBool(true)): ?>
      <div>
        <?php if (!empty($erfolg)): ?>
          <p class="alert alert--ok"><?= $page->dankeText()->or('Danke für deine Nachricht.') ?></p>
        <?php endif ?>

        <?php if (!empty($fehler)): ?>
          <div class="alert alert--fail">
            <?php foreach ($fehler as $f): ?><p><?= html($f) ?></p><?php endforeach ?>
          </div>
        <?php endif ?>

        <?php if (empty($erfolg)): ?>
          <form method="post" action="<?= $page->url() ?>#formular" id="formular">
            <div class="field">
              <label for="name">Name</label>
              <input type="text" id="name" name="name" required value="<?= esc($eingabe['name'] ?? '') ?>">
            </div>
            <div class="field">
              <label for="email">E-Mail</label>
              <input type="email" id="email" name="email" required value="<?= esc($eingabe['email'] ?? '') ?>">
            </div>
            <div class="field">
              <label for="betreff">Betreff</label>
              <input type="text" id="betreff" name="betreff" required value="<?= esc($eingabe['betreff'] ?? '') ?>">
            </div>
            <div class="field">
              <label for="nachricht">Nachricht</label>
              <textarea id="nachricht" name="nachricht" required><?= esc($eingabe['nachricht'] ?? '') ?></textarea>
            </div>

            <?php /* Spamfalle: für Menschen unsichtbar, Bots füllen sie aus. */ ?>
            <div class="field field--hp" aria-hidden="true">
              <label for="website">Website</label>
              <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <button class="btn btn--primary" type="submit">Nachricht senden</button>
          </form>
        <?php endif ?>
      </div>
    <?php endif ?>

  </div>
</section>

<?php snippet('footer') ?>
