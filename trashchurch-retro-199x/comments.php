<?php
if ( post_password_required() ) return;
?>
<div id="comments">
<?php if ( have_comments() ) : ?>
  <h3 style="margin:0 0 20px;font-size:24px;font-family:var(--tr-mono);text-shadow:0 0 8px var(--tr-border-alt);">
    <?php
      printf(
        _n('%s Comment','%s Comments', get_comments_number(),'trashchurch-retro-199x'),
        number_format_i18n( get_comments_number() )
      );
    ?>
  </h3>
  <ol class="comment-list" style="list-style:none;margin:0;padding:0;">
    <?php
      wp_list_comments(array(
        'style'=>'ol',
        'avatar_size'=>48,
        'callback'=>function($comment,$args,$depth){
          $tag = ( 'div' === $args['style'] ) ? 'div':'li'; ?>
          <<?php echo $tag; ?> <?php comment_class('tr-comment'); ?> id="comment-<?php comment_ID(); ?>">
            <div class="tr-comment-meta">
              <?php echo get_comment_author_link(); ?> ·
              <?php printf('%1$s %2$s', get_comment_date(), get_comment_time()); ?>
              <?php edit_comment_link(' · Edit'); ?>
            </div>
            <div class="comment-content">
              <?php if ( '0' == $comment->comment_approved ) : ?>
                <em><?php esc_html_e('Awaiting moderation.','trashchurch-retro-199x'); ?></em><br>
              <?php endif; ?>
              <?php comment_text(); ?>
            </div>
            <div style="margin-top:6px;">
              <?php comment_reply_link( array_merge( $args, array(
                'reply_text'=>__('Reply','trashchurch-retro-199x'),
                'depth'=>$depth,
                'max_depth'=>$args['max_depth']
              ) ) ); ?>
            </div>
          </<?php echo $tag; ?>>
        <?php }
      ));
    ?>
  </ol>
  <?php if ( get_comment_pages_count() > 1 && get_option('page_comments') ): ?>
    <nav class="tr-pagination" aria-label="<?php esc_attr_e('Comment navigation','trashchurch-retro-199x'); ?>">
      <?php paginate_comments_links(); ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php
if ( ! comments_open() && get_comments_number() ) {
  echo '<p><em>'.esc_html__('Comments are closed.','trashchurch-retro-199x').'</em></p>';
}
comment_form(array(
  'title_reply_before'=>'<h3 style="margin:40px 0 10px;font-size:20px;font-weight:700;font-family:var(--tr-mono);">',
  'title_reply_after'=>'</h3>',
  'comment_notes_before'=>'',
  'comment_notes_after'=>'',
));
?>
</div>