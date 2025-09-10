<aside class="tr-sidebar">
  <?php if ( is_active_sidebar('tr199x-sidebar') ) : dynamic_sidebar('tr199x-sidebar'); else: ?>
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
            echo '<li><a href="'.get_permalink($r['ID']).'">'.esc_html($r['post_title']).'</a></li>';
          }
        ?>
      </ul>
    </div>
    <div class="widget">
      <h3 class="widget-title"><?php esc_html_e('Archives','trashchurch-retro-199x'); ?></h3>
      <ul><?php wp_get_archives(array('type'=>'monthly','limit'=>6)); ?></ul>
    </div>
    <div class="widget">
      <h3 class="widget-title"><?php esc_html_e('Meta','trashchurch-retro-199x'); ?></h3>
      <ul>
        <?php wp_register(); ?>
        <li><?php wp_loginout(); ?></li>
        <li><a href="<?php echo esc_url(get_feed_link('rss2')); ?>">RSS</a></li>
      </ul>
    </div>
    <div class="widget">
      <h3 class="widget-title"><?php esc_html_e('Guestbook Tip','trashchurch-retro-199x'); ?></h3>
      <p><?php esc_html_e('Create a page named "Guestbook" and enable comments for authentic sign-ins.','trashchurch-retro-199x'); ?></p>
    </div>
  <?php endif; ?>
</aside>