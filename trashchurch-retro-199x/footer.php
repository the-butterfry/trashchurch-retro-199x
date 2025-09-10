  <footer class="tr-footer">
    <?php if ( get_theme_mod('tr199x_show_badges', true) ) : ?>
      <div class="tr-badges">
        <img src="<?php echo esc_url( get_template_directory_uri().'/assets/badges/btn-valid-html.gif'); ?>" alt="Valid HTML?">
        <img src="<?php echo esc_url( get_template_directory_uri().'/assets/badges/btn-under-construction.gif'); ?>" alt="Under Construction">
        <img src="<?php echo esc_url( get_template_directory_uri().'/assets/badges/btn-best-viewed.gif'); ?>" alt="Best Viewed 1024x768">
        <img src="<?php echo esc_url( get_template_directory_uri().'/assets/badges/btn-guestbook.gif'); ?>" alt="Sign Guestbook">
        <img src="<?php echo esc_url( get_template_directory_uri().'/assets/badges/btn-netscape.gif'); ?>" alt="Netscape Now">
      </div>
    <?php endif; ?>

    <?php if ( function_exists('tr199x_hit_counter') && get_theme_mod('tr199x_enable_hit_counter', true ) ): ?>
      <?php tr199x_hit_counter(); ?>
      <?php if ( get_theme_mod('tr199x_hits_async', false ) ): ?>
        <div id="global-hit-count"><?php echo TR199X_HitCounter::get_global_hits(); ?></div>
      <?php endif; ?>
    <?php endif; ?>

    <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?> · <?php esc_html_e('Trash Church Retro 199X','trashchurch-retro-199x'); ?></p>
  </footer>
</div><!-- .tr-wrapper -->
<?php wp_footer(); ?>
</body>
</html>