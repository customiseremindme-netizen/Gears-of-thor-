<?php
defined('GOT_APP') || exit;

use Got\Hours;
use Got\Html;
use Got\Text;
use Got\Turnstile;

/** @var Got\Site $site */
/** @var array $form */

$c = $site->c['contact'];
$f = $c['form'];
$values = $form['values'] ?? [];
$errors = $form['errors'] ?? [];
$status = $form['status'] ?? 'idle';
$val = static fn (string $key): string => is_string($values[$key] ?? null) ? $values[$key] : '';
$goals = Text::lines(s($f['goals'] ?? ''));
$times = Text::lines(s($f['times'] ?? ''));
$hoursConfirmed = $site->hoursConfirmed();
$today = $hoursConfirmed ? Hours::today($site->hoursDays()) : null;
$whatsapp = $site->whatsappHref();

$field = static function (string $name) use ($errors): array {
    $id = 'enq-' . $name;
    $hasError = isset($errors[$name]);
    return [
        'id' => $id,
        'class' => 'field' . ($hasError ? ' has-error' : ''),
        'aria' => ' aria-describedby="' . $id . '-error"' . ($hasError ? ' aria-invalid="true"' : ''),
        'error' => '<p class="field__error" id="' . $id . '-error" data-error-for="' . $name . '"' . ($hasError ? '' : ' hidden') . '>' . e($errors[$name] ?? '') . '</p>',
    ];
};
$name = $field('name');
$phone = $field('phone');
$goal = $field('goal');
$time = $field('time');
$message = $field('message');
$consent = $field('consent');
?>
<section class="section contact" id="contact" aria-labelledby="contact-title">
  <div class="container contact__grid">
    <div class="contact__info">
      <?php if (s($c['eyebrow'] ?? '') !== ''): ?>
        <p class="eyebrow reveal"><?= e($c['eyebrow']) ?></p>
      <?php endif; ?>
      <h2 class="display h-xl reveal" id="contact-title" style="--d:1"><?= Text::heading(s($c['heading'] ?? '')) ?></h2>
      <?php if (s($c['text'] ?? '') !== ''): ?>
        <div class="lead reveal" style="--d:2"><?= Text::paragraphs(s($c['text']), $site->tokens) ?></div>
      <?php endif; ?>

      <dl class="info-list reveal" style="--d:2">
        <?php if ($site->addressLines()): ?>
          <div class="info-list__row">
            <dt><?= Html::icon('pin') ?><?= e($c['address_label'] ?? 'Address') ?></dt>
            <dd>
              <address><?= implode('<br>', array_map('e', $site->addressLines())) ?></address>
              <a class="btn btn--outline btn--sm" href="<?= e($site->directionsUrl()) ?>" target="_blank" rel="noopener"><span><?= e($c['directions_label'] ?? 'Get Directions') ?></span><?= Html::icon('arrow') ?></a>
            </dd>
          </div>
        <?php endif; ?>

        <?php if ($site->phone() !== ''): ?>
          <div class="info-list__row">
            <dt><?= Html::icon('phone') ?><?= e($c['phone_label'] ?? 'Phone') ?></dt>
            <dd>
              <a class="info-list__big" href="<?= e($site->phoneHref()) ?>"><?= e($site->phone()) ?></a>
              <div class="info-list__actions">
                <a class="btn btn--accent btn--sm" href="<?= e($site->phoneHref()) ?>"><?= Html::icon('phone') ?><span><?= e($c['call_label'] ?? 'Call Now') ?></span></a>
                <?php if ($whatsapp): ?>
                  <a class="btn btn--outline btn--sm" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener"><?= Html::icon('whatsapp') ?><span><?= e($c['whatsapp_label'] ?? 'WhatsApp') ?></span></a>
                <?php endif; ?>
              </div>
            </dd>
          </div>
        <?php endif; ?>

        <?php if ($hoursConfirmed): ?>
          <div class="info-list__row" id="hours">
            <dt><?= Html::icon('clock') ?><?= e($c['hours_label'] ?? 'Opening hours') ?></dt>
            <dd>
              <p class="open-status" data-open-status data-hours='<?= e(json_encode($site->hoursDays())) ?>' hidden></p>
              <table class="hours">
                <caption class="visually-hidden">Opening hours (India Standard Time)</caption>
                <tbody>
                  <?php foreach (Hours::grouped($site->hoursDays()) as $group): ?>
                    <tr<?= in_array($today['key'] ?? '', $group['days'], true) ? ' class="is-today"' : '' ?> data-days="<?= e(implode(' ', $group['days'])) ?>">
                      <th scope="row"><?= e($group['label']) ?></th>
                      <td><?= $group['sessions'] ? implode('<br>', array_map(static fn ($s) => e(Hours::range($s)), $group['sessions'])) : 'Closed' ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <?php if (s($site->c['hours']['note'] ?? '') !== ''): ?>
                <p class="hours__note"><?= e($site->c['hours']['note']) ?></p>
              <?php endif; ?>
            </dd>
          </div>
        <?php endif; ?>

        <?php if ($site->str('business.email') !== ''): ?>
          <div class="info-list__row">
            <dt><?= Html::icon('mail') ?>Email</dt>
            <dd><a href="mailto:<?= e($site->str('business.email')) ?>"><?= e($site->str('business.email')) ?></a></dd>
          </div>
        <?php endif; ?>

        <?php if ($site->socials()): ?>
          <div class="info-list__row">
            <dt><?= Html::icon('instagram') ?><?= e($c['social_label'] ?? 'Follow') ?></dt>
            <dd class="info-list__social">
              <?php foreach ($site->socials() as $social): ?>
                <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener"><?= e($social['handle']) ?></a>
              <?php endforeach; ?>
            </dd>
          </div>
        <?php endif; ?>
      </dl>
    </div>

    <div class="contact__form reveal" id="enquire" tabindex="-1" style="--d:1">
      <div class="form-panel">
        <div class="form-success" data-form-success role="status" tabindex="-1"<?= $status === 'success' ? '' : ' hidden' ?>>
          <span class="form-success__icon" aria-hidden="true"><?= Html::icon('check') ?></span>
          <p class="form-success__message" data-success-message><?= e($f['success'] ?? '') ?></p>
          <a class="link-arrow" href="#enquire" data-form-again><span>Send another enquiry</span></a>
        </div>

        <form class="enquiry-form" method="post" action="<?= e(url('/enquire')) ?>#enquire" novalidate data-enquiry-form<?= $site->preview ? ' data-preview="1"' : '' ?><?= $status === 'success' ? ' hidden' : '' ?>>
          <?php if (s($f['heading'] ?? '') !== ''): ?>
            <h3 class="form-title"><?= e($f['heading']) ?></h3>
          <?php endif; ?>
          <?php if (s($f['intro'] ?? '') !== ''): ?>
            <p class="form-intro"><?= e($f['intro']) ?></p>
          <?php endif; ?>

          <div class="form-alert" data-form-alert role="alert"<?= $status === 'error' ? '' : ' hidden' ?>>
            <p data-form-alert-text><?= e($form['message'] ?? '') ?></p>
            <button class="btn btn--outline btn--sm" type="submit" data-form-retry hidden><span>Try again</span></button>
          </div>

          <div class="<?= $name['class'] ?>">
            <label for="<?= $name['id'] ?>"><?= e($f['name_label'] ?? 'Name') ?> <span class="req" aria-hidden="true">*</span></label>
            <input id="<?= $name['id'] ?>" name="name" type="text" autocomplete="name" required maxlength="80" value="<?= e($val('name')) ?>"<?= $name['aria'] ?>>
            <?= $name['error'] ?>
          </div>

          <div class="<?= $phone['class'] ?>">
            <label for="<?= $phone['id'] ?>"><?= e($f['phone_label'] ?? 'Mobile number') ?> <span class="req" aria-hidden="true">*</span></label>
            <input id="<?= $phone['id'] ?>" name="phone" type="tel" inputmode="tel" autocomplete="tel" required maxlength="20" value="<?= e($val('phone')) ?>"<?= $phone['aria'] ?>>
            <?= $phone['error'] ?>
          </div>

          <div class="field-row">
            <div class="<?= $goal['class'] ?>">
              <label for="<?= $goal['id'] ?>"><?= e($f['goal_label'] ?? 'Fitness goal') ?> <span class="req" aria-hidden="true">*</span></label>
              <select id="<?= $goal['id'] ?>" name="goal" required<?= $goal['aria'] ?>>
                <option value="">Choose one</option>
                <?php foreach ($goals as $option): ?>
                  <option<?= $val('goal') === $option ? ' selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $goal['error'] ?>
            </div>

            <div class="<?= $time['class'] ?>">
              <label for="<?= $time['id'] ?>"><?= e($f['time_label'] ?? 'Preferred contact time') ?> <span class="req" aria-hidden="true">*</span></label>
              <select id="<?= $time['id'] ?>" name="time" required<?= $time['aria'] ?>>
                <option value="">Choose one</option>
                <?php foreach ($times as $option): ?>
                  <option<?= $val('time') === $option ? ' selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $time['error'] ?>
            </div>
          </div>

          <div class="<?= $message['class'] ?>">
            <label for="<?= $message['id'] ?>"><?= e($f['message_label'] ?? 'Message (optional)') ?></label>
            <textarea id="<?= $message['id'] ?>" name="message" rows="4" maxlength="1000"<?= $message['aria'] ?>><?= e($val('message')) ?></textarea>
            <?= $message['error'] ?>
          </div>

          <div class="<?= $consent['class'] ?> field--check">
            <input id="<?= $consent['id'] ?>" name="consent" type="checkbox" value="1" required<?= !empty($values['consent']) ? ' checked' : '' ?><?= $consent['aria'] ?>>
            <label for="<?= $consent['id'] ?>"><?= e($f['consent'] ?? '') ?> <a href="<?= e(url('/privacy')) ?>" target="_blank"><?= e($site->str('footer.privacy_label') ?: 'Privacy notice') ?></a></label>
            <?= $consent['error'] ?>
          </div>

          <div class="hp" aria-hidden="true">
            <label for="enq-hp">Leave this field empty</label>
            <input id="enq-hp" name="hp_note" type="text" tabindex="-1" autocomplete="off">
          </div>
          <input type="hidden" name="_token" value="<?= e($form['token'] ?? '') ?>" data-form-token>

          <?php if (Turnstile::enabled() && !$site->preview): ?>
            <div class="cf-turnstile" data-sitekey="<?= e(Turnstile::siteKey()) ?>" data-theme="dark"></div>
          <?php endif; ?>

          <button class="btn btn--accent btn--lg btn--block form-submit" type="submit" data-submit>
            <span data-submit-label><?= e($f['submit'] ?? 'Request a Callback') ?></span>
            <span class="spinner" aria-hidden="true"></span>
          </button>
          <p class="form-note"><span aria-hidden="true">*</span> Required</p>
        </form>
      </div>
    </div>
  </div>
</section>
