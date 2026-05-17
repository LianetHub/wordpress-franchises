<?php

/**
 * Разметка каталога франшиз (как catalog.html) внутри <main class="wrap catalog-page">.
 */

defined('ABSPATH') || exit;

if (! function_exists('wc_get_page_id')) {
    return;
}

$shop_url = get_permalink(wc_get_page_id('shop')) ?: home_url('/');

$cur_sphere = isset($_GET['sphere']) ? sanitize_text_field(wp_unslash($_GET['sphere'])) : '';
$cur_category = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';
$cur_verified = ! empty($_GET['verified']);
$cur_invest_max = isset($_GET['invest_max']) ? max(0, (int) $_GET['invest_max']) : 0;
$cur_profit_min = isset($_GET['profit_min']) ? max(0, (int) $_GET['profit_min']) : 0;
$cur_payback_max = isset($_GET['payback_max']) ? max(0, (int) $_GET['payback_max']) : 0;

if (is_product_category()) {
    $term = get_queried_object();
    if ($term instanceof WP_Term && $term->taxonomy === 'product_cat') {
        if ((int) $term->parent > 0) {
            $parent = get_term((int) $term->parent, 'product_cat');
            if ($parent && ! is_wp_error($parent)) {
                $cur_sphere = (string) $parent->name;
            }
            $cur_category = (string) $term->name;
        } else {
            $cur_sphere = (string) $term->name;
            $cur_category = '';
        }
    }
}

$default_orderby = function_exists('get_option') ? get_option('woocommerce_default_catalog_orderby', 'menu_order') : 'menu_order';
$cur_orderby = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : $default_orderby;

$form_action = $shop_url;
if (function_exists('franchises_catalog_url_for_selection')) {
    $form_action = franchises_catalog_url_for_selection($cur_sphere, $cur_category);
} elseif (is_product_category()) {
    $term = get_queried_object();
    if ($term instanceof WP_Term && function_exists('franchises_product_cat_flat_url')) {
        $form_action = franchises_product_cat_flat_url($term);
    } else {
        $link = get_term_link(get_queried_object());
        if (! is_wp_error($link)) {
            $form_action = (string) $link;
        }
    }
}

$current_selection_id = function_exists('franchises_get_current_selection_id')
    ? franchises_get_current_selection_id()
    : 0;

$hero_base_title = 'Каталог франшиз';
$hero_sub = 'Подберите франшизу по бюджету, отрасли и сроку окупаемости.';
if ($current_selection_id > 0) {
    $hero_base_title = get_the_title($current_selection_id) ?: $hero_base_title;
    $selection_excerpt = get_post_field('post_excerpt', $current_selection_id);
    if (is_string($selection_excerpt) && trim($selection_excerpt) !== '') {
        $hero_sub = $selection_excerpt;
    }
} elseif ($cur_category !== '') {
    $hero_base_title = $cur_category;
} elseif ($cur_sphere !== '') {
    $hero_base_title = $cur_sphere;
} elseif (is_product_category()) {
    $hero_base_title = single_term_title('', false) ?: $hero_base_title;
    $tdesc = term_description();
    if (is_string($tdesc) && trim(wp_strip_all_tags($tdesc)) !== '') {
        $hero_sub = wp_strip_all_tags($tdesc);
    }
}

$cur_search_q = isset($_GET['q']) ? sanitize_text_field(wp_unslash((string) $_GET['q'])) : '';
$hero_title = function_exists('franchises_catalog_build_hero_title')
    ? franchises_catalog_build_hero_title(
        $hero_base_title,
        $cur_verified,
        $cur_invest_max,
        $cur_profit_min,
        $cur_payback_max,
        $cur_search_q
    )
    : $hero_base_title;

$parents = get_terms([
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'parent'     => 0,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);
if (is_wp_error($parents)) {
    $parents = [];
}

$collection_posts = function_exists('franchises_get_selection_posts')
    ? franchises_get_selection_posts(40)
    : [];

$orderby_options = function_exists('franchises_wc_catalog_orderby_choices')
    ? franchises_wc_catalog_orderby_choices()
    : [
        'menu_order' => 'По умолчанию',
        'date'       => 'Сначала новые',
        'title'      => 'По названию',
    ];

global $wp_query;
$found = (int) $wp_query->found_posts;
$paged = max(1, (int) get_query_var('paged'));
$per_page = (int) $wp_query->get('posts_per_page');
if ($per_page <= 0) {
    $per_page = (int) get_option('posts_per_page', 12);
}
$from = $found > 0 ? (($paged - 1) * $per_page + 1) : 0;
$to = min($found, $paged * $per_page);
$count_line = $found > 0
    ? sprintf('Показано франшиз: %d–%d из %d', $from, $to, $found)
    : 'Франшизы не найдены';

?>
<section class="catalog-hero" aria-label="Каталог франшиз">
    <?php franchises_render_breadcrumbs([], ['with_container' => false, 'inline' => true]); ?>
    <h1 class="page-title"><?php echo esc_html($hero_title); ?></h1>
    <p class="page-subtitle"><?php echo esc_html($hero_sub); ?></p>
</section>

<div class="catalog-layout">
    <aside class="catalog-sidebar" aria-label="Подборки">
        <div class="sidebar-block">
            <div class="sidebar-title">Подборки</div>
            <ul class="sidebar-list" data-collections-list>
                <li>
                    <a href="<?php echo esc_url($shop_url); ?>" class="sidebar-link<?php echo is_shop() && ! is_product_category() ? ' active' : ''; ?>">Все франшизы</a>
                </li>
                <?php foreach ($collection_posts as $sel_post) :
                    if (! $sel_post instanceof WP_Post) {
                        continue;
                    }
                    $link = get_permalink($sel_post);
                    if (! is_string($link) || $link === '') {
                        continue;
                    }
                    $active = $current_selection_id > 0 && $current_selection_id === (int) $sel_post->ID;
                ?>
                    <li>
                        <a href="<?php echo esc_url($link); ?>" class="sidebar-link<?php echo $active ? ' active' : ''; ?>"><?php echo esc_html(get_the_title($sel_post)); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <main class="catalog-main">
        <?php
        if (function_exists('woocommerce_output_all_notices')) {
            woocommerce_output_all_notices();
        }
        ?>

        <div class="catalog-tags-wrap" aria-label="Подборки для мобильной версии">
            <div class="segment-tabs catalog-tags" aria-label="Подборки" data-collections-tabs>
                <a class="seg<?php echo is_shop() && ! is_product_category() ? ' active' : ''; ?>" href="<?php echo esc_url($shop_url); ?>">Все</a>
                <?php foreach ($collection_posts as $sel_post) :
                    if (! $sel_post instanceof WP_Post) {
                        continue;
                    }
                    $link = get_permalink($sel_post);
                    if (! is_string($link) || $link === '') {
                        continue;
                    }
                    $active = $current_selection_id > 0 && $current_selection_id === (int) $sel_post->ID;
                ?>
                    <a class="seg<?php echo $active ? ' active' : ''; ?>" href="<?php echo esc_url($link); ?>"><?php echo esc_html(get_the_title($sel_post)); ?></a>
                <?php endforeach; ?>
            </div>
            <button class="catalog-tags-toggle" type="button" aria-expanded="false">Показать все</button>
        </div>

        <div class="mobile-filter-action">
            <button class="btn btn-primary mobile-filter-btn" type="button" data-filter-open>Фильтры</button>
        </div>


        <?php
        wc_get_template(
            'parts/catalog-filters.php',
            [
                'shop_url'        => $shop_url,
                'form_action'     => $form_action,
                'parents'         => $parents,
                'cur_sphere'      => $cur_sphere,
                'cur_category'    => $cur_category,
                'cur_payback_max' => $cur_payback_max,
                'cur_verified'    => $cur_verified,
                'cur_invest_max'  => $cur_invest_max,
                'cur_profit_min'  => $cur_profit_min,
                'cur_orderby'     => $cur_orderby,
                'orderby_options' => $orderby_options,
                'count_line'      => $count_line,
                'reset_url'       => function_exists('franchises_catalog_reset_filters_url')
                    ? franchises_catalog_reset_filters_url()
                    : $shop_url,
                'show_reset'      => function_exists('franchises_catalog_has_active_filters')
                    ? franchises_catalog_has_active_filters()
                    : false,
                'advanced_open'   => function_exists('franchises_catalog_has_advanced_filters')
                    ? franchises_catalog_has_advanced_filters($cur_invest_max, $cur_profit_min)
                    : ($cur_invest_max > 0 || $cur_profit_min > 0),
            ],
            '',
            get_template_directory() . '/woocommerce/'
        );
        ?>


        <?php
        if (have_posts()) {
            woocommerce_product_loop_start();
            while (have_posts()) {
                the_post();
                do_action('woocommerce_shop_loop');
                wc_get_template_part('content', 'product');
            }
            woocommerce_product_loop_end();
            do_action('woocommerce_after_shop_loop');
        } else {
            require get_template_directory() . '/woocommerce/parts/catalog-empty-state.php';
        }
        ?>

    </main>
</div>

<?php
get_template_part('templates/components/help-section', null, [
    'section_class'   => 'catalog-bottom',
    'button_href'     => home_url('/#contacts'),
    'button_fancybox' => false,
]);
?>

<div class="filter-modal" id="filter-modal" aria-hidden="true">
    <div class="filter-backdrop" data-filter-close></div>
    <div class="filter-sheet" role="dialog" aria-modal="true" aria-label="Фильтры">
        <div class="filter-sheet-head">
            <div class="filter-sheet-title">Фильтры</div>
            <button class="filter-sheet-close" type="button" data-filter-close aria-label="Закрыть">×</button>
        </div>
        <div class="filter-sheet-body"></div>
    </div>
</div>