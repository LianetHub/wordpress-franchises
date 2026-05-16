<?php

defined('ABSPATH') || exit;
?>
<div class="popup popup--small lead-feedback-popup" id="lead-feedback" data-lead-feedback hidden>
    <div class="lead-feedback-card is-success" data-lead-feedback-card>
        <div class="lead-feedback-mark" data-lead-feedback-mark aria-hidden="true" data-lead-feedback-success-block>
            <svg class="lead-feedback-check" viewBox="0 0 80 80" focusable="false" aria-hidden="true">
                <circle class="lead-feedback-check-circle" cx="40" cy="40" r="30"></circle>
                <path class="lead-feedback-check-path" d="M26 40.5l9 9 19-19"></path>
            </svg>
        </div>
        <p class="lead-feedback-text popup__subtitle" data-lead-feedback-success-text>
            <strong><?php esc_html_e('Заявка отправлена', 'franchises'); ?></strong>
            <span><?php esc_html_e('В ближайшее время менеджер свяжется с вами.', 'franchises'); ?></span>
        </p>
        <p class="lead-feedback-text popup__subtitle" data-lead-feedback-error-text hidden>
            <?php esc_html_e('Не удалось отправить заявку. Попробуйте ещё раз.', 'franchises'); ?>
        </p>
    </div>
    <button type="button" data-fancybox-close class="popup__btn btn btn-primary lead-feedback-btn">
        <?php esc_html_e('Понятно', 'franchises'); ?>
    </button>
</div>

<div class="popup selection-popup-card-wrap" id="selection-popup" hidden>
    <h2 class="selection-popup-title popup__title title-md" id="selection-popup-title">
        <?php esc_html_e('Подберем франшизы под ваш бюджет', 'franchises'); ?>
    </h2>
    <p class="selection-popup-subtitle popup__subtitle">
        <?php esc_html_e('Оставьте имя и телефон. В ближайшее время менеджер свяжется с вами.', 'franchises'); ?>
    </p>
    <div class="selection-popup-form-wrap">
        <?php franchises_render_selection_popup_cf7(); ?>
    </div>
</div>