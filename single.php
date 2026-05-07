<?php get_header(); ?>

<?php require_once(TEMPLATE_PATH . '/components/breadcrumbs.php'); ?>

<?php if (have_posts()) :
    while (have_posts()) :
        the_post();
        $reading_time = hard_cleaning_theme_reading_time(get_the_ID()) . ' мин. читать';

        $checker = get_field('post_checker');
?>
        <section class="heading">
            <div class="heading__container container">
                <div class="heading__offer">
                    <div class="heading__stats">
                        <time
                            datetime="<?php the_time('c'); ?>"
                            class="heading__stat icon-calendar">
                            <?php the_time('d.m.Y'); ?>
                        </time>
                        <div
                            class="heading__stat icon-clock">
                            <?php echo $reading_time; ?>
                        </div>
                        <div
                            class="heading__stat icon-like">
                            <?php echo (int) get_post_meta(get_the_ID(), 'hard_cleaning_theme_likes', true); ?>
                        </div>
                        <div
                            class="heading__stat icon-eye">
                            <?php echo hard_cleaning_theme_get_post_views(get_the_ID()); ?>
                        </div>
                    </div>

                    <?php if (get_the_title()) : ?>
                        <h1 class="heading__title title">
                            <?php the_title(); ?>
                        </h1>
                    <?php endif; ?>

                    <?php if (has_excerpt()) : ?>
                        <div class="heading__subtitle subtitle">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (has_post_thumbnail()) : ?>
                    <div class="heading__image">
                        <?php
                        the_post_thumbnail('full', [
                            'width'  => false,
                            'height' => false,
                            'class'  => 'cover-image'
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <section data-post-id="<?php echo get_the_ID(); ?>" class="article">
            <div class="container">
                <div class="article__wrapper">
                    <article class="article__content typography-block">
                        <?php the_content(); ?>
                    </article>

                    <?php if ($checker) :
                        $checker_id = $checker->ID;
                        $checker_name = get_the_title($checker_id);
                        $checker_position = get_field('position', $checker_id);
                        $checker_img = get_the_post_thumbnail_url($checker_id, 'thumbnail');
                    ?>
                        <div class="article__checker checker-block">
                            <div class="checker-block__label">Статью проверил:</div>
                            <div class="checker-block__content">
                                <?php if ($checker_img) : ?>
                                    <div class="checker-block__avatar">
                                        <img src="<?php echo $checker_img; ?>" alt="<?php echo $checker_name; ?>">
                                    </div>
                                <?php endif; ?>
                                <div class="checker-block__info">
                                    <div class="checker-block__name"><?php echo $checker_name; ?></div>
                                    <?php if ($checker_position) : ?>
                                        <div class="checker-block__position"><?php echo $checker_position; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="article__footer">
                    <div class="article__likes">
                        <div class="article__likes-text">Статья была полезной?</div>
                        <div class="article__likes-btns">
                            <button type="button" class="article__likes-btn article-use__yes icon-like">Да</button>
                            <button type="button" class="article__likes-btn article-use__no icon-dislike">Нет</button>
                        </div>
                    </div>
                    <div class="article__bottom">

                        <button
                            type="button"
                            class="article__copy icon-copy"
                            data-url="<?php the_permalink(); ?>">Скопировать</button>

                        <?php
                        $share_url   = urlencode(get_permalink());
                        $share_title = urlencode(get_the_title());
                        ?>
                        <div class="article__socials socials">
                            <a href="https://api.whatsapp.com/send?text=<?php echo $share_title . ' ' . $share_url; ?>"
                                class="socials__item"
                                target="_blank"
                                rel="nofollow">
                                <img
                                    src="<?php echo get_template_directory_uri(); ?>/assets/img/icons/whatsapp.svg"
                                    alt="Поделиться в WhatsApp">
                            </a>
                            <a href="https://t.me/share/url?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
                                class="socials__item"
                                target="_blank"
                                rel="nofollow">
                                <img
                                    src="<?php echo get_template_directory_uri(); ?>/assets/img/icons/telegram.svg"
                                    alt="Поделиться в Telegram">
                            </a>
                            <a href="https://vk.com/share.php?url=<?php echo $share_url; ?>" class="socials__item"
                                target="_blank"
                                rel="nofollow">
                                <img
                                    src="<?php echo get_template_directory_uri(); ?>/assets/img/icons/vk.svg"
                                    alt="Поделиться в VK">
                            </a>
                            <a href="https://connect.ok.ru/offer?url=<?php echo $share_url; ?>&title=<?php echo $share_title; ?>"
                                class="socials__item"
                                target="_blank"
                                rel="nofollow">
                                <img
                                    src="<?php echo get_template_directory_uri(); ?>/assets/img/icons/ok.svg"
                                    alt="Поделиться в OK">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<?php
    endwhile;
endif; ?>

<?php

$current_post_id = get_the_ID();
$args = array(
    'post_type' => 'post',
    'posts_per_page' => 3,
    'post__not_in' => array($current_post_id),
);

$query = new WP_Query($args);

if ($query->have_posts()) :
?>
    <section class="posts">
        <div class="container">
            <h2 class="posts__title title">Другие статьи</h2>
            <div class="posts__body">
                <div class="posts__grid">
                    <?php
                    while ($query->have_posts()) : $query->the_post();
                    ?>
                        <?php get_template_part('templates/components/blog-card'); ?>
                    <?php
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>


<?php get_footer(); ?>