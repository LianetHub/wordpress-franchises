<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_fibosearch_is_active')) {
    function franchises_fibosearch_is_active(): bool
    {
        return shortcode_exists('fibosearch') || shortcode_exists('wcas-search-form');
    }
}

if (! function_exists('franchises_get_catalog_search_query')) {
    /**
     * Текст поиска в каталоге: тема использует ?q=, FiboSearch по умолчанию — ?s=.
     */
    function franchises_get_catalog_search_query(): string
    {
        if (! empty($_GET['q'])) {
            return sanitize_text_field(wp_unslash((string) $_GET['q']));
        }
        if (! empty($_GET['s'])) {
            return sanitize_text_field(wp_unslash((string) $_GET['s']));
        }

        return '';
    }
}

if (! function_exists('franchises_header_search_icon_svg')) {
    function franchises_header_search_icon_svg(string $extra_class = ''): string
    {
        $class = trim('search-icon ' . $extra_class);

        return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" aria-hidden="true">'
            . '<circle cx="11" cy="11" r="7"></circle>'
            . '<path d="M20 20l-3.5-3.5"></path>'
            . '</svg>';
    }
}

if (! function_exists('franchises_render_header_search')) {
    /**
     * @param string $context desktop|mobile-menu|mobile-bar
     */
    function franchises_render_header_search(string $context = 'desktop'): void
    {
        $shop_url   = franchises_header_shop_url();
        $search_val = franchises_get_catalog_search_query();

        if (franchises_fibosearch_is_active()) {
            $classes = 'header-search header-search--fibosearch header-search--' . sanitize_html_class($context);

            echo '<div class="header-search-wrap" data-header-search="' . esc_attr($context) . '">';
            echo do_shortcode(
                '[fibosearch class="' . esc_attr($classes) . '" layout="search-bar" mobile_overlay="0" darken_bg="0"]'
            );
            echo '</div>';

            return;
        }

?>
        <form class="header-search" role="search" method="get" action="<?php echo esc_url($shop_url); ?>" aria-label="<?php esc_attr_e('Поиск франшизы', 'franchises'); ?>">
            <?php echo franchises_header_search_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
            ?>
            <input type="search" name="q" placeholder="<?php esc_attr_e('Поиск по франшизам', 'franchises'); ?>" aria-label="<?php esc_attr_e('Введите запрос', 'franchises'); ?>" value="<?php echo esc_attr($search_val); ?>">
            <button class="search-btn" type="submit"><?php esc_html_e('Найти', 'franchises'); ?></button>
        </form>
<?php
    }
}

if (franchises_fibosearch_is_active()) {
    add_filter('dgwt/wcas/indexer/taxonomies', static function (array $taxonomies): array {
        if (! in_array('product_cat', $taxonomies, true)) {
            $taxonomies[] = 'product_cat';
        }

        return $taxonomies;
    });

    add_filter('dgwt/wcas/form/magnifier_ico', static function (string $html, string $class): string {
        unset($html);

        return franchises_header_search_icon_svg($class);
    }, 10, 2);

    add_filter('dgwt/wcas/form/html', static function (string $html): string {
        $shop_url = franchises_header_shop_url();

        $html = preg_replace(
            '#action=(["\'])[^"\']*\1#i',
            'action=$1' . esc_url($shop_url) . '$1',
            $html,
            1
        ) ?? $html;

        $html = preg_replace(
            '#<input([^>]*class=(["\'])[^"\']*dgwt-wcas-search-input[^"\']*\2)([^>]*)name=(["\'])s\4#i',
            '<input$1$3name=$4q$4',
            $html,
            1
        ) ?? $html;

        if (strpos($html, 'search-icon') === false) {
            $icon = franchises_header_search_icon_svg('dgwt-wcas-ico-magnifier');
            $html = preg_replace(
                '#(<div class="[^"]*dgwt-wcas-sf-wrapp[^"]*">)#i',
                '$1' . $icon,
                $html,
                1
            ) ?? $html;
        }

        return $html;
    });

    add_filter('dgwt/wcas/search_form_action', static function (string $url): string {
        return franchises_header_shop_url();
    });

    add_filter('dgwt/wcas/labels', static function (array $labels): array {
        $labels['search_submit'] = __('Найти', 'franchises');

        return $labels;
    });

    add_action('wp_enqueue_scripts', static function (): void {
        if (! wp_script_is('jquery-dgwt-wcas', 'registered')) {
            return;
        }

        wp_enqueue_script('jquery-dgwt-wcas');
    }, 20);

    add_action('wp_enqueue_scripts', static function (): void {
        if (! wp_script_is('app-js', 'registered')) {
            return;
        }

        wp_localize_script('app-js', 'franchisesFiboSearch', [
            'shopUrl' => franchises_header_shop_url(),
            'i18n'    => [
                'submit'      => __('Найти', 'franchises'),
                'placeholder' => __('Поиск по франшизам', 'franchises'),
            ],
        ]);
    }, 30);
}
