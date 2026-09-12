<?php
/**
 * Sponsorenband. Erwartet: $sponsoren (Structure), optional $limit.
 */
$liste = $sponsoren->filterBy('aktiv', true);
if (isset($limit)) $liste = $liste->limit($limit);
if ($liste->isEmpty()) return;
?>
<div class="sponsors">
  <?php foreach ($liste as $s):
    $logo = $s->logo()->toFile();
    $klasse = 'sponsor sponsor--' . $s->kategorie()->or('sponsor');
    $tag = $s->url()->isNotEmpty() ? 'a' : 'div';
  ?>
    <<?= $tag ?> class="<?= $klasse ?>"<?= $tag === 'a' ? ' href="' . $s->url() . '" rel="noopener"' : '' ?>>
      <?php if ($logo): ?>
        <img src="<?= $logo->url() ?>" alt="<?= $s->name()->escape('attr') ?>" loading="lazy">
      <?php else: ?>
        <span><?= $s->name() ?></span>
      <?php endif ?>
    </<?= $tag ?>>
  <?php endforeach ?>
</div>
