<?php get_header(); ?>
<div class="tr-layout" id="primary-content">
  <main>
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <article <?php post_class('tr-panel tr-post'); ?>>
        <?php if ( get_theme_mod('tr199x_under_construction', true) && has_tag('under-construction') ) : ?>
          <div class="tr-ribbon"><?php esc_html_e('UNDER CONSTRUCTION','trashchurch-retro-199x'); ?></div>
        <?php endif; ?>
        <h1><?php the_title(); ?></h1>
        <div class="tr-meta">
          <?php echo get_the_date(); ?> · <?php the_author(); ?>
          <?php if ( has_category() ) { echo ' · '; the_category(', '); } ?>
          <?php if ( has_tag() ) { echo ' · '; the_tags('', ', '); } ?>
        </div>
        <div class="tr-content-body">
          <?php the_content(); ?>
          <?php wp_link_pages(); ?>
        </div>
        <?php if ( function_exists('tr199x_post_hits') && get_theme_mod('tr199x_enable_hit_counter', true ) ) tr199x_post_hits(); ?>
        <div class="tr-meta" style="margin-top:18px;">
          <?php edit_post_link('[ Edit ]'); ?>
        </div>
        <?php if ( comments_open() || get_comments_number() ) comments_template(); ?>
      </article>
    <?php endwhile; endif; ?>
  </main>
  <?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>