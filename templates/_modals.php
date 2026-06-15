<?php

defined('ABSPATH') || exit;

$get_modal_option = static function (string $field, string $default): string {
    if (function_exists('franchises_get_theme_option')) {
        return (string) franchises_get_theme_option($field, $default);
    }

    return $default;
};

$selection_title    = $get_modal_option('selection_form_title', __('Подберем франшизы под ваш бюджет', 'franchises'));
$selection_subtitle = $get_modal_option('selection_form_subtitle', __('Оставьте имя и телефон. В ближайшее время менеджер свяжется с вами.', 'franchises'));

$success_title     = $get_modal_option('success_title', __('Заявка отправлена', 'franchises'));
$success_subtitle  = $get_modal_option('success_subtitle', __('В ближайшее время менеджер свяжется с вами.', 'franchises'));
$success_close_btn = $get_modal_option('success_close_btn', __('Понятно', 'franchises'));

$error_title       = $get_modal_option('error_title', __('Не удалось отправить заявку', 'franchises'));
$error_subtitle    = $get_modal_option('error_subtitle', __('Попробуйте ещё раз.', 'franchises'));
$error_close_btn   = $get_modal_option('error_close_btn', __('Понятно', 'franchises'));
?>
<div class="popup popup--small lead-feedback-popup" id="lead-feedback" data-lead-feedback hidden>
    <div class="lead-feedback-card" data-lead-feedback-card>
        <div class="lead-feedback-mark" data-lead-feedback-success-block aria-hidden="true">
            <svg class="lead-feedback-check" viewBox="0 0 80 80" focusable="false" aria-hidden="true">
                <circle class="lead-feedback-check-circle" cx="40" cy="40" r="30"></circle>
                <path class="lead-feedback-check-path" d="M26 40.5l9 9 19-19"></path>
            </svg>
        </div>
        <div class="lead-feedback-mark" data-lead-feedback-error-block aria-hidden="true" hidden>
            <svg class="lead-feedback-cross" viewBox="0 0 80 80" focusable="false" aria-hidden="true">
                <circle class="lead-feedback-cross-circle" cx="40" cy="40" r="30"></circle>
                <path class="lead-feedback-cross-path" d="M28 28l24 24"></path>
                <path class="lead-feedback-cross-path" d="M52 28L28 52"></path>
            </svg>
        </div>
        <p class="lead-feedback-text popup__subtitle" data-lead-feedback-success-text>
            <strong><?php echo esc_html($success_title); ?></strong>
            <span><?php echo esc_html($success_subtitle); ?></span>
        </p>
        <p class="lead-feedback-text popup__subtitle" data-lead-feedback-error-text hidden>
            <strong><?php echo esc_html($error_title); ?></strong>
            <span data-lead-feedback-error-message><?php echo esc_html($error_subtitle); ?></span>
        </p>
    </div>
    <button type="button" data-fancybox-close class="popup__btn btn btn-primary lead-feedback-btn">
        <span data-lead-feedback-success-close><?php echo esc_html($success_close_btn); ?></span>
        <span data-lead-feedback-error-close hidden><?php echo esc_html($error_close_btn); ?></span>
    </button>
</div>

<div class="popup selection-popup-card-wrap" id="selection-popup" hidden>
    <h2 class="selection-popup-title popup__title title-md" id="selection-popup-title">
        <?php echo esc_html($selection_title); ?>
    </h2>
    <p class="selection-popup-subtitle popup__subtitle">
        <?php echo esc_html($selection_subtitle); ?>
    </p>
    <div class="selection-popup-form-wrap">
        <?php franchises_render_selection_popup_cf7(); ?>
    </div>
</div>
<div class="popup selection-popup-card-wrap" id="presentation-popup" hidden>
    <h2 class="selection-popup-title popup__title title-md" id="presentation-popup-title">
        Получить презентацию франшизы
    </h2>
    <p class="selection-popup-subtitle popup__subtitle">
        Оставьте имя и телефон. Мы отправим презентацию франшизы и расскажем подробности по условиям сотрудничества.
    </p>
    <div class="selection-popup-form-wrap">
        <?php echo do_shortcode('[contact-form-7 id="32b1185" title="Контактная форма Получить презентацию"]'); ?>
    </div>
</div>