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

/**
 * Урезанное меню WooCommerce + убрать метки, атрибуты, бренды из подменю «Франшизы».
 * Приоритет 99999: WooCommerce и доп. плагины успевают зарегистрировать пункты.
 */
add_action('admin_menu', static function (): void {
    remove_menu_page('edit.php?post_type=shop_order');
    remove_menu_page('edit.php?post_type=shop_coupon');
    remove_submenu_page('woocommerce', 'wc-reports');
    remove_submenu_page('woocommerce', 'wc-status');
    remove_menu_page('wc-admin&path=/customers');
    remove_menu_page('wc-admin&path=/analytics/overview');
    remove_menu_page('woocommerce-marketing');
    remove_menu_page('wc-admin&path=/payments');
    remove_menu_page('wc-settings&tab=checkout');

    remove_submenu_page('edit.php?post_type=product', 'product-reviews');
    remove_submenu_page('edit.php?post_type=product', 'product_attributes');
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_tag');
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_tag&post_type=product');
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_brand');
    remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_brand&post_type=product');

    global $submenu;
    $parent = 'edit.php?post_type=product';
    if (isset($submenu[$parent]) && is_array($submenu[$parent])) {
        foreach ($submenu[$parent] as $index => $item) {
            if (! is_array($item) || empty($item[2])) {
                continue;
            }
            $slug = (string) $item[2];
            if (
                $slug === 'product_attributes'
                || str_contains($slug, 'taxonomy=product_tag')
                || str_contains($slug, 'taxonomy=product_brand')
            ) {
                unset($submenu[$parent][$index]);
            }
        }
    }
}, 99999);

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

// 4. Очищаем метабоксы данных товара (вкладка «Атрибуты» в WC — ключ массива «attribute»)
add_filter('woocommerce_product_data_tabs', function ($tabs) {
    unset(
        $tabs['inventory'],
        $tabs['shipping'],
        $tabs['linked_product'],
        $tabs['attribute'],
        $tabs['attributes'],
        $tabs['variations'],
        $tabs['advanced']
    );
    return $tabs;
}, 999);

// Встроенные бренды WooCommerce 9.4+ — выключаем до регистрации таксономии.
add_filter('pre_option_wc_feature_woocommerce_brands_enabled', static function ($pre) {
    return 'no';
}, 5);

// Метки, бренды и глобальные атрибуты не используем — только категории product_cat.
add_action('init', static function (): void {
    if (! function_exists('unregister_taxonomy_for_object_type')) {
        return;
    }
    unregister_taxonomy_for_object_type('product_tag', 'product');
    if (taxonomy_exists('product_brand')) {
        unregister_taxonomy_for_object_type('product_brand', 'product');
    }
}, 100);

add_filter('woocommerce_attribute_taxonomies', static function (): array {
    return [];
}, 100);

add_filter('register_taxonomy_product_tag_args', static function ($args) {
    if (! is_array($args)) {
        return $args;
    }
    $args['show_ui'] = false;
    $args['show_in_menu'] = false;
    $args['show_admin_column'] = false;
    $args['show_in_nav_menus'] = false;

    return $args;
}, 999);

add_filter('register_taxonomy_product_brand_args', static function ($args) {
    if (! is_array($args)) {
        return $args;
    }
    $args['show_ui'] = false;
    $args['show_in_menu'] = false;
    $args['show_admin_column'] = false;
    $args['show_in_nav_menus'] = false;

    return $args;
}, 999);

/** Старые ссылки на архивы меток и брендов товара ведём в каталог. */
add_action('template_redirect', static function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    if (! function_exists('is_tax')) {
        return;
    }
    if (! is_tax('product_tag') && ! is_tax('product_brand')) {
        return;
    }
    if (! function_exists('wc_get_page_id')) {
        return;
    }
    $shop_id = wc_get_page_id('shop');
    if ($shop_id <= 0) {
        return;
    }
    $url = get_permalink($shop_id);
    if (! is_string($url) || $url === '') {
        return;
    }
    wp_safe_redirect($url, 301);
    exit;
}, 5);

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

if (! function_exists('franchises_product_price_amount')) {
    /**
     * Цена товара WooCommerce (regular price) в рублях для карточек и фильтров.
     */
    function franchises_product_price_amount(int $post_id): ?int
    {
        if (! function_exists('wc_get_product')) {
            return null;
        }

        $product = wc_get_product($post_id);
        if (! $product) {
            return null;
        }

        $price = $product->get_regular_price();
        if ($price === '' || $price === null) {
            return null;
        }

        return (int) wc_format_decimal($price, 0, false);
    }
}

if (! function_exists('franchises_format_product_price_ru')) {
    function franchises_format_product_price_ru(int $post_id): string
    {
        $amount = franchises_product_price_amount($post_id);

        return ($amount !== null && $amount > 0) ? franchises_format_money_ru($amount) : '';
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

        $verified = ! empty($acf['verified']);

        $profit_min = isset($acf['monthly_profit_min']) && $acf['monthly_profit_min'] !== '' ? (int) $acf['monthly_profit_min'] : null;
        $profit_max = isset($acf['monthly_profit_max']) && $acf['monthly_profit_max'] !== '' ? (int) $acf['monthly_profit_max'] : null;
        $payback_min = isset($acf['payback_min']) && $acf['payback_min'] !== '' ? (int) $acf['payback_min'] : null;
        $payback_max = isset($acf['payback_max']) && $acf['payback_max'] !== '' ? (int) $acf['payback_max'] : null;

        $invest = franchises_product_price_amount($post_id);
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
            'post_id'          => $post_id,
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
            'name'             => get_the_title($post_id),
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

if (! function_exists('franchises_render_franchise_card')) {
    /**
     * @param array<string, mixed> $franchise_card
     */
    function franchises_render_franchise_card(array $franchise_card): bool
    {
        if ($franchise_card === []) {
            return false;
        }

        $template = get_template_directory() . '/templates/components/franchise-card.php';
        if (! is_readable($template)) {
            return false;
        }

        global $product;
        $saved_product = $product ?? null;
        $product = null;

        include $template;

        $product = $saved_product;

        return true;
    }
}

if (! function_exists('franchises_render_franchise_card_for_product')) {
    /**
     * @param array<string, mixed> $overrides
     */
    function franchises_render_franchise_card_for_product(int $product_id, array $overrides = []): bool
    {
        if ($product_id <= 0 || ! function_exists('franchises_franchise_card_from_post')) {
            return false;
        }

        $card = franchises_franchise_card_from_post($product_id);
        if ($overrides !== []) {
            $card = array_merge($card, $overrides);
        }

        return franchises_render_franchise_card($card);
    }
}

// -------------------------------------------------------------------------
// Карточка франшизы (single product): галерея, хлебные крошки, оглавление, FAQ
// -------------------------------------------------------------------------

add_filter('body_class', function (array $classes): array {
    if (function_exists('is_product') && is_product()) {
        $classes[] = 'header-solid';
        $classes[] = 'woocommerce-single-franchise';
    }
    return $classes;
});

if (! function_exists('franchises_product_gallery_urls')) {
    /**
     * URL изображений: обложка + галерея WooCommerce (без дубликатов).
     *
     * @return list<string>
     */
    function franchises_product_gallery_urls(WC_Product $product): array
    {
        $urls = [];
        $image_id = $product->get_image_id();
        if ($image_id) {
            $u = wp_get_attachment_image_url((int) $image_id, 'full');
            if ($u) {
                $urls[] = $u;
            }
        }
        foreach ($product->get_gallery_image_ids() as $gid) {
            $u = wp_get_attachment_image_url((int) $gid, 'full');
            if ($u && ! in_array($u, $urls, true)) {
                $urls[] = $u;
            }
        }
        if ($urls === []) {
            $ph = function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src('woocommerce_single') : '';
            if ($ph) {
                $urls[] = $ph;
            }
        }

        return array_values(array_filter($urls));
    }
}

if (! function_exists('franchises_product_breadcrumb_trail')) {
    /**
     * Цепочка хлебных крошек для карточки товара (franchises_render_breadcrumbs).
     *
     * @return list<array{label: string, href: string}>
     */
    function franchises_product_breadcrumb_trail(int $post_id): array
    {
        $trail = [];
        $trail[] = ['label' => 'Главная', 'href' => home_url('/')];

        $shop_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
        $shop_url = $shop_id > 0 ? get_permalink($shop_id) : '';
        if ($shop_url) {
            $trail[] = ['label' => 'Каталог франшиз', 'href' => $shop_url];
        }

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
                if ((int) $picked->parent > 0) {
                    $parent = get_term((int) $picked->parent, 'product_cat');
                    if ($parent && ! is_wp_error($parent)) {
                        $link = get_term_link($parent);
                        $trail[] = [
                            'label' => (string) $parent->name,
                            'href'  => is_wp_error($link) ? '' : (string) $link,
                        ];
                    }
                }
                $link = get_term_link($picked);
                $trail[] = [
                    'label' => (string) $picked->name,
                    'href'  => is_wp_error($link) ? '' : (string) $link,
                ];
            }
        }

        $title = get_the_title($post_id);
        if (function_exists('get_field')) {
            $full = get_field('product_full_title', $post_id);
            if (is_string($full) && $full !== '') {
                $title = $full;
            }
        }
        $trail[] = ['label' => $title, 'href' => ''];

        return $trail;
    }
}

if (! function_exists('franchises_format_payback_ru')) {
    function franchises_format_payback_ru($min, $max): string
    {
        $imin = $min !== null && $min !== '' ? (int) $min : null;
        $imax = $max !== null && $max !== '' ? (int) $max : null;
        if ($imin === null && $imax === null) {
            return '';
        }
        if ($imin !== null && $imax !== null && $imin !== $imax) {
            return (string) $imin . '–' . (string) $imax . ' ' . russian_plural($imax, ['месяц', 'месяца', 'месяцев']);
        }
        $n = $imin ?? $imax;

        return (string) $n . ' ' . russian_plural((int) $n, ['месяц', 'месяца', 'месяцев']);
    }
}

if (! function_exists('franchises_format_profit_line_ru')) {
    /** «от 420 000 ₽» или «420 000 – 850 000 ₽» */
    function franchises_format_profit_line_ru($min, $max): string
    {
        $pmin = $min !== null && $min !== '' ? (int) $min : null;
        $pmax = $max !== null && $max !== '' ? (int) $max : null;
        if ($pmin === null && $pmax === null) {
            return '';
        }
        if ($pmin !== null && $pmax !== null && $pmin !== $pmax) {
            return franchises_format_money_ru($pmin) . ' – ' . franchises_format_money_ru($pmax);
        }
        $v = $pmin ?? $pmax;

        return 'от ' . franchises_format_money_ru($v);
    }
}

if (! function_exists('franchises_format_investment_line_ru')) {
    /**
     * Инвестиции для сайдбара: ACF investment_min (руб.) или цена товара WooCommerce.
     */
    function franchises_format_investment_line_ru(WC_Product $product): string
    {
        $post_id = $product->get_id();
        if (function_exists('get_field')) {
            $inv = get_field('investment_min', $post_id);
            if ($inv !== null && $inv !== '') {
                return 'от ' . franchises_format_money_ru((int) $inv);
            }
        }
        $price = $product->get_regular_price();
        if ($price !== '' && is_numeric($price)) {
            return 'от ' . franchises_format_money_ru((int) wc_format_decimal($price, 0, false));
        }

        return '';
    }
}

if (! function_exists('franchises_format_pausal_line_ru')) {
    function franchises_format_pausal_line_ru(int $post_id): string
    {
        if (! function_exists('get_field')) {
            return '';
        }
        $p = get_field('pausal', $post_id);
        if ($p === null || $p === '') {
            return '';
        }

        return 'от ' . franchises_format_money_ru((int) $p);
    }
}

if (! function_exists('franchises_format_royalty_display')) {
    function franchises_format_royalty_display(int $post_id): string
    {
        if (! function_exists('get_field')) {
            return '';
        }
        $r = get_field('royalty', $post_id);
        if ($r === null || $r === '') {
            return '';
        }
        if (is_numeric($r)) {
            return rtrim(rtrim((string) ((float) $r), '0'), '.') . '%';
        }

        return (string) $r;
    }
}

if (! function_exists('franchises_extract_toc_from_content')) {
    /**
     * Заголовки h2 с атрибутом id — для блока «Содержание» (без автогенерации id).
     *
     * @return list<array{id: string, title: string}>
     */
    function franchises_extract_toc_from_content(string $html): array
    {
        $out = [];
        if (! preg_match_all('/<h2\b[^>]*\bid\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/h2>/is', $html, $m, PREG_SET_ORDER)) {
            return $out;
        }
        foreach ($m as $row) {
            $id = isset($row[1]) ? sanitize_html_class($row[1]) : '';
            $title = isset($row[2]) ? wp_strip_all_tags((string) $row[2]) : '';
            if ($id !== '' && $title !== '') {
                $out[] = ['id' => $id, 'title' => $title];
            }
        }

        return $out;
    }
}

if (! function_exists('franchises_content_with_toc')) {
    /**
     * Добавляет уникальные id к &lt;h2&gt; без id и строит оглавление (адаптация theme_content_with_toc).
     *
     * @return array{content: string, toc_items: list<array{id: string, title: string}>}
     */
    function franchises_content_with_toc(string $html): array
    {
        $toc_items = [];
        $used_ids = [];

        $out = preg_replace_callback(
            '/<h2\b([^>]*)>(.*?)<\/h2>/is',
            static function (array $m) use (&$toc_items, &$used_ids): string {
                $attrs = $m[1];
                $inner = $m[2];
                $title_text = wp_strip_all_tags((string) $inner);
                if ($title_text === '') {
                    return $m[0];
                }

                $had_id = preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $attrs, $im);
                if ($had_id) {
                    $slug = sanitize_html_class($im[1]);
                    if ($slug === '') {
                        $slug = 'section-' . (count($used_ids) + 1);
                    }
                } else {
                    $base = sanitize_title($title_text);
                    if ($base === '') {
                        $base = 'section-' . (count($used_ids) + 1);
                    }
                    $slug = $base;
                    $n = 2;
                    while (isset($used_ids[$slug])) {
                        $slug = $base . '-' . $n;
                        $n++;
                    }
                    $attrs_clean = preg_replace('/\s*\bid\s*=\s*["\'][^"\']*["\']/i', '', trim($attrs));
                    $slug_attr = ' id="' . esc_attr($slug) . '"';
                    $used_ids[$slug] = true;
                    $toc_items[] = ['id' => $slug, 'title' => $title_text];
                    if ($attrs_clean === '') {
                        return '<h2' . $slug_attr . '>' . $inner . '</h2>';
                    }

                    return '<h2 ' . trim($attrs_clean) . $slug_attr . '>' . $inner . '</h2>';
                }

                $used_ids[$slug] = true;
                $toc_items[] = ['id' => $slug, 'title' => $title_text];

                return '<h2 ' . trim($attrs) . '>' . $inner . '</h2>';
            },
            $html
        );

        return [
            'content'   => is_string($out) ? $out : $html,
            'toc_items' => $toc_items,
        ];
    }
}

if (! function_exists('franchises_get_product_faq_rows')) {
    /**
     * ACF repeater «faq» на товаре: подполя question, answer (или вопрос / ответ).
     *
     * @return list<array{question: string, answer: string}>
     */
    function franchises_get_product_faq_rows(int $post_id): array
    {
        $rows = [];
        if (! function_exists('have_rows') || ! have_rows('faq', $post_id)) {
            return $rows;
        }
        while (have_rows('faq', $post_id)) {
            the_row();
            $q = get_sub_field('question');
            if ($q === null || $q === '') {
                $q = get_sub_field('вопрос');
            }
            $a = get_sub_field('answer');
            if ($a === null || $a === '') {
                $a = get_sub_field('ответ');
            }
            if ($q && $a) {
                $rows[] = [
                    'question' => wp_strip_all_tags((string) $q),
                    'answer'   => wp_kses_post((string) $a),
                ];
            }
        }

        return $rows;
    }
}

if (! function_exists('franchises_product_similar_query')) {
    function franchises_product_similar_query(int $post_id, int $limit = 12): WP_Query
    {
        $term_ids = [];
        if (taxonomy_exists('product_cat')) {
            $terms = get_the_terms($post_id, 'product_cat');
            if (is_array($terms)) {
                $term_ids = wp_list_pluck($terms, 'term_id');
            }
        }
        $args = [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => $limit,
            'post__not_in'        => [$post_id],
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'orderby'             => ['menu_order' => 'ASC', 'date' => 'DESC'],
        ];
        if ($term_ids !== []) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => array_map('intval', $term_ids),
                ],
            ];
        }

        return new WP_Query($args);
    }
}

if (! function_exists('franchises_product_popular_query')) {
    /** По категории «Популярные франшизы» (product_cat), иначе последние товары. */
    function franchises_product_popular_query(int $exclude_id, int $limit = 12): WP_Query
    {
        $popular_cat_term = get_term_by('name', 'Популярные франшизы', 'product_cat');
        if (! $popular_cat_term || is_wp_error($popular_cat_term)) {
            $popular_cat_term = get_term_by('slug', 'popularnye-franshizy', 'product_cat');
        }
        $args = [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => $limit,
            'post__not_in'        => [$exclude_id],
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'orderby'             => [
                'menu_order' => 'ASC',
                'date'       => 'DESC',
            ],
        ];
        if ($popular_cat_term && ! is_wp_error($popular_cat_term)) {
            $args['tax_query'] = [
                [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => [(int) $popular_cat_term->term_id],
                    'include_children' => true,
                ],
            ];
        }

        return new WP_Query($args);
    }
}

/**
 * Учёт просмотра страницы франшизы: один раз в сутки с браузера (cookie).
 * AJAX franchises_theme_increment_views остаётся для ручного вызова из JS.
 */
add_action(
    'template_redirect',
    static function (): void {
        static $franchises_product_view_counted = false;
        if ($franchises_product_view_counted) {
            return;
        }
        if (is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax())) {
            return;
        }
        if (! function_exists('is_product') || ! is_product()) {
            return;
        }
        if (function_exists('is_preview') && is_preview()) {
            return;
        }
        if (function_exists('is_customize_preview') && is_customize_preview()) {
            return;
        }
        $post_id = (int) get_queried_object_id();
        if ($post_id <= 0 || get_post_status($post_id) !== 'publish') {
            return;
        }
        $cookie_name = 'fr_viewed_prod_' . $post_id;
        if (! empty($_COOKIE[$cookie_name])) {
            return;
        }
        if (! function_exists('franchises_theme_set_post_views')) {
            return;
        }
        franchises_theme_set_post_views($post_id);
        $franchises_product_view_counted = true;

        if (headers_sent()) {
            return;
        }
        $expire = time() + DAY_IN_SECONDS;
        $path = (defined('COOKIEPATH') && COOKIEPATH) ? COOKIEPATH : '/';
        $domain = (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';
        setcookie($cookie_name, '1', $expire, $path, $domain, is_ssl(), true);
        $_COOKIE[$cookie_name] = '1';
    },
    20
);

// -------------------------------------------------------------------------
// Каталог WooCommerce: верстка catalog.html + GET-фильтры
// -------------------------------------------------------------------------

if (! function_exists('franchises_request_uri_relative_path')) {
    /** Путь запроса без префикса каталога сайта (для подпапок). */
    function franchises_request_uri_relative_path(): string
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = (string) wp_parse_url(strtok($uri, '?') ?: '', PHP_URL_PATH);
        $path = trim($path, '/');
        if ($path === '') {
            return '';
        }
        $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        $home_path = trim($home_path, '/');
        if ($home_path !== '' && ($path === $home_path || str_starts_with($path, $home_path . '/'))) {
            $path = trim(substr($path, strlen($home_path)), '/');
        }

        return $path;
    }
}

if (! function_exists('franchises_shop_page_slug')) {
    function franchises_shop_page_slug(): string
    {
        if (! function_exists('wc_get_page_id')) {
            return '';
        }
        $shop_id = wc_get_page_id('shop');
        if ($shop_id <= 0) {
            return '';
        }
        $shop = get_post($shop_id);
        if (! $shop instanceof WP_Post) {
            return '';
        }

        return (string) $shop->post_name;
    }
}

if (! function_exists('franchises_shop_catalog_url')) {
    function franchises_shop_catalog_url(): string
    {
        if (! function_exists('wc_get_page_id')) {
            return home_url('/');
        }
        $shop_id = wc_get_page_id('shop');
        if ($shop_id <= 0) {
            return home_url('/');
        }
        $permalink = get_permalink($shop_id);

        return is_string($permalink) && $permalink !== '' ? $permalink : home_url('/');
    }
}

if (! function_exists('franchises_resolve_product_cat_term')) {
    function franchises_resolve_product_cat_term(string $value, int $parent_id = 0): ?WP_Term
    {
        if ($value === '' || ! taxonomy_exists('product_cat')) {
            return null;
        }

        $term = get_term_by('name', $value, 'product_cat');
        if (! $term) {
            $term = get_term_by('slug', $value, 'product_cat');
        }
        if (! $term) {
            $term = get_term_by('slug', sanitize_title($value), 'product_cat');
        }
        if (! $term || is_wp_error($term)) {
            return null;
        }
        if ($parent_id > 0 && (int) $term->parent !== $parent_id) {
            return null;
        }

        return $term;
    }
}

if (! function_exists('franchises_product_cat_flat_url')) {
    function franchises_product_cat_flat_url(WP_Term $term): string
    {
        $base = trailingslashit(franchises_shop_catalog_url());

        return $base . $term->slug . '/';
    }
}

if (! function_exists('franchises_product_cat_ancestor_slugs')) {
    /**
     * @return list<string>
     */
    function franchises_product_cat_ancestor_slugs(WP_Term $term): array
    {
        $slugs = [];
        $parent_id = (int) $term->parent;
        while ($parent_id > 0) {
            $parent = get_term($parent_id, 'product_cat');
            if (! $parent instanceof WP_Term || is_wp_error($parent)) {
                break;
            }
            array_unshift($slugs, (string) $parent->slug);
            $parent_id = (int) $parent->parent;
        }

        return $slugs;
    }
}

if (! function_exists('franchises_product_cat_path_matches_term')) {
    /** @param list<string> $path_slugs */
    function franchises_product_cat_path_matches_term(array $path_slugs, WP_Term $term): bool
    {
        if ($path_slugs === []) {
            return true;
        }

        return $path_slugs === franchises_product_cat_ancestor_slugs($term);
    }
}

if (! function_exists('franchises_catalog_filter_query_args')) {
    /**
     * GET-параметры каталога без сферы и категории (они задаются путём URL).
     *
     * @return array<string, string|int>
     */
    function franchises_catalog_filter_query_args(): array
    {
        $args = [];
        if (! empty($_GET['verified'])) {
            $args['verified'] = '1';
        }
        if (isset($_GET['invest_max']) && (int) $_GET['invest_max'] > 0) {
            $args['invest_max'] = (int) $_GET['invest_max'];
        }
        if (isset($_GET['profit_min']) && (int) $_GET['profit_min'] > 0) {
            $args['profit_min'] = (int) $_GET['profit_min'];
        }
        if (isset($_GET['payback_max']) && (int) $_GET['payback_max'] > 0) {
            $args['payback_max'] = (int) $_GET['payback_max'];
        }
        $search_q = function_exists('franchises_get_catalog_search_query') ? franchises_get_catalog_search_query() : '';
        if ($search_q !== '') {
            $args['q'] = $search_q;
        }
        if (isset($_GET['orderby']) && $_GET['orderby'] !== '') {
            $default = function_exists('get_option')
                ? (string) get_option('woocommerce_default_catalog_orderby', 'menu_order')
                : 'menu_order';
            $orderby = sanitize_text_field(wp_unslash((string) $_GET['orderby']));
            if ($orderby !== '' && $orderby !== $default) {
                $args['orderby'] = $orderby;
            }
        }
        if (isset($_GET['paged']) && (int) $_GET['paged'] > 1) {
            $args['paged'] = (int) $_GET['paged'];
        }

        return $args;
    }
}

if (! function_exists('franchises_catalog_url_for_selection')) {
    function franchises_catalog_url_for_selection(string $sphere = '', string $category = ''): string
    {
        $shop_url = franchises_shop_catalog_url();

        if ($category !== '') {
            $parent_id = 0;
            if ($sphere !== '') {
                $parent = franchises_resolve_product_cat_term($sphere);
                $parent_id = $parent ? (int) $parent->term_id : 0;
            }
            $term = franchises_resolve_product_cat_term($category, $parent_id);
            if (! $term && $parent_id > 0) {
                $term = franchises_resolve_product_cat_term($category);
            }
            if ($term instanceof WP_Term) {
                return franchises_product_cat_flat_url($term);
            }
        } elseif ($sphere !== '') {
            $term = franchises_resolve_product_cat_term($sphere);
            if ($term instanceof WP_Term && (int) $term->parent === 0) {
                return franchises_product_cat_flat_url($term);
            }
        }

        return $shop_url;
    }
}

if (! function_exists('franchises_catalog_url_with_filters')) {
    function franchises_catalog_url_with_filters(string $base_url, array $extra_args = []): string
    {
        $args = array_merge(franchises_catalog_filter_query_args(), $extra_args);

        return $args !== [] ? add_query_arg($args, $base_url) : $base_url;
    }
}

if (! function_exists('franchises_normalize_url_path')) {
    function franchises_normalize_url_path(string $url): string
    {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $path = $path !== '' ? $path : '/';

        return trailingslashit($path);
    }
}

if (! function_exists('franchises_fix_shop_prefixed_product_cat_request')) {
    /**
     * WooCommerce отдаёт канонические ссылки вида /{shop}/{родитель}/{категория}/, но правила
     * перезаписи часто не сопоставляют их с product_cat (404). Преобразуем в query_vars архива.
     *
     * @param array<string, string|int> $query_vars
     * @return array<string, string|int>
     */
    function franchises_fix_shop_prefixed_product_cat_request(array $query_vars): array
    {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return $query_vars;
        }
        if (! function_exists('wc_get_page_id') || ! taxonomy_exists('product_cat')) {
            return $query_vars;
        }

        $shop_id = wc_get_page_id('shop');
        if ($shop_id <= 0) {
            return $query_vars;
        }
        $shop = get_post($shop_id);
        if (! $shop instanceof WP_Post || $shop->post_name === '') {
            return $query_vars;
        }
        $shop_slug = (string) $shop->post_name;

        // Сначала реальный путь из URI: для /{shop}/{родитель}/{ребёнок}/ поле pagename часто
        // обрезано или совпадает только с родителем, из‑за чего дочерние категории не распознаются.
        $path = franchises_request_uri_relative_path();
        if ($path === '' && ! empty($query_vars['pagename']) && is_string($query_vars['pagename'])) {
            $path = trim($query_vars['pagename'], '/');
        }
        if ($path === '') {
            return $query_vars;
        }

        $parts = array_values(array_filter(explode('/', $path), 'strlen'));
        while ($parts !== []) {
            $last = (string) $parts[count($parts) - 1];
            if ($last === 'feed' || $last === 'embed' || $last === 'trackback') {
                array_pop($parts);
                continue;
            }
            break;
        }

        if ($parts === [] || $parts[0] !== $shop_slug) {
            return $query_vars;
        }

        $paged = 0;
        $n = count($parts);
        if ($n >= 3 && $parts[$n - 2] === 'page' && ctype_digit((string) $parts[$n - 1])) {
            $paged = (int) $parts[$n - 1];
            array_pop($parts);
            array_pop($parts);
        }

        array_shift($parts);
        if ($parts === []) {
            return $query_vars;
        }

        $leaf_slug = (string) array_pop($parts);
        $ancestors = $parts;

        $term = get_term_by('slug', $leaf_slug, 'product_cat');
        if (! $term instanceof WP_Term || is_wp_error($term)) {
            return $query_vars;
        }

        if (! franchises_product_cat_path_matches_term($ancestors, $term)) {
            return $query_vars;
        }

        foreach (
            [
                'pagename',
                'page_id',
                'page',
                'attachment',
                'attachment_id',
                'name',
                'error',
                'year',
                'monthnum',
                'day',
                'hour',
                'minute',
                'second',
                'category_name',
                'cat',
            ] as $unset
        ) {
            unset($query_vars[$unset]);
        }

        // Не задаём post_type здесь: иначе WP считает запрос архивом типа product,
        // is_shop() в WC становится true, и фильтры в woocommerce_product_query
        // обрабатывают страницу как магазин (ломает пересечение tax_query с дочерней категорией).
        $query_vars['product_cat'] = $leaf_slug;
        if ($paged > 1) {
            $query_vars['paged'] = $paged;
        }

        return $query_vars;
    }
}

add_filter('request', 'franchises_fix_shop_prefixed_product_cat_request', 999);

add_action('pre_get_posts', static function (WP_Query $q): void {
    if (is_admin() || ! $q->is_main_query()) {
        return;
    }
    if ($q->is_tax('product_cat')) {
        $q->set('post_type', 'product');
    }
}, 1);

add_filter('term_link', static function ($termlink, $term, $taxonomy) {
    if ($taxonomy !== 'product_cat' || ! ($term instanceof WP_Term)) {
        return $termlink;
    }
    if (! function_exists('wc_get_page_id') || wc_get_page_id('shop') <= 0) {
        return $termlink;
    }

    return franchises_product_cat_flat_url($term);
}, 10, 3);

add_action('template_redirect', static function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    if (! function_exists('is_product_category') || ! function_exists('is_shop')) {
        return;
    }

    $filter_args = franchises_catalog_filter_query_args();
    $sphere = isset($_GET['sphere']) ? sanitize_text_field(wp_unslash((string) $_GET['sphere'])) : '';
    $category = isset($_GET['category']) ? sanitize_text_field(wp_unslash((string) $_GET['category'])) : '';

    if ($sphere !== '' || $category !== '') {
        if (is_shop() || is_product_category()) {
            $target_base = franchises_catalog_url_for_selection($sphere, $category);
            $target = franchises_catalog_url_with_filters($target_base);
            $current_path = franchises_normalize_url_path(
                isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/'
            );
            $target_path = franchises_normalize_url_path($target);
            if ($current_path !== $target_path || isset($_GET['sphere']) || isset($_GET['category'])) {
                wp_safe_redirect($target, 302);
                exit;
            }
        }
    }

    if (! is_product_category()) {
        return;
    }

    $term = get_queried_object();
    if (! $term instanceof WP_Term || $term->taxonomy !== 'product_cat' || (int) $term->parent <= 0) {
        return;
    }

    $shop_slug = franchises_shop_page_slug();
    if ($shop_slug === '') {
        return;
    }

    $path = franchises_request_uri_relative_path();
    if ($path === '') {
        return;
    }

    $parts = array_values(array_filter(explode('/', $path), 'strlen'));
    if ($parts === [] || $parts[0] !== $shop_slug) {
        return;
    }

    array_shift($parts);
    if (count($parts) < 2 || (string) end($parts) !== $term->slug) {
        return;
    }

    $canonical = franchises_catalog_url_with_filters(franchises_product_cat_flat_url($term));
    $current_path = franchises_normalize_url_path(
        isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/'
    );
    $canonical_path = franchises_normalize_url_path($canonical);
    if ($current_path !== $canonical_path) {
        wp_safe_redirect($canonical, 301);
        exit;
    }
}, 5);

if (! function_exists('franchises_is_selection_catalog_view')) {
    function franchises_is_selection_catalog_view(): bool
    {
        if (function_exists('franchises_get_current_selection_id') && franchises_get_current_selection_id() > 0) {
            return true;
        }

        return is_singular('selection');
    }
}

if (! function_exists('franchises_is_product_catalog_view')) {
    function franchises_is_product_catalog_view(): bool
    {
        if (function_exists('franchises_is_selection_catalog_view') && franchises_is_selection_catalog_view()) {
            return false;
        }

        if (! function_exists('is_shop')) {
            return false;
        }

        return is_shop() || is_product_category();
    }
}

if (! function_exists('franchises_uses_catalog_cards_grid')) {
    /** Каталог и страницы подборок — сетка popular-grid, не ul.products WooCommerce. */
    function franchises_uses_catalog_cards_grid(): bool
    {
        if (function_exists('franchises_is_selection_catalog_view') && franchises_is_selection_catalog_view()) {
            return true;
        }

        return franchises_is_product_catalog_view();
    }
}

if (! function_exists('franchises_lcfirst_utf8')) {
    function franchises_lcfirst_utf8(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        return mb_strtolower(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }
}

if (! function_exists('franchises_ru_months_genitive')) {
    function franchises_ru_months_genitive(int $months): string
    {
        $months = abs($months);
        $mod100 = $months % 100;
        $mod10 = $months % 10;
        if ($mod10 === 1 && $mod100 !== 11) {
            return 'месяца';
        }

        return 'месяцев';
    }
}

if (! function_exists('franchises_catalog_has_advanced_filters')) {
    function franchises_catalog_has_advanced_filters(int $invest_max = 0, int $profit_min = 0): bool
    {
        return $invest_max > 0 || $profit_min > 0;
    }
}

if (! function_exists('franchises_catalog_has_extra_title_filters')) {
    function franchises_catalog_has_extra_title_filters(
        bool $verified = false,
        int $invest_max = 0,
        int $profit_min = 0,
        int $payback_max = 0,
        string $search_q = ''
    ): bool {
        if ($verified) {
            return true;
        }
        if ($invest_max > 0 || $profit_min > 0 || $payback_max > 0) {
            return true;
        }

        return $search_q !== '';
    }
}

if (! function_exists('franchises_catalog_build_hero_title')) {
    function franchises_catalog_build_hero_title(
        string $base_title,
        bool $verified = false,
        int $invest_max = 0,
        int $profit_min = 0,
        int $payback_max = 0,
        string $search_q = ''
    ): string {
        $clauses = [];

        if ($invest_max > 0) {
            $clauses[] = 'с вложениями до ' . franchises_format_money_ru($invest_max);
        }
        if ($profit_min > 0) {
            $clauses[] = 'с прибылью в месяц от ' . franchises_format_money_ru($profit_min);
        }
        if ($payback_max > 0) {
            $clauses[] = sprintf(
                'с окупаемостью до %d %s',
                $payback_max,
                franchises_ru_months_genitive($payback_max)
            );
        }
        if ($search_q !== '') {
            $clauses[] = 'по запросу «' . $search_q . '»';
        }

        if (! franchises_catalog_has_extra_title_filters($verified, $invest_max, $profit_min, $payback_max, $search_q)) {
            return $base_title;
        }

        $title = $base_title;

        if ($verified) {
            if (preg_match('/^Франшиз/ui', $title)) {
                $title = 'Проверенные ' . franchises_lcfirst_utf8($title);
            } elseif (preg_match('/^Каталог/ui', $title)) {
                $title = 'Каталог проверенных франшиз';
            } else {
                $title = 'Проверенные ' . franchises_lcfirst_utf8($title);
            }
        }

        if ($clauses !== []) {
            if (count($clauses) > 1) {
                $last = array_pop($clauses);
                $title .= ' ' . implode(', ', $clauses) . ' и ' . $last;
            } else {
                $title .= ' ' . $clauses[0];
            }
        }

        return $title;
    }
}

if (! function_exists('franchises_catalog_has_active_filters')) {
    function franchises_catalog_has_active_filters(): bool
    {
        if (function_exists('franchises_get_catalog_search_query') && franchises_get_catalog_search_query() !== '') {
            return true;
        }
        if (! empty($_GET['verified'])) {
            return true;
        }
        if (! empty($_GET['sphere'])) {
            return true;
        }
        if (! empty($_GET['category'])) {
            return true;
        }
        if (isset($_GET['invest_max']) && (int) $_GET['invest_max'] > 0) {
            return true;
        }
        if (isset($_GET['profit_min']) && (int) $_GET['profit_min'] > 0) {
            return true;
        }
        if (isset($_GET['payback_max']) && (int) $_GET['payback_max'] > 0) {
            return true;
        }

        if (isset($_GET['orderby']) && $_GET['orderby'] !== '') {
            $default = function_exists('get_option')
                ? (string) get_option('woocommerce_default_catalog_orderby', 'menu_order')
                : 'menu_order';
            $cur = sanitize_text_field(wp_unslash((string) $_GET['orderby']));
            if ($cur !== '' && $cur !== $default) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('franchises_catalog_reset_filters_url')) {
    function franchises_catalog_reset_filters_url(): string
    {
        if (function_exists('franchises_get_current_selection_id')) {
            $selection_id = franchises_get_current_selection_id();
            if ($selection_id > 0) {
                $link = get_permalink($selection_id);
                if (is_string($link) && $link !== '') {
                    return $link;
                }
            }
        }

        if (function_exists('is_product_category') && is_product_category()) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                if (function_exists('franchises_product_cat_flat_url')) {
                    return franchises_product_cat_flat_url($term);
                }
                $link = get_term_link($term);
                if (! is_wp_error($link)) {
                    return (string) $link;
                }
            }
        }

        if (function_exists('wc_get_page_id')) {
            $shop_id = wc_get_page_id('shop');
            if ($shop_id > 0) {
                $permalink = get_permalink($shop_id);
                if (is_string($permalink) && $permalink !== '') {
                    return $permalink;
                }
            }
        }

        return home_url('/');
    }
}

if (! function_exists('franchises_catalog_breadcrumbs')) {
    /**
     * @return list<array{label: string, href: string}>
     */
    function franchises_catalog_breadcrumbs(): array
    {
        $trail = [];
        $trail[] = ['label' => 'Главная', 'href' => home_url('/')];
        $shop_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
        $shop_url = $shop_id > 0 ? (string) get_permalink($shop_id) : '';

        if (is_shop()) {
            $trail[] = ['label' => 'Каталог франшиз', 'href' => ''];

            return $trail;
        }

        if ($shop_url !== '') {
            $trail[] = ['label' => 'Каталог франшиз', 'href' => $shop_url];
        }

        if (is_product_category()) {
            $t = get_queried_object();
            if ($t instanceof WP_Term && $t->taxonomy === 'product_cat') {
                if ((int) $t->parent > 0) {
                    $parent = get_term((int) $t->parent, 'product_cat');
                    if ($parent && ! is_wp_error($parent)) {
                        $link = get_term_link($parent);
                        $trail[] = [
                            'label' => (string) $parent->name,
                            'href'  => is_wp_error($link) ? '' : (string) $link,
                        ];
                    }
                }
                $trail[] = ['label' => (string) $t->name, 'href' => ''];
            }

            return $trail;
        }

        return $trail;
    }
}

if (! function_exists('franchises_wc_catalog_orderby_choices')) {
    /** @return array<string, string> */
    function franchises_wc_catalog_orderby_choices(): array
    {
        return [
            'menu_order' => 'По умолчанию',
            'popularity' => 'По популярности',
            'rating'     => 'По рейтингу',
            'date'       => 'Сначала новые',
            'title'      => 'По алфавиту',
            'price'      => 'По инвестициям: сначала дешевле',
            'price-desc' => 'По инвестициям: сначала дороже',
        ];
    }
}

add_filter('woocommerce_product_loop_start', static function (string $html): string {
    if (! franchises_uses_catalog_cards_grid()) {
        return $html;
    }

    return '<div class="popular-grid catalog-cards">';
}, 50);

add_filter('woocommerce_product_loop_end', static function (string $html): string {
    if (! franchises_uses_catalog_cards_grid()) {
        return $html;
    }

    return '</div>';
}, 50);

add_action('woocommerce_product_query', static function ($q): void {
    if (is_admin() || ! $q->is_main_query()) {
        return;
    }
    if (function_exists('franchises_is_selection_catalog_view') && franchises_is_selection_catalog_view()) {
        return;
    }
    if (! function_exists('is_shop') || (! is_shop() && ! is_product_taxonomy())) {
        return;
    }

    $search_q = function_exists('franchises_get_catalog_search_query')
        ? franchises_get_catalog_search_query()
        : (isset($_GET['q']) ? sanitize_text_field(wp_unslash((string) $_GET['q'])) : '');
    if ($search_q !== '') {
        $q->set('s', $search_q);
    }

    $extra_tax = [];

    // На странице магазина (is_shop() && tax) GET-фильтры по категориям; на архиве категории WC уже задаёт tax_query.
    if (is_shop()) {
        $category = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';
        $sphere = isset($_GET['sphere']) ? sanitize_text_field(wp_unslash($_GET['sphere'])) : '';

        if ($category !== '') {
            $parent_id = 0;
            if ($sphere !== '') {
                $parent = franchises_resolve_product_cat_term($sphere);
                $parent_id = $parent ? (int) $parent->term_id : 0;
            }
            $term = franchises_resolve_product_cat_term($category, $parent_id);
            if (! $term && $parent_id > 0) {
                $term = franchises_resolve_product_cat_term($category);
            }
            if ($term instanceof WP_Term) {
                $extra_tax[] = [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => [(int) $term->term_id],
                    'include_children' => false,
                ];
            }
        } elseif ($sphere !== '') {
            $term = franchises_resolve_product_cat_term($sphere);
            if ($term instanceof WP_Term) {
                $extra_tax[] = [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => [(int) $term->term_id],
                    'include_children' => true,
                ];
            }
        }
    }

    if ($extra_tax !== []) {
        $tax_query = $q->get('tax_query');
        $clauses = [];
        if (is_array($tax_query)) {
            foreach ($tax_query as $k => $v) {
                if ($k === 'relation' || ! is_array($v)) {
                    continue;
                }
                $clauses[] = $v;
            }
        }
        foreach ($extra_tax as $piece) {
            $clauses[] = $piece;
        }
        if (count($clauses) > 1) {
            $q->set('tax_query', array_merge(['relation' => 'AND'], $clauses));
        } elseif (count($clauses) === 1) {
            $q->set('tax_query', $clauses);
        }
    }

    $meta_query = $q->get('meta_query');
    if (! is_array($meta_query)) {
        $meta_query = [];
    }
    $meta_parts = [];
    foreach ($meta_query as $k => $v) {
        if ($k === 'relation' || ! is_array($v)) {
            continue;
        }
        $meta_parts[] = $v;
    }

    if (! empty($_GET['verified'])) {
        $meta_parts[] = [
            'key'     => 'verified',
            'value'   => '1',
            'compare' => '=',
        ];
    }

    $invest_max = isset($_GET['invest_max']) ? (int) $_GET['invest_max'] : 0;
    if ($invest_max > 0) {
        $meta_parts[] = [
            'key'     => 'pausal',
            'value'   => $invest_max,
            'compare' => '<=',
            'type'    => 'NUMERIC',
        ];
    }

    $profit_min = isset($_GET['profit_min']) ? (int) $_GET['profit_min'] : 0;
    if ($profit_min > 0) {
        $meta_parts[] = [
            'relation' => 'OR',
            [
                'key'     => 'monthly_profit_min',
                'value'   => $profit_min,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => 'monthly_profit_max',
                'value'   => $profit_min,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ];
    }

    $payback_max = isset($_GET['payback_max']) ? (int) $_GET['payback_max'] : 0;
    if ($payback_max > 0) {
        $meta_parts[] = [
            'relation' => 'OR',
            [
                'key'     => 'payback_max',
                'value'   => $payback_max,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ],
            [
                'key'     => 'payback_min',
                'value'   => $payback_max,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            ],
        ];
    }

    if ($meta_parts !== []) {
        $q->set('meta_query', array_merge(['relation' => 'AND'], $meta_parts));
    }
}, 40);
