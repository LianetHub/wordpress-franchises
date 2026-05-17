<?php

defined('ABSPATH') || exit;

/**
 * URL политики конфиденциальности (страница ID 3).
 */
if (! function_exists('franchises_privacy_policy_url')) {
    function franchises_privacy_policy_url(): string
    {
        $permalink = get_permalink(3);
        if (is_string($permalink) && $permalink !== '') {
            return $permalink;
        }

        return home_url('/politika-konfidenczialnosti/');
    }
}

/**
 * @param string $filter_name
 * @param string $default_shortcode
 */
function franchises_cf7_shortcode(string $filter_name, string $default_shortcode): string
{
    return (string) apply_filters($filter_name, $default_shortcode);
}

if (! function_exists('franchises_selection_cf7_shortcode')) {
    function franchises_selection_cf7_shortcode(): string
    {
        return franchises_cf7_shortcode(
            'franchises_selection_cf7_shortcode',
            '[contact-form-7 id="65f54bf" title="Контактная форма Подбор франшиз"]'
        );
    }
}

if (! function_exists('franchises_home_consult_cf7_shortcode')) {
    function franchises_home_consult_cf7_shortcode(): string
    {
        return franchises_cf7_shortcode(
            'franchises_home_consult_cf7_shortcode',
            '[contact-form-7 id="bfe303a" title="Контактная форма Остались вопросы?"]'
        );
    }
}

if (! function_exists('franchises_franchise_lead_cf7_shortcode')) {
    function franchises_franchise_lead_cf7_shortcode(): string
    {
        return franchises_cf7_shortcode(
            'franchises_franchise_lead_cf7_shortcode',
            '[contact-form-7 id="afc8c72" title="Контактная форма Заявка со страницы Франшизы"]'
        );
    }
}

if (! function_exists('franchises_franchise_question_cf7_shortcode')) {
    function franchises_franchise_question_cf7_shortcode(): string
    {
        return franchises_cf7_shortcode(
            'franchises_franchise_question_cf7_shortcode',
            '[contact-form-7 id="c654df2" title="Контактная форма Задать вопрос со страницы Франшизы"]'
        );
    }
}

if (! function_exists('franchises_render_cf7_shortcode')) {
    function franchises_render_cf7_shortcode(string $shortcode, string $fallback_message = ''): void
    {
        $shortcode = trim($shortcode);
        if ($shortcode === '' || ! shortcode_exists('contact-form-7')) {
            if ($fallback_message !== '') {
                echo '<p class="form-fallback-msg">' . esc_html($fallback_message) . '</p>';
            }
            return;
        }

        echo do_shortcode($shortcode);
    }
}

if (! function_exists('franchises_render_selection_popup_cf7')) {
    function franchises_render_selection_popup_cf7(): void
    {
        franchises_render_cf7_shortcode(
            franchises_selection_cf7_shortcode(),
            __('Форма подбора будет доступна после настройки Contact Form 7.', 'franchises')
        );
    }
}

if (! function_exists('franchises_render_home_consult_cf7')) {
    function franchises_render_home_consult_cf7(): void
    {
        franchises_render_cf7_shortcode(
            franchises_home_consult_cf7_shortcode(),
            __('Форма консультации будет доступна после настройки Contact Form 7.', 'franchises')
        );
    }
}

if (! function_exists('franchises_render_franchise_lead_cf7')) {
    function franchises_render_franchise_lead_cf7(): void
    {
        franchises_render_cf7_shortcode(
            franchises_franchise_lead_cf7_shortcode(),
            __('Форма заявки будет доступна после настройки Contact Form 7.', 'franchises')
        );
    }
}

if (! function_exists('franchises_render_franchise_question_cf7')) {
    function franchises_render_franchise_question_cf7(): void
    {
        franchises_render_cf7_shortcode(
            franchises_franchise_question_cf7_shortcode(),
            __('Форма вопроса будет доступна после настройки Contact Form 7.', 'franchises')
        );
    }
}

add_filter('wpcf7_form_hidden_fields', function (array $fields): array {
    if (is_singular('product')) {
        global $product;
        if (isset($product) && is_object($product) && is_a($product, 'WC_Product', true)) {
            $fields['franchise_id']    = (string) $product->get_id();
            $fields['franchise_title'] = $product->get_name();
            $fields['page_url']        = get_permalink($product->get_id()) ?: '';
        }
    }

    if (is_front_page()) {
        $fields['form_source'] = 'home_consult';
    }

    return $fields;
});

add_filter('wpcf7_form_elements', function (string $content): string {
    $privacy_url = esc_url(franchises_privacy_policy_url());

    return str_replace(
        ['{{privacy_url}}', '%privacy_url%'],
        $privacy_url,
        $content
    );
});
