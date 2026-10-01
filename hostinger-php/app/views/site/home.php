<?php
defined('GOT_APP') || exit;

use Got\Schema;
use Got\View;

/** @var Got\Site $site */
/** @var array $form */

$hints = [
    'statement' => ['Add a heading or text to show this section.', 'statement'],
    'strip' => ['Add some words to show the moving text strip.', 'strip'],
    'training' => ['Switch on at least one training option to show this section.', 'training'],
    'gym' => ['Add photos of your gym (or confirm a facility) to show this section and its menu link.', 'gym'],
    'coaches' => ['Add a coach and switch them on to show this section.', 'coaches'],
    'reviews' => ['Add a genuine review and tick the member’s permission to show this section.', 'reviews'],
    'stories' => ['Add a member story and tick their consent to show this section.', 'stories'],
    'membership' => ['Add a heading to show this section.', 'membership'],
    'faq' => ['Switch on at least one question to show this section.', 'faq'],
];

foreach ($site->sections as $section) {
    $id = $section['id'];
    if ($section['visible']) {
        View::partial('site/sections/' . $id, ['site' => $site, 'form' => $form]);
        continue;
    }
    if ($site->preview) {
        $label = Schema::SECTIONS[$id]['label'];
        $message = $section['reason'] === 'off'
            ? 'You have switched this section off.'
            : ($hints[$id][0] ?? 'This section has no content yet.');
        $link = $section['reason'] === 'off' ? '/admin/content' : '/admin/content/' . ($hints[$id][1] ?? $id);
        ?>
        <aside class="preview-note container" aria-label="Preview note">
          <p><strong><?= e($label) ?></strong> is hidden on the live site. <?= e($message) ?></p>
          <a href="<?= e(url($link)) ?>" target="_top">Edit in dashboard</a>
        </aside>
        <?php
    }
}
