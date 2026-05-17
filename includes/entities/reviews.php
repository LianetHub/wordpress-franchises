<?php

defined('ABSPATH') || exit;

/**
 * @return non-empty-string
 */
function franchises_review_post_type(): string
{
    return 'review';
}

function theme_register_reviews(): void
{
    register_post_type(franchises_review_post_type(), [
        'label'               => 'Отзывы',
        'labels'              => [
            'name'          => 'Отзывы',
            'singular_name' => 'Отзыв',
            'add_new'       => 'Добавить отзыв',
            'add_new_item'  => 'Добавить отзыв',
            'edit_item'     => 'Редактировать отзыв',
            'new_item'      => 'Новый отзыв',
            'view_item'     => 'Просмотр отзыва',
            'search_items'  => 'Найти отзывы',
            'not_found'     => 'Отзывов не найдено',
            'menu_name'     => 'Отзывы',
        ],
        'public'              => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 27,
        'menu_icon'           => 'dashicons-format-quote',
        'supports'            => ['title', 'thumbnail', 'page-attributes'],
        'show_in_rest'        => true,
        'capability_type'     => 'post',
    ]);
}

/**
 * @param mixed $image_data
 */
function franchises_review_acf_image_url($image_data): string
{
    if (is_array($image_data) && ! empty($image_data['url'])) {
        return (string) $image_data['url'];
    }
    if (is_numeric($image_data)) {
        $url = wp_get_attachment_url((int) $image_data);

        return $url ? (string) $url : '';
    }
    if (is_string($image_data) && $image_data !== '') {
        return $image_data;
    }

    return '';
}

/**
 * ID товара WooCommerce из поля review_franchise.
 */
function franchises_review_franchise_product_id(int $post_id): int
{
    if (! function_exists('get_field')) {
        return 0;
    }

    $product_raw = get_field('review_franchise', $post_id);
    $product_id = 0;
    if (is_object($product_raw) && isset($product_raw->ID)) {
        $product_id = (int) $product_raw->ID;
    } elseif (is_numeric($product_raw)) {
        $product_id = (int) $product_raw;
    } elseif (is_array($product_raw) && isset($product_raw['ID'])) {
        $product_id = (int) $product_raw['ID'];
    }

    if ($product_id <= 0 || get_post_type($product_id) !== 'product') {
        return 0;
    }

    return $product_id;
}

/**
 * Название, ссылка и лого — только из выбранной франшизы (товара).
 * Название в ссылке — заголовок товара (post_title), не ACF product_full_title (H1).
 * Лого — миниатюра товара (thumbnail).
 *
 * @return array{url: string, name: string, logo_url: string}
 */
function franchises_review_franchise_data(int $post_id): array
{
    $empty = [
        'url'      => '',
        'name'     => '',
        'logo_url' => '',
    ];

    $product_id = franchises_review_franchise_product_id($post_id);
    if ($product_id <= 0) {
        return $empty;
    }

    $name = get_the_title($product_id);
    $thumb = '';

    if (function_exists('wc_get_product')) {
        $product = wc_get_product($product_id);
        if ($product) {
            $name = $product->get_name();
            $image_id = $product->get_image_id();
            if ($image_id) {
                $thumb_url = wp_get_attachment_image_url((int) $image_id, 'thumbnail');
                $thumb = $thumb_url ? (string) $thumb_url : '';
            }
        }
    }

    if ($thumb === '') {
        $fallback_thumb = get_the_post_thumbnail_url($product_id, 'thumbnail');
        $thumb = $fallback_thumb ? (string) $fallback_thumb : '';
    }

    $permalink = get_permalink($product_id);

    return [
        'url'      => ($permalink && ! is_wp_error($permalink)) ? (string) $permalink : '',
        'name'     => trim((string) $name),
        'logo_url' => $thumb ? (string) $thumb : '',
    ];
}

function franchises_review_display_name(string $name, string $city): string
{
    if ($name !== '' && $city !== '') {
        return $name . ', ' . $city;
    }

    return $name !== '' ? $name : $city;
}

/**
 * Первая буква имени (для аватара-заглушки).
 */
function franchises_review_name_initial(string $name): string
{
    $name = trim(html_entity_decode(wp_strip_all_tags($name), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($name === '') {
        return '';
    }

    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    }

    return strtoupper(substr($name, 0, 1));
}

/**
 * Имя для буквы-аватара: review_name или часть до запятой в display_name.
 */
function franchises_review_resolve_author_initial(string $review_name, string $display_name): string
{
    $source = trim($review_name);
    if ($source === '' && $display_name !== '') {
        $source = trim(html_entity_decode(wp_strip_all_tags($display_name), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('/^([^,，]+)/u', $source, $matches)) {
            $source = trim((string) $matches[1]);
        }
    }

    return franchises_review_name_initial($source);
}

function franchises_review_has_author_photo(string $photo_url): bool
{
    $photo_url = trim($photo_url);
    if ($photo_url === '') {
        return false;
    }

    return (bool) preg_match('#\Ahttps?://#i', $photo_url) || strpos($photo_url, '/') === 0;
}

/**
 * @return array<string, mixed>
 */
function franchises_review_card_from_post(int $post_id): array
{
    $review_name = function_exists('get_field') ? trim((string) get_field('review_name', $post_id)) : '';
    $review_city = function_exists('get_field') ? trim((string) get_field('review_city', $post_id)) : '';

    $display_name = franchises_review_display_name($review_name, $review_city);
    if ($display_name === '') {
        $display_name = get_the_title($post_id);
    }

    $review_meta = function_exists('get_field') ? trim((string) get_field('review_meta', $post_id)) : '';
    $review_text = function_exists('get_field') ? trim((string) get_field('review_text', $post_id)) : '';
    if ($review_text === '') {
        $review_text = trim(wp_strip_all_tags((string) get_post_field('post_content', $post_id)));
    }

    // Только ACF «Фото автора»; миниатюра записи отзыва не подставляется.
    $photo_url = '';
    if (function_exists('get_field')) {
        $photo_url = franchises_review_acf_image_url(get_field('review_photo', $post_id));
    }

    $has_author_photo = franchises_review_has_author_photo($photo_url);
    $author_initial = $has_author_photo
        ? ''
        : franchises_review_resolve_author_initial($review_name, $display_name);

    return [
        'name'            => $review_name,
        'city'            => $review_city,
        'display_name'    => $display_name,
        'meta'            => $review_meta,
        'text'            => $review_text,
        'photo_url'       => $has_author_photo ? $photo_url : '',
        'author_initial'  => $author_initial,
        'franchise'       => franchises_review_franchise_data($post_id),
    ];
}

/**
 * @return array<string, mixed>
 */
function franchises_home_reviews_query_args(): array
{
    $limit = -1;
    if (function_exists('get_field')) {
        $acf_limit = get_field('reviews_limit');
        if (is_numeric($acf_limit) && (int) $acf_limit > 0) {
            $limit = (int) $acf_limit;
        }
    }

    return [
        'post_type'           => franchises_review_post_type(),
        'post_status'         => 'publish',
        'posts_per_page'      => $limit,
        'orderby'             => [
            'menu_order' => 'ASC',
            'date'       => 'DESC',
        ],
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ];
}

/**
 * @return WP_Post[]
 */
function franchises_get_home_reviews(): array
{
    $query = new WP_Query(franchises_home_reviews_query_args());
    $posts = $query->posts;
    wp_reset_postdata();

    return is_array($posts) ? $posts : [];
}

/**
 * @param array<string, mixed> $card
 */
function franchises_review_card_is_visible(array $card): bool
{
    if (trim((string) ($card['display_name'] ?? '')) !== '') {
        return true;
    }
    if (trim((string) ($card['meta'] ?? '')) !== '') {
        return true;
    }
    if (trim((string) ($card['text'] ?? '')) !== '') {
        return true;
    }
    if (trim((string) ($card['photo_url'] ?? '')) !== '') {
        return true;
    }

    $franchise = isset($card['franchise']) && is_array($card['franchise']) ? $card['franchise'] : [];

    return trim((string) ($franchise['name'] ?? '')) !== ''
        || trim((string) ($franchise['url'] ?? '')) !== '';
}

/**
 * @param array<string, mixed> $review_card
 */
function franchises_render_review_card(array $review_card): void
{
    if (! franchises_review_card_is_visible($review_card)) {
        return;
    }

    $template = get_template_directory() . '/templates/components/review-card.php';
    if (! is_readable($template)) {
        return;
    }

    include $template;
}

/**
 * @param array<int, string> $messages
 */
function franchises_reviews_debug_html(array $messages): string
{
    if (! (defined('WP_DEBUG') && WP_DEBUG) || ! current_user_can('edit_posts')) {
        return '';
    }

    $lines = array_filter(array_map('strval', $messages));
    if ($lines === []) {
        return '';
    }

    $body = esc_html(implode(' ', $lines));

    return '<p class="reviews-debug" style="margin:12px 0 0;padding:12px 14px;background:#fff8e6;border:1px solid #f0d78c;border-radius:8px;font-size:13px;color:#5c4a12;">'
        . '<strong>Reviews debug:</strong> '
        . $body
        . '</p>';
}
