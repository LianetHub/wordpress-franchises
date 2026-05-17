<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_header_shop_url')) {
    function franchises_header_shop_url(): string
    {
        if (! function_exists('wc_get_page_id')) {
            return home_url('/');
        }
        $url = get_permalink(wc_get_page_id('shop'));
        return is_string($url) && $url !== '' ? $url : home_url('/');
    }
}

if (! function_exists('franchises_get_contacts_page_id')) {
    function franchises_get_contacts_page_id(): int
    {
        static $page_id = null;

        if ($page_id !== null) {
            return $page_id;
        }

        $pages = get_pages([
            'meta_key'   => '_wp_page_template',
            'meta_value' => 'page-contacts.php',
            'number'     => 1,
        ]);

        $page_id = ($pages && $pages[0] instanceof WP_Post) ? (int) $pages[0]->ID : 0;

        return $page_id;
    }
}

if (! function_exists('franchises_contacts_page_url')) {
    function franchises_contacts_page_url(): string
    {
        $page_id = franchises_get_contacts_page_id();
        if ($page_id > 0) {
            $url = get_permalink($page_id);
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return home_url('/#contacts');
    }
}

if (! function_exists('franchises_get_theme_option')) {
    /**
     * @param mixed $default
     * @return mixed
     */
    function franchises_get_theme_option(string $field, $default = '')
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field($field, 'option');

        if ($value === null || $value === false || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (! function_exists('franchises_get_map_placemark_url')) {
    function franchises_get_map_placemark_url(): string
    {
        $icon = franchises_get_theme_option('map_placemark');

        if (is_array($icon) && ! empty($icon['url'])) {
            return (string) $icon['url'];
        }

        if (is_string($icon) && $icon !== '') {
            return $icon;
        }

        return get_template_directory_uri() . '/assets/img/icons/location.svg';
    }
}

if (! function_exists('franchises_get_map_coords')) {
    /**
     * @return array{0: float, 1: float}|null
     */
    function franchises_get_map_coords(): ?array
    {
        $raw = (string) franchises_get_theme_option('map_coords', '55.8528135688981, 48.842075499999964');
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $raw));
        if (count($parts) < 2) {
            return null;
        }

        $lat = (float) $parts[0];
        $lng = (float) $parts[1];

        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        return [$lat, $lng];
    }
}

if (! function_exists('franchises_header_contacts_url')) {
    function franchises_header_contacts_url(): string
    {
        return home_url('/#contacts');
    }
}

if (! function_exists('franchises_product_cat_term_url')) {
    function franchises_product_cat_term_url(WP_Term $term): string
    {
        $link = get_term_link($term);
        return is_wp_error($link) ? '' : (string) $link;
    }
}

if (! function_exists('franchises_header_sphere_icon_svg')) {
    /**
     * @return string Raw SVG markup (safe, static).
     */
    function franchises_header_sphere_icon_svg(string $sphere_name): string
    {
        $icons = [
            'Торговля'           => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 5h8l-1 6H4z"/><path d="M4 5l1-2h4l1 2" fill="none"/></svg>',
            'Еда'                => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M5 2v4M7 2v4M9 2v4"/><path d="M4 6h6v6H4z" fill="none"/></svg>',
            'Авто'               => '<svg viewBox="0 0 14 14" aria-hidden="true"><rect x="2.5" y="5" width="9" height="4.5" rx="1"/><circle cx="4.5" cy="10.5" r="1"/><circle cx="9.5" cy="10.5" r="1"/></svg>',
            'Обучение'           => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M2 5l5-2 5 2-5 2z"/><path d="M4 6.2V9c0 .9 1.6 1.8 3 1.8S10 9.9 10 9V6.2" fill="none"/></svg>',
            'Красота и здоровье' => '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M7 2l1.2 2.2L10.5 5 8.2 6.2 7 8.5 5.8 6.2 3.5 5l2.3-.8z"/></svg>',
        ];
        $fallback = '<svg viewBox="0 0 14 14" aria-hidden="true"><path d="M3 10l4-8 4 8z"/><path d="M5 7h4" fill="none"/></svg>';

        return $icons[$sphere_name] ?? $fallback;
    }
}

if (! function_exists('franchises_header_get_product_cat_spheres')) {
    /**
     * Parent product_cat terms (сферы) with child categories.
     *
     * @return list<array{name: string, url: string, landing_url: string, children: list<array{name: string, url: string}>}>
     */
    function franchises_header_get_product_cat_spheres(): array
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
                'name'        => (string) $parent->name,
                'url'         => $parent_url !== '' ? $parent_url : $landing_url,
                'landing_url' => $landing_url,
                'children'    => $children,
            ];
        }

        return $result;
    }
}

if (! function_exists('franchises_header_get_collections')) {
    /**
     * @return list<array{id: int, title: string, url: string, active: bool}>
     */
    function franchises_header_get_collections(): array
    {
        if (! post_type_exists('selection')) {
            return [];
        }

        $posts = get_posts([
            'post_type'      => 'selection',
            'post_status'    => 'publish',
            'posts_per_page' => 40,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ]);

        $items = [];
        foreach ($posts as $post) {
            if (! $post instanceof WP_Post) {
                continue;
            }
            $link = get_permalink($post);
            if (! is_string($link) || $link === '') {
                continue;
            }
            $items[] = [
                'id'     => (int) $post->ID,
                'title'  => get_the_title($post),
                'url'    => $link,
                'active' => is_singular('selection') && (int) get_queried_object_id() === (int) $post->ID,
            ];
        }

        return $items;
    }
}

if (! function_exists('franchises_header_is_shop_active')) {
    function franchises_header_is_shop_active(): bool
    {
        return function_exists('is_shop') && is_shop() && ! is_product_category();
    }
}
