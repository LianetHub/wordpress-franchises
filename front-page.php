<?php get_header(); ?>

<?php require_once(TEMPLATE_PATH . '_hero.php'); ?>

<?php require_once(TEMPLATE_PATH . '_category-bar.php'); ?>

<?php require_once(TEMPLATE_PATH . '_stats-block.php'); ?>

<?php
get_template_part('templates/components/month-franchises', null, [
    'variant' => 'home',
]);
?> 

<?php require_once(TEMPLATE_PATH . '_popular-section.php'); ?>

<?php require_once TEMPLATE_PATH . '_collections-section.php'; ?>

<?php get_template_part('templates/components/help-section'); ?>

<section class="logos-section" aria-label="Логотипы франшиз">
    <div class="section-head">

        <div>
            <h2 class="segment-title">Известные франшизы</h2>
            <p class="segment-sub">Сильные имена, устойчивые модели и подтверждённая стабильность на рынке.</p>
        </div>

        <a class="section-link" href="#catalog">Все франшизы<span aria-hidden="true">→</span></a>
    </div>
    <div class="logo-wrap">
        <button class="logo-arrow prev" type="button" aria-label="Предыдущие логотипы"></button>
        <div class="logo-strip swiper">
            <div class="swiper-wrapper">
                <div class="logo-card swiper-slide">LOGO</div>
                <div class="logo-card swiper-slide">LOGO</div>
                <div class="logo-card swiper-slide">LOGO</div>
                <div class="logo-card swiper-slide">LOGO</div>
                <div class="logo-card swiper-slide">LOGO</div>
            </div>
        </div>
        <button class="logo-arrow next" type="button" aria-label="Следующие логотипы"></button>
    </div>
</section>

<div class="logo-modal popup" id="logo-modal" hidden>
    <div class="logo-modal-card" role="dialog" aria-label="Франшиза">
        <button class="logo-modal-close" type="button" data-fancybox-close aria-label="Закрыть">×</button>
        <div class="logo-modal-media">
            <img src="" alt="">
        </div>
        <div class="logo-modal-body">
            <div class="logo-modal-brand">BRAND</div>
            <div class="logo-modal-title">Название франшизы</div>
            <div class="logo-modal-meta">Инвестиции: уточняйте у менеджера</div>
            <a class="btn btn-primary logo-modal-cta" href="franchise.html">Подробнее</a>
        </div>
    </div>
</div>

<?php require_once(TEMPLATE_PATH . '_reviews-section.php'); ?>


<section class="final-section" id="contacts" aria-label="Форма заявки">
    <h2 class="segment-title">Остались вопросы?</h2>
    <p class="segment-sub">Оставьте контакты — мы свяжемся и подскажем.</p>
    <div class="form-card final-form wpcf7-wrap">
        <?php franchises_render_home_consult_cf7(); ?>
    </div>
</section>


<?php get_footer(); ?>