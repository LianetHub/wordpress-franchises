<?php

/**
 * The main template file
 */
get_header(); ?>


<div class="typography-block">
    <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post();
            the_content();
        endwhile;
    endif;
    ?>
</div>


<?php get_footer(); ?>