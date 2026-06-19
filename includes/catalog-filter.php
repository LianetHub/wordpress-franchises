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
                'relation' => 'OR',
                [
                    'key'     => 'investment_min',
                    'value'   => $f['invest_max'],
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ],
                [
                    'key'     => '_regular_price',
                    'value'   => (string) $f['invest_max'],
                    'compare' => '<=',
                    'type'    => 'DECIMAL',
                ],
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
