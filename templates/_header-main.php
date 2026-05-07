<header class="site-header" aria-label="Навигация">
    <div class="header-inner">
        <button class="menu-toggle" aria-label="Открыть меню" aria-controls="mobile-menu" aria-expanded="false">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"></path>
            </svg>
        </button>
        <div class="brand">
            <a href="<?php echo home_url(); ?>" class="logo">
                <span class="logo__icon">
                    <?php
                    $logo_icon = get_field('logo_icon', 'option');
                    if ($logo_icon):
                    ?>
                        <img src="<?php echo esc_url($logo_icon['url']); ?>" alt="logo icon">
                    <?php endif; ?>
                </span>
                <?php echo esc_html(get_field('logo_text', 'option')); ?>
            </a>
        </div>
        <nav class="nav" aria-label="Основное меню">
            <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Все франшизы</a>
            <div class="nav-dropdown" data-categories-dropdown>
                <button class="nav-dropdown-trigger" type="button" aria-haspopup="true" aria-expanded="false" data-categories-trigger>
                    Категории <svg class="nav-chev" viewBox="0 0 12 8" aria-hidden="true">
                        <path d="M1 1l5 5 5-5"></path>
                    </svg>
                </button>
                <div class="nav-dropdown-panel" data-categories-panel>
                    <div class="categories-menu" role="dialog" aria-label="Категории">
                        <div class="categories-left" data-categories-list></div>
                        <div class="categories-right">
                            <div class="categories-title" data-categories-title>Категория</div>
                            <div class="categories-subgrid" data-categories-subgrid></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="nav-dropdown" data-collections-dropdown>
                <button class="nav-dropdown-trigger" type="button" aria-haspopup="true" aria-expanded="false" data-collections-trigger>
                    Подборки <svg class="nav-chev" viewBox="0 0 12 8" aria-hidden="true">
                        <path d="M1 1l5 5 5-5"></path>
                    </svg>
                </button>
                <div class="nav-dropdown-panel collections-dropdown-panel" data-collections-panel>
                    <div class="collections-menu" role="dialog" aria-label="Подборки">
                        <div class="collections-title">Подборки</div>
                        <div class="collections-subgrid" data-collections-subgrid-header></div>
                    </div>
                </div>
            </div>
        </nav>
        <div class="header-actions">
            <form class="header-search" role="search" aria-label="Поиск франшизы">
                <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M20 20l-3.5-3.5"></path>
                </svg>
                <input type="search" name="q" placeholder="Поиск по франшизам" aria-label="Введите запрос">
                <button class="search-btn" type="submit">Найти</button>
            </form>
        </div>
    </div>
</header>

<div class="mobile-menu" id="mobile-menu" aria-hidden="true">
    <div class="mobile-menu-card" role="dialog" aria-modal="true" aria-label="Меню">
        <div class="mobile-menu-head">
            <a class="logo" href="index.html" aria-label="Лого — на главную">ЛОГО</a>
            <button class="mobile-menu-close" type="button" aria-label="Закрыть меню" data-mobile-close></button>
        </div>
        <div class="mobile-menu-body">
            <form class="header-search" role="search" aria-label="Поиск франшизы">
                <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="M20 20l-3.5-3.5"></path>
                </svg>
                <input type="search" name="q" placeholder="Поиск по франшизам" aria-label="Введите запрос">
                <button class="search-btn" type="submit">Найти</button>
            </form>

            <nav class="mobile-menu-list" aria-label="Навигация">
                <a class="mobile-menu-link" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Все франшизы</a>

                <div class="mobile-acc" data-mobile-acc="categories">
                    <button class="mobile-acc-trigger" type="button" aria-expanded="false" aria-controls="mobile-categories" data-mobile-acc-trigger="categories">
                        Категории <span class="mobile-chev" aria-hidden="true"></span>
                    </button>
                    <div class="mobile-acc-content" id="mobile-categories">
                        <div class="mobile-category-grid" data-mobile-categories-grid></div>
                    </div>
                </div>


            </nav>

            <div class="mobile-menu-cta">
                <a class="btn btn-primary" href="#contacts">Получить подбор</a>
            </div>
        </div>
    </div>
</div>

<div class="mobile-search-bar" aria-label="Поиск по франшизам">
    <form class="header-search" role="search" aria-label="Поиск франшизы">
        <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="7"></circle>
            <path d="M20 20l-3.5-3.5"></path>
        </svg>
        <input type="search" name="q" placeholder="Поиск по франшизам" aria-label="Введите запрос">
        <button class="search-btn" type="submit">Найти</button>
    </form>
</div>