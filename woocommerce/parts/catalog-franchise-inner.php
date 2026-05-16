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

$form_action = is_shop() ? $shop_url : get_term_link(get_queried_object());
if (is_wp_error($form_action)) {
    $form_action = $shop_url;
}

$hero_title = 'Каталог франшиз';
$hero_sub = 'Подберите франшизу по бюджету, отрасли и сроку окупаемости.';
if (is_product_category()) {
    $hero_title = single_term_title('', false) ?: $hero_title;
    $tdesc = term_description();
    if (is_string($tdesc) && trim(wp_strip_all_tags($tdesc)) !== '') {
        $hero_sub = wp_strip_all_tags($tdesc);
    }
}

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

$collection_posts = [];
if (post_type_exists('selection')) {
    $collection_posts = get_posts([
        'post_type'      => 'selection',
        'post_status'    => 'publish',
        'posts_per_page' => 40,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ]);
}

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
    <div class="breadcrumbs" aria-label="Хлебные крошки">
        <?php
        $crumbs = function_exists('franchises_catalog_breadcrumbs') ? franchises_catalog_breadcrumbs() : [];
        foreach ($crumbs as $i => $c) {
            $is_last = $i === count($crumbs) - 1;
            echo '<span>';
            if (! $is_last && ! empty($c['href'])) {
                echo '<a href="' . esc_url($c['href']) . '">' . esc_html($c['label']) . '</a>';
            } else {
                echo esc_html($c['label']);
            }
            echo '</span>';
        }
        ?>
    </div>
    <h1 class="page-title"><?php echo esc_html($hero_title); ?></h1>
    <h2 class="page-subtitle"><?php echo esc_html($hero_sub); ?></h2>
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
                    $active = is_singular('selection') && (int) get_queried_object_id() === (int) $sel_post->ID;
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
                    $active = is_singular('selection') && (int) get_queried_object_id() === (int) $sel_post->ID;
                ?>
                    <a class="seg<?php echo $active ? ' active' : ''; ?>" href="<?php echo esc_url($link); ?>"><?php echo esc_html(get_the_title($sel_post)); ?></a>
                <?php endforeach; ?>
            </div>
            <button class="catalog-tags-toggle" type="button" aria-expanded="false">Показать все</button>
        </div>

        <div class="mobile-filter-action">
            <button class="btn btn-primary mobile-filter-btn" type="button" data-filter-open>Фильтры</button>
        </div>

        <form method="get" action="<?php echo esc_url($form_action); ?>" aria-label="Фильтр каталога" id="franchises-catalog-filters">
            <div class="filter-card advanced-collapsed">
                <div class="filter-grid">
                    <select class="filter-select filter-select-native" name="sphere" data-filter="sphere" aria-label="Сфера бизнеса">
                        <option value=""><?php esc_html_e('Все сферы', 'woocommerce'); ?></option>
                        <?php foreach ($parents as $p) : ?>
                            <option value="<?php echo esc_attr($p->name); ?>" <?php selected($cur_sphere, $p->name); ?>><?php echo esc_html($p->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select filter-select-native" name="category" data-filter="category" aria-label="Категория франшизы">
                        <option value=""><?php esc_html_e('Все категории', 'woocommerce'); ?></option>
                        <?php foreach ($parents as $p) :
                            $children = get_terms([
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => true,
                                'parent'     => (int) $p->term_id,
                                'orderby'    => 'name',
                                'order'      => 'ASC',
                            ]);
                            if (is_wp_error($children) || ! $children) {
                                continue;
                            }
                            foreach ($children as $ch) :
                        ?>
                                <option value="<?php echo esc_attr($ch->name); ?>" data-sphere="<?php echo esc_attr($p->name); ?>" <?php selected($cur_category, $ch->name); ?>><?php echo esc_html($ch->name); ?></option>
                        <?php
                            endforeach;
                        endforeach; ?>
                    </select>
                    <select class="filter-select filter-select-native" name="payback_max" data-filter="payback" aria-label="Окупаемость">
                        <option value="0">Любая окупаемость</option>
                        <option value="12" <?php selected($cur_payback_max, 12); ?>>до 12 мес.</option>
                        <option value="18" <?php selected($cur_payback_max, 18); ?>>до 18 мес.</option>
                        <option value="24" <?php selected($cur_payback_max, 24); ?>>до 24 мес.</option>
                        <option value="36" <?php selected($cur_payback_max, 36); ?>>до 36 мес.</option>
                    </select>
                    <button class="btn btn-primary filter-btn" type="submit">Подобрать</button>
                </div>
                <label class="filter-check filter-check-inline">
                    <input type="checkbox" name="verified" value="1" <?php checked($cur_verified); ?>>
                    <span>Только проверенные франшизы</span>
                </label>

                <div class="filter-advanced">
                    <div class="range-filters" aria-label="Фильтры по вложениям и прибыли">
                        <div class="range-card">
                            <div class="range-head">
                                <div class="range-title">Вложения</div>
                                <div class="range-value" id="invest-value"><?php echo $cur_invest_max > 0 ? 'до ' . esc_html(franchises_format_money_ru($cur_invest_max)) : 'Любые вложения'; ?></div>
                            </div>
                            <input type="hidden" name="invest_max" id="invest_max_input" value="<?php echo esc_attr((string) $cur_invest_max); ?>">
                            <input
                                class="range-input"
                                id="invest-range"
                                type="range"
                                min="50000"
                                max="3000000"
                                step="50000"
                                value="<?php echo esc_attr((string) ($cur_invest_max > 0 ? $cur_invest_max : 3000000)); ?>"
                                data-range-hidden="#invest_max_input"
                                data-range-label="#invest-value"
                                data-range-empty-label="<?php echo esc_attr__('Любые вложения', 'franchises'); ?>"
                                data-range-prefix="<?php echo esc_attr__('до ', 'franchises'); ?>"
                                data-range-empty-value="0"
                                data-range-empty-at="max">
                            <div class="preset-row">
                                <button class="preset-btn" type="button" data-invest="50000">до 50 000 ₽</button>
                                <button class="preset-btn" type="button" data-invest="100000">до 100 000 ₽</button>
                                <button class="preset-btn" type="button" data-invest="300000">до 300 000 ₽</button>
                                <button class="preset-btn" type="button" data-invest="500000">до 500 000 ₽</button>
                                <button class="preset-btn" type="button" data-invest="1000000">до 1 000 000 ₽</button>
                                <button class="preset-btn" type="button" data-invest="3000000">до 3 000 000 ₽</button>
                            </div>
                        </div>
                        <div class="range-card">
                            <div class="range-head">
                                <div class="range-title">Прибыль в месяц</div>
                                <div class="range-value" id="profit-value"><?php echo $cur_profit_min > 0 ? 'от ' . esc_html(franchises_format_money_ru($cur_profit_min)) : 'Любая прибыль'; ?></div>
                            </div>
                            <input type="hidden" name="profit_min" id="profit_min_input" value="<?php echo esc_attr((string) $cur_profit_min); ?>">
                            <input
                                class="range-input"
                                id="profit-range"
                                type="range"
                                min="0"
                                max="1000000"
                                step="50000"
                                value="<?php echo esc_attr((string) ($cur_profit_min > 0 ? $cur_profit_min : 0)); ?>"
                                data-range-hidden="#profit_min_input"
                                data-range-label="#profit-value"
                                data-range-empty-label="<?php echo esc_attr__('Любая прибыль', 'franchises'); ?>"
                                data-range-prefix="<?php echo esc_attr__('от ', 'franchises'); ?>"
                                data-range-empty-value="0"
                                data-range-empty-at="min">
                            <div class="preset-row">
                                <button class="preset-btn" type="button" data-profit="100000">от 100 000 ₽</button>
                                <button class="preset-btn" type="button" data-profit="200000">от 200 000 ₽</button>
                                <button class="preset-btn" type="button" data-profit="300000">от 300 000 ₽</button>
                                <button class="preset-btn" type="button" data-profit="500000">от 500 000 ₽</button>
                                <button class="preset-btn" type="button" data-profit="1000000">от 1 000 000 ₽</button>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="filter-toggle" type="button" aria-expanded="false">Показать дополнительные фильтры</button>
            </div>

            <div class="catalog-count mobile-count" id="catalog-count-mobile"><?php echo esc_html($count_line); ?></div>
            <div class="catalog-toolbar">
                <div class="catalog-count" id="catalog-count"><?php echo esc_html($count_line); ?></div>
                <select class="filter-select sort-select filter-select-native" name="orderby" data-sort aria-label="Сортировка" onchange="this.form.submit();">
                    <?php foreach ($orderby_options as $val => $label) : ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($cur_orderby, $val); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

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
            do_action('woocommerce_no_products_found');
        }
        ?>

    </main>
</div>

<section class="help-section catalog-bottom" aria-label="Поможем подобрать франшизу">
    <div class="help-panel">
        <h2 class="help-title">Не нашли подходящую франшизу?</h2>
        <p class="help-text">Оставьте заявку — подберём варианты под ваш бюджет и цели и свяжемся в течение дня.</p>
        <a class="btn btn-primary" href="<?php echo esc_url(home_url('/#contacts')); ?>">Получить подбор</a>
    </div>
</section>

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