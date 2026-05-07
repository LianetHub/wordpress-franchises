<?php
$term = $args['term'] ?? null;
if (!$term) return;

$phone = get_field('phone', 'option');
$phone_clean = $phone ? preg_replace('/[^\d+]/', '', $phone) : '';

$icon = get_field('category_icon', $term);
$image = get_field('category_image', $term);
$term_link = get_term_link($term);

$services_query = new WP_Query([
    'post_type'      => 'services',
    'posts_per_page' => -1,
    'tax_query'      => [[
        'taxonomy' => 'service_cat',
        'field'    => 'term_id',
        'terms'    => $term->term_id,
        'include_children' => false
    ]],
    'meta_query'     => [
        [
            'key'     => 'current_city',
            'value'   => 'Санкт-Петербург',
            'compare' => '='
        ]
    ]
]);

$total_services = $services_query->found_posts;
$limit = is_tax('service_cat') ? 0 : 4;
$counter = 0;

$min_price = 0;
$prices_group = get_field('all_services_prices_list', 'option');

if ($services_query->have_posts()) {
    while ($services_query->have_posts()) {
        $services_query->the_post();
        $sid = get_the_ID();
        $service_data = $prices_group['service_data_' . $sid] ?? null;
        $price = !empty($service_data['service_price']) ? (int)preg_replace('/[^\d]/', '', $service_data['service_price']) : 0;

        if ($price > 0 && ($min_price === 0 || $price < $min_price)) {
            $min_price = $price;
        }
    }
    $services_query->rewind_posts();
}
?>

<li class="services__item">
    <?php if ($image): ?>
        <a href="<?php echo esc_url($term_link); ?>" class="services__item-image">
            <img src="<?php echo esc_url($image['url']); ?>"
                alt="<?php echo esc_attr($term->name); ?>"
                width="482"
                height="686"
                loading="lazy"
                decoding="async">
        </a>
    <?php endif; ?>
    <div class="services__item-body">
        <div class="services__item-main">
            <div class="services__item-header">
                <?php if ($icon): ?>
                    <div class="services__item-icon" aria-hidden="true">
                        <?php echo get_processed_svg($icon['url'], '#ffffff'); ?>
                    </div>
                <?php endif; ?>

                <a href="<?php echo esc_url($term_link); ?>" class="services__item-info">
                    <div class="services__item-title">
                        <?php echo fix_widows_after_prepositions($term->name); ?>
                    </div>
                    <?php if ($min_price > 0): ?>
                        <div class="services__item-price">
                            от&nbsp;<?php echo format_service_price($min_price); ?>&nbsp;₽
                        </div>
                    <?php endif; ?>
                </a>
            </div>
            <a class="services__item-btn btn btn-secondary-outline icon-phone"
                href="tel:<?php echo $phone_clean; ?>">Вызвать бригаду</a>
        </div>

        <?php if ($services_query->have_posts()): ?>
            <div class="services__item-tags">
                <?php while ($services_query->have_posts()): $services_query->the_post();
                    if ($limit === 0 || $counter < $limit): ?>
                        <a href="<?php the_permalink(); ?>" class="services__item-tag">
                            <?php echo fix_widows_after_prepositions(get_the_title()); ?>
                        </a>
                <?php
                    endif;
                    $counter++;
                endwhile; ?>

                <?php if ($limit > 0 && $total_services > $limit):
                    $remaining = $total_services - $limit; ?>
                    <a href="<?php echo esc_url($term_link); ?>" class="services__item-tag services__item-tag--more">
                        +<?php echo $remaining; ?> <?php echo russian_plural($remaining, ['услуга', 'услуги', 'услуг']); ?>
                    </a>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>
            </div>
        <?php endif; ?>
    </div>
</li>