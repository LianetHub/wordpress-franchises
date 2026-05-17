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

$get_404_option = static function (string $field, string $default): string {
    if (function_exists('franchises_get_theme_option')) {
        return (string) franchises_get_theme_option($field, $default);
    }

    if (function_exists('get_field')) {
        $value = get_field($field, 'option');
        if ($value !== null && $value !== false && $value !== '') {
            return (string) $value;
        }
    }

    return $default;
};

$error_404_title    = $get_404_option('404_title', __('Страница не найдена', 'franchises'));
$error_404_subtitle = $get_404_option('404_subtitle', __('Запрашиваемый адрес не существует или был перемещён. Перейдите в каталог франшиз или на главную — мы поможем подобрать подходящее предложение.', 'franchises'));

?>

<?php require_once TEMPLATE_PATH . 'components/breadcrumbs.php'; ?>

<section class="error-404" aria-labelledby="error-404-title">
    <div class="error-404__card" role="status">
        <div class="error-404__visual" aria-hidden="true">
            <span class="error-404__code">404</span>
        </div>
        <h1 id="error-404-title" class="error-404__title"><?php echo esc_html($error_404_title); ?></h1>
        <p class="error-404__text"><?php echo esc_html($error_404_subtitle); ?></p>
        <div class="error-404__actions">
            <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>">Каталог франшиз</a>
            <a class="btn btn-outline" href="<?php echo esc_url($home_url); ?>">На главную</a>
            <a class="btn btn-outline" data-fancybox href="#selection-popup">Получить подбор</a>
        </div>
    </div>
</section>

<?php get_footer(); ?>