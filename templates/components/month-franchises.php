<?php
/**
 * Компонент "Франшизы месяца".
 * Франшизы выбираются через ACF-поле month_franchises на главной странице.
 */

defined('ABSPATH') || exit;
$variant = isset($args['variant']) ? sanitize_html_class($args['variant']) : 'home';

$front_page_id = (int) get_option('page_on_front');

$month_franchises = function_exists('get_field')
    ? get_field('month_franchises', $front_page_id)
    : [];

$month_franchise_ids = [];

if (is_array($month_franchises)) {
    foreach ($month_franchises as $item) {
        if ($item instanceof WP_Post) {
            $month_franchise_ids[] = (int) $item->ID;
        } elseif (is_numeric($item)) {
            $month_franchise_ids[] = (int) $item;
        }
    }
}

$month_franchise_ids = array_slice(array_filter(array_unique($month_franchise_ids)), 0, 2);

if (empty($month_franchise_ids)) {
    return;
}

$month_franchises_query = new WP_Query([
    'post_type'      => 'product',
    'post__in'       => $month_franchise_ids,
    'orderby'        => 'post__in',
    'posts_per_page' => 2,
]);
?>

<?php if ($month_franchises_query->have_posts()) : ?>
	<section class="month-franchises month-franchises--<?php echo esc_attr($variant); ?>">        
<div class="month-franchises__head">
    <h2 class="month-franchises__title segment-title">Франшизы месяца</h2>
    <p class="month-franchises__subtitle segment-sub">
        Франшизы, на которые стоит обратить внимание
    </p>
</div>

        <div class="month-franchises__grid">
            <?php
            while ($month_franchises_query->have_posts()) :
                $month_franchises_query->the_post();

                $product = wc_get_product(get_the_ID());

                if (! $product || ! $product->is_visible()) {
                    continue;
                }

                $product_id = $product->get_id();
                $title      = get_the_title($product_id);
                $link       = get_permalink($product_id);
                $excerpt    = get_the_excerpt($product_id);
                $image      = get_the_post_thumbnail_url($product_id, 'medium');

                if (! $image && function_exists('wc_placeholder_img_src')) {
                    $image = wc_placeholder_img_src('medium');
                }
                ?>
                <a class="month-franchise-mini" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener">
                    <span class="month-franchise-mini__image">
                        <?php if ($image) : ?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                        <?php endif; ?>
                    </span>

                    <span class="month-franchise-mini__content">
                        <span class="month-franchise-mini__title">
                            <?php echo esc_html($title); ?>
                        </span>

                        <?php if ($excerpt) : ?>
                            <span class="month-franchise-mini__text">
                                <?php echo esc_html(wp_trim_words($excerpt, 10, '...')); ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endwhile; ?>
        </div>
    </section>

    <?php wp_reset_postdata(); ?>
<?php endif; ?>