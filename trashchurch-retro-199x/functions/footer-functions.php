<?php
/**
 * Footer helpers (menus, customizer, Konami egg).
 * Note: Retro hit counter is provided by your existing code (tr199x_hit_counter etc).
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

/* Customizer: Footer GIFs (Guestbook + Netscape 88×31) and toggle */
add_action( 'customize_register', function( WP_Customize_Manager $wp_customize ) {
    $section_id = 'tr199x_footer_extras';
    $wp_customize->add_section( $section_id, array(
        'title'       => __( 'Footer Extras (199x GIFs)', 'trashchurch-retro-199x' ),
        'priority'    => 165,
        'description' => __( 'Configure Guestbook and Netscape badges used in the footer.', 'trashchurch-retro-199x' ),
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
        'label'   => __( 'Show footer 199x badges (Guestbook + Netscape)', 'trashchurch-retro-199x' ),
    ) );

    $wp_customize->add_setting( 'tr199x_footer_gif_guestbook', array(
        'type'              => 'theme_mod',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
        'default'           => '',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'tr199x_footer_gif_guestbook', array(
        'label'       => __( 'Guestbook GIF (88×31)', 'trashchurch-retro-199x' ),
        'section'     => $section_id,
        'settings'    => 'tr199x_footer_gif_guestbook',
    ) ) );

    $wp_customize->add_setting( 'tr199x_footer_gif_netscape', array(
        'type'              => 'theme_mod',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
        'default'           => '',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'tr199x_footer_gif_netscape', array(
        'label'       => __( 'Netscape GIF (88×31)', 'trashchurch-retro-199x' ),
        'section'     => $section_id,
        'settings'    => 'tr199x_footer_gif_netscape',
    ) ) );
} );

/* Konami Code easter egg: toggles body.egg-on and reveals .tr-egg-reveal */
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
