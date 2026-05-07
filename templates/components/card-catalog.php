<?php
$sid = get_the_ID();
$title = get_the_title();
$link = get_permalink();
$image = get_field('service_card_image') ?: ['url' => get_the_post_thumbnail_url($sid, 'full'), 'alt' => $title];

$prices_group = get_field('all_services_prices_list', 'option');
$service_data = $prices_group['service_data_' . $sid] ?? null;
$display_price = !empty($service_data['service_price']) ? $service_data['service_price'] : '';

$post_terms = get_the_terms($sid, 'service_cat');
$tag_names = [];
if ($post_terms && !is_wp_error($post_terms)) {
    foreach (array_slice($post_terms, 0, 2) as $t) {
        $tag_names[] = $t->name;
    }
}
?>

<div class="catalog__card">
    <a href="<?php echo esc_url($link); ?>" class="catalog__card-image">
        <?php if (!empty($image['url'])): ?>
            <img src="<?php echo esc_url($image['url']); ?>"
                alt="<?php echo esc_attr($image['alt'] ?: $title); ?>"
                class="cover-image"
                loading="lazy">
        <?php else: ?>
            <div class="catalog__card-placeholder">
                <img
                    src="<?php echo get_template_directory_uri(); ?>/assets/img/catalog-card-placeholder.svg"
                    alt="Нет фото"
                    loading="lazy"
                    class="cover-image">
            </div>
        <?php endif; ?>
    </a>
    <div class="catalog__card-content">
        <a href="<?php echo esc_url($link); ?>" class="catalog__card-link">
            <div class="catalog__card-title">
                <?php echo esc_html($title); ?>
            </div>
            <?php if ($display_price): ?>
                <div class="catalog__card-price">
                    от <?php echo format_service_price($display_price); ?> ₽
                </div>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url($link); ?>" class="catalog__card-btn btn btn-primary">
            Подробнее
        </a>
    </div>
</div>