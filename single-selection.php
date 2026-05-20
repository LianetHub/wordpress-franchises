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

$filter_type = franchises_selection_filter_type($selection_id);
$needs_post_filter = franchises_selection_needs_post_filter($filter_type);

if ($needs_post_filter) {
    $all_ids = franchises_selection_product_ids($selection_id, 500);
    $total = count($all_ids);
    $offset = ($paged - 1) * $per_page;
    $page_ids = array_slice($all_ids, $offset, $per_page);

    $products_query = new WP_Query([
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'post__in'               => $page_ids !== [] ? $page_ids : [0],
        'orderby'                => 'post__in',
        'posts_per_page'         => $per_page,
        'paged'                  => 1,
        'no_found_rows'          => true,
        'ignore_sticky_posts'    => true,
    ]);
    $products_query->found_posts = $total;
    $products_query->max_num_pages = $total > 0 ? (int) ceil($total / $per_page) : 0;
} else {
    $products_query = new WP_Query(franchises_selection_products_query_args($selection_id, [
        'posts_per_page' => $per_page,
        'paged'          => $paged,
    ]));
}

global $wp_query;
$wp_query = $products_query;

require get_template_directory() . '/woocommerce/parts/catalog-franchise-inner.php';

wp_reset_postdata();
unset($GLOBALS['franchises_current_selection_id']);

get_footer();
