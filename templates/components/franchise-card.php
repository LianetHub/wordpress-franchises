<?php
/**
 * Карточка франшизы (сетка «Популярные» / каталог).
 *
 * Варианты вызова:
 * 1) Передать массив $franchise_card (ручной мок или своя сборка).
 * 2) Через get_template_part( ..., null, [ 'franchise_card' => $arr ] ).
 * 3) В цикле WooCommerce: глобальный $product — поля подтянутся из ACF поста товара.
 *
 * Поля ACF (имена): product_full_title, year_founded, franchise_since, own_stores,
 * franch_stores, tm_number, monthly_profit_min, monthly_profit_max, payback_min,
 * payback_max, verified, pausal, royalty.
 *
 * Короткое описание в моке — в ACF отдельного поля нет; используйте excerpt поста
 * или передавайте ключ 'desc' в $franchise_card.
 */

defined('ABSPATH') || exit;

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
        $profit_for_filter = $profit_min ?? $profit_max;
        $payback_for_filter = $payback_min ?? $payback_max;

        $thumb = get_the_post_thumbnail_url($post_id, 'large') ?: '';

        $permalink = get_permalink($post_id) ?: '#';
        $slug = (string) get_post_field('post_name', $post_id);

        $sphere = '';
        $category = '';
        $tags_pipe = '';
        if (taxonomy_exists('product_cat')) {
            $terms = get_the_terms($post_id, 'product_cat');
            if (is_array($terms) && $terms !== []) {
                $names = wp_list_pluck($terms, 'name');
                $category = (string) ($names[0] ?? '');
                $tags_pipe = implode('|', $names);
            }
        }

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

$franchise_card = isset($franchise_card) && is_array($franchise_card) ? $franchise_card : [];

if (
    $franchise_card === []
    && isset($product)
    && class_exists('WC_Product')
    && is_a($product, 'WC_Product', true)
    && $product->is_visible()
) {
    $franchise_card = franchises_franchise_card_from_post($product->get_id());
}

if ($franchise_card === []) {
    return;
}

$defaults = [
    'href'             => '#',
    'order'            => 0,
    'date'             => 0,
    'popularity'       => 0,
    'sphere'           => '',
    'category'         => '',
    'invest'           => null,
    'payback'          => null,
    'profit'           => null,
    'verified'         => false,
    'tags'             => '',
    'name'             => '',
    'desc'             => '',
    'image'            => '',
    'franchise_id'     => '',
    'franchise_url'    => '',
    'acf_year_founded' => null,
    'acf_franchise_since' => null,
    'acf_own_stores'   => null,
    'acf_franch_stores' => null,
    'acf_tm_number'    => null,
    'acf_monthly_profit_min' => null,
    'acf_monthly_profit_max' => null,
    'acf_payback_min'  => null,
    'acf_payback_max'  => null,
    'acf_pausal'       => null,
    'acf_royalty'      => null,
];

$c = wp_parse_args($franchise_card, $defaults);

$verified_bool = ! empty($c['verified']);
$verified_attr = $verified_bool ? 'true' : 'false';

$invest_int = $c['invest'] !== null && $c['invest'] !== '' ? (int) $c['invest'] : 0;
$payback_int = $c['payback'] !== null && $c['payback'] !== '' ? (int) $c['payback'] : 0;
$profit_int = $c['profit'] !== null && $c['profit'] !== '' ? (int) $c['profit'] : 0;

$meta_value = $invest_int > 0 ? franchises_format_money_ru($invest_int) : '';

$href = esc_url($c['href']);
$name = esc_html((string) $c['name']);
$desc = esc_html((string) $c['desc']);
$img = esc_url((string) $c['image']);
$img_alt = esc_attr(wp_strip_all_tags((string) $c['name']));

$franchise_id = esc_attr((string) $c['franchise_id']);
$franchise_url = esc_url((string) $c['franchise_url']);

$attr = static function (string $k, $v): string {
    if ($v === null || $v === '') {
        return '';
    }

    return ' ' . esc_attr($k) . '="' . esc_attr(is_scalar($v) ? (string) $v : '') . '"';
};

?>
<a class="popular-card"
    href="<?php echo $href; ?>"
    data-order="<?php echo esc_attr((string) $c['order']); ?>"
    data-date="<?php echo esc_attr((string) $c['date']); ?>"
    data-popularity="<?php echo esc_attr((string) $c['popularity']); ?>"
    data-sphere="<?php echo esc_attr((string) $c['sphere']); ?>"
    data-category="<?php echo esc_attr((string) $c['category']); ?>"
    data-invest="<?php echo esc_attr((string) $invest_int); ?>"
    data-payback="<?php echo esc_attr((string) $payback_int); ?>"
    data-profit="<?php echo esc_attr((string) $profit_int); ?>"
    data-verified="<?php echo esc_attr($verified_attr); ?>"
    data-tags="<?php echo esc_attr((string) $c['tags']); ?>"
    data-name="<?php echo esc_attr(wp_strip_all_tags((string) $c['name'])); ?>"
    data-desc="<?php echo esc_attr(wp_strip_all_tags((string) $c['desc'])); ?>"
    data-image="<?php echo $img; ?>"
    data-franchise-id="<?php echo $franchise_id; ?>"
    data-franchise-url="<?php echo $franchise_url; ?>"
    <?php
    echo $attr('data-year-founded', $c['acf_year_founded']);
    echo $attr('data-franchise-since', $c['acf_franchise_since']);
    echo $attr('data-own-stores', $c['acf_own_stores']);
    echo $attr('data-franch-stores', $c['acf_franch_stores']);
    echo $attr('data-tm-number', $c['acf_tm_number']);
    echo $attr('data-monthly-profit-min', $c['acf_monthly_profit_min']);
    echo $attr('data-monthly-profit-max', $c['acf_monthly_profit_max']);
    echo $attr('data-payback-min', $c['acf_payback_min']);
    echo $attr('data-payback-max', $c['acf_payback_max']);
    echo $attr('data-pausal', $c['acf_pausal']);
    echo $attr('data-royalty', $c['acf_royalty']);
    ?>
>
    <div class="popular-media">
        <?php if ($img !== '') : ?>
            <img loading="lazy" alt="<?php echo $img_alt; ?>" src="<?php echo $img; ?>">
        <?php endif; ?>
        <?php if ($verified_bool) : ?>
            <span class="popular-badge">Проверено</span>
        <?php endif; ?>
    </div>
    <div class="popular-brand"><?php echo $name; ?></div>
    <div class="popular-desc"><?php echo $desc; ?></div>
    <div class="popular-meta">
        <span class="meta-label">Инвестиции от</span>
        <?php if ($meta_value !== '') : ?>
            <span class="meta-value"><?php echo esc_html($meta_value); ?></span>
        <?php endif; ?>
    </div>
</a>
