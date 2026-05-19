<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_header_shop_url')) {
    function franchises_header_shop_url(): string
    {
        if (! function_exists('wc_get_page_id')) {
            return home_url('/');
        }

        $url = get_permalink(wc_get_page_id('shop'));

        return is_string($url) && $url !== '' ? $url : home_url('/');
    }
}

if (! function_exists('franchises_header_contacts_url')) {
    function franchises_header_contacts_url(): string
    {
        return home_url('/#contacts');
    }
}

if (! function_exists('franchises_header_get_collections')) {
    /**
     * @return list<array{id: int, title: string, url: string, active: bool}>
     */
    function franchises_header_get_collections(): array
    {
        if (! post_type_exists('selection')) {
            return [];
        }

        $posts = function_exists('franchises_get_selection_posts')
            ? franchises_get_selection_posts(40)
            : [];

        $items = [];
        foreach ($posts as $post) {
            if (! $post instanceof WP_Post) {
                continue;
            }
            $link = get_permalink($post);
            if (! is_string($link) || $link === '') {
                continue;
            }
            $items[] = [
                'id'     => (int) $post->ID,
                'title'  => get_the_title($post),
                'url'    => $link,
                'active' => function_exists('franchises_get_current_selection_id')
                    && franchises_get_current_selection_id() === (int) $post->ID,
            ];
        }

        return $items;
    }
}

if (! function_exists('franchises_header_is_shop_active')) {
    function franchises_header_is_shop_active(): bool
    {
        return function_exists('is_shop') && is_shop() && ! is_product_category();
    }
}
