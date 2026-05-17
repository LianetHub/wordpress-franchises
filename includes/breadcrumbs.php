<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_normalize_breadcrumb_items')) {
    /**
     * @param  list<array{label?: string, href?: string, name?: string, link?: string}>  $items
     * @return list<array{label: string, href: string}>
     */
    function franchises_normalize_breadcrumb_items(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = isset($item['label']) ? (string) $item['label'] : (isset($item['name']) ? (string) $item['name'] : '');
            $href = isset($item['href']) ? (string) $item['href'] : (isset($item['link']) ? (string) $item['link'] : '');

            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'href'  => $href,
            ];
        }

        return $normalized;
    }
}

if (! function_exists('franchises_breadcrumb_current_url')) {
    function franchises_breadcrumb_current_url(): string
    {
        if (is_singular()) {
            $permalink = get_permalink();
            if (is_string($permalink) && $permalink !== '') {
                return $permalink;
            }
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';

        return home_url($request_uri);
    }
}

if (! function_exists('franchises_get_breadcrumb_items')) {
    /**
     * @return list<array{label: string, href: string}>
     */
    function franchises_get_breadcrumb_items(): array
    {
        if (function_exists('franchises_is_product_catalog_view') && franchises_is_product_catalog_view()) {
            return franchises_catalog_breadcrumbs();
        }

        if (is_singular('product') && function_exists('franchises_product_breadcrumb_trail')) {
            return franchises_product_breadcrumb_trail((int) get_the_ID());
        }

        $queried_object = get_queried_object();
        $items = [];
        $items[] = ['label' => 'Главная', 'href' => home_url('/')];

        if (is_post_type_archive('services') || is_tax('service_cat') || is_singular('services')) {
            $items[] = ['label' => 'Услуги', 'href' => (string) get_post_type_archive_link('services')];

            if (is_tax('service_cat') && $queried_object instanceof WP_Term) {
                $ancestors = get_ancestors($queried_object->term_id, 'service_cat');
                foreach (array_reverse($ancestors) as $ancestor_id) {
                    $ancestor = get_term($ancestor_id);
                    if ($ancestor && ! is_wp_error($ancestor)) {
                        $link = get_term_link($ancestor);
                        $items[] = [
                            'label' => (string) $ancestor->name,
                            'href'  => is_wp_error($link) ? '' : (string) $link,
                        ];
                    }
                }
                $items[] = ['label' => (string) $queried_object->name, 'href' => ''];
            } elseif (is_singular('services')) {
                $terms = get_the_terms(get_the_ID(), 'service_cat');
                if ($terms && ! is_wp_error($terms)) {
                    $main_term = $terms[0];
                    foreach ($terms as $term) {
                        if ((int) $term->parent !== 0) {
                            $main_term = $term;
                            break;
                        }
                    }

                    $ancestors = get_ancestors($main_term->term_id, 'service_cat');
                    foreach (array_reverse($ancestors) as $ancestor_id) {
                        $ancestor = get_term($ancestor_id);
                        if ($ancestor && ! is_wp_error($ancestor)) {
                            $link = get_term_link($ancestor);
                            $items[] = [
                                'label' => (string) $ancestor->name,
                                'href'  => is_wp_error($link) ? '' : (string) $link,
                            ];
                        }
                    }
                    $term_link = get_term_link($main_term);
                    $items[] = [
                        'label' => (string) $main_term->name,
                        'href'  => is_wp_error($term_link) ? '' : (string) $term_link,
                    ];
                }
                $items[] = ['label' => get_the_title(), 'href' => ''];
            } elseif (is_post_type_archive('services')) {
                $items[count($items) - 1]['href'] = '';
            }
        } elseif (is_post_type_archive('portfolio') || is_singular('portfolio')) {
            $items[] = ['label' => 'Портфолио', 'href' => (string) get_post_type_archive_link('portfolio')];

            if (is_singular('portfolio')) {
                $items[] = ['label' => get_the_title(), 'href' => ''];
            } else {
                $items[count($items) - 1]['href'] = '';
            }
        } elseif (is_home() || is_singular('post')) {
            $blog_page_id = (int) get_option('page_for_posts');
            $blog_name = $blog_page_id > 0 ? get_the_title($blog_page_id) : 'Блог';
            $blog_link = $blog_page_id > 0 ? (string) get_permalink($blog_page_id) : home_url('/blog');

            if (is_home()) {
                $items[] = ['label' => (string) $blog_name, 'href' => ''];
            } else {
                $items[] = ['label' => (string) $blog_name, 'href' => $blog_link];
                $items[] = ['label' => get_the_title(), 'href' => ''];
            }
        } elseif (is_post_type_archive('certificates')) {
            $items[] = ['label' => 'Документы', 'href' => ''];
        } elseif (is_page()) {
            $ancestors = get_post_ancestors(get_the_ID());
            if ($ancestors) {
                foreach (array_reverse($ancestors) as $ancestor_id) {
                    $items[] = [
                        'label' => get_the_title($ancestor_id),
                        'href'  => (string) get_permalink($ancestor_id),
                    ];
                }
            }
            $items[] = ['label' => get_the_title(), 'href' => ''];
        } elseif (is_404()) {
            $items[] = ['label' => 'Страница не найдена', 'href' => ''];
        } else {
            $items[] = ['label' => get_the_title(), 'href' => ''];
        }

        return apply_filters('franchises_breadcrumb_items', franchises_normalize_breadcrumb_items($items));
    }
}

if (! function_exists('franchises_render_breadcrumbs')) {
    /**
     * @param  list<array{label?: string, href?: string, name?: string, link?: string}>  $items
     * @param  array{with_container?: bool, inline?: bool, aria_label?: string}  $args
     */
    function franchises_render_breadcrumbs(array $items = [], array $args = []): void
    {
        $args = wp_parse_args($args, [
            'with_container' => true,
            'inline'         => false,
            'aria_label'     => 'Хлебные крошки',
        ]);

        if ($items === []) {
            $items = franchises_get_breadcrumb_items();
        } else {
            $items = franchises_normalize_breadcrumb_items($items);
        }

        $items = apply_filters('franchises_breadcrumb_items', $items);

        if ($items === []) {
            return;
        }

        $nav_class = 'breadcrumbs';
        if (! empty($args['inline'])) {
            $nav_class .= ' breadcrumbs--inline';
        }

        $breadcrumb_items = $items;
        $breadcrumb_nav_class = $nav_class;
        $breadcrumb_aria_label = (string) $args['aria_label'];
        $breadcrumb_with_container = (bool) $args['with_container'];
        $breadcrumb_current_url = franchises_breadcrumb_current_url();

        require get_theme_file_path('templates/components/breadcrumbs-view.php');
    }
}
