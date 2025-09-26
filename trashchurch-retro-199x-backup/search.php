<?php get_header(); ?>
<div class="tr-layout" id="primary-content">
  <main>
    <div class="tr-panel tr-post">
      <h1><?php printf( esc_html__('Search: %s','trashchurch-retro-199x'), esc_html( get_search_query() ) ); ?></h1>
    </div>
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <article <?php post_class('tr-panel tr-post'); ?>>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="tr-meta"><?php echo get_the_date(); ?> · <?php the_author(); ?></div>
        <div><?php the_excerpt(); ?></div>
        <a class="tr-read-more" href="<?php the_permalink(); ?>"><?php esc_html_e('View','trashchurch-retro-199x'); ?> »</a>
      </article>
    <?php endwhile;
      echo '<div class="tr-pagination">';
      the_posts_pagination();
      echo '</div>';
    else: ?>
      <div class="tr-panel"><p><?php esc_html_e('No results. Try a different keyword.','trashchurch-retro-199x'); ?></p></div>
    <?php endif; ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>