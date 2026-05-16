<!-- <?php
        $callback_form_image = get_field('callback_form_image', 'option');
        $callback_form_title = get_field('callback_form_title', 'option');
        $callback_form_subtitle = get_field('callback_form_subtitle', 'option');

        $error_title = get_field('error_title', 'option');
        $error_subtitle = get_field('error_subtitle', 'option');
        $error_close_btn = get_field('error_close_btn', 'option') ?? "ок, закрыть";
        $error_icon = get_field('error_icon', 'option');

        $success_title = get_field('success_title', 'option');
        $success_subtitle = get_field('success_subtitle', 'option');
        $success_close_btn = get_field('success_close_btn', 'option') ?? "ок, закрыть";
        $success_icon = get_field('success_icon', 'option');
        ?>

<div class="popup" id="callback">
    <div class="popup__image">
        <?php if ($callback_form_image): ?>
            <img
                src="<?php echo esc_url($callback_form_image['url']); ?>"
                alt="<?php echo esc_attr($callback_form_image['alt']) ?: 'Обложка'; ?>"
                class="cover-image">
        <?php endif; ?>
    </div>
    <div class="popup__content">
        <?php if ($callback_form_title): ?>
            <h3 class="popup__title title-md"> <?php echo esc_html($callback_form_title) ?></h3>
        <?php endif; ?>
        <?php if ($callback_form_subtitle): ?>
            <p class="popup__subtitle subtitle"><?php echo esc_html($callback_form_subtitle) ?></p>
        <?php endif; ?>
        <div class="popup__form">
            <?php echo do_shortcode('[contact-form-7 id="b41fe87" title="Контактная форма Задать вопрос"]') ?>
        </div>
    </div>
</div>


<div class="popup popup--small" id="error-submitting">
    <?php if ($error_icon): ?>
        <div class="popup__icon">
            <img src="<?php echo esc_url($error_icon['url']); ?>" alt="<?php echo esc_attr($error_icon['alt']) ?: 'Иконка'; ?>">
        </div>
    <?php endif; ?>
    <?php if ($error_title): ?>
        <h3 class="popup__title title-sm">
            <?php echo esc_html($error_title) ?>
        </h3>
    <?php endif; ?>
    <?php if ($error_subtitle): ?>
        <p class="popup__subtitle">
            <?php echo esc_html($error_subtitle) ?>
        </p>
    <?php endif; ?>
    <button type="button" data-fancybox-close class="popup__btn btn btn-secondary">
        <?php echo esc_html($error_close_btn) ?>
    </button>
</div>

<div class="popup popup--small" id="success-submitting">
    <?php if ($success_icon): ?>
        <div class="popup__icon">
            <img src="<?php echo esc_url($success_icon['url']); ?>" alt="<?php echo esc_attr($success_icon['alt']) ?: 'Иконка'; ?>">
        </div>
    <?php endif; ?>
    <?php if ($success_title): ?>
        <h3 class="popup__title title-sm">
            <?php echo esc_html($success_title) ?>
        </h3>
    <?php endif; ?>
    <?php if ($success_subtitle): ?>
        <p class="popup__subtitle">
            <?php echo esc_html($success_subtitle) ?>
        </p>
    <?php endif; ?>
    <button type="button" data-fancybox-close class="popup__btn btn btn-secondary">
        <?php echo esc_html($success_close_btn) ?>
    </button>
</div> -->

<div class="popup popup--small lead-feedback-popup" id="lead-feedback" hidden>
    <div class="lead-feedback-mark" data-lead-feedback-mark aria-hidden="true">
        <svg class="lead-feedback-check" viewBox="0 0 80 80" focusable="false" aria-hidden="true">
            <circle class="lead-feedback-check-circle" cx="40" cy="40" r="30"></circle>
            <path class="lead-feedback-check-path" d="M26 40.5l9 9 19-19"></path>
        </svg>
    </div>
    <p class="lead-feedback-text popup__subtitle" data-lead-feedback-text></p>
    <button type="button" data-fancybox-close class="popup__btn btn btn-primary lead-feedback-btn">Понятно</button>
</div>

<div class="popup selection-popup-card-wrap" id="selection-popup" hidden>
    <h2 class="selection-popup-title popup__title title-md" id="selection-popup-title">Подберем франшизы под ваш бюджет</h2>
    <p class="selection-popup-subtitle popup__subtitle">Оставьте имя и телефон. В ближайшее время менеджер свяжется с вами.</p>
    <form class="selection-popup-form form-grid" data-selection-form novalidate>
        <label class="selection-popup-field field" for="selection-popup-name">
            <span>Имя</span>
            <input id="selection-popup-name" class="input" type="text" name="name" autocomplete="name" placeholder="Как к вам обращаться" required data-selection-name>
        </label>
        <label class="selection-popup-field field" for="selection-popup-phone">
            <span>Телефон</span>
            <input id="selection-popup-phone" class="input" type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 (___) ___-__-__" required data-selection-phone>
        </label>
        <label class="consent selection-popup-consent">
            <input type="checkbox" name="consent" required data-selection-consent>
            <span>Я соглашаюсь на обработку персональных данных и принимаю <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">политику конфиденциальности</a>.</span>
        </label>
        <button class="btn btn-primary selection-popup-submit" type="submit">Отправить заявку</button>
    </form>
</div>