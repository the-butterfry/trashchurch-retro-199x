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
    ?>
    <!-- Calendar framed to match site piping and given a gap under the header.
         The body class for this page template will be:
         body.page-template-page-teamup-calender
         Target that class in your stylesheet if you want to change the spacing. -->
    <div class="tr-teamup tr-pipe-frame">
      <?php echo do_shortcode( '[retro_calendar]' ); ?>
    </div>
  </div>
</main>
<?php get_footer(); ?>
