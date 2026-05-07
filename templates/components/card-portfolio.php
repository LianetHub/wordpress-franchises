<?php
$is_slider = get_field('case_display_mode');
$single_img = get_field('case_single_img');
$before = get_field('case_before_img');
$after = get_field('case_after_img');

$services = get_field('case_service_link');
$case_area = get_field('case_area');
$case_duration = get_field('case_duration');
$case_staff = get_field('case_staff');
$case_title = get_the_title();
$case_link = get_permalink();

$case_excerpt = has_excerpt() ? get_the_excerpt() : get_the_content();

$primary_term_id = (!empty($services) && is_array($services)) ? $services[0] : $services;
$term = $primary_term_id ? get_term($primary_term_id, 'service_cat') : null;
?>

<div class="case-card">
    <?php if (!$is_slider && $single_img): ?>
        <div class="case-card__image">
            <a href="<?php echo esc_url($case_link); ?>">
                <img src="<?php echo esc_url($single_img['url']); ?>"
                    alt="<?php echo esc_attr($single_img['alt'] ?: $case_title); ?>"
                    class="cover-image"
                    loading="lazy"
                    decoding="async">
            </a>
        </div>
    <?php elseif ($before && $after): ?>

        <div class="case-card__slider before-slider">
            <div class="before-slider__layer before-slider__layer--after">
                <img class="before-slider__image"
                    src="<?php echo esc_url($after['url']); ?>"
                    alt="<?php echo esc_attr($after['alt'] ?: 'После'); ?>"
                    loading="lazy"
                    decoding="async">
            </div>

            <div class="before-slider__layer before-slider__layer--before">
                <img class="before-slider__image"
                    src="<?php echo esc_url($before['url']); ?>"
                    alt="<?php echo esc_attr($before['alt'] ?: 'До'); ?>"
                    loading="lazy"
                    decoding="async">
            </div>

            <span class="before-slider__label before-slider__label--before">До</span>
            <span class="before-slider__label before-slider__label--after">После</span>

            <div class="before-slider__divider icon-arrows"></div>
        </div>
    <?php endif; ?>

    <div class="case-card__details">
        <?php if ($term && !is_wp_error($term)) : ?>
            <a href="<?php echo esc_url(get_term_link($term)); ?>" class="case-card__category">
                <?php echo esc_html($term->name); ?>
            </a>
        <?php endif; ?>

        <ul class="case-card__meta">
            <?php if ($case_area) : ?>
                <li class="case-card__meta-item icon-size"><?php echo esc_html($case_area); ?></li>
            <?php endif; ?>

            <?php if ($case_duration) : ?>
                <li class="case-card__meta-item icon-clock"><?php echo esc_html($case_duration); ?></li>
            <?php endif; ?>

            <?php if ($case_staff) : ?>
                <li class="case-card__meta-item icon-team"><?php echo esc_html($case_staff); ?></li>
            <?php endif; ?>
        </ul>

        <h3 class="case-card__title">
            <a href="<?php echo esc_url($case_link); ?>">
                <?php echo esc_html($case_title); ?>
            </a>
        </h3>

        <?php if ($case_excerpt) : ?>
            <p class="case-card__desc">
                <?php echo wp_trim_words($case_excerpt, 20); ?>
            </p>
        <?php endif; ?>

        <a href="<?php echo esc_url($case_link); ?>"
            class="case-card__btn btn btn-primary">Подробнее</a>
    </div>
</div>