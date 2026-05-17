<?php get_header(); ?>

<?php require_once(TEMPLATE_PATH . '_hero.php'); ?>

<section class="category-bar" aria-label="Категории франшиз" id="catalog">
    <div class="catalog-title">Каталог франшиз</div>
    <div class="category-grid-wrap collapsed" id="category-grid-wrap">
        <div class="category-grid" data-spheres-grid>
            <?php
            $home_spheres = function_exists('franchises_header_get_product_cat_spheres')
                ? franchises_header_get_product_cat_spheres()
                : [];
            foreach ($home_spheres as $sphere) :
                $sphere_name = (string) ($sphere['name'] ?? '');
                $sphere_url = (string) ($sphere['landing_url'] ?? $sphere['url'] ?? '');
                if ($sphere_name === '' || $sphere_url === '') {
                    continue;
                }
            ?>
                <a class="chip" href="<?php echo esc_url($sphere_url); ?>">
                    <span class="icon" aria-hidden="true"><?php echo franchises_header_sphere_icon_svg($sphere_name); ?></span>
                    <span class="chip-text"><?php echo esc_html($sphere_name); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="cta-group">
        <button class="btn btn-outline secondary-btn category-toggle" type="button" aria-expanded="false" aria-controls="category-grid-wrap">Показать все отрасли</button>
        <a class="btn btn-primary catalog-btn" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
            Все франшизы
        </a>
    </div>
</section>

<?php require_once(TEMPLATE_PATH . '_stats-block.php'); ?>

<?php
$franchises_popular_cat_term = false;
$popular_all_href = home_url('/');
if (class_exists('WooCommerce', false) && function_exists('wc_get_page_id')) {
    $shop_page_id = wc_get_page_id('shop');
    if ($shop_page_id > 0) {
        $popular_all_href = (string) get_permalink($shop_page_id);
    }
    $franchises_popular_cat_term = get_term_by('name', 'Популярные франшизы', 'product_cat');
    if (! $franchises_popular_cat_term || is_wp_error($franchises_popular_cat_term)) {
        $franchises_popular_cat_term = get_term_by('slug', 'popularnye-franshizy', 'product_cat');
    }
    if ($franchises_popular_cat_term && ! is_wp_error($franchises_popular_cat_term)) {
        $tlink = get_term_link($franchises_popular_cat_term);
        if (! is_wp_error($tlink)) {
            $popular_all_href = (string) $tlink;
        }
    }
}
?>

<?php if (class_exists('WooCommerce', false)) : ?>
    <?php
    $popular_cat_term = $franchises_popular_cat_term;

    $popular_franchises_args = [
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 12,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'orderby'             => [
            'menu_order' => 'ASC',
            'date'       => 'DESC',
        ],
    ];
    if ($popular_cat_term && ! is_wp_error($popular_cat_term)) {
        $popular_franchises_args['tax_query'] = [
            [
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => [(int) $popular_cat_term->term_id],
                'include_children' => true,
            ],
        ];
    }

    $popular_franchises_query = new WP_Query($popular_franchises_args);
    $popular_franchises_notice = ($popular_cat_term && ! is_wp_error($popular_cat_term))
        ? 'Карточки из каталога WooCommerce (категория «Популярные франшизы») и полей ACF.'
        : 'Категория «Популярные франшизы» в каталоге не найдена — показаны последние опубликованные франшизы.';
    ?>
    <section class="popular-section stats-next-tight" aria-label="Популярные франшизы из каталога" data-popular-section>
        <div class="popular-head">
            <div>
                <h2 class="segment-title">Популярные франшизы</h2>
                <p class="popular-sub"><?php echo esc_html($popular_franchises_notice); ?></p>
            </div>
        </div>
        <div class="popular-grid" data-popular-grid>
            <?php if ($popular_franchises_query->have_posts()) : ?>
                <?php
                $popular_franchises_i = 0;
                while ($popular_franchises_query->have_posts()) :
                    $popular_franchises_query->the_post();
                    $franchise_card = franchises_franchise_card_from_post(get_the_ID());
                    $franchise_card['popularity'] = max(1, 100 - $popular_franchises_i);
                    $franchise_card['order'] = $popular_franchises_i;
                    $popular_franchises_i++;
                    get_template_part('templates/components/franchise-card', null, ['franchise_card' => $franchise_card]);
                endwhile;
                wp_reset_postdata();
                ?>
            <?php else : ?>
                <p class="popular-sub" style="grid-column: 1 / -1;">В каталоге пока нет опубликованных франшиз для этого блока.</p>
            <?php endif; ?>
        </div>
        <div class="segment-actions">
            <a class="btn btn-primary" href="<?php echo esc_url($popular_all_href); ?>" data-popular-open>Смотреть все популярные франшизы</a>
        </div>
    </section>
<?php endif; ?>

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
            <div class="logo-modal-meta">Инвестиции от 0 ₽</div>
            <a class="btn btn-primary logo-modal-cta" href="franchise.html">Подробнее</a>
        </div>
    </div>
</div>

<section class="reviews-section" aria-label="Отзывы">
    <h2 class="segment-title">Отзывы предпринимателей, которые уже запустили бизнес</h2>
    <p class="segment-sub">Опыт запуска реальных людей, их результаты и выводы.</p>
    <div class="reviews-wrap">
        <button class="reviews-arrow prev" type="button" aria-label="Предыдущий отзыв"></button>
        <div class="reviews-strip swiper">
            <div class="swiper-wrapper">
                <article class="review-card swiper-slide">
                    <div class="review-media">Фото
                        клиента</div>
                    <div class="review-head">
                        <div class="review-name">Алексей, Москва</div>
                        <div class="review-meta">Купил франшизу и открыл кофейню</div>
                    </div>
                    <div class="review-text">Купил франшизу через платформу — помогли с подбором и запуском, вышли на стабильные продажи за 2 месяца. Поддержка по маркетингу и персоналу реально ускорила старт, а чек‑листы и обучение команды сняли много рисков.</div>
                    <div class="review-franchise">
                        <div class="review-logo">Лого</div>
                        <a class="review-link" href="franchise.html">Franchise Coffee</a>
                    </div>
                </article>
                <article class="review-card swiper-slide">
                    <div class="review-media">Фото
                        клиента</div>
                    <div class="review-head">
                        <div class="review-name">Марина, Казань</div>
                        <div class="review-meta">Купила франшизу салона красоты</div>
                    </div>
                    <div class="review-text">Выбрала франшизу, проверили договор и условия. Запуск прошёл без сюрпризов, помогли с локацией и первой поставкой. Уже на второй месяц увидела стабильный поток клиентов.</div>
                    <div class="review-franchise">
                        <div class="review-logo">Лого</div>
                        <a class="review-link" href="franchise.html">Beauty Line</a>
                    </div>
                </article>
                <article class="review-card swiper-slide">
                    <div class="review-media">Фото
                        клиента</div>
                    <div class="review-head">
                        <div class="review-name">Иван, Екатеринбург</div>
                        <div class="review-meta">Купил франшизу доставки</div>
                    </div>
                    <div class="review-text">Франшизу купил быстро, окупаемость попадает в заявленные сроки. Понравилось, что сразу дали понятный план запуска и KPI, и помогли довести рекламные кампании до первых продаж.</div>
                    <div class="review-franchise">
                        <div class="review-logo">Лого</div>
                        <a class="review-link" href="franchise.html">Fast Delivery</a>
                    </div>
                </article>
                <article class="review-card swiper-slide">
                    <div class="review-media">Фото
                        клиента</div>
                    <div class="review-head">
                        <div class="review-name">Ольга, Новосибирск</div>
                        <div class="review-meta">Купила франшизу в ритейле</div>
                    </div>
                    <div class="review-text">Купила франшизу через платформу, документы и согласования прошли быстро. Команда франчайзера на связи, дорабатываем формат под мой город и подбираем оптимальный ассортимент.</div>
                    <div class="review-franchise">
                        <div class="review-logo">Лого</div>
                        <a class="review-link" href="franchise.html">City Retail</a>
                    </div>
                </article>
            </div>
        </div>
        <button class="reviews-arrow next" type="button" aria-label="Следующий отзыв"></button>
    </div>
    <div class="reviews-dots" aria-label="Пагинация отзывов"></div>
</section>

<section class="final-section" id="contacts" aria-label="Форма заявки">
    <h2 class="segment-title">Остались вопросы?</h2>
    <p class="segment-sub">Оставьте контакты — мы свяжемся и подскажем.</p>
    <div class="form-card final-form wpcf7-wrap">
        <?php franchises_render_home_consult_cf7(); ?>
    </div>
</section>


<?php get_footer(); ?>