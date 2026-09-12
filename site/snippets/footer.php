</main>

<footer class="site-footer">
  <div class="wrap site-footer__cols">

    <div>
      <h3><?= $site->titel()->or($site->title()) ?></h3>
      <?php if ($site->adresse()->isNotEmpty()): ?>
        <p><?= nl2br($site->adresse()->escape()) ?></p>
      <?php endif ?>
    </div>

    <div>
      <h3>Kontakt</h3>
      <?php if ($site->email()->isNotEmpty()): ?>
        <p><a href="mailto:<?= $site->email() ?>"><?= $site->email() ?></a></p>
      <?php endif ?>
      <?php if ($k = $site->find('kontakt')): ?>
        <p><a href="<?= $k->url() ?>">Kontaktformular</a></p>
      <?php endif ?>
    </div>

    <div>
      <h3>Folgen</h3>
      <?php if ($site->facebook()->isNotEmpty()): ?>
        <p><a href="<?= $site->facebook() ?>" rel="noopener">Facebook</a></p>
      <?php endif ?>
      <?php if ($site->instagram()->isNotEmpty()): ?>
        <p><a href="<?= $site->instagram() ?>" rel="noopener">Instagram</a></p>
      <?php endif ?>
    </div>

  </div>
  <div class="site-footer__base wrap">
    <?= $site->fusszeile()->or('© ' . date('Y') . ' OK Urner Abendläufe') ?>
  </div>
</footer>

<script src="<?= url('assets/js/site.js') ?>" defer></script>
</body>
</html>
