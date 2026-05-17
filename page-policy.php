<?php

/**
 * Template Name: Page Policy
 */
get_header(); ?>

<?php require_once(TEMPLATE_PATH . '/components/breadcrumbs.php'); ?>

<section class="heading">
    <div class="heading__container container">
        <div class="heading__offer">
            <h1 class="heading__title title">
                <?php the_title(); ?>
            </h1>
        </div>
    </div>
</section>
<?php if (!empty(get_the_content())): ?>
    <article class="article typography-block">
        <?php the_content(); ?>
    </article>
<?php endif; ?>

<?php get_footer(); ?>