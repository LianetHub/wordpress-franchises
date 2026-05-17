<?php

defined('ABSPATH') || exit;

/**
 * @return non-empty-string
 */
function franchises_selection_post_type(): string
{
    return 'selection';
}

/**
 * Готовые типы подборок (заголовок в админке → выберите соответствующий тип).
 *
 * @return array<string, string>
 */
function franchises_selection_filter_choices(): array
{
    return [
        'no_pausal'  => 'Франшизы без взноса - паушальный взнос 0 или не указан',
        'payback_12' => 'Окупаемость до 12 мес. - хотя бы одно поле окупаемости ≤ 12',
        'no_royalty' => 'Без роялти - роялти не указано или «нет»',
        'low_invest' => 'Недорогие старты - инвестиции до 500 000 ₽',
        'manual'     => 'кастомные списки',
    ];
}

/**
 * Порог «недорогих стартов» (цена товара WooCommerce), ₽.
 */
function franchises_selection_low_invest_max(): int
{
    return (int) apply_filters('franchises_selection_low_invest_max', 500000);
}

function theme_register_selections(): void
{
    register_post_type(franchises_selection_post_type(), [
        'label'  => 'Подборки',
        'labels' => [
            'name'               => 'Подборки',
            'singular_name'      => 'Подборка',
            'add_new'            => 'Добавить подборку',
            'add_new_item'       => 'Добавить новую подборку',
            'edit_item'          => 'Редактировать подборку',
            'new_item'           => 'Новая подборка',
            'view_item'          => 'Посмотреть подборку',
            'search_items'       => 'Найти подборку',
            'not_found'          => 'Подборок не найдено',
            'parent_item_colon'  => '',
            'menu_name'          => 'Подборки франшиз',
        ],
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'podborki'],
        'supports'           => ['title', 'thumbnail', 'excerpt', 'page-attributes'],
        'menu_icon'          => 'dashicons-star-filled',
        'show_in_rest'       => true,
    ]);
}

/**
 * ID подборки на странице single-selection (после подмены основного запроса на товары).
 */
function franchises_get_current_selection_id(): int
{
    if (! empty($GLOBALS['franchises_current_selection_id'])) {
        return (int) $GLOBALS['franchises_current_selection_id'];
    }
    if (is_singular(franchises_selection_post_type())) {
        return (int) get_queried_object_id();
    }

    return 0;
}

/**
 * @return list<array{label: string, href: string}>
 */
function franchises_selection_breadcrumbs(int $selection_id): array
{
    $trail = [
        ['label' => 'Главная', 'href' => home_url('/')],
    ];

    $shop_url = '';
    if (function_exists('wc_get_page_id')) {
        $shop_id = wc_get_page_id('shop');
        if ($shop_id > 0) {
            $shop_url = (string) get_permalink($shop_id);
        }
    }

    if ($shop_url !== '') {
        $trail[] = ['label' => 'Каталог франшиз', 'href' => $shop_url];
    }

    $title = $selection_id > 0 ? get_the_title($selection_id) : '';
    $trail[] = [
        'label' => $title !== '' ? (string) $title : 'Подборка',
        'href'  => '',
    ];

    return $trail;
}

/**
 * @return list<WP_Post>
 */
function franchises_get_selection_posts(int $limit = 40): array
{
    if (! post_type_exists(franchises_selection_post_type())) {
        return [];
    }

    $posts = get_posts([
        'post_type'      => franchises_selection_post_type(),
        'post_status'    => 'publish',
        'posts_per_page' => max(1, $limit),
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ]);

    return array_values(array_filter($posts, static fn($post): bool => $post instanceof WP_Post));
}

/**
 * Подсказка типа фильтра по slug записи (если в ACF ещё не выбрано).
 */
function franchises_selection_filter_type_from_slug(string $slug): string
{
    $slug = sanitize_title($slug);
    if ($slug === '') {
        return '';
    }

    $patterns = [
        'no_pausal'  => ['bez-vznos', 'bez-pausal', 'no-pausal', 'bez-paus'],
        'payback_12' => ['12-mes', 'okupaemost-12', 'payback-12'],
        'no_royalty' => ['bez-royalti', 'no-royalty', 'bez-roialti'],
        'low_invest' => ['nedorog', 'low-invest', 'deshevy', 'budget'],
        'manual'     => ['pod-klyuch', 'pod-kluch', 'turnkey', 'top-2026', 'top-franshiz', 'top-202'],
    ];

    foreach ($patterns as $type => $needles) {
        foreach ($needles as $needle) {
            if (str_contains($slug, $needle)) {
                return $type;
            }
        }
    }

    return '';
}

function franchises_selection_filter_type(int $selection_id): string
{
    $type = '';
    if (function_exists('get_field')) {
        $type = (string) get_field('selection_filter_type', $selection_id);
    }

    if (in_array($type, ['turnkey', 'top_2026'], true)) {
        $type = 'manual';
    }

    if ($type !== '' && array_key_exists($type, franchises_selection_filter_choices())) {
        return $type;
    }

    $slug_type = franchises_selection_filter_type_from_slug((string) get_post_field('post_name', $selection_id));
    if ($slug_type !== '') {
        return $slug_type;
    }

    return 'manual';
}

function franchises_selection_needs_post_filter(string $type): bool
{
    return in_array($type, ['manual', 'no_royalty', 'no_pausal', 'payback_12', 'low_invest'], true);
}

/**
 * @return list<int>
 */
function franchises_selection_manual_product_ids(int $selection_id): array
{
    if (! function_exists('get_field')) {
        return [];
    }

    $raw = get_field('selection_franchises', $selection_id);
    if (! is_array($raw)) {
        return [];
    }

    $ids = [];
    foreach ($raw as $item) {
        $id = 0;
        if ($item instanceof WP_Post) {
            $id = (int) $item->ID;
        } elseif (is_numeric($item)) {
            $id = (int) $item;
        } elseif (is_array($item) && isset($item['ID'])) {
            $id = (int) $item['ID'];
        }
        if ($id > 0 && get_post_type($id) === 'product') {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function franchises_product_pausal_amount(int $product_id): ?int
{
    if (! function_exists('get_field')) {
        return null;
    }
    $pausal = get_field('pausal', $product_id);
    if ($pausal === null || $pausal === '') {
        return null;
    }

    return (int) $pausal;
}

function franchises_product_has_no_pausal(int $product_id): bool
{
    $pausal = franchises_product_pausal_amount($product_id);

    return $pausal === null || $pausal <= 0;
}

function franchises_product_has_no_royalty(int $product_id): bool
{
    if (! function_exists('get_field')) {
        return true;
    }
    $royalty = get_field('royalty', $product_id);
    if ($royalty === null || $royalty === '' || $royalty === false) {
        return true;
    }
    if (is_numeric($royalty) && (float) $royalty <= 0) {
        return true;
    }
    if (is_string($royalty) && preg_match('/^(нет|no|0\s*%?|отсутствует|без)$/iu', trim($royalty))) {
        return true;
    }

    return false;
}

/**
 * Заполненные значения окупаемости (мес.): payback_min, payback_max, legacy payback.
 *
 * @return list<int>
 */
function franchises_product_payback_values(int $product_id): array
{
    if (! function_exists('get_field')) {
        return [];
    }

    $values = [];
    foreach (['payback_min', 'payback_max', 'payback'] as $field) {
        $raw = get_field($field, $product_id);
        if ($raw !== null && $raw !== '') {
            $values[] = (int) $raw;
        }
    }

    return array_values(array_unique($values));
}

/**
 * Хотя бы одно заполненное поле окупаемости ≤ $months (пустые поля не учитываются).
 */
function franchises_product_payback_within(int $product_id, int $months): bool
{
    if ($months <= 0) {
        return false;
    }

    $values = franchises_product_payback_values($product_id);
    if ($values === []) {
        return false;
    }

    foreach ($values as $value) {
        if ($value <= $months) {
            return true;
        }
    }

    return false;
}

/**
 * Размер инвестиции — цена товара WooCommerce (regular price).
 */
function franchises_product_investment_amount(int $product_id): ?int
{
    return function_exists('franchises_product_price_amount')
        ? franchises_product_price_amount($product_id)
        : null;
}

function franchises_product_matches_selection_type(int $product_id, string $type, int $selection_id = 0): bool
{
    switch ($type) {
        case 'no_pausal':
            return franchises_product_has_no_pausal($product_id);
        case 'payback_12':
            return franchises_product_payback_within($product_id, 12);
        case 'no_royalty':
            return franchises_product_has_no_royalty($product_id);
        case 'low_invest':
            $invest = franchises_product_investment_amount($product_id);

            return $invest !== null && $invest > 0 && $invest <= franchises_selection_low_invest_max();
        case 'manual':
            return $selection_id > 0 && in_array($product_id, franchises_selection_manual_product_ids($selection_id), true);
        default:
            return true;
    }
}

/**
 * @param array<string, mixed> $card
 */
function franchises_selection_card_matches(int $selection_id, array $card): bool
{
    $product_id = (int) ($card['product_id'] ?? 0);
    if ($product_id <= 0) {
        return true;
    }

    return franchises_product_matches_selection_type(
        $product_id,
        franchises_selection_filter_type($selection_id),
        $selection_id
    );
}

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function franchises_selection_products_query_args(int $selection_id, array $overrides = []): array
{
    $type = franchises_selection_filter_type($selection_id);
    $manual_ids = franchises_selection_manual_product_ids($selection_id);

    $args = [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => (int) get_option('posts_per_page', 12),
        'ignore_sticky_posts' => true,
    ];

    if ($type === 'manual') {
        $args['post__in'] = $manual_ids !== [] ? $manual_ids : [0];
        $args['orderby'] = 'post__in';
    } else {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
    }

    return array_merge($args, $overrides);
}

/**
 * @return list<int>
 */
function franchises_selection_product_ids(int $selection_id, int $max = 500): array
{
    $type = franchises_selection_filter_type($selection_id);

    if ($type === 'manual') {
        return franchises_selection_manual_product_ids($selection_id);
    }

    $query = new WP_Query(array_merge(
        franchises_selection_products_query_args($selection_id, [
            'posts_per_page' => max(1, $max),
            'no_found_rows'  => true,
        ]),
        ['no_found_rows' => true]
    ));

    $ids = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = (int) get_the_ID();
            if ($post_id <= 0) {
                continue;
            }
            if (franchises_product_matches_selection_type($post_id, $type, $selection_id)) {
                $ids[] = $post_id;
            }
            if (count($ids) >= $max) {
                break;
            }
        }
    }
    wp_reset_postdata();

    return $ids;
}

/**
 * @return array{text: string, link: string, image_url: string}|null
 */
function franchises_selection_hero_slide(int $selection_id): ?array
{
    if ($selection_id <= 0 || get_post_type($selection_id) !== franchises_selection_post_type()) {
        return null;
    }

    $link = get_permalink($selection_id);
    if (! is_string($link) || $link === '') {
        return null;
    }

    $thumb = get_the_post_thumbnail_url($selection_id, 'large');
    if (! is_string($thumb) || $thumb === '') {
        return null;
    }

    return [
        'text'      => get_the_title($selection_id),
        'link'      => $link,
        'image_url' => $thumb,
    ];
}

/**
 * @return list<array{text: string, link: string, image_url: string}>
 */
function franchises_hero_slides_from_selections(): array
{
    $selection_posts = [];

    if (function_exists('get_field')) {
        $picked = get_field('hero_selections');
        if (is_array($picked)) {
            foreach ($picked as $item) {
                if ($item instanceof WP_Post && $item->post_status === 'publish') {
                    $selection_posts[] = $item;
                } elseif (is_numeric($item)) {
                    $post = get_post((int) $item);
                    if ($post instanceof WP_Post && $post->post_status === 'publish') {
                        $selection_posts[] = $post;
                    }
                }
            }
        }
    }

    if ($selection_posts === []) {
        $selection_posts = franchises_get_selection_posts(12);
    }

    $slides = [];
    foreach ($selection_posts as $post) {
        $slide = franchises_selection_hero_slide((int) $post->ID);
        if ($slide !== null) {
            $slides[] = $slide;
        }
    }

    return $slides;
}

add_action('acf/init', static function (): void {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'    => 'group_franchises_selection',
        'title'  => 'Подборка франшиз',
        'fields' => [
            [
                'key'           => 'field_selection_filter_type',
                'label'         => 'Тип подборки',
                'name'          => 'selection_filter_type',
                'type'          => 'select',
                'choices'       => franchises_selection_filter_choices(),
                'default_value' => 'manual',
                'return_format' => 'value',
                'instructions'  => '«Под ключ», «Топ франшиз» и другие кастомные подборки — тип «Вручную» и список франшиз ниже. Миниатюра записи — картинка в hero.',
            ],
            [
                'key'               => 'field_selection_franchises',
                'label'             => 'Франшизы',
                'name'              => 'selection_franchises',
                'type'              => 'relationship',
                'post_type'         => ['product'],
                'filters'           => ['search'],
                'return_format'     => 'id',
                'min'               => 0,
                'max'               => 0,
                'conditional_logic' => [[[
                    'field'    => 'field_selection_filter_type',
                    'operator' => '==',
                    'value'    => 'manual',
                ]]],
            ],
        ],
        'location' => [[[
            'param'    => 'post_type',
            'operator' => '==',
            'value'    => franchises_selection_post_type(),
        ]]],
    ]);
});

add_action('template_redirect', static function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    if (! is_post_type_archive(franchises_selection_post_type())) {
        return;
    }

    $target = home_url('/');
    if (function_exists('wc_get_page_id')) {
        $shop_id = wc_get_page_id('shop');
        if ($shop_id > 0) {
            $permalink = get_permalink($shop_id);
            if (is_string($permalink) && $permalink !== '') {
                $target = $permalink;
            }
        }
    }

    wp_safe_redirect($target, 301);
    exit;
}, 5);
