<?php
/**
 * Template Name: User Sidebar Page
 * Template Post Type: page
 * Description: Sidebared page without a printed title. Only renders the page’s own content (blocks/shortcodes).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>
<div class="tr-layout">
  <main id="primary" class="tr-panel tr-post">
    <?php
    while ( have_posts() ) :
      the_post();

      // Intentionally DO NOT print the page title.

      // Render only the editor content (blocks + shortcodes).
      the_content();

      // Optional: keep comments disabled by default for a "blank" feel.
      // if ( comments_open() || get_comments_number() ) comments_template();

    endwhile;
    ?>
  </main>

  <?php get_sidebar(); ?>
</div>
<?php
get_footer();
