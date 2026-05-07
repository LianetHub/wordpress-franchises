<?php
/* Customize Admin Panel */
class Wptuts_Simple_Admin
{
	function __construct()
	{
		// Hook onto the action 'admin_menu' for our function to remove menu items
		add_action('admin_menu', array($this, 'remove_menus'));
		// Hook onto the action 'admin_bar'
		add_action('wp_before_admin_bar_render', array($this, 'my_admin_bar_render'));
		// CUSTOM ADMIN LOGIN HEADER LOGO
		add_action('login_head',  array($this, 'my_custom_login_logo'));
		// CUSTOM ADMIN LOGIN LOGO LINK		
		add_filter('login_headerurl', array($this, 'change_wp_login_url'));
		// CUSTOM ADMIN LOGIN LOGO & ALT TEXT
		add_filter('login_headertext', array($this, 'change_wp_login_title'));
		// Custom css: ACF menu
		add_action('admin_head', array($this, 'custom_css'));
	}

	// This function removes each menu item
	function remove_menus()
	{
		global $submenu;
		if (isset($submenu['themes.php'][6])) {
			unset($submenu['themes.php'][6]);
		}
		remove_menu_page('edit-comments.php');
	}

	function my_admin_bar_render()
	{
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('comments');
		$wp_admin_bar->remove_menu('wp-logo');
	}

	function custom_css()
	{
		echo "
        <style>
            #toplevel_page_theme-general-settings .wp-menu-name { 
                background: #1a76d3 !important; 
                color: #ffffff !important; 
                font-weight: 600;
            }
			#toplevel_page_theme-general-settings .wp-menu-image::before {
				color: #ffffff !important; 
			}
            #toplevel_page_theme-general-settings:hover .wp-menu-name { 
                background: #0a5096 !important; 
            }
           
        </style>";
	}

	function my_custom_login_logo()
	{
		echo '
        <style type="text/css">
            body.login {
                background-color: #fff !important;
            }
            .login h1 a {
                background: url(' . get_template_directory_uri() . '/assets/img/logo.svg) #ffffff center no-repeat !important;
                background-size: contain !important;
                width: 100% !important;
                height: 320px !important;
                margin-bottom: 20px !important;
            }
            .login #loginform {
                background: linear-gradient(150deg, #306E8C 3.77%, #1F4B62 153.22%) !important;
                border: none !important;
                color: #ffffff !important;
            }
            .login label {
                color: #ffffff !important;
            }
            .wp-core-ui .button-primary {
                background: #FFF !important;
                border-color: #FFF !important;
                color: #4A81A0 !important;
                text-shadow: none !important;
                box-shadow: none !important;
                font-weight: 700 !important;
				box-shadow: 0 6px 10px 0 rgba(255, 255, 255, 0.10) !important;
            }
            .wp-core-ui .button-primary:hover {
                background: #f0f0f0 !important;
                border-color: #f0f0f0 !important;
                box-shadow: 0 8px 15px 0 rgba(255, 255, 255, 0.20) !important;
            }
        </style>';
	}

	function change_wp_login_url()
	{
		return home_url();
	}

	function change_wp_login_title()
	{
		return get_option('blogname');
	}
}
$wptuts_simple_admin = new Wptuts_Simple_Admin();

remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'dns-prefetch', 11);
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'rest_output_link_header', 11, 0);
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
add_filter('emoji_svg_url', '__return_false');

add_filter('wpcf7_autop_or_not', '__return_false');
