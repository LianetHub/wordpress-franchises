<?php

/**
 * Страница одной франшизы (товар WooCommerce).
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

get_header();

if (! function_exists('wc_get_product')) {
    echo '<p class="wrap">WooCommerce не активен.</p>';
    get_footer();
    return;
}

while (have_posts()) {
    the_post();
    if (post_password_required()) {
        echo '<div class="wrap">' . get_the_password_form() . '</div>';
        continue;
    }
    wc_get_template_part('content', 'single-product');
}

get_footer();
