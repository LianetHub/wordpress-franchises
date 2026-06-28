<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_force_https_url')) {
    function franchises_force_https_url(string $url): string
    {
        if ($url === '' || ! preg_match('#\Ahttps?://#i', $url)) {
            return $url;
        }

        return (string) set_url_scheme($url, 'https');
    }
}

add_filter('post_link', 'franchises_force_https_url', 99);
add_filter('page_link', 'franchises_force_https_url', 99);
add_filter('post_type_link', 'franchises_force_https_url', 99);
add_filter('term_link', 'franchises_force_https_url', 99);
add_filter('home_url', static function ($url) {
    return is_string($url) ? franchises_force_https_url($url) : $url;
}, 99);
add_filter('site_url', static function ($url) {
    return is_string($url) ? franchises_force_https_url($url) : $url;
}, 99);

add_filter('rank_math/sitemap/entry', static function ($entry) {
    if (! is_array($entry) || empty($entry['loc']) || ! is_string($entry['loc'])) {
        return $entry;
    }

    $entry['loc'] = franchises_force_https_url($entry['loc']);

    return $entry;
}, 99);

add_filter('rank_math/opengraph/url', static function ($url) {
    return is_string($url) ? franchises_force_https_url($url) : $url;
}, 99);

add_filter('rank_math/frontend/canonical', static function ($canonical) {
    return is_string($canonical) ? franchises_force_https_url($canonical) : $canonical;
}, 99);
