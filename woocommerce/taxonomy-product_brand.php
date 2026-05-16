<?php

/**
 * Бренды товаров отключены: перенаправление в каталог.
 */

defined('ABSPATH') || exit;

if (function_exists('wc_get_page_id')) {
    $shop_id = wc_get_page_id('shop');
    if ($shop_id > 0) {
        $url = get_permalink($shop_id);
        if (is_string($url) && $url !== '') {
            wp_safe_redirect($url, 301);
            exit;
        }
    }
}

wp_safe_redirect(home_url('/'), 301);
exit;
