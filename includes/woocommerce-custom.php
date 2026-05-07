<?php

/**
 * Настройки WooCommerce: превращаем магазин в каталог франшиз
 */

add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
});

add_filter('woocommerce_admin_features', function ($features) {
    return array_values(array_diff($features, ['customers', 'analytics', 'marketing']));
});

add_action('admin_menu', function () {
    remove_menu_page('edit.php?post_type=shop_order');
    remove_menu_page('edit.php?post_type=shop_coupon');
    remove_submenu_page('woocommerce', 'wc-reports');
    remove_submenu_page('woocommerce', 'wc-status');
    remove_menu_page('wc-admin&path=/customers');
    remove_menu_page('wc-admin&path=/analytics/overview');
    remove_menu_page('woocommerce-marketing');
}, 999);

add_filter('woocommerce_settings_tabs_array', function ($tabs) {
    unset(
        $tabs['shipping'],
        $tabs['checkout'],
        $tabs['payments'],
        $tabs['accounts'],
        $tabs['tax'],
        $tabs['emails'],
        $tabs['integration'],
        $tabs['advanced']
    );
    return $tabs;
}, 999);

add_filter('woocommerce_product_data_tabs', function ($tabs) {
    unset(
        $tabs['inventory'],
        $tabs['shipping'],
        $tabs['linked_product'],
        $tabs['attribute'],
        $tabs['variations'],
        $tabs['advanced']
    );
    return $tabs;
}, 999);

add_filter('woocommerce_is_purchasable', '__return_false');

add_filter('woocommerce_register_post_type_product', function ($args) {
    $labels = [
        'name'               => 'Франшизы',
        'singular_name'      => 'Франшиза',
        'menu_name'          => 'Франшизы',
        'add_new'            => 'Добавить франшизу',
        'add_new_item'       => 'Добавить новую франшизу',
        'edit_item'          => 'Редактировать франшизу',
        'new_item'           => 'Новая франшиза',
        'view_item'          => 'Посмотреть франшизу',
        'search_items'       => 'Найти франшизу',
        'not_found'          => 'Франшиз не найдено',
        'not_found_in_trash' => 'В корзине франшиз не найдено',
        'all_items'          => 'Все франшизы',
    ];
    $args['labels'] = $labels;
    return $args;
});

add_filter('gettext', function ($translated_text, $text, $domain) {
    if ($domain === 'woocommerce' || $domain === 'default') {
        switch ($text) {
            case 'Product':
                return 'Франшиза';
            case 'Products':
                return 'Франшизы';
            case 'Product Data':
                return 'Данные франшизы';
        }
    }
    return $translated_text;
}, 20, 3);

add_action('admin_head', function () {
    echo '<style>
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="wc-admin&path=%2Fcustomers"]),
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="checkout"]),
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="wc-orders"]), 
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="wc-reports"]),
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="extensions"])
     { 
        display: none !important;
     } 
    </style>';
});
