<?php
/**
 * Template Name: Member Services
 * Description: Member area landing page without a printed page title. If the page content is empty, shows a default "Member News" list (category: member-posts).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();
?>

<div class="tr-layout">
  <main id="primary" class="tr-panel tr-post">
    <?php
    while ( have_posts() ) :
      the_post();

      // Intentionally do NOT output the page title here.

      // Detect if the editor has content
      $raw = get_the_content();
      $has_content = strlen( trim( preg_replace( '/\xc2\xa0/', ' ', wp_strip_all_tags( $raw ) ) ) ) > 0;

      if ( $has_content ) {
        the_content();
      } else {
        // Default content block when editor is empty
        if ( ! is_user_logged_in() ) {
          // If this page isn't already protected elsewhere, prompt login
          $login_url = wp_login_url( get_permalink() );
          echo '<p>' . wp_kses_post( sprintf(
            __( 'Please %s to view member content.', 'trashchurch-retro-199x' ),
            '<a href="' . esc_url( $login_url ) . '">' . esc_html__( 'log in', 'trashchurch-retro-199x' ) . '</a>'
          ) ) . '</p>';
        } else {
          echo '<h2 class="tr-section-title">' . esc_html__( 'Member News', 'trashchurch-retro-199x' ) . '</h2>';

          // Query latest posts in the "member-posts" category
          $q = new WP_Query( array(
            'post_type'           => 'post',
            'posts_per_page'      => 5,
            'ignore_sticky_posts' => true,
            'tax_query'           => array(
              array(
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => array( 'member-posts' ),
              ),
            ),
          ) );

          if ( $q->have_posts() ) {
            echo '<div class="tr-member-news">';
            while ( $q->have_posts() ) {
              $q->the_post();

              // Title (allow injected badge HTML from the_title filter)
              $title = get_the_title(); // already filtered by 'the_title'
              $perma = get_permalink();

              echo '<article class="tr-news-item">';
                echo '<h3 class="tr-news-title"><a href="' . esc_url( $perma ) . '">' . wp_kses_post( $title ) . '</a></h3>';
                echo '<div class="tr-news-meta tr-meta">' . esc_html( get_the_date() ) . '</div>';
                echo '<div class="tr-news-excerpt tr-excerpt">' . wp_kses_post( wp_trim_words( get_the_excerpt(), 28, '…' ) ) . '</div>';
              echo '</article>';
            }
            echo '</div>';

            // Removed "More member news" button that linked to the category archive.
          } else {
            echo '<p>' . esc_html__( 'No member news yet. Check back soon!', 'trashchurch-retro-199x' ) . '</p>';
          }
          wp_reset_postdata();
        }
      }

      // Optional: comments off for this landing page by default.
      // if ( comments_open() || get_comments_number() ) comments_template();

    endwhile;
    ?>
  </main>

  <?php get_sidebar(); ?>
</div>

<?php
get_footer();
