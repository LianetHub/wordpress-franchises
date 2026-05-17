<?php

/**
 * CTA-блок «Не нашли подходящую франшизу?».
 *
 * get_template_part('templates/components/help-section');
 * get_template_part('templates/components/help-section', null, [
 *     'section_class'   => 'catalog-bottom',
 *     'button_href'     => home_url('/#contacts'),
 *     'button_fancybox' => false,
 * ]);
 */

defined('ABSPATH') || exit;

$section_class   = isset($section_class) ? (string) $section_class : '';
$button_href     = isset($button_href) ? (string) $button_href : '#selection-popup';
$button_fancybox = !isset($button_fancybox) || $button_fancybox;

$section_classes = trim('help-section ' . $section_class);

?>
<section class="<?php echo esc_attr($section_classes); ?>" aria-label="Поможем подобрать франшизу">
    <div class="help-panel">
        <h2 class="help-title">Не нашли подходящую франшизу?</h2>
        <p class="help-text">Оставьте заявку — подберём варианты под ваш бюджет и цели и свяжемся в течение дня.</p>
        <a
            class="btn btn-primary"
            href="<?php echo esc_url($button_href); ?>"
            <?php if ($button_fancybox) : ?>
            data-fancybox
            data-src="#selection-popup"
            <?php endif; ?>>Получить подбор</a>
    </div>
</section>