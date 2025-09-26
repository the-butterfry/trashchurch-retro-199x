<?php get_header(); ?>
<div class="tr-layout" id="primary-content">
<main>
  <?php
  // Array of available themed 404 partials
  $theme_404s = [
    '404-consider.php',
    '404-hacker.php',
    '404-geocities.php',
    '404-underconstruction.php',
    '404-pixelart.php',
    '404-gifparty.php',
    '404-hitcounter.php',
    '404-webring.php',
    '404-marquee.php',
    '404-cyberspace.php',
    '404-occult.php',
    '404-glitch.php',
  ];    // Choose one at random
    $chosen_404 = $theme_404s[array_rand($theme_404s)];
    // Load the partial (must exist in theme root or a subdir like 'partials/')
    if ( locate_template($chosen_404) ) {
      include(locate_template($chosen_404));
    } else {
      // Fallback to default if not found
    ?>
      <div class="tr-panel tr-post">
        <h1><?php esc_html_e('404: Page Not Found','trashchurch-retro-199x'); ?></h1>
        <p><?php esc_html_e('The page evaporated into the dial-up ether. Try a search:','trashchurch-retro-199x'); ?></p>
        <?php get_search_form(); ?>
      </div>
    <?php } ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>
