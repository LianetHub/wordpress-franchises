<?php

defined('ABSPATH') || exit;

/**
 * @var list<int> $product_ids
 * @var string    $popular_all_href
 * @var string    $popular_franchises_notice
 */

$product_ids = isset($product_ids) && is_array($product_ids) ? $product_ids : null;
$popular_all_href = isset($popular_all_href) ? (string) $popular_all_href : null;
$popular_franchises_notice = isset($popular_franchises_notice) ? (string) $popular_franchises_notice : null;

if ($product_ids === null && function_exists('franchises_popular_franchise_product_ids')) {
    $popular_limit = function_exists('franchises_home_popular_limit') ? franchises_home_popular_limit() : 12;
    $product_ids = franchises_popular_franchise_product_ids($popular_limit);
    $popular_all_href = function_exists('franchises_home_popular_all_url')
        ? franchises_home_popular_all_url()
        : home_url('/');
    $popular_franchises_notice = function_exists('franchises_home_popular_seo_text')
        ? franchises_home_popular_seo_text(count($product_ids))
        : '';
} else {
    $product_ids = is_array($product_ids) ? $product_ids : [];
    $popular_all_href = $popular_all_href ?? home_url('/');
    $popular_franchises_notice = $popular_franchises_notice ?? '';
}
?>

<section class="popular-section stats-next-tight" aria-label="<?php esc_attr_e('Популярные франшизы', 'franchises'); ?>" data-popular-section>
    <div class="popular-head">
        <div>
            <h2 class="segment-title"><?php esc_html_e('Популярные франшизы', 'franchises'); ?></h2>
            <?php if ($popular_franchises_notice !== '') : ?>
                <p class="popular-sub"><?php echo esc_html($popular_franchises_notice); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="popular-grid" data-popular-grid>
        <?php if ($product_ids !== [] && function_exists('franchises_render_franchise_card_for_product')) : ?>
            <?php
            $popular_franchises_i = 0;
            foreach ($product_ids as $product_id) {
                $product_id = (int) $product_id;
                if ($product_id <= 0) {
                    continue;
                }
                franchises_render_franchise_card_for_product($product_id, [
                    'popularity' => max(1, 100 - $popular_franchises_i),
                    'order'      => $popular_franchises_i,
                ]);
                $popular_franchises_i++;
            }
            ?>
        <?php else : ?>
            <p class="popular-sub" style="grid-column: 1 / -1;">
                <?php esc_html_e('В каталоге пока нет опубликованных франшиз для этого блока.', 'franchises'); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="segment-actions">
        <a class="btn btn-primary" href="<?php echo esc_url($popular_all_href); ?>" data-popular-open>
            <?php esc_html_e('Смотреть все популярные франшизы', 'franchises'); ?>
        </a>
    </div>
</section>