<?php
/**
 * The header for our theme
 *
 * (excerpt) - this file now puts Menu text into a screen-reader-only <span>
 */ ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="tr-sr" href="#content"><?php esc_html_e( 'Skip to content', 'trashchurch-retro-199x' ); ?></a>

<div class="tr-wrapper">

  <header class="tr-header" role="banner" aria-label="<?php esc_attr_e( 'Site header', 'trashchurch-retro-199x' ); ?>">
    <?php get_template_part( 'header-title-wrap-by-id' ); ?>

    <div class="tr-nav-row">
      <button
        class="tr-nav-toggle"
        aria-expanded="false"
        aria-controls="site-navigation"
        aria-label="<?php esc_attr_e( 'Primary menu', 'trashchurch-retro-199x' ); ?>"
      >
        <!-- visible CSS-drawn hamburger (aria-hidden so screen readers use the sr-only text) -->
        <span class="tr-hamburger" aria-hidden="true"></span>
        <span class="tr-sr"><?php esc_html_e( 'Menu', 'trashchurch-retro-199x' ); ?></span>
      </button>

      <div class="tr-nav-bar">
        <nav id="site-navigation" class="tr-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'trashchurch-retro-199x' ); ?>">
          <?php
          wp_nav_menu( array(
            'theme_location' => 'primary',
            'menu_class'     => 'tr-main-menu',
            'container'      => false,
            'depth'          => 2,
            'fallback_cb'    => false,
          ) );
          ?>
        </nav>
      </div> <!-- /.tr-nav-bar -->
    </div> <!-- /.tr-nav-row -->

    <?php if ( get_theme_mod( 'tr199x_enable_marquee', false ) ) :
      $mtext  = get_theme_mod( 'tr199x_marquee_text', __( 'WELCOME TO THE TRASHCHURCH RETRO 199X PORTAL! ADJUST EFFECTS IN CUSTOMIZER!', 'trashchurch-retro-199x' ) );
      $mspeed = (int) get_theme_mod( 'tr199x_marquee_speed', 10 );
      $mspeed = max( 1, min( 40, $mspeed ) );
    ?>
      <div class="tr-marquee" role="region" aria-label="<?php esc_attr_e( 'Site news ticker', 'trashchurch-retro-199x' ); ?>">
        <marquee behavior="scroll" direction="left" scrollamount="<?php echo esc_attr( $mspeed ); ?>">
          <?php echo esc_html( $mtext ); ?>
        </marquee>
      </div>
    <?php endif; ?>

  </header>

  <div id="content" class="site-content">
