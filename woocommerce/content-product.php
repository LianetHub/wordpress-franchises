<?php

defined('ABSPATH') || exit;

global $product;

if (! class_exists('WC_Product') || ! is_a($product, 'WC_Product', true) || ! $product->is_visible()) {
    return;
}

$featured_ids = [1580, 1582]; 

$extra_class = '';
if (in_array((int) $product->get_id(), $featured_ids, true)) {
    $extra_class = ' catalog-card-cell--featured';
}
?>

<div <?php wc_product_class('catalog-card-cell' . $extra_class, $product); ?>>
    <?php get_template_part('templates/components/franchise-card'); ?>
</div>