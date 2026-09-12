<?php
/**
 * Das Vereinslogo als Vektor. Schiebt sich beim Laden einmal von
 * links ins Bild und bleibt dann stehen.
 *
 * Die Grafik ist aus dem alten PNG nachgezeichnet — dadurch ist sie
 * in jeder Grösse scharf, nimmt über currentColor die Textfarbe an
 * und wiegt 28 KB statt eines ausgefransten Bitmaps.
 */
?>
<span class="runlogo">
  <?= svg('assets/img/logo.svg') ?>
</span>
