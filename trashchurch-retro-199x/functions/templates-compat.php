<?php
/**
 * Template compatibility and small presentational tweaks.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Hide the archive title on category archives so the page shows just the list of posts.
 */
add_filter( 'get_the_archive_title', function( $title ) {
    if ( is_category() ) {
        return '';
    }
    return $title;
}, 10 );
