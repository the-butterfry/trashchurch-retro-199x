<?php
/**
 * Template Name: Teamup Calendar
 * Description: Full-width Teamup calendar page without title and without sidebar.
 *
 * Full-width variant that adds a unique class so layout/CSS can target it reliably.
 *
 * @package TrashChurch_Retro_199X
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>

<main id="primary" class="site-main retro-container teamup-fullwidth">
	<?php
	while ( have_posts() ) :
		the_post(); ?>
		<article <?php post_class( 'tr-panel tr-post' ); ?>>
			<div class="tr-content-body">
				<?php the_content(); ?>
			</div>

			<!-- Calendar framed to match site piping. Full-width because there's no sidebar wrapper. -->
			<div class="tr-teamup tr-pipe-frame" aria-hidden="false">
				<?php echo do_shortcode( '[retro_calendar]' ); ?>
			</div>

			<?php
			if ( function_exists( 'tr199x_post_hits' ) && get_theme_mod( 'tr199x_enable_hit_counter', true ) ) {
				tr199x_post_hits();
			}
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</article>
	<?php endwhile; ?>
</main>

<?php get_footer(); ?>
