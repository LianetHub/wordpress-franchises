<?php

/**
 * Шаблон страницы 404.
 */

get_header();

$home_url = home_url('/');
$shop_url = $home_url;

if (class_exists('WooCommerce', false) && function_exists('wc_get_page_id')) {
    $shop_id = wc_get_page_id('shop');
    if ($shop_id > 0) {
        $permalink = get_permalink($shop_id);
        if (is_string($permalink) && $permalink !== '') {
            $shop_url = $permalink;
        }
    }
}

$help_url = home_url('/#contacts');
?>

<?php require_once TEMPLATE_PATH . 'components/breadcrumbs.php'; ?>

<section class="error-404" aria-labelledby="error-404-title">
    <div class="error-404__card" role="status">
        <div class="error-404__visual" aria-hidden="true">
            <span class="error-404__code">404</span>
        </div>
        <h1 id="error-404-title" class="error-404__title">Страница не найдена</h1>
        <p class="error-404__text">
            Запрашиваемый адрес не существует или был перемещён. Перейдите в каталог франшиз или на главную — мы поможем подобрать подходящее предложение.
        </p>
        <div class="error-404__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">Каталог франшиз</a>
            <a class="btn btn-outline" href="<?php echo esc_url($home_url); ?>">На главную</a>
            <a class="btn btn-outline" href="<?php echo esc_url($help_url); ?>">Получить подбор</a>
        </div>
    </div>
</section>

<?php get_footer(); ?>