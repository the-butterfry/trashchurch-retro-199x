<?php
/**
 * Theme Footer (4 columns + "Get Real" + Site Hits)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Resolve URLs used in the footer
$member_page  = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'member-services' ) : null;
$member_url   = $member_page ? get_permalink( $member_page ) : home_url( '/member-services/' );

$playlist_page = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'playlist' ) : null;
$playlist_url  = $playlist_page ? get_permalink( $playlist_page ) : home_url( '/playlist/' );

$guestbook_mod = get_theme_mod( 'tr199x_footer_gif_guestbook', '' );
$netscape_mod  = get_theme_mod( 'tr199x_footer_gif_netscape', '' );
$gif_guestbook = $guestbook_mod ? $guestbook_mod : get_template_directory_uri() . '/assets/badges/btn-guestbook.gif';
$gif_netscape  = $netscape_mod ? $netscape_mod : get_template_directory_uri() . '/assets/badges/btn-netscape.gif';
$show_badges   = get_theme_mod( 'tr199x_show_badges', true );
?>
<footer class="tr-footer" role="contentinfo">
  <div class="tr-footer-wrap"><!-- was .tr-layout; renamed to avoid global layout grid -->
    <div class="tr-footer-grid">
      <!-- Column 1: Brand + Member button -->
      <section class="tr-footer-col tr-footer-about" aria-labelledby="tr-footer-about-title">
        <h2 id="tr-footer-about-title" class="tr-footer-title">Trash Church</h2>

        <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) : ?>
          <div class="tr-footer-logo"><?php the_custom_logo(); ?></div>
        <?php endif; ?>

        <p class="tr-footer-cta">
          <a class="tr-member-btn tr-footer-member-btn" href="<?php echo esc_url( $member_url ); ?>">
            <span class="label-full"><?php esc_html_e( 'Member Services', 'trashchurch-retro-199x' ); ?></span>
            <span class="label-short"><?php esc_html_e( 'Members', 'trashchurch-retro-199x' ); ?></span>
          </a>
        </p>
      </section>

      <!-- Column 2: Quick Links -->
      <nav class="tr-footer-col tr-footer-links" aria-label="<?php esc_attr_e( 'Quick Links', 'trashchurch-retro-199x' ); ?>">
        <h2 class="tr-footer-title"><?php esc_html_e( 'Quick Links', 'trashchurch-retro-199x' ); ?></h2>
        <?php
        if ( has_nav_menu( 'footer_primary' ) ) {
          wp_nav_menu( array(
            'theme_location' => 'footer_primary',
            'menu_class'     => 'tr-footer-menu',
            'container'      => false,
            'depth'          => 1,
            'fallback_cb'    => false,
          ) );
        } else {
          echo '<ul class="tr-footer-menu">';
          echo '<li><a href="' . esc_url( home_url( '/tenets/' ) ) . '">Tenets</a></li>';
          echo '<li><a href="' . esc_url( home_url( '/holidays/' ) ) . '">Holidays</a></li>';
          echo '<li><a href="' . esc_url( home_url( '/sacrements/' ) ) . '">Sacrements</a></li>';
          echo '<li><a href="' . esc_url( home_url( '/trash-vibes/' ) ) . '">Trash Vibes</a></li>';
          echo '</ul>';
        }
        ?>
      </nav>

      <!-- Column 3: Connect -->
      <nav class="tr-footer-col tr-footer-social" aria-label="<?php esc_attr_e( 'Connect', 'trashchurch-retro-199x' ); ?>">
        <h2 class="tr-footer-title"><?php esc_html_e( 'Connect', 'trashchurch-retro-199x' ); ?></h2>
        <?php
        if ( has_nav_menu( 'footer_social' ) ) {
          wp_nav_menu( array(
            'theme_location' => 'footer_social',
            'menu_class'     => 'tr-footer-social-list',
            'container'      => false,
            'depth'          => 1,
            'fallback_cb'    => false,
          ) );
        }
        ?>
        <ul class="tr-footer-connect-extras">
          <li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'trashchurch-retro-199x' ); ?></a></li>
        </ul>
      </nav>

      <!-- Column 4: GIFs + Site Hits + Easter Egg (moved here under hits) -->
      <section class="tr-footer-col tr-footer-extras-col" aria-labelledby="tr-footer-get-real-title">
        <h2 id="tr-footer-get-real-title" class="tr-footer-title"><?php esc_html_e( 'Get Real', 'trashchurch-retro-199x' ); ?></h2>

        <?php if ( $show_badges ) : ?>
          <div class="tr-badges-line">
            <a class="tr-gif tr-gif-guestbook" href="<?php echo esc_url( home_url( '/guestbook/' ) ); ?>">
              <img src="<?php echo esc_url( $gif_guestbook ); ?>" alt="<?php esc_attr_e( 'Sign Guestbook', 'trashchurch-retro-199x' ); ?>" loading="lazy" decoding="async" />
            </a>
            <img class="tr-gif tr-gif-netscape" src="<?php echo esc_url( $gif_netscape ); ?>" alt="<?php esc_attr_e( 'Netscape Now', 'trashchurch-retro-199x' ); ?>" loading="lazy" decoding="async" />
          </div>
        <?php endif; ?>

        <div class="tr-hits-line">
          <?php
          if ( function_exists('tr199x_hit_counter') && get_theme_mod('tr199x_enable_hit_counter', true) ) {
            tr199x_hit_counter();
            if ( get_theme_mod('tr199x_hits_async', false) && class_exists('TR199X_HitCounter') ) {
              echo '<div id="global-hit-count">' . esc_html( TR199X_HitCounter::get_global_hits() ) . '</div>';
            }
          }
          ?>
        </div>

        <!-- Easter egg moved under hits -->
        <p class="tr-egg-wrap">
          <a class="tr-egg-reveal is-hidden" href="<?php echo esc_url( $playlist_url ); ?>" aria-label="<?php esc_attr_e( 'Playlist', 'trashchurch-retro-199x' ); ?>">🎶 <?php esc_html_e( 'Playlist', 'trashchurch-retro-199x' ); ?></a>
        </p>
      </section>

      <!-- Bottom: stays under column 1 on desktop -->
      <div class="tr-footer-bottom">
        <div class="tr-footer-copy">
          &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
        </div>

        <?php if ( has_nav_menu( 'footer_utility' ) ) : ?>
          <nav class="tr-footer-utility" aria-label="<?php esc_attr_e( 'Utility links', 'trashchurch-retro-199x' ); ?>">
            <?php
            wp_nav_menu( array(
              'theme_location' => 'footer_utility',
              'menu_class'     => 'tr-footer-utility-list',
              'container'      => false,
              'depth'          => 1,
              'fallback_cb'    => false,
            ) );
            ?>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php wp_footer(); ?>
</footer>
