<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function tr199x_get_playlist_id() {
    $p = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'playlist' ) : null;
    return $p ? (int) $p->ID : 0;
}

/* Exclude /playlist from site search results */
add_action( 'pre_get_posts', function( WP_Query $q ) {
    if ( is_admin() || ! $q->is_search() ) return;
    $pid = tr199x_get_playlist_id();
    if ( ! $pid ) return;
    $exclude = (array) $q->get( 'post__not_in', array() );
    $exclude[] = $pid;
    $q->set( 'post__not_in', array_unique( array_map( 'intval', $exclude ) ) );
} );

/* Add noindex to /playlist */
add_action( 'wp_head', function() {
    if ( is_page( 'playlist' ) ) {
        echo '<meta name="robots" content="noindex,follow" />' . "\n";
    }
}, 5 );

/* Exclude /playlist from core XML sitemaps */
add_filter( 'wp_sitemaps_posts_query_args', function( $args, $post_type ) {
    if ( $post_type !== 'page' ) return $args;
    $pid = tr199x_get_playlist_id();
    if ( ! $pid ) return $args;
    $args['post__not_in'] = array_merge( $args['post__not_in'] ?? array(), array( $pid ) );
    return $args;
}, 10, 2 );
