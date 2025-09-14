<?php
/**
 * Visibility label/badge helper.
 * Appends "Public" or "Members" to post titles on the front-end for posts.
 *
 * Rules:
 * - If a post has category slug "public-posts", label is "Public".
 * - Else if a post has "member-posts", label is "Members".
 * - Else no label.
 * - Logged-out users still only see public posts due to existing visibility rules.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Return visibility slug for a post: 'public', 'members', or '' if neither.
 */
function tr199x_get_visibility_slug( $post = null ) : string {
    $post = get_post( $post );
    if ( ! $post || $post->post_type !== 'post' ) return '';
    if ( has_term( 'public-posts', 'category', $post ) ) {
        return 'public';
    }
    if ( has_term( 'member-posts', 'category', $post ) ) {
        return 'members';
    }
    return '';
}

/**
 * Return visibility label for a post: 'Public', 'Members', or '' if neither.
 */
function tr199x_get_visibility_label( $post = null ) : string {
    $slug = tr199x_get_visibility_slug( $post );
    if ( $slug === 'public' ) return __( 'Public', 'trashchurch-retro-199x' );
    if ( $slug === 'members' ) return __( 'Members', 'trashchurch-retro-199x' );
    return '';
}

/**
 * Render a small badge span for the given post or slug.
 */
function tr199x_render_visibility_badge( $post = null ) : string {
    $slug  = is_string( $post ) ? $post : tr199x_get_visibility_slug( $post );
    if ( ! $slug ) return '';
    $label = tr199x_get_visibility_label( $post );
    return sprintf(
        '<span class="tr-cat-badge tr-cat-badge--%1$s" aria-label="%2$s">%2$s</span>',
        esc_attr( $slug ),
        esc_html( $label )
    );
}

/**
 * Append the badge to post titles in front-end contexts (loops, blocks, single, etc.).
 * Skips admin, feeds, and duplicates.
 */
function tr199x_append_visibility_badge_to_title( $title, $post_id = 0 ) {
    if ( is_admin() || is_feed() ) return $title;

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'post' ) return $title;

    // Avoid double-appending if some markup already present
    if ( strpos( $title, 'tr-cat-badge' ) !== false ) return $title;

    $badge = tr199x_render_visibility_badge( $post );
    if ( ! $badge ) return $title;

    // Append with a space so it sits after the title (inside link in most contexts)
    return $title . ' ' . $badge;
}
add_filter( 'the_title', 'tr199x_append_visibility_badge_to_title', 10, 2 );
