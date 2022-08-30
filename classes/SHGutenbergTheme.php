<?php

require_once __DIR__ . '/SHBlocks.php';
require_once __DIR__ . '/SHUtils.php';
require_once __DIR__ . '/helpers/RESTLogic.php';

class SHGutenbergTheme
{
	protected $shBlocks;
	protected $shortcodes;

	public function __construct()
	{
		$this->shBlocks = new shBlocks();
		$this->shortcodes = [
			'year' => date('Y')
		];
		$this->register_actions();
		$this->register_shortcodes();
		$this->register_filters();
		$this->theme_support();
		$this->add_option_pages();
	}

	public function register_actions()
	{
		add_action('admin_notices', [$this, 'register_admin_notices']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
		add_action('enqueue_block_editor_assets', [$this, 'enqueue_assets']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		// add_action('acf/include_field_types', [$this, 'include_custom_field_types']);
		add_action('wp_head', [$this, 'modify_head']);
		add_action('init', [$this, 'init_hooks']);
		add_action('admin_init', [$this, 'admin_init_hooks']);
		add_action('admin_menu', [$this, 'admin_menu_hooks']);
		add_action('login_enqueue_scripts', [$this, 'login_page_hooks']);
		add_action('rest_api_init', [$this, 'rest_init_hooks']);
		add_action( 'admin_head', function () {
			echo '<style type="text/css">
				  .attachment-266x266, .thumbnail img {
					   width: 100% !important;
					   height: auto !important;
				  }
				  </style>';
		  });
	}

	public function register_filters()
	{
		add_filter('acf/format_value/type=image', ['SHUtils', 'format_acf_images'], 100, 3);
		add_filter('acf/format_value/type=gallery', ['SHUtils', 'px_format_acf_gallery_images'], 100, 3);
		// this filter uses the name of an ACF field to add Gravity Forms as a select list of options
		add_filter( 'acf/load_field/name=footer_form', ['SHUtils', 'px_acf_populate_gf_forms_ids'], 100, 1);
		// this filter gets the custom post types and adds them to a select list
		add_filter('acf/load_field/name=type_of_content', ['SHUtils', 'px_acf_load_post_types'], 100, 1);
		add_filter('cron_schedules', ['SHUtils', 'add_30_day_cron_schedule'], 10, 1);
		add_filter('acf/fields/wysiwyg/toolbars', ['SHUtils', 'modify_acf_wysiwyg_toolbars'], 10, 1);
		add_filter('tiny_mce_before_init', ['SHUtils', 'modify_tiny_mce_format_options'], 10, 1);
		add_filter('post_thumbnail_html', [$this, 'remove_width_attribute'], 10);
		add_filter('image_send_to_editor', [$this, 'remove_width_attribute'], 10);
		add_filter('wp_prepare_attachment_for_js', [$this, 'common_svg_media_thumbnails'], 10, 3);
		
		add_filter('block_editor_settings', function ($settings) {
			unset($settings['styles'][0]);
			return $settings;
		});
		add_filter('comments_open', function () {
			return false;
		});
		add_filter('pings_open', function () {
			return false;
		});
		add_filter('comments_array', function ($comments) {
			return [];
		});
		add_filter('excerpt_length', function ($length) {
			return 20;
		}, 999);
		add_filter('excerpt_more', function ($more) {
			return '...';
		});
		add_filter('login_headerurl', function () {
			return home_url();
		});
		// Allow SVG
		add_filter( 'wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {

			global $wp_version;
			if ( $wp_version !== '4.7.1' ) {
			return $data;
			}
		
			$filetype = wp_check_filetype( $filename, $mimes );
		
			return [
				'ext'             => $filetype['ext'],
				'type'            => $filetype['type'],
				'proper_filename' => $data['proper_filename']
			];
		
		}, 10, 4 );
		add_filter('upload_mimes', function ( $mimes ) {
			$mimes['svg'] = 'image/svg+xml';
			return $mimes;
		});
		add_filter('the_content', function ($content) {
			return preg_replace('/<p>\\s*?(<iframe.*?><\/iframe>)?\\s*<\\/p>/s', '<div class="iframe">$1</div>', $content);
		});
		add_filter('wp_mail_content_type', function () {
			return 'text/html';
		});
		// Allowed menu classes
		// add_filter('nav_menu_css_class', function ($classes, $item) {
		// 	return is_array($classes) ?
		// 		array_intersect(
		// 			$classes,
		// 			array(
		// 				'current-menu-item',
		// 				'current-menu-parent',
		// 				'menu-item-has-children'
		// 			)
		// 		) : $classes;
		// }, 10, 2);

		// add_filter('nav_menu_item_id', function () {
		// 	return '';
		// }, 100, 1);

		// accessible menus
		add_filter('wp_nav_menu', function ($menu_html, $args) {
			$bad = array('menu', 'navigation', 'nav');
			$menu_label = $args->menu;
			$menu_label = strtolower($menu_label);
			$menu_label = str_replace($bad, '', $menu_label);
			$menu_label = trim($menu_label);
			$menu_html = '<nav aria-label="' . $menu_label . '">' . $menu_html . '</nav>';
			return $menu_html;
		}, 10, 2);

		// limit number of post revisions
		add_filter( 'wp_revisions_to_keep', function($num, $post){
			$revisions = 5;
			return $revisions;
		}, 10, 2 );

		// add search button to main menu
		// add_filter('wp_nav_menu_items', function ($items, $args) {
		// 	if ($args->menu == 'Main Menu') {
		// 		$items .= '<li class="main-nav__search"><button id="headerSearchButton" class="btn">' . __('Search') . '</button></li>';
		// 		// $items .= '<li class="main-nav__desktop-menu"><button id="desktopNavButton" class="menu-button"><span><svg width="18" height="4" viewBox="0 0 18 4" fill="none" xmlns="http://www.w3.org/2000/svg" class="desktop"><path d="M2 0C0.9 0 0 0.9 0 2C0 3.1 0.9 4 2 4C3.1 4 4 3.1 4 2C4 0.9 3.1 0 2 0ZM16 0C14.9 0 14 0.9 14 2C14 3.1 14.9 4 16 4C17.1 4 18 3.1 18 2C18 0.9 17.1 0 16 0ZM9 0C7.9 0 7 0.9 7 2C7 3.1 7.9 4 9 4C10.1 4 11 3.1 11 2C11 0.9 10.1 0 9 0Z" fill="#010817"/></svg></span></button></li>';
		// 	}
		// 	return $items;
		// }, 10, 2);
		
		

		

	}

	public function common_svg_media_thumbnails($response, $attachment, $meta)
	{
		if ($response['type'] === 'image' && $response['subtype'] === 'svg+xml' && class_exists('SimpleXMLElement')) {
			try {
				$path = get_attached_file($attachment->ID);
				if (@file_exists($path)) {
					$svg = new SimpleXMLElement(@file_get_contents($path));
					$src = $response['url'];
					$width = (int) $svg['width'];
					$height = (int) $svg['height'];

					//media gallery
					$response['image'] = compact('src', 'width', 'height');
					$response['thumb'] = compact('src', 'width', 'height');

					//media single
					$response['sizes']['full'] = array(
						'height'        => $height,
						'width'         => $width,
						'url'           => $src,
						'orientation'   => $height > $width ? 'portrait' : 'landscape',
					);
				}
			} catch (Exception $e) {
			}
		}

		return $response;
	}

	public function remove_width_attribute($html)
	{
		$html = preg_replace('/(width|height)="\d*"\s/', "", $html);
		return $html;
	}

	public function register_shortcodes()
	{
		foreach ($this->shortcodes as $slug => $returnValue) {
			add_shortcode($slug, function ($atts) use ($returnValue) {
				return $returnValue;
			});
		}
	}

	public function enqueue_assets()
	{
		wp_enqueue_style('main', get_template_directory_uri() . '/dist/app.min.css', [], filemtime(get_template_directory() . '/dist/app.min.css'));

		wp_deregister_script('jquery');
		wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js', [], null, false);

		wp_enqueue_script('polyfills', 'https://polyfill.io/v3/polyfill.min.js?features=Symbol.iterator,Symbol.isConcatSpreadable,Array.from,Array.prototype.find,NodeList.prototype.forEach,Promise,Object.assign', [], null, true);

		wp_enqueue_script('pxmodules', get_template_directory_uri() . '/dist/pxmodules.min.js', [], filemtime(get_template_directory() . '/dist/pxmodules.min.js'), true);

		wp_enqueue_script('shBlocks', get_template_directory_uri() . '/dist/shBlocks.min.js', ['jquery', 'polyfills', 'pxmodules'], filemtime(get_template_directory() . '/dist/shBlocks.min.js'), true);

		wp_enqueue_script('main', get_template_directory_uri() . '/dist/app.js', ['shBlocks'], filemtime(get_template_directory() . '/dist/app.js'), true);

		is_admin() ? wp_localize_script('shBlocks', 'PXCustomBlocks', $this->shBlocks->customBlocks) : wp_localize_script('main', 'PXConstants', ['ajaxUrl' => admin_url('admin-ajax.php')]);
	}

	public function enqueue_admin_assets()
	{
		// wp_enqueue_style('pxcustomfieldtypes', get_template_directory_uri() . '/dist/custom-field-types.min.css', filemtime(get_template_directory() . '/dist/custom-field-types.min.css'));
		// wp_enqueue_script('pxcustomfieldtypes', get_template_directory_uri() . '/dist/custom-field-types.min.js', filemtime(get_template_directory() . '/dist/custom-field-types.min.js'), true);
	}

	public function register_admin_notices()
	{
		SHUtils::acf_sync_notice();
	}

	public function theme_support()
	{
		add_theme_support('title-tag');
		add_theme_support('menus');
		add_theme_support('post-thumbnails', ['post']);
	}

	public function add_option_pages()
	{
		if (function_exists('acf_add_options_page')) {
			acf_add_options_page([
				'page_title' 	=> 'Theme General Settings',
				'menu_title'	=> 'Theme Settings',
				'menu_slug' 	=> 'theme-general-settings',
				'capability'	=> 'edit_posts',
				'redirect'		=> false
			]);

			acf_add_options_page([
				'page_title' 	=> 'Modals',
				'menu_title'	=> 'Modals',
				'menu_slug' 	=> 'theme-modals',
				'capability'	=> 'edit_posts',
				'icon_url'		=> 'dashicons-editor-expand',
				'redirect'		=> false
			]);
		}
	}

	// public function include_custom_field_types()
	// {
	// 	$customFieldTypes = array_values(array_diff(scandir(get_template_directory() . '/custom-field-types'), ['.', '..']));

	// 	foreach ($customFieldTypes as $fieldType) {
	// 		include_once get_template_directory() . '/custom-field-types/' . $fieldType . '/' . $fieldType . '.php';
	// 	}
	// }

	public function modify_head()
	{
		SHUtils::add_google_analytics();
	}

	public function init_hooks()
	{
		// SHUtils::register_custom_post_types();
		SHUtils::clean_head();
		SHUtils::remove_default_taxes();
	}

	public function admin_init_hooks()
	{
		SHUtils::disable_comments_logic();
		SHUtils::add_tinymce_editor_styles();
	}

	public function admin_menu_hooks()
	{
		SHUtils::cleanup_admin_menu();
	}

	public function login_page_hooks()
	{
		SHUtils::set_login_page_styles();
	}

	public function rest_init_hooks()
	{
		$R = new RESTLogic();
		$R->register_fields();
		$R->register_routes();
	}
}
