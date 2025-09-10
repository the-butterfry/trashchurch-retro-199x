<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="tr-sr" href="#primary-content"><?php _e('Skip to content','trashchurch-retro-199x'); ?></a>
<div class="tr-wrapper">
  <header class="tr-header">
    <?php if ( has_custom_logo() ) the_custom_logo(); ?>
    <h1 class="tr-title"><a href="<?php echo esc_url( home_url('/') ); ?>"><?php bloginfo('name'); ?></a></h1>
    <div class="tr-tagline"><?php bloginfo('description'); ?></div>

    <button class="tr-nav-toggle" id="tr-nav-toggle" aria-expanded="false" aria-controls="tr-primary-nav">
      <?php esc_html_e('MENU','trashchurch-retro-199x'); ?> ☰
    </button>

    <nav class="tr-nav" id="tr-primary-nav" role="navigation" aria-label="<?php esc_attr_e('Primary Menu','trashchurch-retro-199x'); ?>">
      <?php
        wp_nav_menu(array(
          'theme_location'=>'primary',
          'container'=>false,
          'fallback_cb'=>function(){
            echo '<ul><li><a href="'.esc_url(home_url('/')).'">'.esc_html__('Home','trashchurch-retro-199x').'</a></li></ul>';
          }
        ));
      ?>
    </nav>

    <?php if ( get_theme_mod('tr199x_enable_marquee', false ) ) : ?>
      <div class="tr-marquee">
        <marquee scrollamount="<?php echo intval(get_theme_mod('tr199x_marquee_speed',10)); ?>">
          <?php echo esc_html( get_theme_mod('tr199x_marquee_text', __('WELCOME TO THE TRASHCHURCH RETRO 199X PORTAL! ADJUST EFFECTS IN CUSTOMIZER!','trashchurch-retro-199x')) ); ?>
        </marquee>
      </div>
    <?php endif; ?>
  </header>