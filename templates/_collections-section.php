<?php

defined('ABSPATH') || exit;

if (! class_exists('WooCommerce')) {
    return;
}
?>

<section class="segment-section" id="collections" aria-label="Подборки" data-collections-section>
    <div class="segment-block">
        <div class="segment-head">
            <div>
                <h2 class="segment-title">Подборки</h2>
                <p class="segment-sub">Подборки синхронизируются с каталогом и показывают франшизы внутри выбранной группы.</p>
            </div>
        </div>
        <?php franchises_render_home_collections_section(); ?>
    </div>
</section>