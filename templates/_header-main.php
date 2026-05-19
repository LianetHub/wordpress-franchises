<?php

defined('ABSPATH') || exit;

$shop_url        = franchises_header_shop_url();
$contacts_url    = franchises_header_contacts_url();
$spheres         = franchises_product_cat_get_spheres();
$collections     = franchises_header_get_collections();
$shop_active     = franchises_header_is_shop_active();
$logo_text       = (string) get_field('logo_text', 'option');
$logo_icon       = get_field('logo_icon', 'option');
$has_categories  = $spheres !== [];
$has_collections = $collections !== [];

?>
<header class="site-header" aria-label="<?php esc_attr_e('Навигация', 'franchises'); ?>">
    <div class="header-inner">
        <button class="menu-toggle" type="button" aria-label="<?php esc_attr_e('Открыть меню', 'franchises'); ?>" aria-controls="mobile-menu" aria-expanded="false">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"></path>
            </svg>
        </button>
        <div class="brand">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="logo" aria-label="<?php echo esc_attr($logo_text !== '' ? $logo_text : __('На главную', 'franchises')); ?>">
                <?php if (is_array($logo_icon) && ! empty($logo_icon['url'])) : ?>
                    <span class="logo__icon">
                        <img src="<?php echo esc_url($logo_icon['url']); ?>" alt="">
                    </span>
                <?php endif; ?>
                <?php if ($logo_text !== '') : ?>
                    <?php echo esc_html($logo_text); ?>
                <?php endif; ?>
            </a>
        </div>
        <nav class="nav" aria-label="<?php esc_attr_e('Основное меню', 'franchises'); ?>">
            <a href="<?php echo esc_url($shop_url); ?>" <?php echo $shop_active ? ' aria-current="page"' : ''; ?>><?php esc_html_e('Все франшизы', 'franchises'); ?></a>

            <?php if ($has_categories) : ?>
                <div class="nav-dropdown" data-categories-dropdown>
                    <button class="nav-dropdown-trigger" type="button" aria-haspopup="true" aria-expanded="false" data-categories-trigger>
                        <?php esc_html_e('Категории', 'franchises'); ?>
                        <svg class="nav-chev" viewBox="0 0 12 8" aria-hidden="true">
                            <path d="M1 1l5 5 5-5"></path>
                        </svg>
                    </button>
                    <div class="nav-dropdown-panel" data-categories-panel>
                        <div class="categories-menu" role="dialog" aria-label="<?php esc_attr_e('Категории', 'franchises'); ?>">
                            <div class="categories-left" data-categories-list>
                                <?php foreach ($spheres as $index => $sphere) : ?>
                                    <button
                                        class="categories-item<?php echo $index === 0 ? ' active' : ''; ?>"
                                        type="button"
                                        data-index="<?php echo (int) $index; ?>">
                                        <span><?php echo esc_html($sphere['name']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="categories-right" data-categories-panels>
                                <?php foreach ($spheres as $index => $sphere) : ?>
                                    <div
                                        class="categories-panel<?php echo $index === 0 ? ' is-active' : ''; ?>"
                                        data-categories-panel="<?php echo (int) $index; ?>"
                                        <?php echo $index > 0 ? ' hidden' : ''; ?>>
                                        <div class="categories-title"><?php echo esc_html($sphere['name']); ?></div>
                                        <div class="categories-subgrid">
                                            <?php if ($sphere['children']) : ?>
                                                <?php foreach ($sphere['children'] as $child) : ?>
                                                    <a href="<?php echo esc_url($child['url']); ?>"><?php echo esc_html($child['name']); ?></a>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <a href="<?php echo esc_url($sphere['url']); ?>"><?php esc_html_e('Смотреть все франшизы в отрасли', 'franchises'); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($has_collections) : ?>
                <div class="nav-dropdown" data-collections-dropdown>
                    <button class="nav-dropdown-trigger" type="button" aria-haspopup="true" aria-expanded="false" data-collections-trigger>
                        <?php esc_html_e('Подборки', 'franchises'); ?>
                        <svg class="nav-chev" viewBox="0 0 12 8" aria-hidden="true">
                            <path d="M1 1l5 5 5-5"></path>
                        </svg>
                    </button>
                    <div class="nav-dropdown-panel collections-dropdown-panel" data-collections-panel>
                        <div class="collections-menu" role="dialog" aria-label="<?php esc_attr_e('Подборки', 'franchises'); ?>">
                            <div class="collections-title"><?php esc_html_e('Подборки', 'franchises'); ?></div>
                            <div class="collections-subgrid" data-collections-subgrid-header>
                                <a href="<?php echo esc_url($shop_url); ?>" <?php echo $shop_active ? ' aria-current="page"' : ''; ?>><?php esc_html_e('Все франшизы', 'franchises'); ?></a>
                                <?php foreach ($collections as $item) : ?>
                                    <a href="<?php echo esc_url($item['url']); ?>" <?php echo $item['active'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html($item['title']); ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            if (has_nav_menu('header_menu')) {
                wp_nav_menu([
                    'theme_location' => 'header_menu',
                    'container'      => false,
                    'menu_class'     => 'nav-extra',
                    'depth'          => 1,
                    'fallback_cb'    => false,
                ]);
            }
            ?>
        </nav>
        <div class="header-actions">
            <?php franchises_render_header_search('desktop'); ?>
        </div>
    </div>
</header>

<div class="catalog-dropdown-backdrop" data-categories-backdrop aria-hidden="true"></div>

<div class="mobile-menu" id="mobile-menu" aria-hidden="true">
    <div class="mobile-menu-card" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Меню', 'franchises'); ?>">
        <div class="mobile-menu-head">
            <a class="logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr($logo_text !== '' ? $logo_text : __('На главную', 'franchises')); ?>">
                <?php echo esc_html($logo_text !== '' ? $logo_text : __('ЛОГО', 'franchises')); ?>
            </a>
            <button class="mobile-menu-close" type="button" aria-label="<?php esc_attr_e('Закрыть меню', 'franchises'); ?>" data-mobile-close></button>
        </div>
        <div class="mobile-menu-body">
            <?php franchises_render_header_search('mobile-menu'); ?>

            <nav class="mobile-menu-list" aria-label="<?php esc_attr_e('Навигация', 'franchises'); ?>">
                <a class="mobile-menu-link" href="<?php echo esc_url($shop_url); ?>" <?php echo $shop_active ? ' aria-current="page"' : ''; ?>><?php esc_html_e('Все франшизы', 'franchises'); ?></a>

                <?php if ($has_categories) : ?>
                    <div class="mobile-acc" data-mobile-acc="categories">
                        <button class="mobile-acc-trigger" type="button" aria-expanded="false" aria-controls="mobile-categories" data-mobile-acc-trigger="categories">
                            <?php esc_html_e('Категории', 'franchises'); ?>
                            <span class="mobile-chev" aria-hidden="true"></span>
                        </button>
                        <div class="mobile-acc-content" id="mobile-categories">
                            <div class="mobile-category-grid" data-mobile-categories-grid>
                                <?php foreach ($spheres as $sphere) : ?>
                                    <a class="chip" href="<?php echo esc_url($sphere['landing_url']); ?>">
                                        <span class="icon" aria-hidden="true"><?php
                                                                                echo franchises_product_cat_icon_html(
                                                                                    (int) ($sphere['term_id'] ?? 0),
                                                                                    (string) ($sphere['name'] ?? '')
                                                                                );
                                                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                                                ?></span>
                                        <span class="chip-text"><?php echo esc_html($sphere['name']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($has_collections) : ?>
                    <div class="mobile-acc" data-mobile-acc="collections">
                        <button class="mobile-acc-trigger" type="button" aria-expanded="false" aria-controls="mobile-collections" data-mobile-acc-trigger="collections">
                            <?php esc_html_e('Подборки', 'franchises'); ?>
                            <span class="mobile-chev" aria-hidden="true"></span>
                        </button>
                        <div class="mobile-acc-content" id="mobile-collections">
                            <div class="mobile-category-grid" data-mobile-collections-grid>
                                <a class="chip mobile-collections-chip<?php echo $shop_active ? ' is-active' : ''; ?>" href="<?php echo esc_url($shop_url); ?>">
                                    <span class="chip-text"><?php esc_html_e('Все франшизы', 'franchises'); ?></span>
                                </a>
                                <?php foreach ($collections as $item) : ?>
                                    <a class="chip mobile-collections-chip<?php echo $item['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>">
                                        <span class="chip-text"><?php echo esc_html($item['title']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>

            <div class="mobile-menu-cta">
                <a class="btn btn-primary" href="<?php echo esc_url($contacts_url); ?>"><?php esc_html_e('Получить подбор', 'franchises'); ?></a>
            </div>
        </div>
    </div>
</div>

<div class="mobile-search-bar" aria-label="<?php esc_attr_e('Поиск по франшизам', 'franchises'); ?>">
    <?php franchises_render_header_search('mobile-bar'); ?>
</div>