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

<section class="popular-section stats-next-tight" aria-label="Популярные франшизы" data-popular-section>
    <div class="popular-head">
        <div>
            <h2 class="segment-title">Популярные франшизы</h2>
            <p class="popular-sub">Самые востребованные франшизы с устойчивой экономикой и проверенной моделью.</p>
        </div>
    </div>
    <div class="popular-grid" data-popular-grid>
        <a class="popular-card" href="franchise-fit-service.html" data-order="0" data-date="0" data-popularity="100" data-sphere="Авто" data-category="Автосервисы" data-invest="1250000" data-payback="18" data-profit="380000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="FIT SERVICE" data-desc="Франшиза автосервиса · сильная сеть и высокий поток" data-image="https://cdn.businessmens.ru/2048x-/franchise_file/1338/76560_prev001.jpg" data-franchise-id="fit-service" data-franchise-url="franchise-fit-service.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://cdn.businessmens.ru/2048x-/franchise_file/1338/76560_prev001.jpg"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">FIT SERVICE</div>
            <div class="popular-desc">Франшиза автосервиса · сильная сеть и высокий поток</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">1 250 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-apteka-zdorovo.html" data-order="0" data-date="0" data-popularity="99" data-sphere="Торговля" data-category="Аптеки" data-invest="650000" data-payback="10" data-profit="180000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Аптека Здорово" data-desc="Франшиза аптеки · стабильный спрос и быстрый старт" data-image="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="apteka-zdorovo" data-franchise-url="franchise-apteka-zdorovo.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">Аптека Здорово</div>
            <div class="popular-desc">Франшиза аптеки · стабильный спрос и быстрый старт</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">650 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-kafe-kruzhka.html" data-order="0" data-date="0" data-popularity="98" data-sphere="Еда" data-category="Пекарни" data-invest="700000" data-payback="11" data-profit="220000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Кафе Кружка" data-desc="Франшиза пекарни и кафе · трафик у дома и на доставке" data-image="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="kafe-kruzhka" data-franchise-url="franchise-kafe-kruzhka.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">Кафе Кружка</div>
            <div class="popular-desc">Франшиза пекарни и кафе · трафик у дома и на доставке</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">700 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-carwash-24.html" data-order="0" data-date="0" data-popularity="97" data-sphere="Авто" data-category="Автосервисы" data-invest="1100000" data-payback="15" data-profit="300000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="CarWash 24" data-desc="Франшиза автосервиса · круглосуточный поток клиентов" data-image="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="carwash-24" data-franchise-url="franchise-carwash-24.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">CarWash 24</div>
            <div class="popular-desc">Франшиза автосервиса · круглосуточный поток клиентов</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">1 100 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-tyre-service-pro.html" data-order="0" data-date="0" data-popularity="96" data-sphere="Авто" data-category="Автосервисы" data-invest="850000" data-payback="13" data-profit="240000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Tyre Service Pro" data-desc="Франшиза шиномонтажа · сезонный спрос и сервис" data-image="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="tyre-service-pro" data-franchise-url="franchise-tyre-service-pro.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">Tyre Service Pro</div>
            <div class="popular-desc">Франшиза шиномонтажа · сезонный спрос и сервис</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">850 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-sovetskaya-apteka.html" data-order="0" data-date="0" data-popularity="95" data-sphere="Торговля" data-category="Аптеки" data-invest="590000" data-payback="12" data-profit="165000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Советская аптека" data-desc="Франшиза аптеки · повседневный спрос и быстрый старт" data-image="https://cdn.businessmens.ru/2048x-/franchise_file/1338/3fcaae.jpg" data-franchise-id="sovetskaya-apteka" data-franchise-url="franchise-sovetskaya-apteka.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://cdn.businessmens.ru/2048x-/franchise_file/1338/3fcaae.jpg"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">Советская аптека</div>
            <div class="popular-desc">Франшиза аптеки · повседневный спрос и быстрый старт</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">590 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-english-room.html" data-order="0" data-date="0" data-popularity="95" data-sphere="Обучение" data-category="Языковые школы" data-invest="450000" data-payback="8" data-profit="120000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="English Room" data-desc="Франшиза языковой школы · онлайн и офлайн форматы" data-image="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="english-room" data-franchise-url="franchise-english-room.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">English Room</div>
            <div class="popular-desc">Франшиза языковой школы · онлайн и офлайн форматы</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">450 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-semeynaya-stomatologiya.html" data-order="0" data-date="0" data-popularity="92" data-sphere="Красота и здоровье" data-category="Стоматологии" data-invest="780000" data-payback="14" data-profit="260000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Семейная стоматология" data-desc="Франшиза стоматологии · современный сервис и сильный маркетинг" data-image="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="semeynaya-stomatologiya" data-franchise-url="franchise-semeynaya-stomatologiya.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">Семейная стоматология</div>
            <div class="popular-desc">Франшиза стоматологии · современный сервис и сильный маркетинг</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">780 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-nastoyashaya-pekarna.html" data-order="0" data-date="0" data-popularity="90" data-sphere="Еда" data-category="Пекарни" data-invest="420000" data-payback="9" data-profit="140000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Настоящая пекарня" data-desc="Франшиза пекарни · свежая выпечка у дома" data-image="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="nastoyashaya-pekarna" data-franchise-url="franchise-nastoyashaya-pekarna.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">Настоящая пекарня</div>
            <div class="popular-desc">Франшиза пекарни · свежая выпечка у дома</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">420 000 ₽</span></div>
        </a>
        <a class="popular-card" href="franchise-lingua-club.html" data-order="0" data-date="0" data-popularity="88" data-sphere="Обучение" data-category="Языковые школы" data-invest="360000" data-payback="8" data-profit="110000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Lingua Club" data-desc="Франшиза языковой школы · групповые и индивидуальные занятия" data-image="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="lingua-club" data-franchise-url="franchise-lingua-club.html">
            <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
            <div class="popular-brand">Lingua Club</div>
            <div class="popular-desc">Франшиза языковой школы · групповые и индивидуальные занятия</div>
            <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">360 000 ₽</span></div>
        </a>
    </div>
    <div class="segment-actions">
        <a class="btn btn-primary" href="<?php echo esc_url($popular_all_href); ?>" data-popular-open>Смотреть все популярные франшизы</a>
    </div>
</section>

<section class="segment-section" id="collections" aria-label="Подборки" data-collections-section>
    <div class="segment-block">
        <div class="segment-head">
            <div>
                <h2 class="segment-title">Подборки</h2>
                <p class="segment-sub">Подборки синхронизируются с каталогом и показывают франшизы внутри выбранной группы.</p>
            </div>
        </div>
        <div class="segment-tabs segment-tags" aria-label="Подборки" data-collections-chips></div>
        <div class="popular-grid" data-cards-source hidden>
            <a class="popular-card" href="franchise-fit-service.html" data-order="0" data-date="0" data-popularity="100" data-sphere="Авто" data-category="Автосервисы" data-invest="1250000" data-payback="18" data-profit="380000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="FIT SERVICE" data-desc="Франшиза автосервиса · сильная сеть и высокий поток" data-image="https://cdn.businessmens.ru/2048x-/franchise_file/1338/76560_prev001.jpg" data-franchise-id="fit-service" data-franchise-url="franchise-fit-service.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://cdn.businessmens.ru/2048x-/franchise_file/1338/76560_prev001.jpg"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">FIT SERVICE</div>
                <div class="popular-desc">Франшиза автосервиса · сильная сеть и высокий поток</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">1 250 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-apteka-zdorovo.html" data-order="0" data-date="0" data-popularity="99" data-sphere="Торговля" data-category="Аптеки" data-invest="650000" data-payback="10" data-profit="180000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Аптека Здорово" data-desc="Франшиза аптеки · стабильный спрос и быстрый старт" data-image="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="apteka-zdorovo" data-franchise-url="franchise-apteka-zdorovo.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Аптека Здорово</div>
                <div class="popular-desc">Франшиза аптеки · стабильный спрос и быстрый старт</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">650 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-kafe-kruzhka.html" data-order="0" data-date="0" data-popularity="98" data-sphere="Еда" data-category="Пекарни" data-invest="700000" data-payback="11" data-profit="220000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Кафе Кружка" data-desc="Франшиза пекарни и кафе · трафик у дома и на доставке" data-image="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="kafe-kruzhka" data-franchise-url="franchise-kafe-kruzhka.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Кафе Кружка</div>
                <div class="popular-desc">Франшиза пекарни и кафе · трафик у дома и на доставке</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">700 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-carwash-24.html" data-order="0" data-date="0" data-popularity="97" data-sphere="Авто" data-category="Автосервисы" data-invest="1100000" data-payback="15" data-profit="300000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="CarWash 24" data-desc="Франшиза автосервиса · круглосуточный поток клиентов" data-image="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="carwash-24" data-franchise-url="franchise-carwash-24.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">CarWash 24</div>
                <div class="popular-desc">Франшиза автосервиса · круглосуточный поток клиентов</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">1 100 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-tyre-service-pro.html" data-order="0" data-date="0" data-popularity="96" data-sphere="Авто" data-category="Автосервисы" data-invest="850000" data-payback="13" data-profit="240000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Tyre Service Pro" data-desc="Франшиза шиномонтажа · сезонный спрос и сервис" data-image="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="tyre-service-pro" data-franchise-url="franchise-tyre-service-pro.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Tyre Service Pro</div>
                <div class="popular-desc">Франшиза шиномонтажа · сезонный спрос и сервис</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">850 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-sovetskaya-apteka.html" data-order="0" data-date="0" data-popularity="95" data-sphere="Торговля" data-category="Аптеки" data-invest="590000" data-payback="12" data-profit="165000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Советская аптека" data-desc="Франшиза аптеки · повседневный спрос и быстрый старт" data-image="https://cdn.businessmens.ru/2048x-/franchise_file/1338/3fcaae.jpg" data-franchise-id="sovetskaya-apteka" data-franchise-url="franchise-sovetskaya-apteka.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://cdn.businessmens.ru/2048x-/franchise_file/1338/3fcaae.jpg"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Советская аптека</div>
                <div class="popular-desc">Франшиза аптеки · повседневный спрос и быстрый старт</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">590 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-english-room.html" data-order="0" data-date="0" data-popularity="95" data-sphere="Обучение" data-category="Языковые школы" data-invest="450000" data-payback="8" data-profit="120000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="English Room" data-desc="Франшиза языковой школы · онлайн и офлайн форматы" data-image="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="english-room" data-franchise-url="franchise-english-room.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">English Room</div>
                <div class="popular-desc">Франшиза языковой школы · онлайн и офлайн форматы</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">450 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-semeynaya-stomatologiya.html" data-order="0" data-date="0" data-popularity="92" data-sphere="Красота и здоровье" data-category="Стоматологии" data-invest="780000" data-payback="14" data-profit="260000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Семейная стоматология" data-desc="Франшиза стоматологии · современный сервис и сильный маркетинг" data-image="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="semeynaya-stomatologiya" data-franchise-url="franchise-semeynaya-stomatologiya.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Семейная стоматология</div>
                <div class="popular-desc">Франшиза стоматологии · современный сервис и сильный маркетинг</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">780 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-nastoyashaya-pekarna.html" data-order="0" data-date="0" data-popularity="90" data-sphere="Еда" data-category="Пекарни" data-invest="420000" data-payback="9" data-profit="140000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Настоящая пекарня" data-desc="Франшиза пекарни · свежая выпечка у дома" data-image="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="nastoyashaya-pekarna" data-franchise-url="franchise-nastoyashaya-pekarna.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Настоящая пекарня</div>
                <div class="popular-desc">Франшиза пекарни · свежая выпечка у дома</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">420 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-lingua-club.html" data-order="0" data-date="0" data-popularity="88" data-sphere="Обучение" data-category="Языковые школы" data-invest="360000" data-payback="8" data-profit="110000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Lingua Club" data-desc="Франшиза языковой школы · групповые и индивидуальные занятия" data-image="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="lingua-club" data-franchise-url="franchise-lingua-club.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Lingua Club</div>
                <div class="popular-desc">Франшиза языковой школы · групповые и индивидуальные занятия</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">360 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-techno-shop.html" data-order="0" data-date="0" data-popularity="67" data-sphere="Торговля" data-category="Электроника" data-invest="900000" data-payback="16" data-profit="210000" data-verified="false" data-tags="Новые франшизы" data-name="Techno Shop" data-desc="Франшиза электроники · компактный магазин гаджетов" data-image="https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="techno-shop" data-franchise-url="franchise-techno-shop.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">Techno Shop</div>
                <div class="popular-desc">Франшиза электроники · компактный магазин гаджетов</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">900 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-cosmo-studio.html" data-order="0" data-date="0" data-popularity="66" data-sphere="Красота и здоровье" data-category="Косметология" data-invest="600000" data-payback="12" data-profit="200000" data-verified="false" data-tags="Новые франшизы" data-name="Cosmo Studio" data-desc="Франшиза косметологии · бьюти-сервис и повторные продажи" data-image="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="cosmo-studio" data-franchise-url="franchise-cosmo-studio.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">Cosmo Studio</div>
                <div class="popular-desc">Франшиза косметологии · бьюти-сервис и повторные продажи</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">600 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-kids-club.html" data-order="0" data-date="0" data-popularity="65" data-sphere="Обучение" data-category="Детские центры" data-invest="580000" data-payback="10" data-profit="140000" data-verified="false" data-tags="Новые франшизы" data-name="Kids Club" data-desc="Франшиза детского центра · развитие и досуг" data-image="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="kids-club" data-franchise-url="franchise-kids-club.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">Kids Club</div>
                <div class="popular-desc">Франшиза детского центра · развитие и досуг</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">580 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-coffeeway.html" data-order="0" data-date="0" data-popularity="64" data-sphere="Еда" data-category="Кофейни" data-invest="620000" data-payback="10" data-profit="160000" data-verified="false" data-tags="Новые франшизы" data-name="CoffeeWay" data-desc="Франшиза кофейни · take away и быстрая посадка" data-image="https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="coffeeway" data-franchise-url="franchise-coffeeway.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">CoffeeWay</div>
                <div class="popular-desc">Франшиза кофейни · take away и быстрая посадка</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">620 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-detailing-lab.html" data-order="0" data-date="0" data-popularity="63" data-sphere="Авто" data-category="Автосервисы" data-invest="980000" data-payback="14" data-profit="260000" data-verified="false" data-tags="Новые франшизы" data-name="Detailing Lab" data-desc="Франшиза детейлинга · премиальный уход за авто" data-image="https://images.unsplash.com/photo-1494976388531-d1058494cdd8?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="detailing-lab" data-franchise-url="franchise-detailing-lab.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1494976388531-d1058494cdd8?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">Detailing Lab</div>
                <div class="popular-desc">Франшиза детейлинга · премиальный уход за авто</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">980 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-it-school.html" data-order="0" data-date="0" data-popularity="62" data-sphere="Обучение" data-category="Детские центры" data-invest="500000" data-payback="10" data-profit="150000" data-verified="false" data-tags="Новые франшизы" data-name="IT School" data-desc="Франшиза школы IT · обучение разработке и дизайну" data-image="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="it-school" data-franchise-url="franchise-it-school.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">IT School</div>
                <div class="popular-desc">Франшиза школы IT · обучение разработке и дизайну</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">500 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-apteka-gorod.html" data-order="0" data-date="0" data-popularity="61" data-sphere="Торговля" data-category="Аптеки" data-invest="540000" data-payback="11" data-profit="145000" data-verified="false" data-tags="Новые франшизы" data-name="Аптека Город" data-desc="Франшиза аптеки · формат для спальных районов" data-image="https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="apteka-gorod" data-franchise-url="franchise-apteka-gorod.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">Аптека Город</div>
                <div class="popular-desc">Франшиза аптеки · формат для спальных районов</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">540 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-dental-plus.html" data-order="0" data-date="0" data-popularity="60" data-sphere="Красота и здоровье" data-category="Стоматологии" data-invest="950000" data-payback="14" data-profit="320000" data-verified="false" data-tags="Новые франшизы" data-name="Dental Plus" data-desc="Франшиза стоматологии · современная клиника и сервис" data-image="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="dental-plus" data-franchise-url="franchise-dental-plus.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">Dental Plus</div>
                <div class="popular-desc">Франшиза стоматологии · современная клиника и сервис</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">950 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-avtomoyka-city.html" data-order="0" data-date="0" data-popularity="59" data-sphere="Авто" data-category="Автомойки" data-invest="760000" data-payback="12" data-profit="190000" data-verified="false" data-tags="Новые франшизы" data-name="Автомойка City" data-desc="Франшиза автомойки · быстрый запуск и поток машин" data-image="https://images.unsplash.com/photo-1520340356584-f9917d1eea6f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="avtomoyka-city" data-franchise-url="franchise-avtomoyka-city.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1520340356584-f9917d1eea6f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">Автомойка City</div>
                <div class="popular-desc">Франшиза автомойки · быстрый запуск и поток машин</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">760 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-pekarnya-dom.html" data-order="0" data-date="0" data-popularity="58" data-sphere="Еда" data-category="Пекарни" data-invest="480000" data-payback="9" data-profit="130000" data-verified="false" data-tags="Новые франшизы" data-name="Пекарня Дом" data-desc="Франшиза пекарни · домашняя выпечка и кофе" data-image="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="pekarnya-dom" data-franchise-url="franchise-pekarnya-dom.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"></div>
                <div class="popular-brand">Пекарня Дом</div>
                <div class="popular-desc">Франшиза пекарни · домашняя выпечка и кофе</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">480 000 ₽</span></div>
            </a>
        </div>
        <div class="popular-grid" data-collections-grid>
            <a class="popular-card" href="franchise-fit-service.html" data-order="0" data-date="0" data-popularity="100" data-sphere="Авто" data-category="Автосервисы" data-invest="1250000" data-payback="18" data-profit="380000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="FIT SERVICE" data-desc="Франшиза автосервиса · сильная сеть и высокий поток" data-image="https://cdn.businessmens.ru/2048x-/franchise_file/1338/76560_prev001.jpg" data-franchise-id="fit-service" data-franchise-url="franchise-fit-service.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://cdn.businessmens.ru/2048x-/franchise_file/1338/76560_prev001.jpg"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">FIT SERVICE</div>
                <div class="popular-desc">Франшиза автосервиса · сильная сеть и высокий поток</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">1 250 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-apteka-zdorovo.html" data-order="0" data-date="0" data-popularity="99" data-sphere="Торговля" data-category="Аптеки" data-invest="650000" data-payback="10" data-profit="180000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Аптека Здорово" data-desc="Франшиза аптеки · стабильный спрос и быстрый старт" data-image="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="apteka-zdorovo" data-franchise-url="franchise-apteka-zdorovo.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1587854692152-cbe660dbde88?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Аптека Здорово</div>
                <div class="popular-desc">Франшиза аптеки · стабильный спрос и быстрый старт</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">650 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-kafe-kruzhka.html" data-order="0" data-date="0" data-popularity="98" data-sphere="Еда" data-category="Пекарни" data-invest="700000" data-payback="11" data-profit="220000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Кафе Кружка" data-desc="Франшиза пекарни и кафе · трафик у дома и на доставке" data-image="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="kafe-kruzhka" data-franchise-url="franchise-kafe-kruzhka.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Кафе Кружка</div>
                <div class="popular-desc">Франшиза пекарни и кафе · трафик у дома и на доставке</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">700 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-carwash-24.html" data-order="0" data-date="0" data-popularity="97" data-sphere="Авто" data-category="Автосервисы" data-invest="1100000" data-payback="15" data-profit="300000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="CarWash 24" data-desc="Франшиза автосервиса · круглосуточный поток клиентов" data-image="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="carwash-24" data-franchise-url="franchise-carwash-24.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">CarWash 24</div>
                <div class="popular-desc">Франшиза автосервиса · круглосуточный поток клиентов</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">1 100 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-tyre-service-pro.html" data-order="0" data-date="0" data-popularity="96" data-sphere="Авто" data-category="Автосервисы" data-invest="850000" data-payback="13" data-profit="240000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="Tyre Service Pro" data-desc="Франшиза шиномонтажа · сезонный спрос и сервис" data-image="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="tyre-service-pro" data-franchise-url="franchise-tyre-service-pro.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Tyre Service Pro</div>
                <div class="popular-desc">Франшиза шиномонтажа · сезонный спрос и сервис</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">850 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-sovetskaya-apteka.html" data-order="0" data-date="0" data-popularity="95" data-sphere="Торговля" data-category="Аптеки" data-invest="590000" data-payback="12" data-profit="165000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Советская аптека" data-desc="Франшиза аптеки · повседневный спрос и быстрый старт" data-image="https://cdn.businessmens.ru/2048x-/franchise_file/1338/3fcaae.jpg" data-franchise-id="sovetskaya-apteka" data-franchise-url="franchise-sovetskaya-apteka.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://cdn.businessmens.ru/2048x-/franchise_file/1338/3fcaae.jpg"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Советская аптека</div>
                <div class="popular-desc">Франшиза аптеки · повседневный спрос и быстрый старт</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">590 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-english-room.html" data-order="0" data-date="0" data-popularity="95" data-sphere="Обучение" data-category="Языковые школы" data-invest="450000" data-payback="8" data-profit="120000" data-verified="true" data-tags="Популярные франшизы|Проверено|Новые франшизы" data-name="English Room" data-desc="Франшиза языковой школы · онлайн и офлайн форматы" data-image="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="english-room" data-franchise-url="franchise-english-room.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">English Room</div>
                <div class="popular-desc">Франшиза языковой школы · онлайн и офлайн форматы</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">450 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-semeynaya-stomatologiya.html" data-order="0" data-date="0" data-popularity="92" data-sphere="Красота и здоровье" data-category="Стоматологии" data-invest="780000" data-payback="14" data-profit="260000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Семейная стоматология" data-desc="Франшиза стоматологии · современный сервис и сильный маркетинг" data-image="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="semeynaya-stomatologiya" data-franchise-url="franchise-semeynaya-stomatologiya.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1606811971618-4486d14f3f99?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Семейная стоматология</div>
                <div class="popular-desc">Франшиза стоматологии · современный сервис и сильный маркетинг</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">780 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-nastoyashaya-pekarna.html" data-order="0" data-date="0" data-popularity="90" data-sphere="Еда" data-category="Пекарни" data-invest="420000" data-payback="9" data-profit="140000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Настоящая пекарня" data-desc="Франшиза пекарни · свежая выпечка у дома" data-image="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="nastoyashaya-pekarna" data-franchise-url="franchise-nastoyashaya-pekarna.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Настоящая пекарня</div>
                <div class="popular-desc">Франшиза пекарни · свежая выпечка у дома</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">420 000 ₽</span></div>
            </a>
            <a class="popular-card" href="franchise-lingua-club.html" data-order="0" data-date="0" data-popularity="88" data-sphere="Обучение" data-category="Языковые школы" data-invest="360000" data-payback="8" data-profit="110000" data-verified="true" data-tags="Популярные франшизы|Проверено" data-name="Lingua Club" data-desc="Франшиза языковой школы · групповые и индивидуальные занятия" data-image="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70" data-franchise-id="lingua-club" data-franchise-url="franchise-lingua-club.html">
                <div class="popular-media"><img loading="lazy" alt="" src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=1200&amp;q=70"><span class="popular-badge">Проверено</span></div>
                <div class="popular-brand">Lingua Club</div>
                <div class="popular-desc">Франшиза языковой школы · групповые и индивидуальные занятия</div>
                <div class="popular-meta"><span class="meta-label">Инвестиции от</span><span class="meta-value">360 000 ₽</span></div>
            </a>
        </div>
        <div class="segment-actions">
            <a class="btn btn-primary" href="<?php echo esc_url(class_exists('WooCommerce', false) && function_exists('wc_get_page_id') ? add_query_arg('verified', '1', (string) get_permalink(wc_get_page_id('shop'))) : home_url('/')); ?>" data-collections-open>Смотреть подборку полностью</a>
        </div>
    </div>
</section>


<section class="help-section" aria-label="Поможем подобрать франшизу">
    <div class="help-panel">
        <h2 class="help-title">Не нашли подходящую франшизу?</h2>
        <p class="help-text">Оставьте заявку — подберём варианты под ваш бюджет и цели и свяжемся в течение дня.</p>
        <a class="btn btn-primary" href="#contacts">Получить подбор</a>
    </div>
</section>

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
        <div class="logo-strip">
            <div class="logo-card">LOGO</div>
            <div class="logo-card">LOGO</div>
            <div class="logo-card">LOGO</div>
            <div class="logo-card">LOGO</div>
            <div class="logo-card">LOGO</div>
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
        <div class="reviews-strip">
            <article class="review-card">
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
            <article class="review-card">
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
            <article class="review-card">
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
            <article class="review-card">
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
        <button class="reviews-arrow next" type="button" aria-label="Следующий отзыв"></button>
    </div>
    <div class="reviews-dots" aria-label="Пагинация отзывов"></div>
</section>

<section class="final-section" id="contacts" aria-label="Форма заявки">
    <h2 class="segment-title">Остались вопросы?</h2>
    <p class="segment-sub">Оставьте контакты — мы свяжемся и подскажем.</p>
    <form class="form-card final-form">
        <div class="field">
            <label for="lead_name">Имя</label>
            <input class="input" id="lead_name" type="text" name="lead_name" placeholder="Ваше имя">
        </div>
        <div class="field">
            <label for="lead_phone">Телефон</label>
            <input class="input" id="lead_phone" type="tel" name="lead_phone" placeholder="+7 (___) ___‑__‑__">
        </div>
        <button class="btn btn-primary" type="submit">Получить консультацию</button>
        <label class="consent">
            <input type="checkbox" name="consent" required>
            <span>Я соглашаюсь на обработку персональных данных и принимаю <a href="privacy-policy.html">политику конфиденциальности</a>.</span>
        </label>
    </form>
</section>


<?php get_footer(); ?>