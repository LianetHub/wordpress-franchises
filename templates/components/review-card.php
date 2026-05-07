<?php
$post_id = $args['id'];
$extra_class = !empty($args['class']) ? ' ' . esc_attr($args['class']) : '';

$text = get_field('text', $post_id);
$link_field = get_field('review_link', $post_id);
$link = is_array($link_field) ? $link_field['url'] : $link_field;
$stars = get_field('review_stars', $post_id) ?: 5;
$author = get_the_title($post_id);
$avatar = get_the_post_thumbnail_url($post_id, 'thumbnail');

$first_letter = mb_substr($author, 0, 1);

$tag = $link ? 'a' : 'div';
$attrs = $link ? 'href="' . esc_url($link) . '" target="_blank"' : '';
$bg_style = $avatar ? 'style="background-image: url(' . esc_url($avatar) . ');"' : '';
?>

<div class="review-card<?php echo $extra_class; ?>">
    <div class="review-card__stars">
        <?php
        for ($i = 1; $i <= 5; $i++):
            $star_color = ($i <= (int)$stars) ? '#F4B942' : '#FBE7BD';
        ?>
            <svg width="24" height="24" viewBox="0 0 11 11" fill="<?php echo $star_color; ?>">
                <path d="M5.5 0L6.73482 3.79321H10.7246L7.4949 6.13858L8.72972 9.93179L5.5 7.58642L2.27028 9.93179L3.5051 6.13858L0.275386 3.79321H4.26518L5.5 0Z" />
            </svg>
        <?php endfor; ?>
    </div>

    <blockquote class="review-card__text">
        <?php echo esc_html($text); ?>
    </blockquote>

    <div class="review-card__footer">
        <<?php echo $tag; ?> <?php echo $attrs; ?>
            class="review-card__avatar <?php echo !$avatar ? 'review-card__avatar--letter' : ''; ?>"
            <?php echo $bg_style; ?>>
            <?php echo !$avatar ? esc_html($first_letter) : ''; ?>
        </<?php echo $tag; ?>>

        <div class="review-card__author">
            <h4 class="review-card__author-name">
                <?php if ($link): ?>
                    <a href="<?php echo esc_url($link); ?>" target="_blank" class="review-card__author-link">
                        <?php echo esc_html($author); ?>
                    </a>
                <?php else: ?>
                    <?php echo esc_html($author); ?>
                <?php endif; ?>
            </h4>
            <span class="review-card__author-source">Источник: Яндекс</span>
        </div>
    </div>
</div>