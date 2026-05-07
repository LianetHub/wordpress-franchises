<?php get_header(); ?>

<?php require_once(TEMPLATE_PATH . '/components/breadcrumbs.php'); ?>

<section class="posts">
    <div class="container">
        <div class="posts__hint hint">Полезные материалы</div>
        <h1 class="posts__title title">Блог</h1>
        <p class="posts__subtitle subtitle">Делимся профессиональными секретами клининга, советами по уходу за домом и кейсами по спасению имущества после сложных загрязнений.</p>

        <div class="posts__body">
            <div class="posts__grid"> <?php if (have_posts()) : ?>
                    <?php while (have_posts()) : the_post(); ?>
                        <?php get_template_part('templates/components/blog-card'); ?>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p>Постов не найдено</p>
                <?php endif; ?>
            </div>

            <?php
            the_posts_pagination([
                'prev_text' => '<span class="screen-reader-text">Предыдущая</span>',
                'next_text' => '<span class="screen-reader-text">Следующая</span>',
            ]);
            ?>
        </div>
    </div>
</section>

<?php get_footer(); ?>