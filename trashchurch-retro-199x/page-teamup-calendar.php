<?php
/**
 * Template Name: Teamup Calendar
 * Description: Full-width Teamup calendar page without title.
 *
 * @package TrashChurch_Retro_199X
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="primary" class="site-main retro-container">
  <div class="page-content">
    <?php
    while ( have_posts() ) :
      the_post();
      the_content();
    endwhile;

    echo do_shortcode( '[retro_calendar]' );
    ?>
  </div>
</main>
<?php get_footer(); ?>
