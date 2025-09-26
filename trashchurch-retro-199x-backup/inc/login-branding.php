<?php
/**
 * Login screen branding (logic)
 *
 * Enqueues a single, dedicated login stylesheet (assets/css/login.css) and
 * injects only the small CSS variables the stylesheet needs:
 *   --tr-login-logo: url('...')
 *   --tr-login-logo-w: 250px
 *   --tr-login-logo-h: 250px
 *
 * Keep this file in: wp-content/themes/trashchurch-retro-199x/inc/login-branding.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root URL to the login logo — by default serve from the theme assets.
 * Filter: 'tr199x_login_logo_path'
 *
 * @return string
 */
function tr199x_get_login_logo_path() {
	$default = untrailingslashit( get_template_directory_uri() ) . '/assets/img/login-logo.png';
	$path = apply_filters( 'tr199x_login_logo_path', $default );
	return is_string( $path ) ? trim( $path ) : $default;
}

/**
 * Login styling settings (sizes).
 * Filter: 'tr199x_login_branding_settings'
 */
function tr199x_login_branding_settings() {
	return apply_filters( 'tr199x_login_branding_settings', array(
		'logo_width'      => 250,
		'logo_height'     => 250,
	) );
}

/**
 * Enqueue only the dedicated login stylesheet and inject CSS variables.
 */
function tr199x_enqueue_login_styles() {
	$handle = 'tr199x-login-style';
	$src = get_stylesheet_directory_uri() . '/assets/css/login.css';
	$path = get_stylesheet_directory() . '/assets/css/login.css';
	$ver = file_exists( $path ) ? filemtime( $path ) : null;

	wp_enqueue_style( $handle, $src, array(), $ver );

	$s = tr199x_login_branding_settings();
	$logo_url = esc_url( tr199x_get_login_logo_path() );
	$logo_w = intval( $s['logo_width'] );
	$logo_h = intval( $s['logo_height'] );

	// Add CSS variables scoped to the login page body.
	$inline = sprintf(
		"body.login { --tr-login-logo: url('%s'); --tr-login-logo-w: %dpx; --tr-login-logo-h: %dpx; }",
		$logo_url,
		$logo_w,
		$logo_h
	);
	wp_add_inline_style( $handle, $inline );
}
add_action( 'login_enqueue_scripts', 'tr199x_enqueue_login_styles', 15 );

/**
 * Make the login logo link point to the site home instead of wordpress.org
 */
function tr199x_login_logo_url() {
	return home_url();
}
add_filter( 'login_headerurl', 'tr199x_login_logo_url' );

/**
 * Change the title attribute (hover text) on the login logo
 */
function tr199x_login_logo_title() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'tr199x_login_logo_title' );
