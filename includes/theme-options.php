<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_get_contacts_page_id')) {
    function franchises_get_contacts_page_id(): int
    {
        static $page_id = null;

        if ($page_id !== null) {
            return $page_id;
        }

        $pages = get_pages([
            'meta_key'   => '_wp_page_template',
            'meta_value' => 'page-contacts.php',
            'number'     => 1,
        ]);

        $page_id = ($pages && $pages[0] instanceof WP_Post) ? (int) $pages[0]->ID : 0;

        return $page_id;
    }
}

if (! function_exists('franchises_contacts_page_url')) {
    function franchises_contacts_page_url(): string
    {
        $page_id = franchises_get_contacts_page_id();
        if ($page_id > 0) {
            $url = get_permalink($page_id);
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return home_url('/#contacts');
    }
}

if (! function_exists('franchises_get_theme_option')) {
    /**
     * @param mixed $default
     * @return mixed
     */
    function franchises_get_theme_option(string $field, $default = '')
    {
        if (! function_exists('get_field')) {
            return $default;
        }

        $value = get_field($field, 'option');

        if ($value === null || $value === false || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (! function_exists('franchises_format_theme_digits')) {
    function franchises_format_theme_digits($value): string
    {
        if ($value === null || $value === false || $value === '') {
            return '';
        }

        return preg_replace('/\D+/', '', (string) $value);
    }
}

if (! function_exists('franchises_get_map_placemark_url')) {
    function franchises_get_map_placemark_url(): string
    {
        $icon = franchises_get_theme_option('map_placemark');

        if (is_array($icon) && ! empty($icon['url'])) {
            return (string) $icon['url'];
        }

        if (is_string($icon) && $icon !== '') {
            return $icon;
        }

        return get_template_directory_uri() . '/assets/img/icons/location.svg';
    }
}

if (! function_exists('franchises_get_map_coords')) {
    /**
     * @return array{0: float, 1: float}|null
     */
    function franchises_get_map_coords(): ?array
    {
        $raw = (string) franchises_get_theme_option('map_coords', '55.8528135688981, 48.842075499999964');
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $raw));
        if (count($parts) < 2) {
            return null;
        }

        $lat = (float) $parts[0];
        $lng = (float) $parts[1];

        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        return [$lat, $lng];
    }
}
