<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_home_popular_limit')) {
    function franchises_home_popular_limit(): int
    {
        return max(1, (int) apply_filters('franchises_home_popular_limit', 12));
    }
}

if (! function_exists('franchises_popular_product_cat_term')) {
    /**
     * Категория WooCommerce «Популярные франшизы» (ручная витрина).
     */
    function franchises_popular_product_cat_term(): ?WP_Term
    {
        if (! taxonomy_exists('product_cat')) {
            return null;
        }

        $term = get_term_by('name', 'Популярные франшизы', 'product_cat');
        if (! $term || is_wp_error($term)) {
            $term = get_term_by('slug', 'popularnye-franshizy', 'product_cat');
        }

        return ($term instanceof WP_Term && ! is_wp_error($term)) ? $term : null;
    }
}

if (! function_exists('franchises_popular_selection_post_id')) {
    /**
     * Опубликованная подборка с типом «Популярные» (по ACF или slug).
     */
    function franchises_popular_selection_post_id(): int
    {
        static $cached = null;
        if ($cached !== null) {
            return (int) $cached;
        }

        $cached = 0;
        if (! function_exists('franchises_get_selection_posts') || ! function_exists('franchises_selection_filter_type')) {
            return 0;
        }

        foreach (franchises_get_selection_posts(40) as $post) {
            if (! $post instanceof WP_Post) {
                continue;
            }
            $id = (int) $post->ID;
            if ($id <= 0) {
                continue;
            }
            if (franchises_selection_filter_type($id) === 'popular') {
                $cached = $id;

                return $id;
            }
        }

        return 0;
    }
}

if (! function_exists('franchises_popular_franchise_product_ids')) {
    /**
     * ID франшиз для блока «Популярные» на главной и связанных виджетов.
     *
     * @return list<int>
     */
    function franchises_popular_franchise_product_ids(?int $limit = null): array
    {
        $limit = $limit ?? franchises_home_popular_limit();

        $selection_id = franchises_popular_selection_post_id();
        if ($selection_id > 0 && function_exists('franchises_selection_product_ids')) {
            $from_selection = franchises_selection_product_ids($selection_id, $limit);
            if ($from_selection !== []) {
                return $from_selection;
            }
        }

        if (function_exists('franchises_get_top_viewed_product_ids')) {
            $from_views = franchises_get_top_viewed_product_ids($limit);
            if ($from_views !== []) {
                return $from_views;
            }
        }

        $legacy_term = franchises_popular_product_cat_term();
        if ($legacy_term instanceof WP_Term && function_exists('franchises_products_from_category_terms')) {
            $from_legacy = franchises_products_from_category_terms([(int) $legacy_term->term_id], $limit);
            if ($from_legacy !== []) {
                return $from_legacy;
            }
        }

        $query = new WP_Query([
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => max(1, $limit),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
            'orderby'             => [
                'menu_order' => 'ASC',
                'date'       => 'DESC',
            ],
            'fields'              => 'ids',
        ]);

        return array_values(array_map('intval', $query->posts));
    }
}

if (! function_exists('franchises_home_popular_all_url')) {
    function franchises_home_popular_all_url(): string
    {
        $selection_id = franchises_popular_selection_post_id();
        if ($selection_id > 0) {
            $link = get_permalink($selection_id);
            if (is_string($link) && $link !== '') {
                return $link;
            }
        }

        $legacy_term = franchises_popular_product_cat_term();
        if ($legacy_term instanceof WP_Term) {
            $tlink = get_term_link($legacy_term);
            if (! is_wp_error($tlink) && is_string($tlink) && $tlink !== '') {
                return $tlink;
            }
        }

        if (function_exists('franchises_home_collections_shop_url')) {
            return franchises_home_collections_shop_url();
        }

        return home_url('/');
    }
}

if (! function_exists('franchises_home_popular_category_labels')) {
    /**
     * Названия категорий для SEO-подзаголовка блока.
     *
     * @return list<string>
     */
    function franchises_home_popular_category_labels(int $max = 4): array
    {
        $max = max(1, $max);
        $labels = [];

        if (function_exists('franchises_get_top_viewed_product_cat_terms')) {
            foreach (franchises_get_top_viewed_product_cat_terms($max) as $term) {
                if ($term instanceof WP_Term) {
                    $labels[] = (string) $term->name;
                }
            }
        }

        if ($labels !== []) {
            return $labels;
        }

        $legacy = franchises_popular_product_cat_term();
        if ($legacy instanceof WP_Term) {
            return [(string) $legacy->name];
        }

        return [];
    }
}

if (! function_exists('franchises_home_popular_seo_text')) {
    /**
     * Подзаголовок блока для пользователей и SEO (не служебное сообщение).
     */
    function franchises_home_popular_seo_text(int $products_count = 0): string
    {
        $selection_id = franchises_popular_selection_post_id();

        if ($selection_id > 0) {
            $excerpt = get_post_field('post_excerpt', $selection_id);
            if (is_string($excerpt) && trim($excerpt) !== '') {
                return trim(wp_strip_all_tags($excerpt));
            }
        }

        if ($products_count > 0) {
            return __(
                'Самые просматриваемые франшизы в каталоге: сравните паушальный взнос, объём инвестиций и срок окупаемости — выберите модель под свой бюджет.',
                'franchises'
            );
        }

        return __(
            'В каталоге скоро появятся новые предложения. Пока вы можете перейти в полный каталог франшиз или оставить заявку на персональную подборку.',
            'franchises'
        );
    }
}

if (! function_exists('franchises_render_home_popular_section')) {
    function franchises_render_home_popular_section(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        $limit = franchises_home_popular_limit();
        $product_ids = franchises_popular_franchise_product_ids($limit);
        $popular_all_href = franchises_home_popular_all_url();
        $popular_franchises_notice = franchises_home_popular_seo_text(count($product_ids));

        $template = locate_template('templates/_popular-section.php');
        if (! $template) {
            return;
        }

        require $template;
    }
}
