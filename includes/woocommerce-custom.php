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
     * Цепочка для верстки .page-head .breadcrumbs (как в макете каталога).
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
    /** Как на главной: по тегу «Популярные франшизы», иначе последние товары. */
    function franchises_product_popular_query(int $exclude_id, int $limit = 12): WP_Query
    {
        $popular_tag_term = get_term_by('name', 'Популярные франшизы', 'product_tag');
        if (! $popular_tag_term || is_wp_error($popular_tag_term)) {
            $popular_tag_term = get_term_by('slug', 'popularnye-franshizy', 'product_tag');
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
        if ($popular_tag_term && ! is_wp_error($popular_tag_term)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_tag',
                    'field'    => 'term_id',
                    'terms'    => [(int) $popular_tag_term->term_id],
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
