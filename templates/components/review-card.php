<?php

/**
 * Карточка отзыва (слайдер на главной).
 *
 * franchises_render_review_card( $card );
 * get_template_part( ..., null, [ 'review_card' => $card ] );
 */

defined('ABSPATH') || exit;

if (! isset($review_card) || ! is_array($review_card)) {
    $review_card = [];
}

if ($review_card === [] && isset($args) && is_array($args) && isset($args['review_card']) && is_array($args['review_card'])) {
    $review_card = $args['review_card'];
}

if ($review_card === [] || ! function_exists('franchises_review_card_is_visible') || ! franchises_review_card_is_visible($review_card)) {
    return;
}

$display_name = isset($review_card['display_name']) ? trim((string) $review_card['display_name']) : '';
if ($display_name === '' && function_exists('franchises_review_display_name')) {
    $first_name = isset($review_card['name']) ? trim((string) $review_card['name']) : '';
    $city = isset($review_card['city']) ? trim((string) $review_card['city']) : '';
    $display_name = franchises_review_display_name($first_name, $city);
}
$meta = isset($review_card['meta']) ? trim((string) $review_card['meta']) : '';
$text = isset($review_card['text']) ? trim((string) $review_card['text']) : '';
$author_name = isset($review_card['name']) ? trim((string) $review_card['name']) : '';
$photo_url = isset($review_card['photo_url']) ? trim((string) $review_card['photo_url']) : '';
$author_initial = isset($review_card['author_initial']) ? trim((string) $review_card['author_initial']) : '';

if ($author_initial === '' && $photo_url === '' && function_exists('franchises_review_resolve_author_initial')) {
    $author_initial = franchises_review_resolve_author_initial($author_name, $display_name);
}

$has_author_photo = function_exists('franchises_review_has_author_photo')
    ? franchises_review_has_author_photo($photo_url)
    : ($photo_url !== '');

$franchise = isset($review_card['franchise']) && is_array($review_card['franchise']) ? $review_card['franchise'] : [];
$franchise_name = isset($franchise['name']) ? trim((string) $franchise['name']) : '';
$franchise_url = isset($franchise['url']) ? trim((string) $franchise['url']) : '';
$franchise_logo = isset($franchise['logo_url']) ? trim((string) $franchise['logo_url']) : '';

$photo_alt = $display_name !== '' ? $display_name : $author_name;
?>
<article class="review-card swiper-slide">
    <div class="review-media<?php echo ! $has_author_photo && $author_initial !== '' ? ' review-media--initial' : ''; ?>">
        <?php if ($has_author_photo) : ?>
            <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($photo_alt); ?>" width="72" height="72" loading="lazy" decoding="async">
        <?php elseif ($author_initial !== '') : ?>
            <span class="review-media__initial" aria-hidden="true"><?php echo esc_html($author_initial); ?></span>
        <?php endif; ?>
    </div>
    <div class="review-head">
        <?php if ($display_name !== '') : ?>
            <div class="review-name"><?php echo esc_html($display_name); ?></div>
        <?php endif; ?>
        <?php if ($meta !== '') : ?>
            <div class="review-meta"><?php echo esc_html($meta); ?></div>
        <?php endif; ?>
    </div>
    <?php if ($text !== '') : ?>
        <div class="review-text"><?php echo esc_html($text); ?></div>
    <?php endif; ?>
    <?php if ($franchise_name !== '' || $franchise_url !== '') : ?>
        <div class="review-franchise">
            <?php if ($franchise_logo !== '') : ?>
                <div class="review-logo">
                    <img src="<?php echo esc_url($franchise_logo); ?>" alt="<?php echo esc_attr($franchise_name); ?>" width="36" height="36" loading="lazy" decoding="async">
                </div>
            <?php endif; ?>
            <?php if ($franchise_name !== '' && $franchise_url !== '') : ?>
                <a class="review-link" href="<?php echo esc_url($franchise_url); ?>"><?php echo esc_html($franchise_name); ?></a>
            <?php elseif ($franchise_name !== '') : ?>
                <span class="review-link"><?php echo esc_html($franchise_name); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</article>