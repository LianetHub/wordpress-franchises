<?php

/**
 * Блок «Отзывы» на главной.
 *
 * Заголовки — ACF страницы «Главная» (reviews_title, reviews_subtitle, reviews_limit).
 * Карточки — CPT review + ACF полей записи.
 */

defined('ABSPATH') || exit;

if (! function_exists('franchises_get_home_reviews')) {
    return;
}

$reviews_posts = franchises_get_home_reviews();

if ($reviews_posts === []) {
    return;
}

$reviews_cards_html = '';

foreach ($reviews_posts as $review_post) {
    if (! $review_post instanceof WP_Post) {
        continue;
    }

    $review_card = franchises_review_card_from_post((int) $review_post->ID);

    if (! franchises_review_card_is_visible($review_card)) {
        continue;
    }

    ob_start();
    franchises_render_review_card($review_card);
    $reviews_cards_html .= (string) ob_get_clean();
}

if ($reviews_cards_html === '') {
    return;
}

$reviews_title = function_exists('get_field') ? get_field('reviews_title') : null;
$reviews_subtitle = function_exists('get_field') ? get_field('reviews_subtitle') : null;

if ($reviews_title === '' || $reviews_title === null) {
    $reviews_title = 'Отзывы предпринимателей, которые уже запустили бизнес';
}
if ($reviews_subtitle === '' || $reviews_subtitle === null) {
    $reviews_subtitle = 'Опыт запуска реальных людей, их результаты и выводы.';
}

?>
<section class="reviews-section" aria-label="Отзывы">
    <h2 class="segment-title"><?php echo esc_html((string) $reviews_title); ?></h2>
    <?php if ($reviews_subtitle !== '') : ?>
        <p class="segment-sub"><?php echo esc_html((string) $reviews_subtitle); ?></p>
    <?php endif; ?>
    <div class="reviews-wrap">
        <button class="reviews-arrow prev" type="button" aria-label="Предыдущий отзыв"></button>
        <div class="reviews-strip swiper">
            <div class="swiper-wrapper">
                <?php echo $reviews_cards_html; ?>
            </div>
        </div>
        <button class="reviews-arrow next" type="button" aria-label="Следующий отзыв"></button>
    </div>
    <div class="reviews-dots swiper-pagination" aria-label="Пагинация отзывов"></div>
</section>