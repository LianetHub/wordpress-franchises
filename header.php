<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- favicon -->
    <!-- favicon -->

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
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