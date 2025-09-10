<form role="search" method="get" class="tr-search-form" action="<?php echo esc_url( home_url('/') ); ?>">
  <label class="tr-sr" for="s"><?php _e('Search for:','trashchurch-retro-199x'); ?></label>
  <input type="search" id="s" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e('Search...','trashchurch-retro-199x'); ?>">
  <button type="submit"><?php esc_html_e('Go','trashchurch-retro-199x'); ?></button>
</form>