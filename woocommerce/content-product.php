<?php

defined('ABSPATH') || exit;

global $product;

if (! class_exists('WC_Product') || ! is_a($product, 'WC_Product', true) || ! $product->is_visible()) {
    return;
}
?>
<div <?php wc_product_class('catalog-card-cell', $product); ?>>
    <?php get_template_part('templates/components/franchise-card'); ?>
</div>