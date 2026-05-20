<?php

/**
 * Инвестиции и паушальный взнос: ACF min/max, синхронизация investment_min → цена WooCommerce.
 */

defined('ABSPATH') || exit;

// -------------------------------------------------------------------------
// Чтение ACF (с legacy-полями investment / pausal)
// -------------------------------------------------------------------------

if (! function_exists('franchises_acf_money_int')) {
    /**
     * @param mixed $raw
     */
    function franchises_acf_money_int($raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }

        return (int) round((float) $raw);
    }
}

if (! function_exists('franchises_product_acf_money')) {
    /**
     * Первое непустое числовое ACF-поле из списка имён.
     *
     * @param list<string> $field_names
     */
    function franchises_product_acf_money(int $post_id, array $field_names): ?int
    {
        if (! function_exists('get_field')) {
            return null;
        }

        foreach ($field_names as $name) {
            $raw = get_field($name, $post_id);
            $value = franchises_acf_money_int($raw);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }
}

if (! function_exists('franchises_product_investment_min')) {
    function franchises_product_investment_min(int $post_id): ?int
    {
        $min = franchises_product_acf_money($post_id, ['investment_min', 'investment']);
        if ($min !== null) {
            return $min;
        }

        return franchises_product_wc_regular_price_amount($post_id);
    }
}

if (! function_exists('franchises_product_investment_max')) {
    function franchises_product_investment_max(int $post_id): ?int
    {
        $max = franchises_product_acf_money($post_id, ['investment_max']);
        if ($max !== null) {
            return $max;
        }

        $legacy = franchises_product_acf_money($post_id, ['investment']);
        if ($legacy !== null) {
            return $legacy;
        }

        return franchises_product_investment_min($post_id);
    }
}

if (! function_exists('franchises_product_pausal_min')) {
    function franchises_product_pausal_min(int $post_id): ?int
    {
        return franchises_product_acf_money($post_id, ['pausal_min', 'pausal']);
    }
}

if (! function_exists('franchises_product_pausal_max')) {
    function franchises_product_pausal_max(int $post_id): ?int
    {
        $max = franchises_product_acf_money($post_id, ['pausal_max']);
        if ($max !== null) {
            return $max;
        }

        return franchises_product_pausal_min($post_id);
    }
}

if (! function_exists('franchises_product_wc_regular_price_amount')) {
    function franchises_product_wc_regular_price_amount(int $post_id): ?int
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

/** Нижняя граница инвестиций (фильтры, data-invest, подборки). */
if (! function_exists('franchises_product_investment_amount')) {
    function franchises_product_investment_amount(int $post_id): ?int
    {
        return franchises_product_investment_min($post_id);
    }
}

if (! function_exists('franchises_product_pausal_amount')) {
    /** Нижняя граница паушального взноса (legacy-хелпер). */
    function franchises_product_pausal_amount(int $product_id): ?int
    {
        return franchises_product_pausal_min($product_id);
    }
}

if (! function_exists('franchises_product_has_no_pausal')) {
    function franchises_product_has_no_pausal(int $product_id): bool
    {
        $min = franchises_product_pausal_min($product_id);
        $max = franchises_product_pausal_max($product_id);

        if ($min === null && $max === null) {
            return true;
        }

        $effective_min = $min ?? $max;
        $effective_max = $max ?? $min;

        return ($effective_min === null || $effective_min <= 0)
            && ($effective_max === null || $effective_max <= 0);
    }
}

// -------------------------------------------------------------------------
// Границы для отображения (без подстановки WC-цены и max ← min)
// -------------------------------------------------------------------------

if (! function_exists('franchises_product_investment_bounds_for_display')) {
    /**
     * @return array{0: ?int, 1: ?int}
     */
    function franchises_product_investment_bounds_for_display(int $post_id): array
    {
        $min = franchises_product_acf_money($post_id, ['investment_min']);
        $max = franchises_product_acf_money($post_id, ['investment_max']);

        if ($min === null && $max === null) {
            $legacy = franchises_product_acf_money($post_id, ['investment']);
            if ($legacy !== null) {
                return [$legacy, null];
            }
        }

        return [$min, $max];
    }
}

if (! function_exists('franchises_product_pausal_bounds_for_display')) {
    /**
     * @return array{0: ?int, 1: ?int}
     */
    function franchises_product_pausal_bounds_for_display(int $post_id): array
    {
        $min = franchises_product_acf_money($post_id, ['pausal_min']);
        $max = franchises_product_acf_money($post_id, ['pausal_max']);

        if ($min === null && $max === null) {
            $legacy = franchises_product_acf_money($post_id, ['pausal']);
            if ($legacy !== null) {
                return [$legacy, null];
            }
        }

        return [$min, $max];
    }
}

// -------------------------------------------------------------------------
// Форматирование
// -------------------------------------------------------------------------

if (! function_exists('franchises_price_on_request_text')) {
    function franchises_price_on_request_text(): string
    {
        return (string) apply_filters('franchises_price_on_request_text', 'Уточняйте у менеджера');
    }
}

if (! function_exists('franchises_normalize_money_bound')) {
    function franchises_normalize_money_bound($value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }

        $n = (int) round((float) $value);

        return $n > 0 ? $n : null;
    }
}

if (! function_exists('franchises_product_investment_bounds_for_display_resolved')) {
    /**
     * Границы инвестиций для вывода: ACF → legacy → regular price WooCommerce.
     *
     * @return array{0: ?int, 1: ?int}
     */
    function franchises_product_investment_bounds_for_display_resolved(int $post_id): array
    {
        [$min, $max] = franchises_product_investment_bounds_for_display($post_id);
        $min = franchises_normalize_money_bound($min);
        $max = franchises_normalize_money_bound($max);

        if ($min === null && $max === null) {
            $wc = franchises_product_wc_regular_price_amount($post_id);
            $min = franchises_normalize_money_bound($wc);
        }

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }
}

if (! function_exists('franchises_format_money_range_line_ru')) {
    /**
     * Только min → «от …»; только max → «до …»; диапазон → «… – …».
     *
     * @param int|null $min
     * @param int|null $max
     * @param bool     $card_mode без «от»/«до» в value (подпись задаётся отдельно в карточке)
     */
    function franchises_format_money_range_line_ru(?int $min, ?int $max, bool $card_mode = false): string
    {
        $min = franchises_normalize_money_bound($min);
        $max = franchises_normalize_money_bound($max);

        if ($min === null && $max === null) {
            return '';
        }

        if ($min !== null && $max !== null) {
            if ($min > $max) {
                [$min, $max] = [$max, $min];
            }
            if ($min !== $max) {
                return franchises_format_money_ru($min) . ' – ' . franchises_format_money_ru($max);
            }

            $max = null;
        }

        if ($min !== null) {
            $formatted = franchises_format_money_ru($min);

            return $card_mode ? $formatted : 'от ' . $formatted;
        }

        $formatted = franchises_format_money_ru($max);

        return $card_mode ? $formatted : 'до ' . $formatted;
    }
}

if (! function_exists('franchises_format_investment_card_parts_ru')) {
    /**
     * Подпись и значение инвестиций для карточки франшизы.
     *
     * @return array{label: string, value: string}
     */
    function franchises_format_investment_card_parts_ru(int $post_id): array
    {
        [$min, $max] = franchises_product_investment_bounds_for_display_resolved($post_id);

        if ($min === null && $max === null) {
            return [
                'label' => 'Инвестиции',
                'value' => franchises_price_on_request_text(),
            ];
        }

        if ($min !== null && $max !== null && $min !== $max) {
            return [
                'label' => 'Инвестиции',
                'value' => franchises_format_money_ru($min) . ' – ' . franchises_format_money_ru($max),
            ];
        }

        $amount = $min ?? $max;
        if ($amount === null) {
            return [
                'label' => 'Инвестиции',
                'value' => franchises_price_on_request_text(),
            ];
        }

        if ($min !== null && ($max === null || $min === $max)) {
            return [
                'label' => $min === $max ? 'Инвестиции' : 'Инвестиции от',
                'value' => franchises_format_money_ru($amount),
            ];
        }

        return [
            'label' => 'Инвестиции до',
            'value' => franchises_format_money_ru($amount),
        ];
    }
}

if (! function_exists('franchises_format_investment_line_ru')) {
    function franchises_format_investment_line_ru(WC_Product $product): string
    {
        $post_id = $product->get_id();
        [$min, $max] = franchises_product_investment_bounds_for_display_resolved($post_id);
        $line = franchises_format_money_range_line_ru($min, $max, false);

        return $line !== '' ? $line : franchises_price_on_request_text();
    }
}

if (! function_exists('franchises_format_investment_card_value_ru')) {
    function franchises_format_investment_card_value_ru(int $post_id): string
    {
        $parts = franchises_format_investment_card_parts_ru($post_id);

        return $parts['value'];
    }
}

if (! function_exists('franchises_format_pausal_line_ru')) {
    function franchises_format_pausal_line_ru(int $post_id): string
    {
        [$min, $max] = franchises_product_pausal_bounds_for_display($post_id);
        $min = franchises_normalize_money_bound($min);
        $max = franchises_normalize_money_bound($max);

        return franchises_format_money_range_line_ru($min, $max, false);
    }
}

// -------------------------------------------------------------------------
// Синхронизация investment_min → _regular_price
// -------------------------------------------------------------------------

if (! function_exists('franchises_sync_product_price_from_investment')) {
    function franchises_sync_product_price_from_investment(int $post_id): void
    {
        if ($post_id <= 0 || get_post_type($post_id) !== 'product') {
            return;
        }

        if (! function_exists('wc_get_product')) {
            return;
        }

        $min = franchises_product_acf_money($post_id, ['investment_min', 'investment']);
        if ($min === null || $min <= 0) {
            return;
        }

        $product = wc_get_product($post_id);
        if (! $product) {
            return;
        }

        $price_str = (string) $min;
        $current = $product->get_regular_price();
        if ($current === $price_str) {
            return;
        }

        $product->set_regular_price($price_str);
        $product->set_price($price_str);
        $product->save();
    }
}

if (! function_exists('franchises_maybe_sync_product_price_from_investment')) {
    function franchises_maybe_sync_product_price_from_investment(int $post_id): void
    {
        static $running = false;

        if ($running) {
            return;
        }

        $running = true;
        franchises_sync_product_price_from_investment($post_id);
        $running = false;
    }
}

add_action('acf/save_post', static function ($post_id): void {
    if (! is_numeric($post_id)) {
        return;
    }
    franchises_maybe_sync_product_price_from_investment((int) $post_id);
}, 20);

add_action('save_post_product', static function (int $post_id): void {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    franchises_maybe_sync_product_price_from_investment($post_id);
}, 25);

// -------------------------------------------------------------------------
// Админка: скрыть ручной ввод цены WooCommerce
// -------------------------------------------------------------------------

add_action('admin_head', static function (): void {
    if (! function_exists('get_current_screen')) {
        return;
    }

    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'product') {
        return;
    }

    if (! in_array($screen->base, ['post', 'post-new'], true)) {
        return;
    }

    echo '<style>
        #general_product_data .pricing.show_if_simple,
        #general_product_data p._regular_price_field,
        #general_product_data p._sale_price_field,
        .woocommerce_variation .variable_pricing { display: none !important; }
    </style>';
}, 20);

add_action('woocommerce_product_options_general_product_data', static function (): void {
    echo '<p class="form-field franchises-investment-hint" style="padding:12px 12px 0;margin:0;">';
    echo '<span class="description">';
    echo esc_html('Цена в WooCommerce подставляется автоматически из ACF «investment_min» при сохранении. Редактируйте инвестиции в полях investment_min / investment_max.');
    echo '</span></p>';
}, 4);

add_filter('manage_product_posts_columns', static function (array $columns): array {
    unset($columns['price']);

    return $columns;
}, 20);
