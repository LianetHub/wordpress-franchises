<?php

/**
 * Страница подборки — каталог франшиз с фильтром подборки.
 */

defined('ABSPATH') || exit;

if (! class_exists('WooCommerce')) {
    wp_safe_redirect(home_url('/'));
    exit;
}

$selection_id = (int) get_queried_object_id();
$GLOBALS['franchises_current_selection_id'] = $selection_id;

get_header();

$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$per_page = function_exists('franchises_catalog_products_per_page')
    ? franchises_catalog_products_per_page()
    : (int) get_option('posts_per_page', 12);
if ($per_page <= 0) {
    $per_page = 12;
}

$products_query = franchises_selection_catalog_query($selection_id, $paged, $per_page);

global $wp_query;
$wp_query = $products_query;

require get_template_directory() . '/woocommerce/parts/catalog-franchise-inner.php';

wp_reset_postdata();
unset($GLOBALS['franchises_current_selection_id']);

get_footer();
