<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- favicon -->
    <?php $assets_uri = get_template_directory_uri() . '/assets'; ?>
    <link rel="icon" type="image/png" href="<?php echo esc_url($assets_uri . '/favicon-96x96.png'); ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?php echo esc_url($assets_uri . '/favicon.svg'); ?>" />
    <link rel="shortcut icon" href="<?php echo esc_url($assets_uri . '/favicon.ico'); ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url($assets_uri . '/apple-touch-icon.png'); ?>" />
    <meta name="apple-mobile-web-app-title" content="Франшизы" />
    <link rel="manifest" href="<?php echo esc_url($assets_uri . '/site.webmanifest'); ?>" />
    <!-- favicon -->

<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=109308668', 'ym');

    ym(109308668, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
</script>
<!-- /Yandex.Metrika counter -->



    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
	<noscript><div><img src="https://mc.yandex.ru/watch/109308668" style="position:absolute; left:-9999px;" alt="" /></div></noscript>

    <div class="wrapper">
        <?php require_once(TEMPLATE_PATH . '_header-main.php'); ?>
        <main class="wrap<?php
                            echo is_front_page() ? ' wrap-home' : '';
                            $franchises_catalog_layout = (function_exists('is_shop') && (is_shop() || is_product_category()))
                                || is_singular('selection')
                                || (
                                    function_exists('franchises_get_current_selection_id')
                                    && franchises_get_current_selection_id() > 0
                                );
                            if ($franchises_catalog_layout) {
                                echo ' catalog-page';
                            }
                            ?>">