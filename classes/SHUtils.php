<?php

if (file_exists(get_template_directory() . '/vendor/autoload.php')) {
	require_once get_template_directory() . '/vendor/autoload.php';
}

// require_once __DIR__ . '/helpers/CustomPostTypes.php';

use SendGrid\Mail\Mail;

class SHUtils
{

	public static function remove_default_taxes() {
		global $wp_taxonomies;
		$taxonomies = array( 'category', 'post_tag' );
		foreach( $taxonomies as $taxonomy ) {
		if ( taxonomy_exists( $taxonomy) )
		unset( $wp_taxonomies[$taxonomy]);
		}

	}

	public static function add_google_analytics()
	{
		$code = get_field('tracking_id', 'options') ? get_field('tracking_id', 'options') : '';
		$initActive = !empty($code) && $_COOKIE['acceptedCookiePrompt'] !== 'no';

		$hostBlacklist = ['pixelsmith.co', 'wpengine.com', '.test', 'localhost'];
		$analyticsActive = array_reduce($hostBlacklist, function ($carry, $item) {
			return $carry && (strpos($_SERVER['HTTP_HOST'], $item) === false);
		}, $initActive );

		if (!$analyticsActive) {
			echo '<!– [Analytics ID: ' . $code . '] : Not displayed due to domain –>';
			return;
		}

		echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $code . '"></script>';
		echo '<script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag(\'js\', new Date());gtag(\'config\', \'' . $code . '\');</script>';
	}

	public static function include_svg_markup($value, $post_id, $field)
	{
		$url = parse_url($value['url']);
		$pathInfo = pathinfo($url['path']);

		if (!isset($pathInfo['extension'])) {
			return $value;
		}

		if ($pathInfo['extension'] === 'svg') {
			$svg = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $url['path']);
			$value['svg'] = $svg;
		}

		return $value;
	}

	public static function acf_sync_notice()
	{
		$acf = new acf_admin_field_groups();

		$acf->check_sync();

		$sync = $acf->sync;

		if (empty($sync)) return;

		echo '<div class="notice notice-error is-dismissible">';
		echo '<p>Your custom fields are out of date, please <a href="' . admin_url('edit.php?post_type=acf-field-group&post_status=sync') . '">update here</a></p>';
		echo '</div>';
	}

	public static function clean_head()
	{
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'feed_links', 2);
		remove_action('wp_head', 'index_rel_link');
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'feed_links_extra', 3);
		remove_action('wp_head', 'start_post_rel_link', 10, 0);
		remove_action('wp_head', 'parent_post_rel_link', 10, 0);
		remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
		remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
		remove_action('wp_head', 'wp_oembed_add_host_js');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('wp_print_styles', 'print_emoji_styles');
		remove_action('admin_print_styles', 'print_emoji_styles');
	}

	public static function disable_comments_logic()
	{
		global $pagenow;

		// Disable comment support for each post type
		foreach (get_post_types() as $post_type) {
			if (post_type_supports($post_type, 'comments')) {
				remove_post_type_support($post_type, 'comments');
				remove_post_type_support($post_type, 'trackbacks');
			}
		}

		// Remove comments metabox from dashboard
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

		// Redirect users trying to access the comments edit screen
		if ($pagenow === 'edit-comments.php') {
			wp_redirect(admin_url());
			exit;
		}
	}

	public static function add_tinymce_editor_styles()
	{
		add_editor_style('dist/app.min.css');
	}

	public static function cleanup_admin_menu()
	{
		// Dashboard
		// remove_menu_page( 'upload.php' );

		// Posts
		// remove_menu_page( 'edit.php' );

		// Media
		//remove_menu_page( 'upload.php' );

		// Links
		//remove_menu_page( 'link-manager.php' );

		// Pages
		// remove_menu_page( 'edit.php?post_type=page' );

		// Comments
		remove_menu_page('edit-comments.php');

		// Appearance
		remove_menu_page('themes.php');
		add_menu_page('Menus', 'Menus', 'manage_options', 'nav-menus.php', '', 'dashicons-menu');
		add_menu_page('Themes', 'Themes', 'manage_options', 'themes.php');
		// remove_submenu_page( 'themes.php', 'themes.php' );
		// remove_submenu_page( 'themes.php', 'theme-editor.php' );
		// remove_submenu_page( 'themes.php', 'nav-menus.php' );
		// remove_submenu_page( 'themes.php', 'customize.php' );

		// Plugins
		// remove_menu_page( 'plugins.php' );

		// Users
		// remove_menu_page( 'users.php' );

		// Tools
		remove_menu_page('tools.php');

		// Settings
		//remove_menu_page( 'options-general.php' );
	}

	public static function local_mail_filter($args)
	{
		if (
			(strpos($_SERVER['HTTP_HOST'], 'localhost') === false &&
				strpos($_SERVER['HTTP_HOST'], '.test') === false &&
				strpos($_SERVER['HTTP_HOST'], 'pixelsmith.co') === false) ||
			!class_exists('Mail') ||
			!class_exists('Sendgrid')
		) return $args;

		if (!is_array($args['to'])) $args['to'] = explode(',', $args['to']);

		$email = new Mail();
		$email->setFrom("test@example.com", "Example User");
		$email->setSubject($args['subject']);

		foreach ($args['to'] as $address) {
			$email->addTo($address);
		}

		$email->addContent("text/html", $args['message']);
		$sendgrid = new SendGrid('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');

		try {
			$response = $sendgrid->send($email);
			// print $response->statusCode() . "\n";
			// print_r($response->headers());
			// print $response->body() . "\n";
		} catch (Exception $e) {
			echo 'Caught exception: ' . $e->getMessage() . "\n";
		}

		return $args;
	}

	// public static function register_custom_post_types()
	// {
	// 	$CPT = new CustomPostTypes();
	// 	$CPT->register();
	// }

	public static function set_login_page_styles()
	{
		wp_enqueue_style('custom-login', get_template_directory_uri() . '/dist/login-page.min.css');

		$style = '';
		$logo = get_field('admin_logo', 'options');
		$background_color = get_field('background_color', 'options');
		$background_image = get_field('background_image', 'options');
		$background_image_position = get_field('background_image_position', 'options');
		$background_size = get_field('background_size', 'options');

		if (!empty($logo)) {
			$width = ((int) $logo['width'] > 320) ? 'auto' : $logo['width'] . 'px';
			$style .= '#login h1 a,
					.login h1 a {
						background-image: url(' . $logo['url'] . ');
						width: ' . $width . ';
						background-size: contain;
					}';
		}

		if (!empty($background_color)) {
			$style .= 'body.login {
				background-color: ' . $background_color . ';
			}';
		}

		if (!empty($background_image)) {
			$style .= 'body.login {
				background-image: url(' . $background_image['url'] . ');
				background-repeat: no-repeat;
			}';
		}

		if (!empty($background_image_position)) {
			$style .= 'body.login {
				background-position: ' . $background_image_position . ';
			}';
		}

		if (!empty($background_size)) {
			$style .= 'body.login {
				background-size: ' . $background_size . ';
			}';
		}

		echo '<style>' . $style . '</style>';
	}

	public static function add_30_day_cron_schedule($schedules)
	{
		$schedules['thirty_days'] = array(
			'interval' => 60 * 60 * 24 * 30,
			'display'  => esc_html__('Every Thirty Days'),
		);

		return $schedules;
	}

	public static function modify_acf_wysiwyg_toolbars($toolbars)
	{
		$fullToolbar = [
			'styleselect',
			'bold',
			'italic',
			'hr', 'link',
			'unlink',
			'bullist',
			'numlist',
			'blockquote',
			'alignleft',
			'aligncenter',
			'alignright',
			'removeformat'
		];

		$basicToolbar = [
			'bold',
			'italic',
			'link',
			'unlink',
			'removeformat'
		];

		unset($toolbars['Full']);
		unset($toolbars['Basic']);

		$toolbars['Full'] = [];
		$toolbars['Full'][1] = $fullToolbar;

		$toolbars['Basic'] = [];
		$toolbars['Basic'][1] = $basicToolbar;

		return $toolbars;
	}

	public static function modify_tiny_mce_format_options($initArray)
	{
		$style_formats = array(

			array(
				'title' => 'Paragraph',
				'block' => 'p',
			),

			array(
				'title' => 'H1',
				'block' => 'h1',
			),

			array(
				'title' => 'H2',
				'block' => 'h2',
				'classes' => 'h2',
			),

			array(
				'title' => 'H3',
				'block' => 'h2',
				'classes' => 'h3',
			),

			array(
				'title' => 'H4',
				'block' => 'h2',
				'classes' => 'h4',
			),

			array(
				'title' => 'H5',
				'block' => 'h2',
				'classes' => 'h5',
			),

			array(
				'title' => 'Button',
				'inline' => 'a',
				'classes' => 'btn',
				'selector' => 'a'
			),

		);

		$initArray['relative_urls'] = true;
		$initArray['style_formats'] = json_encode($style_formats);

		return $initArray;
	}

	public static function add_editor_styles()
	{
		add_editor_style('dist/app.min.css');
	}

	public static function format_acf_images($value, $post_id, $field)
	{

		if (isset($value['url'])) {
			$url = parse_url($value['url']);
			$pathInfo = pathinfo($url['path']);
		}

		// add inline svg to image array
		if (isset($pathInfo['extension']) && $pathInfo['extension'] === 'svg') {
			$svg = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $url['path']);
			$value['svg'] = $svg;
		}

		if (isset($value['ID'])) {
			$value['html'] = wp_get_attachment_image($value['ID'], 'original');
		}

		return $value;
	}

	public static function px_format_acf_gallery_images($value, $post_id, $field)
	{

		foreach ($value as $i => $image) {
			if (isset($image['ID'])) {
				$value[$i]['html'] = wp_get_attachment_image($image['ID'], 'original');
			}
		}

		return $value;
	}

 	// Populate ACF select field options with Gravity Forms forms
	public static function px_acf_populate_gf_forms_ids( $field )
	{
		if ( class_exists( 'GFFormsModel' ) ) {
			$choices = [];

			foreach ( \GFFormsModel::get_forms() as $form ) {
				$choices[ $form->id ] = $form->title;
			}

			$field['choices'] = $choices;
		}

		return $field;
	}

	public static function px_acf_load_post_types( $field )
	{
	    foreach ( get_post_types( $args = array( 'public' => true, '_builtin' => false ), 'names' ) as $post_type ) {

	       $field['choices'][$post_type] = $post_type;

	    }
	    // we removed all builtin objects but need to include post
	    $field['choices']['post'] = 'post' ;

	    // return the field
	    return $field;
	}

	public static function px_get_posts( $query )
	{

		global $post;
		$posts = $query->posts;

		foreach( $posts as $post) {

			setup_postdata($post);

			// add custom data here
			$post->title 			= get_the_title( $post->ID );
			$post->acf 				= get_fields( $post->ID );
			$post->link 			= wp_make_link_relative( get_the_permalink( $post->ID ) );
			$post->date 			= get_the_time( 'F j, Y', $post->ID );
			$post->post_thumbnail 	= get_the_post_thumbnail_url( $post->ID );
			$post->url 				= get_the_permalink( $post->ID );
			$post->excerpt 			= substr( wp_strip_all_tags( get_the_content() ), 0, 110 ) . '...';

		}

		wp_reset_postdata();
		return $posts;

	}

}

define('ALLOW_UNFILTERED_UPLOADS', true);