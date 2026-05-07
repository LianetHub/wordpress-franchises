<?php

/**
 * Template Name: Page Policy
 */
get_header(); ?>

<?php require_once(TEMPLATE_PATH . '/components/breadcrumbs.php'); ?>

<section class="heading">
    <div class="heading__container container">
        <div class="heading__offer">
            <h1 class="heading__title title-lg">
                <?php the_title(); ?>
            </h1>
        </div>
    </div>
</section>
<?php if (!empty(get_the_content())): ?>
    <article class="article">
        <div class="container">
            <div class="article__content typography-block">
                <?php the_content(); ?>
            </div>
        </div>
    </article>
<?php endif; ?>

<?php get_footer(); ?>