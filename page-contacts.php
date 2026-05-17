<?php

/**
 * Template Name: Page Contacts
 */

get_header();

$company_name = function_exists('franchises_get_theme_option')
    ? (string) franchises_get_theme_option('company_name', get_bloginfo('name'))
    : (string) get_bloginfo('name');
$logo_icon    = function_exists('franchises_get_theme_option')
    ? franchises_get_theme_option('logo_icon')
    : (function_exists('get_field') ? get_field('logo_icon', 'option') : null);
$logo_url     = is_array($logo_icon) && ! empty($logo_icon['url']) ? (string) $logo_icon['url'] : '';
$email        = function_exists('get_field') ? (string) get_field('email', 'option') : '';
$phone        = function_exists('get_field') ? (string) get_field('phone', 'option') : '';
$address      = function_exists('get_field') ? (string) get_field('address', 'option') : '';
$map_coords   = function_exists('franchises_get_map_coords') ? franchises_get_map_coords() : null;
$map_zoom     = function_exists('franchises_get_theme_option') ? (int) franchises_get_theme_option('map_zoom', 15) : 15;
$map_icon     = function_exists('franchises_get_map_placemark_url') ? franchises_get_map_placemark_url() : '';
$map_api_key  = function_exists('franchises_get_theme_option')
    ? (string) franchises_get_theme_option('yandex_maps_api_key', '')
    : '';
$site_url     = home_url('/');
?>

<?php require_once(TEMPLATE_PATH . '/components/breadcrumbs.php'); ?>

<section class="contacts" itemscope itemtype="https://schema.org/Organization">
    <?php if ($logo_url !== '') : ?>
        <link itemprop="logo" href="<?php echo esc_url($logo_url); ?>">
    <?php endif; ?>
    <meta itemprop="name" content="<?php echo esc_attr($company_name); ?>">
    <meta itemprop="url" content="<?php echo esc_url($site_url); ?>">


    <h1 class="contacts__title title"><?php the_title(); ?></h1>

    <ul class="contacts__list">
        <?php if ($address !== '') : ?>
            <li class="contacts__item icon-location" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                <div class="contacts__item-caption"><?php esc_html_e('Адрес', 'franchises'); ?></div>
                <address class="contacts__item-address" itemprop="streetAddress"><?php echo esc_html($address); ?></address>
            </li>
        <?php endif; ?>

        <?php if ($email !== '') : ?>
            <li class="contacts__item icon-envelope">
                <div class="contacts__item-caption"><?php esc_html_e('Email', 'franchises'); ?></div>
                <a href="mailto:<?php echo esc_attr(sanitize_email($email)); ?>" class="contacts__item-link" itemprop="email"><?php echo esc_html($email); ?></a>
            </li>
        <?php endif; ?>

        <?php if ($phone !== '') : ?>
            <li class="contacts__item icon-phone">
                <div class="contacts__item-caption"><?php esc_html_e('Телефон', 'franchises'); ?></div>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="contacts__item-link" itemprop="telephone"><?php echo esc_html($phone); ?></a>
            </li>
        <?php endif; ?>
    </ul>

    <?php if ($map_coords) : ?>
        <div
            class="contacts__map"
            id="map"
            data-coords="<?php echo esc_attr(implode(',', $map_coords)); ?>"
            data-zoom="<?php echo esc_attr((string) max(1, min(21, $map_zoom))); ?>"
            <?php if ($map_icon !== '') : ?>
            data-icon="<?php echo esc_url($map_icon); ?>"
            <?php endif; ?>
            <?php if ($map_api_key !== '') : ?>
            data-apikey="<?php echo esc_attr($map_api_key); ?>"
            <?php endif; ?>></div>
    <?php endif; ?>

</section>

<?php get_footer(); ?>