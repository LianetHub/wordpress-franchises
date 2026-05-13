<?php

/**
 * Настройки WooCommerce: Каталог франшиз
 */

add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
});

// 1. Отключаем фичи нового интерфейса (включая платежи и аналитику)
add_filter('woocommerce_admin_features', function ($features) {
    return array_values(array_diff($features, [
        'customers',
        'analytics',
        'marketing',
        'onboarding',
        'payment-gateway-suggestions',
        'shipping-label-banner',
        'homescreen'
    ]));
});

// Отключаем отзывы для франшиз
add_filter('woocommerce_product_supports', function ($supports, $feature) {
    if ($feature === 'reviews') {
        return false;
    }
    return $supports;
}, 10, 2);

// Убираем метабокс отзывов из редактирования
add_action('add_meta_boxes_product', function () {
    remove_meta_box('commentsdiv', 'product', 'normal');
}, 20);

// 2. Удаляем пункты меню
add_action('admin_menu', function () {
    remove_menu_page('edit.php?post_type=shop_order');
    remove_menu_page('edit.php?post_type=shop_coupon');
    remove_submenu_page('woocommerce', 'wc-reports');
    remove_submenu_page('woocommerce', 'wc-status');
    remove_menu_page('wc-admin&path=/customers');
    remove_menu_page('wc-admin&path=/analytics/overview');
    remove_menu_page('woocommerce-marketing');
    remove_submenu_page('edit.php?post_type=product', 'product-reviews');

    remove_menu_page('wc-admin&path=/payments');
    remove_menu_page('wc-settings&tab=checkout');
}, 999);

// 3. Скрываем вкладки в настройках
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

// 4. Очищаем метабоксы данных товара
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

// 5. Переименование в "Франшизы"
add_filter('woocommerce_register_post_type_product', function ($args) {
    $labels = [
        'name'               => 'Франшизы',
        'singular_name'      => 'Франшиза',
        'menu_name'          => 'Франшизы',
        'all_items'          => 'Все франшизы',
        'add_new'            => 'Добавить франшизу',
        'add_new_item'       => 'Добавить новую франшизу',
        'edit_item'          => 'Редактировать франшизу',
        'new_item'           => 'Новая франшиза',
        'view_item'          => 'Посмотреть франшизу',
        'search_items'       => 'Найти франшизу',
        'not_found'          => 'Франшиз не найдено',
        'not_found_in_trash' => 'В корзине франшиз не найдено',
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

// 6. CSS чистка для меню 
add_action('admin_head', function () {
    echo '<style>
        /* Скрываем конкретные пункты в подменю WooCommerce */
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="wc-admin&path=%2Fcustomers"]),
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="checkout"]),
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="wc-orders"]), 
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="wc-reports"]),
        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="extensions"]),
        
        #toplevel_page_admin-page-wc-settings-tab-checkout,
        li[id*="PAYMENTS_MENU_ITEM"],
        a[href*="PAYMENTS_MENU_ITEM"] { 
            display: none !important; 
        }

        #toplevel_page_woocommerce .wp-submenu li:has(a[href*="product-reviews"]),
        .submenu-product-reviews { 
            display: none !important; 
        }
    </style>';
});

/**
 * Отключаем все типы товаров, кроме простого
 */
add_filter('product_type_selector', function ($types) {
    // Оставляем в массиве только ключ 'simple'
    return [
        'simple' => $types['simple']
    ];
});

// -------------------------------------------------------------------------
// Данные карточки франшизы (ACF + WooCommerce) — используется в шаблоне
// templates/components/franchise-card.php и на главной.
// -------------------------------------------------------------------------

if (! function_exists('franchises_format_money_ru')) {
    /**
     * @param int|float|string|null $amount
     */
    function franchises_format_money_ru($amount): string
    {
        if ($amount === '' || $amount === null) {
            return '';
        }
        $n = is_numeric($amount) ? (float) $amount : 0;

        return number_format((int) round($n), 0, ',', ' ') . ' ₽';
    }
}

if (! function_exists('franchises_franchise_card_from_post')) {
    /**
     * Сборка данных карточки из поста (ACF + обложка + постоянная ссылка).
     *
     * @return array<string, mixed>
     */
    function franchises_franchise_card_from_post(int $post_id): array
    {
        $raw_acf = function_exists('get_fields') ? get_fields($post_id) : false;
        $acf = is_array($raw_acf) ? $raw_acf : [];

        $title = '';
        if (isset($acf['product_full_title']) && $acf['product_full_title'] !== '') {
            $title = (string) $acf['product_full_title'];
        } else {
            $title = get_the_title($post_id);
        }

        $verified = ! empty($acf['verified']);

        $pausal = isset($acf['pausal']) && $acf['pausal'] !== '' ? (int) $acf['pausal'] : null;
        $profit_min = isset($acf['monthly_profit_min']) && $acf['monthly_profit_min'] !== '' ? (int) $acf['monthly_profit_min'] : null;
        $profit_max = isset($acf['monthly_profit_max']) && $acf['monthly_profit_max'] !== '' ? (int) $acf['monthly_profit_max'] : null;
        $payback_min = isset($acf['payback_min']) && $acf['payback_min'] !== '' ? (int) $acf['payback_min'] : null;
        $payback_max = isset($acf['payback_max']) && $acf['payback_max'] !== '' ? (int) $acf['payback_max'] : null;

        $invest = $pausal;
        if ($invest === null && function_exists('wc_get_product')) {
            $wc_p = wc_get_product($post_id);
            if ($wc_p && $wc_p->get_regular_price() !== '') {
                $invest = (int) wc_format_decimal($wc_p->get_regular_price(), 0, false);
            }
        }
        $profit_for_filter = $profit_min ?? $profit_max;
        $payback_for_filter = $payback_min ?? $payback_max;

        $thumb = get_the_post_thumbnail_url($post_id, 'large') ?: '';

        $permalink = get_permalink($post_id) ?: '#';
        $slug = (string) get_post_field('post_name', $post_id);

        $sphere = '';
        $category = '';
        $tags_parts = [];
        if (taxonomy_exists('product_cat')) {
            $terms = get_the_terms($post_id, 'product_cat');
            if (is_array($terms) && $terms !== []) {
                $picked = null;
                foreach ($terms as $t) {
                    if ((int) $t->parent > 0) {
                        $picked = $t;
                        break;
                    }
                }
                if (! $picked) {
                    $picked = $terms[0];
                }
                $category = (string) $picked->name;
                if ((int) $picked->parent > 0) {
                    $parent = get_term((int) $picked->parent, 'product_cat');
                    if ($parent && ! is_wp_error($parent)) {
                        $sphere = (string) $parent->name;
                    }
                }
                $tags_parts = array_merge($tags_parts, wp_list_pluck($terms, 'name'));
            }
        }
        if (taxonomy_exists('product_tag')) {
            $ptags = get_the_terms($post_id, 'product_tag');
            if (is_array($ptags) && $ptags !== []) {
                $tags_parts = array_merge($tags_parts, wp_list_pluck($ptags, 'name'));
            }
        }
        if ($verified) {
            $tags_parts[] = 'Проверено';
        }
        $tags_parts = array_values(array_unique(array_filter(array_map('strval', $tags_parts))));
        $tags_pipe = implode('|', $tags_parts);

        $excerpt = (string) get_post_field('post_excerpt', $post_id);
        if ($excerpt === '') {
            $excerpt = wp_trim_words(
                wp_strip_all_tags((string) get_post_field('post_content', $post_id)),
                24,
                '…'
            );
        }

        return [
            'href'             => $permalink,
            'order'            => 0,
            'date'             => (int) get_post_time('U', true, $post_id),
            'popularity'       => 0,
            'sphere'           => $sphere,
            'category'         => $category,
            'invest'           => $invest,
            'payback'          => $payback_for_filter,
            'profit'           => $profit_for_filter,
            'verified'         => $verified,
            'tags'             => $tags_pipe,
            'name'             => $title,
            'desc'             => $excerpt,
            'image'            => $thumb,
            'franchise_id'     => $slug,
            'franchise_url'    => $permalink,
            'acf_year_founded' => $acf['year_founded'] ?? null,
            'acf_franchise_since' => $acf['franchise_since'] ?? null,
            'acf_own_stores'   => $acf['own_stores'] ?? null,
            'acf_franch_stores' => $acf['franch_stores'] ?? null,
            'acf_tm_number'    => $acf['tm_number'] ?? null,
            'acf_monthly_profit_min' => $acf['monthly_profit_min'] ?? null,
            'acf_monthly_profit_max' => $acf['monthly_profit_max'] ?? null,
            'acf_payback_min'  => $acf['payback_min'] ?? null,
            'acf_payback_max'  => $acf['payback_max'] ?? null,
            'acf_pausal'       => $acf['pausal'] ?? null,
            'acf_royalty'      => $acf['royalty'] ?? null,
        ];
    }
}
