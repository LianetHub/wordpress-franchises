<?php

/**
 * Верстка страницы франшизы (динамика из товара WooCommerce + ACF).
 *
 * Подключается только из woocommerce/content-single-product.php через require:
 * переменные $product, $gallery_urls, $breadcrumbs (опц.), $toc_items, $content_html,
 * $faq_rows, $similar_query, $popular_query, $post_id уже заданы родителем.
 */

defined('ABSPATH') || exit;

global $product;

if (! isset($product) || ! is_object($product) || ! is_a($product, 'WC_Product', true)) {
    return;
}

$gallery_urls = isset($gallery_urls) && is_array($gallery_urls) ? $gallery_urls : [];
$toc_items    = isset($toc_items) && is_array($toc_items) ? $toc_items : [];
$content_html = isset($content_html) ? (string) $content_html : '';
$faq_rows     = isset($faq_rows) && is_array($faq_rows) ? $faq_rows : [];
/** @var WP_Query $similar_query */
$similar_query = isset($similar_query) && $similar_query instanceof WP_Query
    ? $similar_query
    : new WP_Query();
/** @var WP_Query $popular_query */
$popular_query = isset($popular_query) && $popular_query instanceof WP_Query
    ? $popular_query
    : new WP_Query();

$post_id = (int) $product->get_id();
$pid     = $post_id;

$acf = function_exists('get_fields') ? get_fields($post_id) : false;
$acf = is_array($acf) ? $acf : [];

$h1 = isset($acf['product_full_title']) && (string) $acf['product_full_title'] !== ''
    ? (string) $acf['product_full_title']
    : get_the_title($post_id);
$subtitle = $product->get_short_description();
if ($subtitle === '') {
    $subtitle = (string) get_post_field('post_excerpt', $post_id);
}
$subtitle = wp_strip_all_tags($subtitle);

$verified = ! empty($acf['verified']);

$info_rows = [
    ['label' => 'Год основания компании', 'value' => $acf['year_founded'] ?? null],
    ['label' => 'Год запуска франшизы', 'value' => $acf['franchise_since'] ?? null],
    ['label' => 'Франшизных предприятий', 'value' => $acf['franch_stores'] ?? null],
    ['label' => 'Товарный<br>знак', 'value' => $acf['tm_number'] ?? null],
    ['label' => 'Городов присутствия', 'value' => $acf['cities_count'] ?? ($acf['cities_presence'] ?? null)],
];

$key_invest = franchises_format_investment_line_ru($product);
$key_profit = franchises_format_profit_line_ru(
    $acf['monthly_profit_min'] ?? null,
    $acf['monthly_profit_max'] ?? null
);
$key_payback = franchises_format_payback_ru(
    $acf['payback_min'] ?? null,
    $acf['payback_max'] ?? null
);
$key_pausal = franchises_format_pausal_line_ru($post_id);
$key_royalty = franchises_format_royalty_display($post_id);

$privacy_href = franchises_privacy_policy_url();

$date_fmt = 'j F Y';
$post_obj = get_post($post_id);
if (function_exists('get_post_datetime') && $post_obj instanceof WP_Post) {
    $published_ts = get_post_datetime('publish', $post_obj);
    $modified_ts  = get_post_datetime('modified', $post_obj);
    $published_s  = $published_ts ? wp_date($date_fmt, $published_ts->getTimestamp()) : '';
    $modified_s   = $modified_ts ? wp_date($date_fmt, $modified_ts->getTimestamp()) : '';
} elseif ($post_obj instanceof WP_Post) {
    $published_s = date_i18n($date_fmt, strtotime($post_obj->post_date));
    $modified_s  = date_i18n($date_fmt, strtotime($post_obj->post_modified));
} else {
    $published_s = '';
    $modified_s  = '';
}
if ($published_s === '') {
    $published_s = (string) get_the_date('j F Y', $post_id);
}
if ($modified_s === '') {
    $modified_s = (string) get_the_modified_date('j F Y', $post_id);
}
$views_n      = franchises_theme_get_post_views($post_id);
$views_s      = $views_n > 0 ? number_format($views_n, 0, ',', ' ') : '—';

$slug = (string) get_post_field('post_name', $post_id);
$card = franchises_franchise_card_from_post($post_id);

?>
<div
    class="franchise-single-root"
    data-default-franchise-id="<?php echo esc_attr($slug); ?>"
    data-card-title="<?php echo esc_attr(wp_strip_all_tags($card['name'] ?? '')); ?>"
    data-card-desc="<?php echo esc_attr(wp_strip_all_tags($card['desc'] ?? '')); ?>"
    data-card-image="<?php echo esc_url((string) ($card['image'] ?? '')); ?>"
    data-card-invest="<?php echo esc_attr((string) ($card['invest'] ?? '')); ?>"
    data-card-profit="<?php echo esc_attr((string) ($card['profit'] ?? '')); ?>"
    data-card-payback="<?php echo esc_attr((string) ($card['payback'] ?? '')); ?>"
    data-card-verified="<?php echo ! empty($card['verified']) ? 'true' : 'false'; ?>"
    data-card-sphere="<?php echo esc_attr((string) ($card['sphere'] ?? '')); ?>"
    data-card-category="<?php echo esc_attr((string) ($card['category'] ?? '')); ?>"
    data-card-tags="<?php echo esc_attr((string) ($card['tags'] ?? '')); ?>"
    data-card-url="<?php echo esc_url((string) ($card['href'] ?? '')); ?>"
    data-card-slug="<?php echo esc_attr($slug); ?>">
    <?php
    $franchise_wc_notice_count = function_exists('wc_notice_count') ? (int) wc_notice_count() : 0;
    if ($franchise_wc_notice_count > 0) :
    ?>
        <div class="woocommerce-notices-wrap wrap" style="max-width:1200px;margin:0 auto;">
            <?php
            if (function_exists('woocommerce_output_all_notices')) {
                woocommerce_output_all_notices();
            } elseif (function_exists('wc_print_notices')) {
                wc_print_notices();
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="page-grid">
        <div class="page-head">
            <?php
            franchises_render_breadcrumbs(
                isset($breadcrumbs) && is_array($breadcrumbs) ? $breadcrumbs : [],
                [
                    'with_container' => false,
                    'inline'         => true,
                ]
            );
            ?>

            <h1 class="page-title"><?php echo esc_html($h1); ?></h1>
            <?php if ($subtitle !== '') : ?>
                <p class="page-subtitle"><?php echo esc_html($subtitle); ?></p>
            <?php endif; ?>
        </div>

        <div class="main-top">
            <section class="card gallery-card" aria-label="Галерея франшизы">
                <?php
                $gallery_alt   = wp_strip_all_tags($h1);
                $gallery_total = count($gallery_urls);
                ?>
                <?php if ($gallery_total > 0) : ?>
                    <div class="gallery-main swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($gallery_urls as $gi => $gurl) : ?>
                                <div class="swiper-slide">
                                    <a
                                        class="gallery-main-link"
                                        href="<?php echo esc_url($gurl); ?>"
                                        data-fancybox="franchise-gallery"
                                        data-caption="<?php echo esc_attr($gallery_alt); ?>"
                                        aria-label="<?php echo esc_attr('Открыть фото ' . ($gi + 1)); ?>">
                                        <img
                                            src="<?php echo esc_url($gurl); ?>"
                                            alt="<?php echo esc_attr($gallery_alt); ?>"
                                            loading="<?php echo $gi === 0 ? 'eager' : 'lazy'; ?>"
                                            class="cover-image"
                                            decoding="async">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($gallery_total > 1) : ?>
                            <button class="gallery-nav gallery-nav-prev" type="button" aria-label="Предыдущее фото">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M15 18l-6-6 6-6"></path>
                                </svg>
                            </button>
                            <button class="gallery-nav gallery-nav-next" type="button" aria-label="Следующее фото">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M9 6l6 6-6 6"></path>
                                </svg>
                            </button>
                            <div class="gallery-count swiper-pagination" data-gallery-count></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($gallery_total > 1) : ?>
                        <div class="thumbs-wrap">
                            <button class="thumbs-nav thumbs-nav-prev" type="button" aria-label="Предыдущие превью">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M15 18l-6-6 6-6"></path>
                                </svg>
                            </button>
                            <button class="thumbs-nav thumbs-nav-next" type="button" aria-label="Следующие превью">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M9 6l6 6-6 6"></path>
                                </svg>
                            </button>
                            <div class="gallery-thumbs swiper" aria-label="Превью галереи">
                                <div class="swiper-wrapper">
                                    <?php foreach ($gallery_urls as $gi => $gurl) : ?>
                                        <div class="swiper-slide gallery-thumb">
                                            <img
                                                src="<?php echo esc_url($gurl); ?>"
                                                alt=""
                                                loading="<?php echo $gi === 0 ? 'eager' : 'lazy'; ?>"
                                                decoding="async"
                                                class="cover-image">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>

        <div class="main-bottom">
            <section class="info-row" aria-label="Информация о франшизе">
                <div class="info-grid">
                    <?php foreach ($info_rows as $row) : ?>
                        <?php
                        $val = $row['value'];
                        if ($val === null || $val === '') {
                            continue;
                        }
                        ?>
                        <div class="info-item">
                            <div class="info-label"><?php echo wp_kses($row['label'], ['br' => []]); ?></div>
                            <div class="info-value"><?php echo esc_html((string) $val); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="card cta-card" aria-label="Форма заявки">
                <h2 class="cta-title">Узнать больше о франшизе</h2>
                <p class="cta-subtitle">Оставьте контакты, и наш менеджер свяжется с вами, пришлёт презентацию и ответит на вопросы.</p>
                <div class="wpcf7-wrap">
                    <?php franchises_render_franchise_lead_cf7(); ?>
                </div>
            </section>

            <?php if ($content_html !== '') : ?>
                <section class="card content-section" aria-label="Описание франшизы">
                    <div class="entry-content franchise-product-content typography-block">
                        <?php echo $content_html; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($faq_rows !== []) : ?>
                <section class="card faq-section" aria-label="Вопросы и ответы">
                    <h2>Вопросы и ответы</h2>
                    <div class="faq-list">
                        <?php foreach ($faq_rows as $faq) : ?>
                            <div class="faq-item">
                                <div class="faq-question"><?php echo esc_html($faq['question']); ?></div>
                                <div class="faq-answer"><?php echo $faq['answer']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="card ask-box" aria-label="Задать вопрос о франшизе">
                <h2 class="ask-title">Задать вопрос о франшизе</h2>
                <p class="ask-subtitle">Вопросы проходят модерацию. После ответа администратора они публикуются в этом разделе.</p>
                <div class="ask-form-grid wpcf7-wrap">
                    <?php franchises_render_franchise_question_cf7(); ?>
                </div>
            </section>
        </div>

        <aside class="side-panel">
            <div class="card">
                <ul class="key-list">
                    <li class="key-item"><span>Инвестиции</span><strong><?php echo esc_html($key_invest); ?></strong></li>
                    <?php if ($key_profit !== '') : ?>
                        <li class="key-item"><span>Месячная прибыль</span><strong><?php echo esc_html($key_profit); ?></strong></li>
                    <?php endif; ?>
                    <?php if ($key_payback !== '') : ?>
                        <li class="key-item"><span>Окупаемость</span><strong><?php echo esc_html($key_payback); ?></strong></li>
                    <?php endif; ?>
                    <?php if ($key_pausal !== '') : ?>
                        <li class="key-item"><span>Паушальный взнос</span><strong><?php echo esc_html($key_pausal); ?></strong></li>
                    <?php endif; ?>
                    <?php if ($key_royalty !== '') : ?>
                        <li class="key-item"><span>Роялти</span><strong><?php echo esc_html($key_royalty); ?></strong></li>
                    <?php endif; ?>
                </ul>
                <?php if ($verified) : ?>
                    <div class="badge">Проверенная франшиза</div>
                <?php endif; ?>
            </div>

            <div class="side-contact card">
                <button class="btn btn-primary" type="button" data-fancybox data-src="#selection-popup">Связаться с франчайзером</button>
            </div>

            <div class="side-meta card">
                <div class="meta-row is-date"><span>Опубликовано</span><strong><?php echo esc_html($published_s); ?></strong></div>
                <div class="meta-row is-date"><span>Обновлено</span><strong><?php echo esc_html($modified_s); ?></strong></div>
                <div class="meta-row is-views"><span>Просмотров</span><strong><?php echo esc_html($views_s); ?></strong></div>
            </div>

            <?php if ($toc_items !== []) : ?>
                <div class="card toc-desktop">
                    <div class="toc-title">Содержание</div>
                    <ol class="toc-list">
                        <?php foreach ($toc_items as $toc) : ?>
                            <li><a href="#<?php echo esc_attr($toc['id']); ?>"><span class="toc-list__text"><?php echo esc_html($toc['title']); ?></span></a></li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <section class="card toc-mobile" aria-label="Содержание">
                    <h2 class="toc-title">Содержание</h2>
                    <ol class="toc-list">
                        <?php foreach ($toc_items as $toc) : ?>
                            <li><a href="#<?php echo esc_attr($toc['id']); ?>"><span class="toc-list__text"><?php echo esc_html($toc['title']); ?></span></a></li>
                        <?php endforeach; ?>
                    </ol>
                </section>
            <?php endif; ?>
        </aside>
    </div>

    <?php if ($similar_query->have_posts() || $popular_query->have_posts()) : ?>
        <div class="bottom-sliders">
            <?php if ($similar_query->have_posts()) : ?>
                <section class="card slider-section" aria-label="Похожие франшизы">
                    <div class="slider-head">
                        <h2 class="section-title">Похожие франшизы</h2>
                    </div>
                    <div class="slider-wrap">
                        <button class="slider-btn" type="button" data-slider-prev aria-label="Прокрутить влево">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M15 5l-7 7 7 7"></path>
                            </svg>
                        </button>
                        <button class="slider-btn" type="button" data-slider-next aria-label="Прокрутить вправо">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        <div class="slider swiper" data-slider="similar">
                            <div class="swiper-wrapper">
                                <?php
                                $saved_product = $product;
                                while ($similar_query->have_posts()) :
                                    $similar_query->the_post();
                                    $product = wc_get_product(get_the_ID());
                                    if ($product && $product->is_visible()) {
                                        echo '<div class="swiper-slide">';
                                        get_template_part('templates/components/franchise-card');
                                        echo '</div>';
                                    }
                                endwhile;
                                wp_reset_postdata();
                                $product = $saved_product;
                                ?>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($popular_query->have_posts()) : ?>
                <section class="card slider-section" aria-label="Популярные франшизы">
                    <div class="slider-head">
                        <h2 class="section-title">Популярные франшизы</h2>
                    </div>
                    <div class="slider-wrap">
                        <button class="slider-btn" type="button" data-slider-prev aria-label="Прокрутить влево">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M15 5l-7 7 7 7"></path>
                            </svg>
                        </button>
                        <button class="slider-btn" type="button" data-slider-next aria-label="Прокрутить вправо">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        <div class="slider swiper" data-slider="popular">
                            <div class="swiper-wrapper">
                                <?php
                                $saved_product = $product;
                                while ($popular_query->have_posts()) :
                                    $popular_query->the_post();
                                    $product = wc_get_product(get_the_ID());
                                    if ($product && $product->is_visible()) {
                                        echo '<div class="swiper-slide">';
                                        get_template_part('templates/components/franchise-card');
                                        echo '</div>';
                                    }
                                endwhile;
                                wp_reset_postdata();
                                $product = $saved_product;
                                ?>
                            </div>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>