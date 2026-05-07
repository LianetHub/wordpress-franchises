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