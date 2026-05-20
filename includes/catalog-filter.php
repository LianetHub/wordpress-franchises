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
