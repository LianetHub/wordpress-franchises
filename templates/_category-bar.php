<?php
$home_spheres = franchises_product_cat_get_spheres();
$shop_url = (class_exists('WooCommerce', false) && function_exists('wc_get_page_id'))
    ? get_permalink(wc_get_page_id('shop'))
    : '';
?>

<section class="category-bar" aria-label="Категории франшиз" id="catalog">
    <div class="catalog-title">Каталог франшиз</div>
    <div class="category-grid-wrap collapsed" id="category-grid-wrap">
        <div class="category-grid" data-spheres-grid>
            <?php foreach ($home_spheres as $sphere) :
                $sphere_name = (string) ($sphere['name'] ?? '');
                $sphere_url = (string) ($sphere['landing_url'] ?? $sphere['url'] ?? '');
                $sphere_term_id = (int) ($sphere['term_id'] ?? 0);
                if ($sphere_name === '' || $sphere_url === '') {
                    continue;
                }
            ?>
                <a class="chip" href="<?php echo esc_url($sphere_url); ?>">
                    <span class="icon" aria-hidden="true"><?php
                                                            echo franchises_product_cat_icon_html($sphere_term_id, $sphere_name);
                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            ?></span>
                    <span class="chip-text"><?php echo esc_html($sphere_name); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="cta-group">
        <button class="btn btn-outline secondary-btn category-toggle" type="button" aria-expanded="false" aria-controls="category-grid-wrap">Показать все отрасли</button>
        <?php if (is_string($shop_url) && $shop_url !== '') : ?>
            <a class="btn btn-primary catalog-btn" href="<?php echo esc_url($shop_url); ?>">
                Все франшизы
            </a>
        <?php endif; ?>
    </div>
</section>