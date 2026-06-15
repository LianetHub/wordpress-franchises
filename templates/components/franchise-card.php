<?php

/**
 * Карточка франшизы (сетка «Популярные» / каталог).
 *
 * Варианты вызова:
 * 1) Передать массив $franchise_card (ручной мок или своя сборка).
 * 2) Через get_template_part( ..., null, [ 'franchise_card' => $arr ] ).
 * 3) В цикле WooCommerce: глобальный $product — поля подтянутся из ACF поста товара.
 *
 * Поля ACF (имена): year_founded, franchise_since, own_stores,
 * franch_stores, tm_number, monthly_profit_min, monthly_profit_max, payback_min,
 * payback_max, verified, pausal_min, pausal_max, investment_min, investment_max, royalty.
 *
 * Сборка массива из поста: franchises_franchise_card_from_post() в includes/woocommerce-custom.php.
 *
 * Короткое описание в моке — в ACF отдельного поля нет; используйте excerpt поста
 * или передавайте ключ 'desc' в $franchise_card.
 */

defined('ABSPATH') || exit;

global $product;

$franchise_card = isset($franchise_card) && is_array($franchise_card) ? $franchise_card : [];

if ($franchise_card === [] && isset($args) && is_array($args) && ! empty($args['franchise_card']) && is_array($args['franchise_card'])) {
    $franchise_card = $args['franchise_card'];
}

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
    'post_id'          => 0,
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
    'acf_pausal_min'   => null,
    'acf_pausal_max'   => null,
    'acf_investment_min' => null,
    'acf_investment_max' => null,
    'invest_display'   => '',
    'invest_label'     => 'Инвестиции',
    'acf_royalty'      => null,
];

$c = wp_parse_args($franchise_card, $defaults);

$verified_bool = ! empty($c['verified']);
$verified_attr = $verified_bool ? 'true' : 'false';

$payback_int = $c['payback'] !== null && $c['payback'] !== '' ? (int) $c['payback'] : 0;
$profit_int = $c['profit'] !== null && $c['profit'] !== '' ? (int) $c['profit'] : 0;

$href = esc_url($c['href']);

$card_post_id = (int) $c['post_id'];
if ($card_post_id <= 0 && isset($product) && class_exists('WC_Product') && is_a($product, 'WC_Product', true)) {
    $card_post_id = (int) $product->get_id();
}
if ($card_post_id <= 0 && (string) $c['franchise_id'] !== '') {
    $slug_post = get_page_by_path((string) $c['franchise_id'], OBJECT, 'product');
    if ($slug_post instanceof WP_Post) {
        $card_post_id = (int) $slug_post->ID;
    }
}
if ($card_post_id <= 0 && (string) $c['href'] !== '' && (string) $c['href'] !== '#') {
    $card_post_id = (int) url_to_postid((string) $c['href']);
}

// meta-value: investment_min / investment_max (ACF), не паушальный взнос.
$invest_int = 0;
$invest_max_int = 0;
$meta_label = (string) ($c['invest_label'] ?? 'Инвестиции');
$meta_value = (string) ($c['invest_display'] ?? '');
if ($card_post_id > 0) {
    if ($meta_value === '' && function_exists('franchises_format_investment_card_parts_ru')) {
        $invest_parts = franchises_format_investment_card_parts_ru($card_post_id);
        $meta_label = $invest_parts['label'];
        $meta_value = $invest_parts['value'];
    } elseif ($meta_value === '' && function_exists('franchises_format_investment_card_value_ru')) {
        $meta_value = franchises_format_investment_card_value_ru($card_post_id);
    }
    if (function_exists('franchises_product_investment_min')) {
        $min_amount = franchises_product_investment_min($card_post_id);
        if ($min_amount !== null && $min_amount > 0) {
            $invest_int = $min_amount;
        }
    }
    if (function_exists('franchises_product_investment_max')) {
        $max_amount = franchises_product_investment_max($card_post_id);
        if ($max_amount !== null && $max_amount > 0) {
            $invest_max_int = $max_amount;
        }
    }
} elseif ($c['invest'] !== null && $c['invest'] !== '') {
    $invest_int = (int) $c['invest'];
    if ($meta_value === '' && $invest_int > 0 && function_exists('franchises_format_money_ru')) {
        $meta_label = 'Инвестиции от';
        $meta_value = franchises_format_money_ru($invest_int);
    }
}

if ($meta_value === '' && function_exists('franchises_price_on_request_text')) {
    $meta_label = 'Инвестиции';
    $meta_value = franchises_price_on_request_text();
}

// popular-brand: заголовок товара (post_title), не ACF product_full_title (H1).
$brand_title = $card_post_id > 0
    ? get_the_title($card_post_id)
    : (string) $c['name'];

$name = esc_html($brand_title);
$brand_title_attr = wp_strip_all_tags($brand_title);

$desc = esc_html((string) $c['desc']);
$img = esc_url((string) $c['image']);
$img_alt = esc_attr($brand_title_attr);

$franchise_id = esc_attr((string) $c['franchise_id']);
$franchise_url = esc_url((string) $c['franchise_url']);

$attr = static function (string $k, $v): string {
    if ($v === null || $v === '') {
        return '';
    }

    return ' ' . esc_attr($k) . '="' . esc_attr(is_scalar($v) ? (string) $v : '') . '"';
};

?>
<div class="popular-card-wrap">
<a class="popular-card"
    href="<?php echo $href; ?>"
    data-order="<?php echo esc_attr((string) $c['order']); ?>"
    data-date="<?php echo esc_attr((string) $c['date']); ?>"
    data-popularity="<?php echo esc_attr((string) $c['popularity']); ?>"
    data-sphere="<?php echo esc_attr((string) $c['sphere']); ?>"
    data-category="<?php echo esc_attr((string) $c['category']); ?>"
    data-invest="<?php echo esc_attr((string) $invest_int); ?>"
    data-invest-max="<?php echo esc_attr((string) $invest_max_int); ?>"
    data-payback="<?php echo esc_attr((string) $payback_int); ?>"
    data-profit="<?php echo esc_attr((string) $profit_int); ?>"
    data-verified="<?php echo esc_attr($verified_attr); ?>"
    data-tags="<?php echo esc_attr((string) $c['tags']); ?>"
    data-name="<?php echo esc_attr($brand_title_attr); ?>"
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
    echo $attr('data-pausal-min', $c['acf_pausal_min']);
    echo $attr('data-pausal-max', $c['acf_pausal_max']);
    echo $attr('data-investment-min', $c['acf_investment_min']);
    echo $attr('data-investment-max', $c['acf_investment_max']);
    echo $attr('data-royalty', $c['acf_royalty']);
    ?>>
    <div class="popular-media">
        <?php if ($img !== '') : ?>
            <img loading="lazy" alt="<?php echo $img_alt; ?>" src="<?php echo $img; ?>" class="cover-image">
        <?php endif; ?>
        <?php if ($verified_bool) : ?>
            <span class="popular-badge">Проверено</span>
        <?php endif; ?>
    </div>
    <div class="popular-brand"><?php echo $name; ?></div>
    <div class="popular-desc"><?php echo $desc; ?></div>
    <div class="popular-meta">
        <span class="meta-label"><?php echo esc_html($meta_label); ?></span>
        <span class="meta-value"><?php echo esc_html($meta_value); ?></span>
    </div>
	
</a>
<button
    type="button"
    class="popular-card__popup-btn"
    data-fancybox
    data-src="#presentation-popup"
    data-franchise-title="<?php echo esc_attr($brand_title_attr); ?>"
    data-franchise-url="<?php echo esc_url($card_post_id > 0 ? get_permalink($card_post_id) : $href); ?>"
>
    Получить презентацию
</button>

</div>