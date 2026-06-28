<?php

/**
 * Theme functions and definitions
 */

// =========================================================================
// 1. ПОДКЛЮЧЕНИЕ МОДУЛЕЙ
// =========================================================================

$includes = [
    'includes/admin-custom.php',
    'includes/theme-options.php',
    'includes/acf-custom.php',
    'includes/product-franchise-fields.php',
    'includes/custom-posts.php',
    'includes/breadcrumbs.php',
    'includes/woocommerce-custom.php',
    'includes/product-cat.php',
    'includes/catalog-filter.php',
    'includes/header-menu.php',
    'includes/fibosearch-custom.php',
    'includes/home-collections.php',
    'includes/home-popular.php',
    'includes/cf7-forms.php',
    'includes/seo-https.php',
];

foreach ($includes as $file) {
    $filepath = locate_template($file);
    if (! $filepath) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            trigger_error(sprintf('Ошибка: файл %s не найден', $file), E_USER_WARNING);
        }
        continue;
    }
    require_once $filepath;
}

define('TEMPLATE_PATH', get_template_directory() . '/templates/');


// =========================================================================
// 2. НАСТРОЙКИ ТЕМЫ
// =========================================================================

function franchises_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');


    register_nav_menus([
        'header_menu'  => 'Меню в шапке',
    ]);
}
add_action('after_setup_theme', 'franchises_theme_setup');

add_action('init', function () {
    unregister_taxonomy_for_object_type('category', 'post');
    unregister_taxonomy_for_object_type('post_tag', 'post');
});

add_action('admin_menu', function () {
    remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category');
    remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');
});


// =========================================================================
// 3. ПОДКЛЮЧЕНИЕ СКРИПТОВ И СТИЛЕЙ
// =========================================================================

add_action('wp_enqueue_scripts', function () {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();

    wp_enqueue_style('swiper', $theme_uri . '/assets/css/libs/swiper-bundle.min.css');
    wp_enqueue_style('fancybox', $theme_uri . '/assets/css/libs/fancybox.css');
    wp_enqueue_style('reset', $theme_uri . '/assets/css/reset.min.css');

    $main_css_ver = filemtime($theme_dir . '/assets/css/style.min.css');
    wp_enqueue_style('main-style', $theme_uri . '/assets/css/style.min.css', array(), $main_css_ver);

    wp_deregister_script('jquery');
    wp_enqueue_script('jquery', $theme_uri . '/assets/js/libs/jquery-4.0.0.min.js', array(), '4.0.0', true);
    wp_enqueue_script('swiper-js', $theme_uri . '/assets/js/libs/swiper-bundle.min.js', array(), null, true);
    wp_enqueue_script('fancybox-js', $theme_uri . '/assets/js/libs/fancybox.umd.js', array(), null, true);


    $app_js_ver = filemtime($theme_dir . '/assets/js/app.min.js');

    wp_enqueue_script('app-js', $theme_uri . '/assets/js/app.min.js', array('jquery', 'swiper-js', 'fancybox-js'), $app_js_ver, true);



    // wp_localize_script('app-js', 'admin_ajax', [
    //     'url' => admin_url('admin-ajax.php')
    // ]);

    // if (is_singular('post')) {
    //     wp_enqueue_script(
    //         'post-scripts',
    //         get_template_directory_uri() . '/assets/js/article-actions.min.js',
    //         array('app-js'),
    //         null,
    //         true
    //     );
    // }
});


// =========================================================================
// 4. ОПТИМИЗАЦИЯ ЗАГРУЗКИ (ASYNC / DEFER)
// =========================================================================

add_filter('style_loader_tag', function ($tag, $handle) {
    if (in_array($handle, ['swiper', 'fancybox', 'contact-form-7'])) {
        return str_replace(" media='all'", " media='print' onload=\"this.media='all'; this.onload=null;\"", $tag);
    }
    return $tag;
}, 10, 2);

add_filter('script_loader_tag', function ($tag, $handle) {
    if (is_admin()) return $tag;

    $defer = [
        'current-template-js-js',
        'swiper-js',
        'fancybox-js',
        'app-js',
        'post-scripts',
    ];

    if (in_array($handle, $defer)) {
        return str_replace(' src', ' defer src', $tag);
    }

    if ($handle === 'yandex-maps') {
        return str_replace(' src', ' async defer src', $tag);
    }

    return $tag;
}, 10, 2);

add_action('wp_enqueue_scripts', function () {
    global $wp_scripts;
    if (isset($wp_scripts->registered['current-template-js-js'])) {
        $wp_scripts->registered['current-template-js-js']->deps[] = 'jquery';
    }
}, 20);


// =========================================================================
// 5. БЕЗОПАСНОСТЬ И ОЧИСТКА
// =========================================================================

add_action('init', function () {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
    remove_action('wp_head', 'rel_canonical');
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    add_filter('xmlrpc_enabled', '__return_false');
});

add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});


// =========================================================================
// 6. ТИПОГРАФИКА И КОНТЕНТ
// =========================================================================

function fix_widows_after_prepositions($text)
{
    if (empty($text) || !is_string($text)) return $text;

    $prepositions = ['в', 'и', 'или', 'к', 'с', 'на', 'у', 'о', 'от', 'для', 'за', 'по', 'без', 'из', 'над', 'под', 'при', 'про', 'через', 'об', 'со', 'ко'];

    foreach ($prepositions as $prep) {
        $pattern = '/(?<=\s|^)(' . preg_quote($prep, '/') . ')\s+/iu';
        $text = preg_replace($pattern, '$1&nbsp;', $text);
    }
    return $text;
}

foreach (['the_content', 'the_title', 'the_excerpt', 'widget_text_content'] as $hook) {
    add_filter($hook, 'fix_widows_after_prepositions', 99);
}

add_filter('acf/format_value', function ($value, $post_id, $field) {
    return fix_widows_after_prepositions($value);
}, 99, 3);

function get_processed_svg($url, $new_color)
{
    if (!$url) return '';
    $path = str_replace(site_url('/'), ABSPATH, $url);
    if (!file_exists($path)) {
        return '<img src="' . esc_url($url) . '" alt="">';
    }
    $svg_code = file_get_contents($path);
    $svg_code = preg_replace('/fill="((?!none)[^"]+)"/i', 'fill="' . $new_color . '"', $svg_code);
    $svg_code = preg_replace('/stroke="((?!none)[^"]+)"/i', 'stroke="' . $new_color . '"', $svg_code);
    return $svg_code;
}

add_filter('the_content', function ($content) {
    if (!is_singular() || strpos($content, '<table') === false) {
        return $content;
    }
    $dom = new DOMDocument();
    $html_encoded = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
    @$dom->loadHTML('<div>' . $html_encoded . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $tables = $dom->getElementsByTagName('table');
    foreach ($tables as $table) {
        $headers = [];
        $th_nodes = $table->getElementsByTagName('th');
        foreach ($th_nodes as $th) {
            $headers[] = trim($th->nodeValue);
        }
        if (!empty($headers)) {
            $rows = $table->getElementsByTagName('tr');
            foreach ($rows as $row) {
                $cells = $row->getElementsByTagName('td');
                $index = 0;
                foreach ($cells as $cell) {
                    if (isset($headers[$index])) {
                        $cell->setAttribute('data-label', $headers[$index]);
                    }
                    $index++;
                }
            }
        }
    }
    $new_content = '';
    $wrapper = $dom->documentElement;
    if ($wrapper) {
        foreach ($wrapper->childNodes as $child) {
            $new_content .= $dom->saveHTML($child);
        }
    } else {
        $new_content = $content;
    }
    return $new_content;
}, 20);

function russian_plural($number, $titles)
{
    $cases = [2, 0, 1, 1, 1, 2];
    return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}

function format_service_price($price)
{
    if (!$price) return '';
    $clean_price = (float)preg_replace('/[^0-9.]/', '', $price);

    if ($clean_price <= 0) return '';
    $formatted = number_format($clean_price, 0, '.', "\u{2009}");

    return $formatted;
}


// =========================================================================
// 7. ПАГИНАЦИЯ
// =========================================================================

function franchises_theme_woocommerce_pagination_args($args)
{
    $args['prev_text'] = '‹';
    $args['next_text'] = '›';
    return $args;
}
add_filter('woocommerce_pagination_args', 'franchises_theme_woocommerce_pagination_args');


// =========================================================================
// 8. СТАТИСТИКА И ВЗАИМОДЕЙСТВИЕ (BLOG METRICS)
// =========================================================================

// Время чтения
function franchises_theme_reading_time($post_id = null, $wpm = 10, $seconds_per_image = 5)
{
    $post_id = $post_id ?: get_the_ID();
    $html = apply_filters('the_content', get_post_field('post_content', $post_id));
    $words = str_word_count(wp_strip_all_tags($html));
    preg_match_all('/<img\b[^>]*>/i', $html, $matches);
    $images = count($matches[0]);
    $words += ($images * $seconds_per_image) * $wpm / 60;
    return max(1, (int) ceil($words / $wpm));
}

function franchises_theme_the_reading_time($before = '', $after = ' мин. читать')
{
    printf('%s%d%s', $before, franchises_theme_reading_time(), $after);
}

// Просмотры (мета-ключ franchises_theme_post_views; AJAX: franchises_theme_increment_views)
function franchises_theme_set_post_views($postID): void
{
    $post_id = (int) $postID;
    if ($post_id <= 0) {
        return;
    }
    $key = 'franchises_theme_post_views';
    $count = get_post_meta($post_id, $key, true);
    $count = ($count === '' || $count === null) ? 0 : (int) $count;
    $count++;
    update_post_meta($post_id, $key, $count);
}

function franchises_theme_get_post_views($postID): int
{
    $post_id = (int) $postID;
    if ($post_id <= 0) {
        return 0;
    }
    $count = get_post_meta($post_id, 'franchises_theme_post_views', true);

    return ($count === '' || $count === null) ? 0 : (int) $count;
}

add_action('wp_ajax_franchises_theme_increment_views', 'franchises_theme_increment_views_ajax');
add_action('wp_ajax_nopriv_franchises_theme_increment_views', 'franchises_theme_increment_views_ajax');
function franchises_theme_increment_views_ajax(): void
{
    if (! isset($_POST['post_id']) || ! is_numeric($_POST['post_id'])) {
        wp_send_json_error();
    }
    franchises_theme_set_post_views((int) $_POST['post_id']);
    wp_send_json_success();
}

// Лайки
add_action('wp_ajax_franchises_theme_add_like', 'franchises_theme_add_like');
add_action('wp_ajax_nopriv_franchises_theme_add_like', 'franchises_theme_add_like');
function franchises_theme_add_like()
{
    if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) wp_send_json_error();
    $post_id = (int) $_POST['post_id'];
    $current_likes = (int) get_post_meta($post_id, 'franchises_theme_likes', true);
    $current_likes++;
    update_post_meta($post_id, 'franchises_theme_likes', $current_likes);
    wp_send_json_success(['likes' => $current_likes]);
}

add_action('wp_ajax_franchises_theme_remove_like', 'franchises_theme_remove_like');
add_action('wp_ajax_nopriv_franchises_theme_remove_like', 'franchises_theme_remove_like');
function franchises_theme_remove_like()
{
    if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) wp_send_json_error();
    $post_id = (int)$_POST['post_id'];
    $current_likes = (int)get_post_meta($post_id, 'franchises_theme_likes', true);
    if ($current_likes > 0) {
        $current_likes--;
        update_post_meta($post_id, 'franchises_theme_likes', $current_likes);
    }
    wp_send_json_success(['likes' => $current_likes]);
}


// =========================================================================
// 9. СЛУЖЕБНЫЕ УВЕДОМЛЕНИЯ (COOKIES)
// =========================================================================

add_action('wp_footer', function () {
    if (!isset($_COOKIE['cookie_notice'])) :
        $privacy_url = function_exists('franchises_privacy_policy_url')
            ? franchises_privacy_policy_url()
            : home_url('/politika-konfidenczialnosti/');
?>
        <div id="cookie-notice" class="cookie cookie--hidden">
            <div class="cookie__text">
                Продолжая использовать этот сайт, вы даёте согласие
                на&nbsp;обработку файлов cookie в&nbsp;соответствии с&nbsp;<a href="<?php echo esc_url($privacy_url); ?>">политикой конфиденциальности</a>.
            </div>
            <button type="button" class="cookie__accept btn btn-primary btn-sm">Хорошо</button>
        </div>

        <script>
            (function() {
                function setCookie(name, value, options) {
                    options = options || {};
                    var expires = options.expires;
                    if (typeof expires == "number" && expires) {
                        var d = new Date();
                        d.setTime(d.getTime() + expires * 1000);
                        expires = options.expires = d;
                    }
                    if (expires && expires.toUTCString) {
                        options.expires = expires.toUTCString();
                    }
                    value = encodeURIComponent(value);
                    var updatedCookie = name + "=" + value;
                    for (var propName in options) {
                        updatedCookie += "; " + propName;
                        var propValue = options[propName];
                        if (propValue !== true) {
                            updatedCookie += "=" + propValue;
                        }
                    }
                    document.cookie = updatedCookie;
                }

                var noticeDiv = document.getElementById('cookie-notice');
                if (noticeDiv) {
                    setTimeout(function() {
                        noticeDiv.classList.remove('cookie--hidden');
                    }, 3000);

                    noticeDiv.querySelector('.cookie__accept').addEventListener('click', function() {
                        setCookie('cookie_notice', 1, {
                            expires: 180 * 24 * 60 * 60,
                            path: '/'
                        });
                        noticeDiv.classList.add('cookie--hidden');
                        setTimeout(function() {
                            noticeDiv.remove();
                        }, 500);
                    });
                }
            })();
        </script>
<?php endif;
});

/**
 * Оглавление по H2 (делегирует в franchises_content_with_toc из includes/woocommerce-custom.php).
 *
 * @return array{content: string, toc: string}
 */
function theme_content_with_toc($content)
{
    if (function_exists('franchises_content_with_toc')) {
        $r = franchises_content_with_toc((string) $content);
        $toc_list = '';
        foreach ($r['toc_items'] as $row) {
            $toc_list .= '<li><a href="#' . esc_attr($row['id']) . '" class="sidebar__link">'
                . esc_html($row['title']) . '</a></li>';
        }

        return [
            'content' => $r['content'],
            'toc'     => $toc_list,
        ];
    }

    return [
        'content' => $content,
        'toc'     => '',
    ];
}
// =========================================================================
// заявки в телеграмм
// =========================================================================
add_action('wpcf7_mail_sent', 'franchises_send_cf7_to_telegram', 20, 1);

function franchises_send_cf7_to_telegram($contact_form)
{
    try {
        if (!class_exists('WPCF7_Submission')) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();

        if (!$submission) {
            return;
        }

        $data = $submission->get_posted_data();

        $bot_token = '8912962432:AAHHIEJ4x85Ad8TGCJAHQJ2Qk8VM1IcJ0tY';
        $chat_id   = '-1003993540731';

        $form_title = method_exists($contact_form, 'title') ? $contact_form->title() : 'CF7 форма';
        $page_url   = $submission->get_meta('url');

        $name     = isset($data['your-name']) ? sanitize_text_field($data['your-name']) : '';
        $phone    = isset($data['tel-phone']) ? sanitize_text_field($data['tel-phone']) : '';
        $email    = isset($data['your-email']) ? sanitize_email($data['your-email']) : '';
        $city     = isset($data['text-city']) ? sanitize_text_field($data['text-city']) : '';
        $comment  = isset($data['text-comment']) ? sanitize_textarea_field($data['text-comment']) : '';
        $question = isset($data['your-question']) ? sanitize_textarea_field($data['your-question']) : '';

        $franchise_title = isset($data['franchise-title']) ? sanitize_text_field($data['franchise-title']) : '';
        $franchise_url   = isset($data['franchise-url']) ? esc_url_raw($data['franchise-url']) : '';

        $utm_source   = isset($data['utm_source']) ? sanitize_text_field($data['utm_source']) : '';
        $utm_medium   = isset($data['utm_medium']) ? sanitize_text_field($data['utm_medium']) : '';
        $utm_campaign = isset($data['utm_campaign']) ? sanitize_text_field($data['utm_campaign']) : '';
        $utm_content  = isset($data['utm_content']) ? sanitize_text_field($data['utm_content']) : '';
        $utm_term     = isset($data['utm_term']) ? sanitize_text_field($data['utm_term']) : '';
        $clientID     = isset($data['clientID']) ? sanitize_text_field($data['clientID']) : '';

        $message = "📩 Новая заявка с сайта\n\n";
        $message .= "Форма: " . $form_title . "\n";

        if ($name !== '') {
            $message .= "Имя: " . $name . "\n";
        }

        if ($phone !== '') {
            $message .= "Телефон: " . $phone . "\n";
        }

        if ($email !== '') {
            $message .= "Email: " . $email . "\n";
        }

        if ($city !== '') {
            $message .= "Город: " . $city . "\n";
        }

        if ($comment !== '') {
            $message .= "Комментарий: " . $comment . "\n";
        }

        if ($question !== '') {
            $message .= "Вопрос: " . $question . "\n";
        }

        if ($franchise_title !== '') {
            $message .= "Франшиза: " . $franchise_title . "\n";
        }

        if ($franchise_url !== '') {
            $message .= "Ссылка на франшизу: " . $franchise_url . "\n";
        }

        if ($page_url !== '') {
            $message .= "Страница с которой отправлена заявка: " . $page_url . "\n";
        }

        $tracking = [
            'UTM source'   => $utm_source,
            'UTM medium'   => $utm_medium,
            'UTM campaign' => $utm_campaign,
            'UTM content'  => $utm_content,
            'UTM term'     => $utm_term,
            'ClientID'     => $clientID,
        ];

        $tracking_message = '';

        foreach ($tracking as $label => $value) {
            if ($value !== '') {
                $tracking_message .= $label . ": " . $value . "\n";
            }
        }

        if ($tracking_message !== '') {
            $message .= "\n";
            $message .= $tracking_message;
        }

        $response = wp_remote_post('https://api.telegram.org/bot' . $bot_token . '/sendMessage', array(
            'timeout'  => 10,
            'blocking' => false,
            'body'     => array(
                'chat_id' => $chat_id,
                'text'    => $message,
            ),
        ));

    } catch (Throwable $e) {
        error_log('Telegram CF7 fatal: ' . $e->getMessage());
    }
}