<?php
/**
 * Footer helpers (menus, customizer, Konami egg).
 * Random badge mode: pick 4 random badge images from /assets/badges/,
 * assigns random links from the textarea in Customizer.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Register footer menu locations */
add_action( 'after_setup_theme', function() {
    register_nav_menus( array(
        'footer_primary' => __( 'Footer (Primary Links)', 'trashchurch-retro-199x' ),
        'footer_utility' => __( 'Footer (Utility Links)', 'trashchurch-retro-199x' ),
        'footer_social'  => __( 'Footer (Connect/Social Links)', 'trashchurch-retro-199x' ),
    ) );
}, 7 );

/* Customizer: Footer badge links textarea */
add_action( 'customize_register', function( WP_Customize_Manager $wp_customize ) {
    $section_id = 'tr199x_footer_extras';
    $wp_customize->add_section( $section_id, array(
        'title'       => __( 'Footer Extras (199x GIFs)', 'trashchurch-retro-199x' ),
        'priority'    => 165,
        'description' => __( 'Upload badge images to /assets/badges/ in your theme folder. Paste a list of links below (one per line). On each page load, 4 badges and links will be randomly paired.', 'trashchurch-retro-199x' ),
    ) );

    $wp_customize->add_setting( 'tr199x_show_badges', array(
        'type'              => 'theme_mod',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default'           => true,
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'tr199x_show_badges', array(
        'type'    => 'checkbox',
        'section' => $section_id,
        'label'   => __( 'Show footer 199x badges', 'trashchurch-retro-199x' ),
    ) );

    $wp_customize->add_setting( 'tr199x_footer_badge_links', array(
        'type'              => 'theme_mod',
        'sanitize_callback' => function( $input ) {
            // Clean up, remove empty lines, sanitize URLs
            $lines = array_filter( array_map( 'trim', explode( "\n", $input ) ) );
            $safe = array_map( 'esc_url_raw', $lines );
            return implode("\n", $safe);
        },
        'default'           => '',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'tr199x_footer_badge_links', array(
        'type'        => 'textarea',
        'section'     => $section_id,
        'settings'    => 'tr199x_footer_badge_links',
        'label'       => __( 'Badge Links (one per line, will be randomly paired with images)', 'trashchurch-retro-199x' ),
    ) );
} );

/* Konami Code egg unchanged—keep your existing JavaScript section */
add_action( 'wp_enqueue_scripts', function() {
    $js = <<<JS
(function() {
  var seq = [38,38,40,40,37,39,37,39,66,65];
  var idx = 0;
  window.addEventListener('keydown', function(ev) {
    if (ev.keyCode === seq[idx]) {
      idx++;
      if (idx === seq.length) {
        idx = 0;
        document.body.classList.toggle('egg-on');
        var link = document.querySelector('.tr-egg-reveal');
        if (link) link.classList.toggle('is-hidden');
      }
    } else {
      idx = 0;
    }
  });
})();
JS;

    if ( wp_script_is( 'tr199x-main', 'enqueued' ) || wp_script_is( 'tr199x-main', 'registered' ) ) {
        wp_add_inline_script( 'tr199x-main', $js, 'after' );
    } else {
        add_action( 'wp_footer', function() use ( $js ) {
            echo '<script>' . $js . '</script>';
        }, 100 );
    }
}, 20 );
