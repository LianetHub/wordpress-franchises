<?php
defined('ABSPATH') || exit;

global $product;

if (! class_exists('WC_Product') || ! is_a($product, 'WC_Product', true) || ! $product->is_visible()) {
    return;
}
?>
<li <?php wc_product_class('', $product); ?>>
    <?php get_template_part('templates/components/franchise-card'); ?>
</li>
