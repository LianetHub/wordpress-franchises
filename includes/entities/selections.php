<?php

function theme_register_selections()
{
    register_post_type('selection', [
        'label'  => 'Подборки',
        'labels' => [
            'name'               => 'Подборки',
            'singular_name'      => 'Подборка',
            'add_new'            => 'Добавить подборку',
            'add_new_item'       => 'Добавить новую подборку',
            'edit_item'          => 'Редактировать подборку',
            'new_item'           => 'Новая подборка',
            'view_item'          => 'Посмотреть подборку',
            'search_items'       => 'Найти подборку',
            'not_found'          => 'Подборок не найдено',
            'parent_item_colon'  => '',
            'menu_name'          => 'Подборки франшиз',
        ],
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'podborki'],
        'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
        'menu_icon'          => 'dashicons-star-filled',
        'show_in_rest'       => true,
    ]);
}
