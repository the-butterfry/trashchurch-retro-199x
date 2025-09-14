<?php
/**
 * Header/Nav specific functions for TrashChurch Retro 199X
 * This isolates navigation/header logic from the main functions.php.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Ensure primary nav location exists.
 * This runs early to avoid clashes with other setup hooks.
 */
function tr199x_register_menus_header() {
    if ( ! function_exists('register_nav_menu') ) return;

    // Only register if not already present
    $existing = function_exists('get_registered_nav_menus') ? get_registered_nav_menus() : array();
    if ( ! is_array($existing) || ! array_key_exists( 'primary', $existing ) ) {
        register_nav_menu( 'primary', __( 'Primary Menu', 'trashchurch-retro-199x' ) );
    }
}
add_action( 'after_setup_theme', 'tr199x_register_menus_header', 5 );

/**
 * Append a "Member Services" item inside the Primary menu <ul>.
 * - Renders as the last <li>, allowing CSS to push it to the right and style as a button.
 * - Includes short/long labels for responsive CSS swapping.
 *
 * NOTE: If you add this item manually in Appearance > Menus, remove this filter
 * to avoid duplicates.
 */
function tr199x_nav_append_member_services( $items, $args ) {
    if ( isset($args->theme_location) && $args->theme_location === 'primary' ) {
        $url         = esc_url( home_url( '/member-services' ) );
        $label_full  = esc_html__( 'Member Services', 'trashchurch-retro-199x' );
        $label_short = esc_html__( 'Members', 'trashchurch-retro-199x' );

        $items .= sprintf(
            '<li class="menu-item menu-item-type-custom menu-item-member-services">' .
              '<a class="tr-member-btn" href="%s">' .
                '<span class="label-full">%s</span>' .
                '<span class="label-short">%s</span>' .
              '</a>' .
            '</li>',
            $url, $label_full, $label_short
        );
    }
    return $items;
}
add_filter( 'wp_nav_menu_items', 'tr199x_nav_append_member_services', 10, 2 );
