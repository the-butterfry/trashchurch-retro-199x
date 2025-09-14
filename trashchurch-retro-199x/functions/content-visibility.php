<?php
// Guard
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Detect "News" context:
 * - Either the pretty permalink /news/ via our rewrite (tr199x_news=1)
 * - Or a static page with the slug 'news'
 */
function tr199x_is_news_context_v11() : bool {
    if ( is_admin() ) return false;
    if ( intval( get_query_var( 'tr199x_news' ) ) === 1 ) return true;
    if ( function_exists( 'is_page' ) && is_page( 'news' ) ) return true;
    return false;
}

/**
 * Category helpers
 */
function tr199x_get_public_cat_id_v11() : int {
    $term = get_category_by_slug( 'public-posts' );
    return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
}
function tr199x_get_member_cat_id_v11() : int {
    $term = get_category_by_slug( 'member-posts' );
    return ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
}

/**
 * NEWS PAGE FIX (safe, scoped):
 * For any post query during the News request:
 * - Logged-in: force category IN [public, members]
 * - Logged-out: force category IN [public] (redundant if the block already filters; harmless)
 *
 * We remove other category query vars to avoid accidental AND logic from block UI.
 * High priority so it wins, but scoped to News only so it doesn't affect the rest of the site.
 */
add_action( 'pre_get_posts', function( WP_Query $q ) {
    if ( is_admin() ) return;
    if ( ! tr199x_is_news_context_v11() ) return;

    // Only affect queries that fetch posts
    $pt = $q->get( 'post_type' );
    if ( is_array( $pt ) ) {
        if ( ! in_array( 'post', $pt, true ) ) return;
    } elseif ( $pt && $pt !== 'post' && $pt !== 'any' ) {
        return;
    }

    $pub = tr199x_get_public_cat_id_v11();
    $mem = tr199x_get_member_cat_id_v11();

    if ( is_user_logged_in() ) {
        // Logged-in: show both (if both exist, otherwise whichever exists)
        $terms = array_values( array_filter( array( $pub, $mem ) ) );
        if ( empty( $terms ) ) return;
        $q->set( 'tax_query', array(
            array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $terms,
                'operator' => 'IN',
            ),
        ) );
    } else {
        // Logged-out: only public
        if ( ! $pub ) return;
        $q->set( 'tax_query', array(
            array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => array( $pub ),
                'operator' => 'IN',
            ),
        ) );
    }

    // Remove other category selectors that can force AND behavior
    foreach ( array( 'cat', 'category__in', 'category__and', 'category_name' ) as $k ) {
        if ( isset( $q->query_vars[ $k ] ) ) unset( $q->query_vars[ $k ] );
    }
}, PHP_INT_MAX - 1 );

/**
 * Ensure core block CSS is present so block List/Grid render correctly.
 * This is safe to call even if already enqueued elsewhere.
 */
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'wp-block-library' );
    wp_enqueue_style( 'wp-block-library-theme' );
}, 5 );

/**
 * Optional: keep unified News URL by redirecting raw category archives.
 */
add_action( 'template_redirect', function() {
    if ( is_category( array( 'public-posts', 'member-posts' ) ) ) {
        // Prefer static page if present
        $page = function_exists( 'get_page_by_path' ) ? get_page_by_path( 'news' ) : null;
        $url  = $page ? get_permalink( $page ) : home_url( '/news/' );
        wp_safe_redirect( $url, 302 );
        exit;
    }
}, 5 );
