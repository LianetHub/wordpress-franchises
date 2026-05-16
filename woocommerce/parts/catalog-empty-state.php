<?php

/**
 * Пустой результат каталога франшиз (фильтры / поиск).
 */

defined('ABSPATH') || exit;

$has_filters = function_exists('franchises_catalog_has_active_filters')
    ? franchises_catalog_has_active_filters()
    : false;
$reset_url = function_exists('franchises_catalog_reset_filters_url')
    ? franchises_catalog_reset_filters_url()
    : home_url('/');
$help_url = home_url('/#contacts');
?>
<div class="catalog-empty" role="status" aria-live="polite">
    <div class="catalog-empty__visual" aria-hidden="true">
        <svg class="catalog-empty__icon" width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="8" y="12" width="32" height="36" rx="6" stroke="currentColor" stroke-width="2" />
            <path d="M20 22h16M20 28h12M20 34h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <circle cx="40" cy="40" r="10" stroke="currentColor" stroke-width="2" />
            <path d="M47 47l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
    </div>
    <h2 class="catalog-empty__title">Франшизы не найдены</h2>
    <p class="catalog-empty__text">
        <?php if ($has_filters) : ?>
            По выбранным параметрам подходящих предложений нет. Смягчите фильтры или сбросьте их, чтобы увидеть весь каталог.
        <?php else : ?>
            В этом разделе пока нет опубликованных франшиз. Загляните в каталог позже или оставьте заявку на подбор.
        <?php endif; ?>
    </p>
    <div class="catalog-empty__actions">
        <?php if ($has_filters) : ?>
            <a class="btn btn-primary" href="<?php echo esc_url($reset_url); ?>">Сбросить фильтры</a>
        <?php endif; ?>
        <a class="btn btn-outline" href="<?php echo esc_url($help_url); ?>">Получить подбор</a>
    </div>
</div>