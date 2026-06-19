<?php

/**
 * Каталог: meta_query для фильтров (вложения, прибыль, окупаемость, verified).
 */

defined('ABSPATH') || exit;

if (! function_exists('franchises_catalog_normalize_filter_criteria')) {
    /**
     * @param array<string, mixed> $raw
     * @return array{
     *   verified: bool,
     *   invest_max: int,
     *   profit_min: int,
     *   payback_max: int,
     *   sphere: string,
     *   category: string,
     *   search_q: string
     * }
     */
    function franchises_catalog_normalize_filter_criteria(array $raw): array
    {
        $parse_int = static function ($value): int {
            if (function_exists('franchises_parse_money_int')) {
                return franchises_parse_money_int($value);
            }

            return max(0, (int) $value);
        };

        return [
            'verified'    => ! empty($raw['verified']),
            'invest_max'  => isset($raw['invest_max']) ? $parse_int($raw['invest_max']) : 0,
            'profit_min'  => isset($raw['profit_min']) ? $parse_int($raw['profit_min']) : 0,
            'payback_max' => isset($raw['payback_max']) ? $parse_int($raw['payback_max']) : 0,
            'sphere'      => isset($raw['sphere']) ? trim((string) $raw['sphere']) : '',
            'category'    => isset($raw['category']) ? trim((string) $raw['category']) : '',
            'search_q'    => isset($raw['search_q']) ? trim((string) $raw['search_q']) : (isset($raw['q']) ? trim((string) $raw['q']) : ''),
        ];
    }
}

if (! function_exists('franchises_catalog_build_meta_query_parts')) {
    /**
     * Части meta_query для woocommerce_product_query (из GET или массива критериев).
     *
     * @param array<string, mixed> $source
     * @return list<array<string, mixed>>
     */
    function franchises_catalog_build_meta_query_parts(array $source = []): array
    {
        if ($source === [] && isset($_GET)) {
            $source = $_GET;
        }

        $f = franchises_catalog_normalize_filter_criteria($source);
        $meta_parts = [];

        if ($f['verified']) {
            $meta_parts[] = [
                'key'     => 'verified',
                'value'   => '1',
                'compare' => '=',
            ];
        }

        if ($f['profit_min'] > 0) {
            $meta_parts[] = [
                'relation' => 'OR',
                [
                    'key'     => 'monthly_profit_min',
                    'value'   => $f['profit_min'],
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => 'monthly_profit_max',
                    'value'   => $f['profit_min'],
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ],
            ];
        }

        if ($f['payback_max'] > 0) {
            $meta_parts[] = [
                'relation' => 'OR',
                [
                    'key'     => 'payback_max',
                    'value'   => $f['payback_max'],
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => 'payback_min',
                    'value'   => $f['payback_max'],
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ],
            ];
        }

        return $meta_parts;
    }
}

if (! function_exists('franchises_catalog_build_tax_query_parts')) {
    /**
     * @param array<string, mixed> $source
     * @return list<array<string, mixed>>
     */
    function franchises_catalog_build_tax_query_parts(array $source = []): array
    {
        if ($source === [] && isset($_GET)) {
            $source = $_GET;
        }

        $f = franchises_catalog_normalize_filter_criteria($source);
        $parts = [];

        if ($f['category'] !== '') {
            $parent_id = 0;
            if ($f['sphere'] !== '') {
                $parent = franchises_resolve_product_cat_term($f['sphere']);
                $parent_id = $parent ? (int) $parent->term_id : 0;
            }
            $term = franchises_resolve_product_cat_term($f['category'], $parent_id);
            if (! $term && $parent_id > 0) {
                $term = franchises_resolve_product_cat_term($f['category']);
            }
            if ($term instanceof WP_Term) {
                $parts[] = [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => [(int) $term->term_id],
                    'include_children' => false,
                ];
            }
        } elseif ($f['sphere'] !== '') {
            $term = franchises_resolve_product_cat_term($f['sphere']);
            if ($term instanceof WP_Term) {
                $parts[] = [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => [(int) $term->term_id],
                    'include_children' => true,
                ];
            }
        }

        return $parts;
    }
}

if (! function_exists('franchises_catalog_get_orderby')) {
    function franchises_catalog_get_orderby(): string
    {
        $default = function_exists('get_option')
            ? (string) get_option('woocommerce_default_catalog_orderby', 'menu_order')
            : 'menu_order';

        if (! isset($_GET['orderby']) || $_GET['orderby'] === '') {
            return $default;
        }

        return sanitize_text_field(wp_unslash((string) $_GET['orderby']));
    }
}

if (! function_exists('franchises_catalog_has_custom_orderby')) {
    /**
     * @param array<string, mixed> $source
     */
    function franchises_catalog_has_custom_orderby(array $source = []): bool
    {
        if ($source === [] && isset($_GET)) {
            $source = $_GET;
        }

        $default = function_exists('get_option')
            ? (string) get_option('woocommerce_default_catalog_orderby', 'menu_order')
            : 'menu_order';
        $orderby = isset($source['orderby'])
            ? sanitize_text_field((string) $source['orderby'])
            : $default;

        return $orderby !== '' && $orderby !== $default;
    }
}

if (! function_exists('franchises_catalog_has_list_filters')) {
    /**
     * @param array<string, mixed> $source
     */
    function franchises_catalog_has_list_filters(array $source = []): bool
    {
        if ($source === [] && isset($_GET)) {
            $source = $_GET;
        }

        $f = franchises_catalog_normalize_filter_criteria($source);

        if ($f['search_q'] !== '') {
            return true;
        }
        if ($f['verified']) {
            return true;
        }
        if ($f['invest_max'] > 0) {
            return true;
        }
        if ($f['profit_min'] > 0) {
            return true;
        }
        if ($f['payback_max'] > 0) {
            return true;
        }
        if ($f['sphere'] !== '') {
            return true;
        }
        if ($f['category'] !== '') {
            return true;
        }

        return false;
    }
}

if (! function_exists('franchises_catalog_ordering_query_args')) {
    /**
     * @return array<string, mixed>
     */
    function franchises_catalog_ordering_query_args(?string $orderby = null): array
    {
        if ($orderby === null) {
            $orderby = franchises_catalog_get_orderby();
        }

        if (function_exists('WC') && WC()->query instanceof WC_Query) {
            return WC()->query->get_catalog_ordering_args($orderby);
        }

        return [
            'orderby' => 'date',
            'order'   => 'DESC',
        ];
    }
}

if (! function_exists('franchises_catalog_join_title_clauses')) {
    /**
     * @param list<string> $clauses
     */
    function franchises_catalog_join_title_clauses(array $clauses): string
    {
        if ($clauses === []) {
            return '';
        }
        if (count($clauses) === 1) {
            return $clauses[0];
        }

        $last = array_pop($clauses);

        return implode(', ', $clauses) . ' и ' . $last;
    }
}

if (! function_exists('franchises_catalog_product_matches_tax_filters')) {
    function franchises_catalog_product_matches_tax_filters(int $product_id, string $sphere, string $category): bool
    {
        if ($category === '' && $sphere === '') {
            return true;
        }

        if (! function_exists('franchises_resolve_product_cat_term')) {
            return true;
        }

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
            if (! $term instanceof WP_Term) {
                return false;
            }

            return has_term((int) $term->term_id, 'product_cat', $product_id);
        }

        $term = franchises_resolve_product_cat_term($sphere);
        if (! $term instanceof WP_Term) {
            return false;
        }

        $product_terms = get_the_terms($product_id, 'product_cat');
        if (! is_array($product_terms)) {
            return false;
        }

        $sphere_id = (int) $term->term_id;
        foreach ($product_terms as $product_term) {
            if (! $product_term instanceof WP_Term) {
                continue;
            }
            if ((int) $product_term->term_id === $sphere_id) {
                return true;
            }
            if ((int) $product_term->parent === $sphere_id) {
                return true;
            }
            $ancestors = get_ancestors((int) $product_term->term_id, 'product_cat');
            if (in_array($sphere_id, $ancestors, true)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('franchises_catalog_product_matches_list_filters')) {
    /**
     * @param array{
     *   verified: bool,
     *   invest_max: int,
     *   profit_min: int,
     *   payback_max: int,
     *   sphere: string,
     *   category: string,
     *   search_q: string
     * } $criteria
     */
    function franchises_catalog_product_matches_list_filters(int $product_id, array $criteria): bool
    {
        if ($criteria['verified']) {
            $verified = get_post_meta($product_id, 'verified', true);
            if ((string) $verified !== '1') {
                return false;
            }
        }

        if (
            $criteria['invest_max'] > 0
            && (
                ! function_exists('franchises_product_investment_min_within')
                || ! franchises_product_investment_min_within($product_id, $criteria['invest_max'])
            )
        ) {
            return false;
        }

        if ($criteria['profit_min'] > 0) {
            if (
                ! function_exists('franchises_product_monthly_profit_from')
                || ! franchises_product_monthly_profit_from($product_id, $criteria['profit_min'])
            ) {
                return false;
            }
        }

        if ($criteria['payback_max'] > 0) {
            if (
                ! function_exists('franchises_product_payback_within')
                || ! franchises_product_payback_within($product_id, $criteria['payback_max'])
            ) {
                return false;
            }
        }

        if (
            ! franchises_catalog_product_matches_tax_filters(
                $product_id,
                $criteria['sphere'],
                $criteria['category']
            )
        ) {
            return false;
        }

        if ($criteria['search_q'] !== '') {
            $title = (string) get_post_field('post_title', $product_id);
            $excerpt = (string) get_post_field('post_excerpt', $product_id);
            $content = wp_strip_all_tags((string) get_post_field('post_content', $product_id));
            $haystack = mb_strtolower(trim($title . ' ' . $excerpt . ' ' . $content));
            $needle = mb_strtolower($criteria['search_q']);
            if ($needle === '' || ! str_contains($haystack, $needle)) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('franchises_sql_money_unsigned_expr')) {
    function franchises_sql_money_unsigned_expr(string $column): string
    {
        $expr = $column;
        $chars = [' ', ',', '₽', "\u{00A0}", "\u{202F}"];

        foreach ($chars as $char) {
            $expr = "REPLACE({$expr}, '" . esc_sql($char) . "', '')";
        }

        return "CAST({$expr} AS UNSIGNED)";
    }
}

if (! function_exists('franchises_catalog_invest_max_posts_clauses')) {
    /**
     * @param array<string, string> $clauses
     * @return array<string, string>
     */
    function franchises_catalog_invest_max_posts_clauses(array $clauses, WP_Query $query): array
    {
        if (is_admin() || ! $query->is_main_query()) {
            return $clauses;
        }
        if (function_exists('franchises_is_selection_catalog_view') && franchises_is_selection_catalog_view()) {
            return $clauses;
        }
        if (! function_exists('is_shop') || (! is_shop() && ! is_product_taxonomy())) {
            return $clauses;
        }

        $source = isset($_GET) ? $_GET : [];
        $max = franchises_catalog_normalize_filter_criteria($source)['invest_max'];
        if ($max <= 0) {
            return $clauses;
        }

        global $wpdb;

        $im_expr = franchises_sql_money_unsigned_expr('fr_cat_inv_im.meta_value');
        $legacy_expr = franchises_sql_money_unsigned_expr('fr_cat_inv_legacy.meta_value');
        $price_expr = franchises_sql_money_unsigned_expr('fr_cat_inv_rp.meta_value');

        $subquery = "
            SELECT CASE
                WHEN fr_cat_inv_im.meta_value IS NOT NULL AND fr_cat_inv_im.meta_value <> ''
                    THEN {$im_expr}
                WHEN fr_cat_inv_legacy.meta_value IS NOT NULL AND fr_cat_inv_legacy.meta_value <> ''
                    THEN {$legacy_expr}
                WHEN fr_cat_inv_rp.meta_value IS NOT NULL AND fr_cat_inv_rp.meta_value <> ''
                    THEN {$price_expr}
                ELSE 0
            END
            FROM {$wpdb->posts} fr_cat_inv_p
            LEFT JOIN {$wpdb->postmeta} fr_cat_inv_im
                ON fr_cat_inv_p.ID = fr_cat_inv_im.post_id AND fr_cat_inv_im.meta_key = 'investment_min'
            LEFT JOIN {$wpdb->postmeta} fr_cat_inv_legacy
                ON fr_cat_inv_p.ID = fr_cat_inv_legacy.post_id AND fr_cat_inv_legacy.meta_key = 'investment'
            LEFT JOIN {$wpdb->postmeta} fr_cat_inv_rp
                ON fr_cat_inv_p.ID = fr_cat_inv_rp.post_id AND fr_cat_inv_rp.meta_key = '_regular_price'
            WHERE fr_cat_inv_p.ID = {$wpdb->posts}.ID
            LIMIT 1
        ";

        $clauses['where'] .= $wpdb->prepare(
            " AND ({$subquery}) BETWEEN %d AND %d",
            1,
            $max
        );

        return $clauses;
    }
}

add_filter('posts_clauses', 'franchises_catalog_invest_max_posts_clauses', 20, 2);
