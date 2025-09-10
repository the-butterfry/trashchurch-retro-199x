<?php get_header(); ?>
<div class="tr-layout" id="primary-content">
  <main>
    <div class="tr-panel tr-post">
      <h1><?php esc_html_e('404: Page Not Found','trashchurch-retro-199x'); ?></h1>
      <p><?php esc_html_e('The page evaporated into the dial-up ether. Try a search:','trashchurch-retro-199x'); ?></p>
      <?php get_search_form(); ?>
    </div>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>