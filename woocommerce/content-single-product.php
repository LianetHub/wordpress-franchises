<?php

/**
 * Вывод карточки франшизы (разметка как в franchise-*.html).
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

global $product;

$resolved_id = (int) (is_singular('product') ? get_queried_object_id() : get_the_ID());
if ($resolved_id <= 0) {
    return;
}

$product = wc_get_product($resolved_id);
if (! $product || ! is_a($product, 'WC_Product', true)) {
    return;
}

$post_id = (int) $product->get_id();
$content_raw = (string) get_post_field('post_content', $post_id);
$content_html = apply_filters('the_content', $content_raw);
$toc_bundle = franchises_content_with_toc($content_html);
$content_html = $toc_bundle['content'];
$toc_items    = $toc_bundle['toc_items'];

$gallery_urls  = franchises_product_gallery_urls($product);
$breadcrumbs   = franchises_product_breadcrumb_trail($post_id);
$faq_rows      = franchises_get_product_faq_rows($post_id);
$similar_query = franchises_product_similar_query($post_id, 12);
$popular_query = franchises_product_popular_query($post_id, 12);

do_action('woocommerce_before_single_product');

?>
<div id="product-<?php echo esc_attr((string) $post_id); ?>" <?php wc_product_class('single-franchise-product', $product); ?>>
    <?php
    /**
     * Переменные выше попадают в область видимости franchise-single.php (require).
     * Не используйте get_template_part( ..., $args ) — в WP в шаблон не передаётся массив $args.
     */
    require get_theme_file_path('templates/woocommerce/franchise-single.php');
    ?>
</div>
<?php

do_action('woocommerce_after_single_product');
