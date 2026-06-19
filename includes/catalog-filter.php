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
        return [
            'verified'    => ! empty($raw['verified']),
            'invest_max'  => isset($raw['invest_max']) ? max(0, (int) $raw['invest_max']) : 0,
            'profit_min'  => isset($raw['profit_min']) ? max(0, (int) $raw['profit_min']) : 0,
            'payback_max' => isset($raw['payback_max']) ? max(0, (int) $raw['payback_max']) : 0,
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

        if ($f['invest_max'] > 0) {
            $meta_parts[] = [
                'key'     => 'investment_min',
                'value'   => [1, (int) $f['invest_max']],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
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

        if ($criteria['invest_max'] > 0) {
            $invest_min = function_exists('franchises_product_investment_min')
                ? franchises_product_investment_min($product_id)
                : null;
            if ($invest_min === null || $invest_min <= 0 || $invest_min > $criteria['invest_max']) {
                return false;
            }
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
