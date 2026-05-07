<div class="posts__card">
    <a href="<?php the_permalink(); ?>" class="posts__card-image">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('full'); ?>
        <?php else : ?>
            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/catalog-card-placeholder.svg"
                alt="Нет фото"
                loading="lazy"
                class="cover-image" />
        <?php endif; ?>
    </a>
    <div class="posts__card-body">
        <div class="posts__card-header">
            <a href="<?php the_permalink(); ?>" class="posts__card-name"><?php the_title(); ?></a>
            <time datetime="<?php the_time('c'); ?>"
                class="posts__card-time icon-calendar">
                <?php the_time('d.m.Y'); ?>
            </time>
        </div>
        <p class="posts__card-desc">
            <?php
            $short_desc = get_field('short_desc');
            if ($short_desc) {
                echo wp_trim_words($short_desc, 12, '...');
            } else {
                echo wp_trim_words(get_the_excerpt(), 12, '...');
            }
            ?>
        </p>
        <a href="<?php the_permalink(); ?>" class="posts__card-more">Читать полностью</a>
    </div>
</div>