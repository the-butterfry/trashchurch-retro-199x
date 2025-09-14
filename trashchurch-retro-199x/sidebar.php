<aside class="tr-sidebar">
  <?php
  // Member Services menu (safe-guarded; renders only if function exists and a menu is assigned)
  if ( function_exists( 'tr199x_render_member_nav' ) ) {
    tr199x_render_member_nav();
  }
  ?>

  <?php if ( is_active_sidebar('tr199x-sidebar') ) : ?>
    <?php dynamic_sidebar('tr199x-sidebar'); ?>
  <?php else: ?>
    <div class="widget">
      <h3 class="widget-title"><?php esc_html_e('Search','trashchurch-retro-199x'); ?></h3>
      <?php get_search_form(); ?>
    </div>

    <div class="widget">
      <h3 class="widget-title"><?php esc_html_e('Recent Posts','trashchurch-retro-199x'); ?></h3>
      <ul>
        <?php
          $recent = wp_get_recent_posts(array('numberposts'=>5));
          foreach ($recent as $r) {
            echo '<li><a href="'.esc_url(get_permalink($r['ID'])).'">'.esc_html($r['post_title']).'</a></li>';
          }
        ?>
      </ul>
    </div>
  <?php endif; ?>
</aside>
