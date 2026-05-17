<?php

/**
 * Template Name: Page Contacts
 */

get_header();

$icons_uri = get_template_directory_uri() . '/assets/img/icons/';

$get_option = static function (string $field, $default = '') {
    if (function_exists('franchises_get_theme_option')) {
        return franchises_get_theme_option($field, $default);
    }

    if (function_exists('get_field')) {
        $value = get_field($field, 'option');
        if ($value !== null && $value !== false && $value !== '') {
            return $value;
        }
    }

    return $default;
};

$format_digits = static function ($value): string {
    if (function_exists('franchises_format_theme_digits')) {
        return franchises_format_theme_digits($value);
    }

    return $value === null || $value === false || $value === '' ? '' : preg_replace('/\D+/', '', (string) $value);
};

$company_name   = (string) $get_option('company_name', get_bloginfo('name'));
$logo_icon      = $get_option('logo_icon');
$logo_url       = is_array($logo_icon) && ! empty($logo_icon['url']) ? (string) $logo_icon['url'] : '';
$email          = (string) $get_option('email');
$phone          = (string) $get_option('phone');
$address        = (string) $get_option('address');
$post_index     = $format_digits($get_option('post_index_number'));
$ogrn           = $format_digits($get_option('ogrn_number'));
$inn            = $format_digits($get_option('inn_number'));
$kpp            = $format_digits($get_option('kpp_number'));
$worktime       = trim((string) $get_option('worktime'));
$map_coords     = function_exists('franchises_get_map_coords') ? franchises_get_map_coords() : null;
$map_zoom       = (int) $get_option('map_zoom', 15);
$map_icon       = function_exists('franchises_get_map_placemark_url') ? franchises_get_map_placemark_url() : '';
$map_api_key    = (string) $get_option('yandex_maps_api_key', '');
$site_url       = home_url('/');

$address_lines = [];
if ($post_index !== '') {
    $address_lines[] = $post_index;
}
if ($address !== '') {
    $address_lines[] = $address;
}
$address_display = implode(', ', $address_lines);

$legal_lines = array_filter([
    $ogrn !== '' ? sprintf(/* translators: %s: OGRN number */__('ОГРН: %s', 'franchises'), $ogrn) : '',
    $inn !== '' ? sprintf(/* translators: %s: INN number */__('ИНН: %s', 'franchises'), $inn) : '',
    $kpp !== '' ? sprintf(/* translators: %s: KPP number */__('КПП: %s', 'franchises'), $kpp) : '',
]);
?>

<?php require_once TEMPLATE_PATH . '/components/breadcrumbs.php'; ?>

<section class="contacts" itemscope itemtype="https://schema.org/Organization">
    <?php if ($logo_url !== '') : ?>
        <link itemprop="logo" href="<?php echo esc_url($logo_url); ?>">
    <?php endif; ?>
    <meta itemprop="name" content="<?php echo esc_attr($company_name); ?>">
    <meta itemprop="url" content="<?php echo esc_url($site_url); ?>">

    <h1 class="contacts__title title"><?php the_title(); ?></h1>

    <ul class="contacts__list">
        <?php if ($company_name !== '') : ?>
            <li class="contacts__item">
                <img class="contacts__icon" src="<?php echo esc_url($icons_uri . 'building.svg'); ?>" alt="" width="24" height="24" decoding="async">
                <div class="contacts__item-body">
                    <div class="contacts__item-caption"><?php esc_html_e('Организация', 'franchises'); ?></div>
                    <p class="contacts__item-text"><?php echo esc_html($company_name); ?></p>
                </div>
            </li>
        <?php endif; ?>

        <?php if ($address_display !== '') : ?>
            <li class="contacts__item" itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                <img class="contacts__icon" src="<?php echo esc_url($icons_uri . 'location.svg'); ?>" alt="" width="24" height="24" decoding="async">
                <div class="contacts__item-body">
                    <div class="contacts__item-caption"><?php esc_html_e('Адрес', 'franchises'); ?></div>
                    <address class="contacts__item-address">
                        <?php if ($post_index !== '') : ?>
                            <span itemprop="postalCode"><?php echo esc_html($post_index); ?></span><?php echo $address !== '' ? ', ' : ''; ?>
                        <?php endif; ?>
                        <?php if ($address !== '') : ?>
                            <span itemprop="streetAddress"><?php echo esc_html($address); ?></span>
                        <?php endif; ?>
                    </address>
                </div>
            </li>
        <?php endif; ?>

        <?php if ($phone !== '') : ?>
            <li class="contacts__item">
                <img class="contacts__icon" src="<?php echo esc_url($icons_uri . 'phone.svg'); ?>" alt="" width="24" height="24" decoding="async">
                <div class="contacts__item-body">
                    <div class="contacts__item-caption"><?php esc_html_e('Телефон', 'franchises'); ?></div>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>" class="contacts__item-link" itemprop="telephone"><?php echo esc_html($phone); ?></a>
                </div>
            </li>
        <?php endif; ?>

        <?php if ($email !== '') : ?>
            <li class="contacts__item">
                <img class="contacts__icon" src="<?php echo esc_url($icons_uri . 'envelope.svg'); ?>" alt="" width="24" height="24" decoding="async">
                <div class="contacts__item-body">
                    <div class="contacts__item-caption"><?php esc_html_e('Email', 'franchises'); ?></div>
                    <a href="mailto:<?php echo esc_attr(sanitize_email($email)); ?>" class="contacts__item-link" itemprop="email"><?php echo esc_html($email); ?></a>
                </div>
            </li>
        <?php endif; ?>

        <?php if ($worktime !== '') : ?>
            <li class="contacts__item">
                <img class="contacts__icon" src="<?php echo esc_url($icons_uri . 'clock.svg'); ?>" alt="" width="24" height="24" decoding="async">
                <div class="contacts__item-body">
                    <div class="contacts__item-caption"><?php esc_html_e('Режим работы', 'franchises'); ?></div>
                    <div class="contacts__item-text" itemprop="openingHours"><?php echo nl2br(esc_html($worktime)); ?></div>
                </div>
            </li>
        <?php endif; ?>

        <?php if ($legal_lines) : ?>
            <li class="contacts__item">
                <img class="contacts__icon" src="<?php echo esc_url($icons_uri . 'document.svg'); ?>" alt="" width="24" height="24" decoding="async">
                <div class="contacts__item-body">
                    <div class="contacts__item-caption"><?php esc_html_e('Реквизиты', 'franchises'); ?></div>
                    <div class="contacts__item-text contacts__item-legal">
                        <?php foreach ($legal_lines as $line) : ?>
                            <p><?php echo esc_html($line); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
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