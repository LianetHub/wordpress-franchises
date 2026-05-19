<?php

//add option page
if (function_exists('acf_add_options_page')) {

	acf_add_options_page(array(
		'page_title' 	=> 'Настройки темы',
		'menu_title'	=> 'Настройки темы',
		'menu_slug' 	=> 'theme-general-settings',
		'capability'	=> 'edit_posts',
		'redirect'		=> false
	));
}


function my_acf_admin_head()
{
?>
	<style type="text/css">
		h2.hndle.ui-sortable-handle {
			background: #1a76d3;
			color: #fff !important;
			-webkit-transition: all 0.25s;
			-o-transition: all 0.25s;
			transition: all 0.25s;
		}

		.acf-admin-page #poststuff .postbox-header h2.hndle.ui-sortable-handle {
			color: #fff !important;
		}

		.acf-field.acf-accordion .acf-label.acf-accordion-title {
			background: #EBE9F5;
			transition: all 0.25s;
		}

		.acf-accordion .acf-accordion-title label {
			text-transform: uppercase;
			color: #000;
		}

		.acf-field p.description {
			color: #ffa500;
		}

		.acf-field-group {
			border: 1px solid #282D41 !important;
		}
	</style>
<?php
}

add_action('acf/input/admin_head', 'my_acf_admin_head');

add_action('acf/init', static function (): void {
	if (! function_exists('acf_add_local_field_group') || ! taxonomy_exists('product_cat')) {
		return;
	}

	$icon_field = function_exists('franchises_product_cat_icon_field_name')
		? franchises_product_cat_icon_field_name()
		: 'category_icon';

	acf_add_local_field_group([
		'key'    => 'group_franchises_product_cat',
		'title'  => 'Категория франшиз',
		'fields' => [
			[
				'key'           => 'field_product_cat_category_icon',
				'label'         => 'Иконка категории',
				'name'          => $icon_field,
				'type'          => 'image',
				'return_format' => 'array',
				'preview_size'  => 'thumbnail',
				'library'       => 'all',
				'mime_types'    => 'png,svg,jpg,jpeg,webp',
				'instructions'  => 'Необязательно: если пусто, используется миниатюра категории WooCommerce (поле «Миниатюра» ниже) или стандартная SVG-иконка по названию. Рекомендуемый размер 28×28 px.',
			],
		],
		'location' => [[[
			'param'    => 'taxonomy',
			'operator' => '==',
			'value'    => 'product_cat',
		]]],
	]);
});
