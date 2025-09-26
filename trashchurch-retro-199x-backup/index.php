<?php get_header(); ?>
<div class="tr-layout" id="primary-content">
  <main>
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <article <?php post_class('tr-panel tr-post'); ?> id="post-<?php the_ID(); ?>">
        <?php if ( get_theme_mod('tr199x_under_construction', true) && has_tag('under-construction') ) : ?>
          <div class="tr-ribbon"><?php esc_html_e('UNDER CONSTRUCTION','trashchurch-retro-199x'); ?></div>
        <?php endif; ?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="tr-meta">
          <?php echo get_the_date(); ?> · <?php the_author(); ?>
          <?php if ( has_category() ) { echo ' · '; the_category(', '); } ?>
          <?php comments_popup_link(' · 0 comments',' · 1 comment',' · % comments'); ?>
        </div>
        <div class="tr-excerpt">
          <?php the_excerpt(); ?>
        </div>
        <a class="tr-read-more" href="<?php the_permalink(); ?>"><?php esc_html_e('Read Full Post','trashchurch-retro-199x'); ?> »</a>
        <?php if ( has_tag() ) : ?>
          <div class="tr-tags"><?php the_tags('', ' '); ?></div>
        <?php endif; ?>
      </article>
    <?php endwhile; ?>

      <div class="tr-pagination">
        <?php the_posts_pagination(array(
          'mid_size'=>1,
          'prev_text'=>'&laquo;',
          'next_text'=>'&raquo;'
        )); ?>
      </div>

    <?php else: ?>
      <div class="tr-panel">
        <h2><?php esc_html_e('No Posts Yet','trashchurch-retro-199x'); ?></h2>
        <p><?php esc_html_e('Publish something to start your retro shrine.','trashchurch-retro-199x'); ?></p>
      </div>
    <?php endif; ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>