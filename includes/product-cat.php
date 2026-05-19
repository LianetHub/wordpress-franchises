<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_product_cat_icon_field_name')) {
    function franchises_product_cat_icon_field_name(): string
    {
        return 'category_icon';
    }
}

if (! function_exists('franchises_product_cat_term_url')) {
    function franchises_product_cat_term_url(WP_Term $term): string
    {
        if (function_exists('franchises_product_cat_flat_url')) {
            return franchises_product_cat_flat_url($term);
        }

        $link = get_term_link($term);

        return is_wp_error($link) ? '' : (string) $link;
    }
}

if (! function_exists('franchises_product_cat_default_icon_svgs')) {
    /**
     * @return array<string, string>
     */
    function franchises_product_cat_default_icon_svgs(): array
    {
        return [
            'Торговля'           => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 5h8l-1 6H4z"/><path d="M4 5l1-2h4l1 2" fill="none"/></svg>',
            'Еда'                => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M5 2v4M7 2v4M9 2v4"/><path d="M4 6h6v6H4z" fill="none"/></svg>',
            'Авто'               => '<svg viewBox="0 0 14 14" aria-hidden="true"><rect x="2.5" y="5" width="9" height="4.5" rx="1"/><circle cx="4.5" cy="10.5" r="1"/><circle cx="9.5" cy="10.5" r="1"/></svg>',
            'Обучение'           => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2 5l5-2 5 2-5 2z"/><path d="M4 6.2V9c0 .9 1.6 1.8 3 1.8S10 9.9 10 9V6.2" fill="none"/></svg>',
            'Красота и здоровье' => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2l1.2 2.2L10.5 5 8.2 6.2 7 8.5 5.8 6.2 3.5 5l2.3-.8z"/></svg>',
        ];
    }
}

if (! function_exists('franchises_product_cat_default_icon_svg')) {
    /**
     * @return string Raw SVG markup (safe, static).
     */
    function franchises_product_cat_default_icon_svg(string $sphere_name): string
    {
        $icons = franchises_product_cat_default_icon_svgs();
        $fallback = '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 10l4-8 4 8z"/><path d="M5 7h4" fill="none"/></svg>';

        return $icons[$sphere_name] ?? $fallback;
    }
}

if (! function_exists('franchises_product_cat_normalize_icon_attachment_id')) {
    /**
     * @param mixed $value
     */
    function franchises_product_cat_normalize_icon_attachment_id($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_array($value)) {
            if (! empty($value['ID'])) {
                return (int) $value['ID'];
            }
            if (! empty($value['id'])) {
                return (int) $value['id'];
            }
        }

        return 0;
    }
}

if (! function_exists('franchises_product_cat_get_wc_thumbnail_id')) {
    /** Миниатюра категории WooCommerce (поле «Миниатюра» в админке). */
    function franchises_product_cat_get_wc_thumbnail_id(int $term_id): int
    {
        if ($term_id <= 0) {
            return 0;
        }

        return franchises_product_cat_normalize_icon_attachment_id(
            get_term_meta($term_id, 'thumbnail_id', true)
        );
    }
}

if (! function_exists('franchises_product_cat_get_icon_attachment_id')) {
    /**
     * 1) ACF category_icon, 2) миниатюра WooCommerce (thumbnail_id), 3) 0.
     */
    function franchises_product_cat_get_icon_attachment_id(int $term_id): int
    {
        if ($term_id <= 0) {
            return 0;
        }

        $field = franchises_product_cat_icon_field_name();

        $meta_id = franchises_product_cat_normalize_icon_attachment_id(
            get_term_meta($term_id, $field, true)
        );
        if ($meta_id > 0) {
            return $meta_id;
        }

        if (function_exists('get_field')) {
            $term = get_term($term_id, 'product_cat');
            $contexts = [
                'product_cat_' . $term_id,
                'term_' . $term_id,
            ];

            if ($term instanceof WP_Term && ! is_wp_error($term)) {
                $contexts[] = $term;
            }

            foreach ($contexts as $context) {
                $value = get_field($field, $context);
                if ($value === null || $value === false || $value === '') {
                    continue;
                }

                $attachment_id = franchises_product_cat_normalize_icon_attachment_id($value);
                if ($attachment_id > 0) {
                    return $attachment_id;
                }

                if (is_array($value) && ! empty($value['url'])) {
                    return 0;
                }

                if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    return 0;
                }
            }
        }

        $wc_thumbnail_id = franchises_product_cat_get_wc_thumbnail_id($term_id);
        if ($wc_thumbnail_id > 0) {
            return $wc_thumbnail_id;
        }

        return 0;
    }
}

if (! function_exists('franchises_product_cat_get_icon_image')) {
    /**
     * @return array{url: string, alt: string, width: int, height: int}|null
     */
    function franchises_product_cat_get_icon_image(int $term_id): ?array
    {
        if ($term_id <= 0) {
            return null;
        }

        $field = franchises_product_cat_icon_field_name();
        $attachment_id = franchises_product_cat_get_icon_attachment_id($term_id);

        if ($attachment_id > 0) {
            $url = wp_get_attachment_image_url($attachment_id, 'full');
            if (! is_string($url) || $url === '') {
                return null;
            }

            $alt = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            $meta = wp_get_attachment_metadata($attachment_id);

            return [
                'url'    => $url,
                'alt'    => $alt,
                'width'  => (int) ($meta['width'] ?? 28),
                'height' => (int) ($meta['height'] ?? 28),
            ];
        }

        if (! function_exists('get_field')) {
            return null;
        }

        $term = get_term($term_id, 'product_cat');
        $contexts = ['product_cat_' . $term_id, 'term_' . $term_id];
        if ($term instanceof WP_Term && ! is_wp_error($term)) {
            $contexts[] = $term;
        }

        foreach ($contexts as $context) {
            $value = get_field($field, $context);

            if (is_array($value) && ! empty($value['url'])) {
                return [
                    'url'    => (string) $value['url'],
                    'alt'    => isset($value['alt']) ? (string) $value['alt'] : '',
                    'width'  => (int) ($value['width'] ?? 28),
                    'height' => (int) ($value['height'] ?? 28),
                ];
            }

            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                return [
                    'url'    => $value,
                    'alt'    => '',
                    'width'  => 28,
                    'height' => 28,
                ];
            }
        }

        return null;
    }
}

if (! function_exists('franchises_product_cat_icon_html')) {
    /**
     * ACF-иконка категории или встроенный SVG по названию.
     *
     * @return string Raw HTML (img escaped; SVG static).
     */
    function franchises_product_cat_icon_html(int $term_id, string $sphere_name = ''): string
    {
        $image = franchises_product_cat_get_icon_image($term_id);

        if ($image !== null) {
            $alt = $image['alt'] !== '' ? $image['alt'] : $sphere_name;

            return sprintf(
                '<img src="%s" alt="%s" width="%d" height="%d" loading="lazy" decoding="async">',
                esc_url($image['url']),
                esc_attr($alt),
                $image['width'],
                $image['height']
            );
        }

        $name = $sphere_name;
        if ($name === '' && $term_id > 0) {
            $term = get_term($term_id, 'product_cat');
            if ($term instanceof WP_Term && ! is_wp_error($term)) {
                $name = (string) $term->name;
            }
        }

        return franchises_product_cat_default_icon_svg($name);
    }
}

if (! function_exists('franchises_product_cat_get_spheres')) {
    /**
     * Parent product_cat terms (сферы) with child categories.
     *
     * @return list<array{term_id: int, name: string, url: string, landing_url: string, children: list<array{name: string, url: string}>}>
     */
    function franchises_product_cat_get_spheres(): array
    {
        if (! taxonomy_exists('product_cat')) {
            return [];
        }

        $parents = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'parent'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);
        if (is_wp_error($parents) || ! $parents) {
            return [];
        }

        $result = [];
        foreach ($parents as $parent) {
            if (! $parent instanceof WP_Term) {
                continue;
            }

            $children_terms = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'parent'     => (int) $parent->term_id,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);
            if (is_wp_error($children_terms)) {
                $children_terms = [];
            }

            $children = [];
            foreach ($children_terms as $child) {
                if (! $child instanceof WP_Term) {
                    continue;
                }
                $child_url = franchises_product_cat_term_url($child);
                if ($child_url === '') {
                    continue;
                }
                $children[] = [
                    'name' => (string) $child->name,
                    'url'  => $child_url,
                ];
            }

            $parent_url = franchises_product_cat_term_url($parent);
            $landing_url = $parent_url;
            if ($children) {
                $landing_url = $children[0]['url'];
            }

            if ($parent_url === '' && $landing_url === '') {
                continue;
            }

            $result[] = [
                'term_id'     => (int) $parent->term_id,
                'name'        => (string) $parent->name,
                'url'         => $parent_url !== '' ? $parent_url : $landing_url,
                'landing_url' => $landing_url,
                'children'    => $children,
            ];
        }

        return $result;
    }
}

/** @deprecated Use franchises_product_cat_default_icon_svg() */
if (! function_exists('franchises_header_sphere_icon_svg')) {
    function franchises_header_sphere_icon_svg(string $sphere_name): string
    {
        return franchises_product_cat_default_icon_svg($sphere_name);
    }
}

/** @deprecated Use franchises_product_cat_get_spheres() */
if (! function_exists('franchises_header_get_product_cat_spheres')) {
    function franchises_header_get_product_cat_spheres(): array
    {
        return franchises_product_cat_get_spheres();
    }
}

if (! function_exists('franchises_theme_term_views_meta_key')) {
    function franchises_theme_term_views_meta_key(): string
    {
        return 'franchises_theme_term_views';
    }
}

if (! function_exists('franchises_theme_get_term_views')) {
    function franchises_theme_get_term_views(int $term_id): int
    {
        if ($term_id <= 0) {
            return 0;
        }
        $count = get_term_meta($term_id, franchises_theme_term_views_meta_key(), true);

        return ($count === '' || $count === null) ? 0 : (int) $count;
    }
}

if (! function_exists('franchises_theme_set_term_views')) {
    function franchises_theme_set_term_views(int $term_id): void
    {
        if ($term_id <= 0) {
            return;
        }
        $key = franchises_theme_term_views_meta_key();
        $count = franchises_theme_get_term_views($term_id);
        update_term_meta($term_id, $key, $count + 1);
    }
}

if (! function_exists('franchises_product_cat_is_trackable_category')) {
    /**
     * Учитываем просмотры дочерних категорий (сфера — parent=0).
     */
    function franchises_product_cat_is_trackable_category(WP_Term $term): bool
    {
        if ($term->taxonomy !== 'product_cat' || (int) $term->count <= 0) {
            return false;
        }

        return (int) $term->parent > 0;
    }
}

if (! function_exists('franchises_get_top_viewed_product_cat_terms')) {
    /**
     * Самые просматриваемые дочерние категории product_cat.
     *
     * @return list<WP_Term>
     */
    function franchises_get_top_viewed_product_cat_terms(int $limit = 5): array
    {
        $limit = max(1, $limit);
        if (! taxonomy_exists('product_cat')) {
            return [];
        }

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'parent'     => 0,
            'number'     => 0,
        ]);
        if (is_wp_error($terms) || ! $terms) {
            return [];
        }

        $child_terms = [];
        foreach ($terms as $parent) {
            if (! $parent instanceof WP_Term) {
                continue;
            }
            $children = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
                'parent'     => (int) $parent->term_id,
                'number'     => 0,
            ]);
            if (is_wp_error($children) || ! $children) {
                continue;
            }
            foreach ($children as $child) {
                if ($child instanceof WP_Term && franchises_product_cat_is_trackable_category($child)) {
                    $child_terms[] = $child;
                }
            }
        }

        if ($child_terms === []) {
            return [];
        }

        usort($child_terms, static function (WP_Term $a, WP_Term $b): int {
            $views_a = franchises_theme_get_term_views((int) $a->term_id);
            $views_b = franchises_theme_get_term_views((int) $b->term_id);
            if ($views_a !== $views_b) {
                return $views_b <=> $views_a;
            }

            return (int) $b->count <=> (int) $a->count;
        });

        return array_slice($child_terms, 0, $limit);
    }
}

if (! function_exists('franchises_get_top_viewed_product_cat_ids')) {
    /**
     * @return list<int>
     */
    function franchises_get_top_viewed_product_cat_ids(int $limit = 5): array
    {
        $ids = [];
        foreach (franchises_get_top_viewed_product_cat_terms($limit) as $term) {
            $ids[] = (int) $term->term_id;
        }

        return $ids;
    }
}

if (! function_exists('franchises_products_from_category_terms')) {
    /**
     * @param list<int> $term_ids
     * @return list<int>
     */
    function franchises_products_from_category_terms(array $term_ids, int $max = 12): array
    {
        $term_ids = array_values(array_filter(array_map('intval', $term_ids)));
        if ($term_ids === [] || $max <= 0) {
            return [];
        }

        $query = new WP_Query([
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => max(1, $max),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'fields'              => 'ids',
            'tax_query'           => [
                [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $term_ids,
                    'include_children' => true,
                ],
            ],
            'meta_key'            => 'franchises_theme_post_views',
            'orderby'             => 'meta_value_num',
            'order'               => 'DESC',
        ]);

        return array_values(array_map('intval', $query->posts));
    }
}

if (! function_exists('franchises_products_from_viewed_categories')) {
    /**
     * Франшизы из самых просматриваемых категорий каталога.
     *
     * @return list<int>
     */
    function franchises_products_from_viewed_categories(int $max = 12): array
    {
        $limit = max(1, (int) apply_filters('franchises_popular_categories_limit', 5));
        $term_ids = franchises_get_top_viewed_product_cat_ids($limit);
        if ($term_ids === []) {
            return [];
        }

        return franchises_products_from_category_terms($term_ids, $max);
    }
}

add_action(
    'template_redirect',
    static function (): void {
        static $counted = false;
        if ($counted || is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax())) {
            return;
        }
        if (! function_exists('is_product_category') || ! is_product_category()) {
            return;
        }
        $term = get_queried_object();
        if (! $term instanceof WP_Term || ! franchises_product_cat_is_trackable_category($term)) {
            return;
        }

        $term_id = (int) $term->term_id;
        $cookie_name = 'fr_viewed_cat_' . $term_id;
        if (! empty($_COOKIE[$cookie_name])) {
            return;
        }

        franchises_theme_set_term_views($term_id);
        $counted = true;

        if (headers_sent()) {
            return;
        }
        $expire = time() + DAY_IN_SECONDS;
        $path = (defined('COOKIEPATH') && COOKIEPATH) ? COOKIEPATH : '/';
        $domain = (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) ? COOKIE_DOMAIN : '';
        setcookie($cookie_name, '1', $expire, $path, $domain, is_ssl(), true);
        $_COOKIE[$cookie_name] = '1';
    },
    21
);
