<?php

require_once __DIR__ . '/entities/selections.php';
require_once __DIR__ . '/entities/reviews.php';


add_action('init', 'register_theme_entities');

function register_theme_entities()
{
    theme_register_selections();
    theme_register_reviews();
}
