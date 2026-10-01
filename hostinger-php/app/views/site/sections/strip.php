<?php
defined('GOT_APP') || exit;

use Got\Html;
use Got\Text;

/** @var Got\Site $site */

$words = Text::lines($site->str('strip.words'));
// Repeat the words so one half of the track is always wider than the screen.
$repeat = max(2, (int) ceil(8 / max(1, count($words))));
$renderList = static function (bool $hidden) use ($words, $repeat): string {
    $html = '<ul class="strip__list"' . ($hidden ? ' aria-hidden="true"' : '') . '>';
    for ($r = 0; $r < $repeat; $r++) {
        foreach ($words as $i => $word) {
            $first = !$hidden && $r === 0;
            $html .= '<li class="strip__word' . ($i % 2 === 1 ? ' strip__word--outline' : '') . '"' . ($first ? '' : ' aria-hidden="true"') . '>' . e($word) . '</li>';
            $html .= '<li class="strip__sep" aria-hidden="true">/</li>';
        }
    }
    return $html . '</ul>';
};
?>
<section class="strip" data-marquee>
  <div class="strip__viewport">
    <div class="strip__track" data-marquee-track>
      <?= $renderList(false) ?>
      <?= $renderList(true) ?>
    </div>
  </div>
  <button class="strip__toggle" type="button" aria-pressed="false" data-marquee-toggle>
    <span class="strip__toggle-pause"><?= Html::icon('pause') ?></span>
    <span class="strip__toggle-play"><?= Html::icon('play') ?></span>
    <span class="visually-hidden" data-marquee-label>Pause moving text</span>
  </button>
</section>
