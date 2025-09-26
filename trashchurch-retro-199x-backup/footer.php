<?php
/**
 * Theme Footer (4 columns + "Get Real" + Site Hits)
 * Random badge mode: picks up to 4 random images from /assets/badges/ and randomizes links.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$member_page  = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'member-services' ) : null;
$member_url   = $member_page ? get_permalink( $member_page ) : home_url( '/member-services/' );
$playlist_page = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'playlist' ) : null;
$playlist_url  = $playlist_page ? get_permalink( $playlist_page ) : home_url( '/playlist/' );

$show_badges = get_theme_mod( 'tr199x_show_badges', true );

// 1. Gather all badge image files from /assets/badges/
$badge_dir  = get_template_directory() . '/assets/badges/';
$badge_uri  = get_template_directory_uri() . '/assets/badges/';
$badge_imgs = [];
if ( is_dir( $badge_dir ) ) {
    $files = scandir( $badge_dir );
    foreach ( $files as $f ) {
        if ( preg_match('/\.(gif|png|jpe?g)$/i', $f ) ) {
            $badge_imgs[] = $badge_uri . $f;
        }
    }
}

// 2. Get links from Customizer textarea and shuffle
$links_raw = get_theme_mod('tr199x_footer_badge_links', '');
$link_lines = array_filter(array_map('trim', explode("\n", $links_raw)));
$badge_links = $link_lines;
shuffle($badge_imgs);
shuffle($badge_links);

// 3. Randomly pick up to 4 images, assign random links, show in grid
$badges = [];
$num = min( 4, count($badge_imgs) );
for ( $i = 0; $i < $num; $i++ ) {
    $img = esc_url( $badge_imgs[$i] );
    $link = !empty($badge_links) ? esc_url( $badge_links[ array_rand($badge_links) ] ) : '';
    $badges[] = [ 'img' => $img, 'link' => $link ];
}
?>
<footer class="tr-footer" role="contentinfo">
  <div class="tr-footer-wrap">
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

      <!-- Column 4: GIFs + Site Hits + Easter Egg -->
      <section class="tr-footer-col tr-footer-extras-col" aria-labelledby="tr-footer-get-real-title">
        <h2 id="tr-footer-get-real-title" class="tr-footer-title"><?php esc_html_e( 'Get Real', 'trashchurch-retro-199x' ); ?></h2>
        <?php if ( $show_badges && $badges ) : ?>
          <div class="tr-badges-grid">
            <?php foreach ( $badges as $badge ) :
                if ( $badge['img'] ) {
                    echo $badge['link']
                        ? '<a class="tr-gif" href="' . $badge['link'] . '" target="_blank" rel="noopener"><img src="' . $badge['img'] . '" alt="Retro Badge" loading="lazy" decoding="async" /></a>'
                        : '<img class="tr-gif" src="' . $badge['img'] . '" alt="Retro Badge" loading="lazy" decoding="async" />';
                }
            endforeach; ?>
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
        <p class="tr-egg-wrap">
          <a class="tr-egg-reveal is-hidden" href="<?php echo esc_url( $playlist_url ); ?>" aria-label="<?php esc_attr_e( 'Playlist', 'trashchurch-retro-199x' ); ?>">🎶 <?php esc_html_e( 'Playlist', 'trashchurch-retro-199x' ); ?></a>
        </p>
      </section>

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
  <!-- Expose WP AJAX URL for frontend scripts -->
  <script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
  </script>
  <?php wp_footer(); ?>
</footer>