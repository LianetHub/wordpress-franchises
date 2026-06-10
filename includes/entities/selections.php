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
 * @return array<string, int>
 */
function franchises_selection_invest_thresholds(): array
{
    return [
        'invest_100000'  => 100000,
        'invest_300000'  => 300000,
        'invest_500000'  => 500000,
        'invest_1000000' => 1000000,
        'invest_2000000' => 2000000,
        'invest_3000000' => 3000000,
    ];
}

/**
 * @return array<string, int>
 */
function franchises_selection_profit_thresholds(): array
{
    return [
        'profit_30000'  => 30000,
        'profit_100000' => 100000,
        'profit_300000' => 300000,
        'profit_500000' => 500000,
    ];
}

function franchises_selection_threshold_for_type(string $type): ?int
{
    $invest = franchises_selection_invest_thresholds();
    if (array_key_exists($type, $invest)) {
        return $invest[$type];
    }

    $profit = franchises_selection_profit_thresholds();
    if (array_key_exists($type, $profit)) {
        return $profit[$type];
    }

    return null;
}

function franchises_selection_is_invest_type(string $type): bool
{
    return $type === 'low_invest' || array_key_exists($type, franchises_selection_invest_thresholds());
}

function franchises_selection_is_profit_type(string $type): bool
{
    return array_key_exists($type, franchises_selection_profit_thresholds());
}

/**
 * Готовые типы подборок (заголовок в админке → выберите соответствующий тип).
 *
 * @return array<string, string>
 */
function franchises_selection_filter_choices(): array
{
    $choices = [
        'popular'    => 'Популярные — франшизы с наибольшим числом просмотров страниц',
        'no_pausal'  => 'Франшизы без взноса - паушальный взнос 0 или не указан',
        'payback_12' => 'Окупаемость до 12 мес. - хотя бы одно поле окупаемости ≤ 12',
        'no_royalty' => 'Без роялти - роялти не указано или «нет»',
        'low_invest' => 'Недорогие старты - инвестиции до 500 000 ₽',
    ];

    foreach (franchises_selection_invest_thresholds() as $type => $threshold) {
        $choices[$type] = 'Инвестиции до ' . franchises_format_money_ru($threshold);
    }

    foreach (franchises_selection_profit_thresholds() as $type => $threshold) {
        $choices[$type] = 'Прибыль от ' . franchises_format_money_ru($threshold) . '/мес';
    }

    $choices['manual'] = 'кастомные списки';

    return $choices;
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
        'popular'          => ['populyarn', 'popular', 'trend'],
        'no_pausal'        => ['bez-vznos', 'bez-pausal', 'no-pausal', 'bez-paus'],
        'payback_12'       => ['12-mes', 'okupaemost-12', 'payback-12'],
        'no_royalty'       => ['bez-royalti', 'no-royalty', 'bez-roialti'],
        'low_invest'       => ['nedorog', 'low-invest', 'deshevy', 'budget'],
        'invest_100000'    => ['do-100-000', 'do-100000', 'invest-do-100000'],
        'invest_300000'    => ['do-300-000', 'do-300000', 'invest-do-300000'],
        'invest_500000'    => ['do-500-000', 'do-500000', 'invest-do-500000'],
        'invest_1000000'   => ['do-1-mln', 'do-1000000', 'do-1-000-000', 'invest-do-1000000'],
        'invest_2000000'   => ['do-2-mln', 'do-2000000', 'do-2-000-000', 'invest-do-2000000'],
        'invest_3000000'   => ['do-3-mln', 'do-3000000', 'do-3-000-000', 'invest-do-3000000'],
        'profit_30000'     => ['ot-30-000', 'ot-30000', 'pribyl-ot-30000', 'profit-from-30000'],
        'profit_100000'    => ['ot-100-000', 'ot-100000', 'pribyl-ot-100000', 'profit-from-100000'],
        'profit_300000'    => ['ot-300-000', 'ot-300000', 'pribyl-ot-300000', 'profit-from-300000'],
        'profit_500000'    => ['ot-500-000', 'ot-500000', 'pribyl-ot-500000', 'profit-from-500000'],
        'manual'           => ['pod-klyuch', 'pod-kluch', 'turnkey', 'top-2026', 'top-franshiz', 'top-202'],
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
    if (in_array($type, ['manual', 'popular', 'no_royalty', 'no_pausal', 'payback_12'], true)) {
        return true;
    }

    return franchises_selection_is_invest_type($type) || franchises_selection_is_profit_type($type);
}

/**
 * @param list<int> $exclude_ids
 * @return list<int>
 */
function franchises_get_top_viewed_product_ids(int $max = 12, array $exclude_ids = []): array
{
    $max = max(1, $max);
    $exclude_ids = array_values(array_filter(array_map('intval', $exclude_ids)));

    $query_args = [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => $max + count($exclude_ids),
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'fields'              => 'ids',
        'meta_key'            => 'franchises_theme_post_views',
        'orderby'             => [
            'meta_value_num' => 'DESC',
            'date'           => 'DESC',
        ],
    ];

    if ($exclude_ids !== []) {
        $query_args['post__not_in'] = $exclude_ids;
    }

    $query = new WP_Query($query_args);

    return array_slice(array_values(array_map('intval', $query->posts)), 0, $max);
}

/**
 * @return list<array{id: int, position: int, order: int}>
 */
function franchises_selection_popular_pins(int $selection_id): array
{
    if ($selection_id <= 0 || ! function_exists('get_field')) {
        return [];
    }

    $raw = get_field('selection_popular_pins', $selection_id);
    if (! is_array($raw)) {
        return [];
    }

    $pins = [];
    $seen_ids = [];
    $order = 0;

    foreach ($raw as $row) {
        if (! is_array($row)) {
            continue;
        }

        $franchise = $row['franchise'] ?? null;
        $id = 0;
        if ($franchise instanceof WP_Post) {
            $id = (int) $franchise->ID;
        } elseif (is_numeric($franchise)) {
            $id = (int) $franchise;
        } elseif (is_array($franchise) && isset($franchise['ID'])) {
            $id = (int) $franchise['ID'];
        }

        $position = isset($row['position']) ? max(1, (int) $row['position']) : 0;
        if ($id <= 0 || $position <= 0 || isset($seen_ids[$id])) {
            continue;
        }

        $post = get_post($id);
        if (! $post instanceof WP_Post || $post->post_type !== 'product' || $post->post_status !== 'publish') {
            continue;
        }

        $seen_ids[$id] = true;
        $pins[] = [
            'id'       => $id,
            'position' => $position,
            'order'    => $order,
        ];
        $order++;
    }

    return $pins;
}

/**
 * @param list<array{id: int, position: int, order: int}> $pins
 * @return array<int, int>
 */
function franchises_selection_resolve_popular_pin_slots(array $pins, int $max): array
{
    $max = max(1, $max);
    if ($pins === []) {
        return [];
    }

    usort($pins, static function (array $a, array $b): int {
        if ($a['position'] !== $b['position']) {
            return $a['position'] <=> $b['position'];
        }

        return $a['order'] <=> $b['order'];
    });

    $slots = [];
    $used_positions = [];

    foreach ($pins as $pin) {
        $position = max(1, (int) $pin['position']);
        while (isset($used_positions[$position]) && $position <= $max) {
            $position++;
        }
        if ($position > $max) {
            continue;
        }

        $used_positions[$position] = true;
        $slots[$position] = (int) $pin['id'];
    }

    return $slots;
}

/**
 * @return list<int>
 */
function franchises_selection_merge_popular_ids(int $selection_id, int $max = 500): array
{
    $max = max(1, $max);
    $pins = franchises_selection_popular_pins($selection_id);
    $pinned_ids = array_map(static fn(array $pin): int => (int) $pin['id'], $pins);
    $slots = franchises_selection_resolve_popular_pin_slots($pins, $max);
    $auto = franchises_get_top_viewed_product_ids($max + count($pinned_ids), $pinned_ids);
    $auto_index = 0;
    $result = [];
    $used = [];

    for ($slot = 1; $slot <= $max; $slot++) {
        if (isset($slots[$slot])) {
            $id = (int) $slots[$slot];
            if (! in_array($id, $used, true)) {
                $result[] = $id;
                $used[] = $id;
            }
            continue;
        }

        while ($auto_index < count($auto) && in_array($auto[$auto_index], $used, true)) {
            $auto_index++;
        }
        if ($auto_index >= count($auto)) {
            break;
        }

        $result[] = (int) $auto[$auto_index];
        $used[] = (int) $auto[$auto_index];
        $auto_index++;
    }

    return $result;
}

/**
 * @return list<int>
 */
function franchises_selection_popular_product_ids(int $selection_id, int $max = 500): array
{
    return franchises_selection_merge_popular_ids($selection_id, $max);
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

function franchises_product_matches_selection_type(int $product_id, string $type, int $selection_id = 0): bool
{
    if ($type === 'low_invest') {
        return franchises_product_investment_within_max($product_id, franchises_selection_low_invest_max());
    }

    if (franchises_selection_is_invest_type($type)) {
        $threshold = franchises_selection_threshold_for_type($type);

        return $threshold !== null
            && franchises_product_investment_within_max($product_id, $threshold);
    }

    if (franchises_selection_is_profit_type($type)) {
        $threshold = franchises_selection_threshold_for_type($type);

        return $threshold !== null
            && franchises_product_monthly_profit_from($product_id, $threshold);
    }

    switch ($type) {
        case 'no_pausal':
            return franchises_product_has_no_pausal($product_id);
        case 'payback_12':
            return franchises_product_payback_within($product_id, 12);
        case 'no_royalty':
            return franchises_product_has_no_royalty($product_id);
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
    } elseif ($type === 'popular') {
        $popular_ids = franchises_selection_merge_popular_ids($selection_id, 500);
        $args['post__in'] = $popular_ids !== [] ? $popular_ids : [0];
        $args['orderby'] = 'post__in';
    } else {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
    }

    return array_merge($args, $overrides);
}

/**
 * @param list<int> $ids
 * @return list<int>
 */
function franchises_selection_valid_product_ids(array $ids): array
{
    $out = [];
    foreach ($ids as $raw_id) {
        $id = (int) $raw_id;
        if ($id <= 0) {
            continue;
        }
        $post = get_post($id);
        if ($post instanceof WP_Post && $post->post_type === 'product' && $post->post_status === 'publish') {
            $out[] = $id;
        }
    }

    return array_values(array_unique($out));
}

/**
 * @return list<int>
 */
function franchises_selection_product_ids(int $selection_id, int $max = 500): array
{
    $max = max(1, $max);
    $type = franchises_selection_filter_type($selection_id);

    if ($type === 'manual') {
        return array_slice(
            franchises_selection_valid_product_ids(franchises_selection_manual_product_ids($selection_id)),
            0,
            $max
        );
    }

    if ($type === 'popular') {
        return franchises_selection_valid_product_ids(
            franchises_selection_merge_popular_ids($selection_id, $max)
        );
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

    return franchises_selection_valid_product_ids($ids);
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
                'instructions'  => '«Популярные» — автоматически по просмотрам страниц; ниже можно закрепить франшизы на конкретных позициях. Кастомные подборки — тип «Вручную» и список франшиз. Миниатюра записи — картинка в hero.',
            ],
            [
                'key'               => 'field_selection_popular_pins',
                'label'             => 'Закреплённые франшизы',
                'name'              => 'selection_popular_pins',
                'type'              => 'repeater',
                'layout'            => 'table',
                'button_label'      => 'Добавить франшизу',
                'instructions'      => 'Укажите позицию в списке (1 — первое место). При совпадении позиций первая строка занимает слот, следующая сдвигается на ближайшую свободную.',
                'conditional_logic' => [[[
                    'field'    => 'field_selection_filter_type',
                    'operator' => '==',
                    'value'    => 'popular',
                ]]],
                'sub_fields'        => [
                    [
                        'key'           => 'field_selection_popular_pin_franchise',
                        'label'         => 'Франшиза',
                        'name'          => 'franchise',
                        'type'          => 'post_object',
                        'post_type'     => ['product'],
                        'return_format' => 'id',
                        'ui'            => 1,
                    ],
                    [
                        'key'      => 'field_selection_popular_pin_position',
                        'label'    => 'Позиция',
                        'name'     => 'position',
                        'type'     => 'number',
                        'min'      => 1,
                        'step'     => 1,
                        'required' => 1,
                    ],
                ],
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
