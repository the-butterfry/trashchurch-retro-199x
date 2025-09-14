<?php
/**
 * Page Tiles shortcode for linking to selected Pages with thumbnail + synopsis.
 * Usage:
 *  - [tr_page_tiles slugs="about,contact,events" columns="3"]
 *  - [tr_page_tiles ids="42,17,88" columns="2"]
 *  - [tr_page_tiles children_of="current" columns="2"]
 *  - [tr_page_tiles children_of="123" columns="4"]
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Compute a short synopsis for a Page.
 */
function tr199x_page_synopsis( WP_Post $p, int $words = 28 ) : string {
    $excerpt = trim( $p->post_excerpt );
    if ( $excerpt === '' ) {
        $content = get_post_field( 'post_content', $p->ID );
        $excerpt = wp_trim_words( wp_strip_all_tags( $content ), $words, '…' );
    }
    return $excerpt;
}

/**
 * Shortcode renderer.
 */
function tr199x_page_tiles_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'ids'         => '',            // CSV of IDs, preserves order
        'slugs'       => '',            // CSV of slugs, preserves order
        'children_of' => '',            // "current" or a numeric page ID
        'columns'     => '3',           // 2..6
        'order'       => 'include',     // include | title | menu_order
        'order_dir'   => 'ASC',         // ASC | DESC (when order != include)
        'image_size'  => 'medium_large' // thumbnail size
    ), $atts, 'tr_page_tiles' );

    $columns = max( 2, min( 6, (int) $atts['columns'] ) );
    $ids     = array();
    $source  = '';

    if ( $atts['ids'] ) {
        $ids = array_values( array_filter( array_map( 'absint', preg_split( '/\\s*,\\s*/', $atts['ids'] ) ) ) );
        $source = 'ids';
    } elseif ( $atts['slugs'] ) {
        $slugs = array_values( array_filter( array_map( 'sanitize_title', preg_split( '/\\s*,\\s*/', $atts['slugs'] ) ) ) );
        foreach ( $slugs as $slug ) {
            $p = get_page_by_path( $slug );
            if ( $p instanceof WP_Post ) $ids[] = (int) $p->ID;
        }
        $source = 'slugs';
    } elseif ( $atts['children_of'] ) {
        if ( $atts['children_of'] === 'current' ) {
            $parent_id = is_page() ? get_queried_object_id() : 0;
        } else {
            $parent_id = absint( $atts['children_of'] );
        }
        $source = $parent_id ? 'children' : '';
    }

    // Build query
    $args = array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'no_found_rows'  => true,
        'posts_per_page' => -1,
    );

    if ( $source === 'ids' || $source === 'slugs' ) {
        if ( empty( $ids ) ) return ''; // nothing to show
        $args['post__in'] = $ids;
        if ( $atts['order'] === 'include' ) {
            $args['orderby'] = 'post__in';
        } elseif ( $atts['order'] === 'title' ) {
            $args['orderby'] = 'title';
            $args['order']   = ( strtoupper( $atts['order_dir'] ) === 'DESC' ) ? 'DESC' : 'ASC';
        } elseif ( $atts['order'] === 'menu_order' ) {
            $args['orderby'] = array( 'menu_order' => 'ASC', 'title' => 'ASC' );
        }
    } elseif ( $source === 'children' ) {
        $args['post_parent'] = $parent_id;
        // Natural page ordering
        if ( $atts['order'] === 'title' ) {
            $args['orderby'] = 'title';
            $args['order']   = ( strtoupper( $atts['order_dir'] ) === 'DESC' ) ? 'DESC' : 'ASC';
        } else {
            $args['orderby'] = array( 'menu_order' => 'ASC', 'title' => 'ASC' );
        }
    } else {
        return ''; // no valid source specified
    }

    $q = new WP_Query( $args );
    if ( ! $q->have_posts() ) return '';

    $col_class = 'columns-' . $columns;
    $size = sanitize_key( $atts['image_size'] );

    ob_start();
    echo '<div class="tr-page-tiles ' . esc_attr( $col_class ) . '">';
    while ( $q->have_posts() ) {
        $q->the_post();
        $pid   = get_the_ID();
        $url   = get_permalink( $pid );
        $title = get_the_title( $pid );
        $syn   = tr199x_page_synopsis( get_post( $pid ) );
        $thumb = get_the_post_thumbnail( $pid, $size, array( 'loading' => 'lazy', 'decoding' => 'async' ) );

        echo '<a class="tr-tile" href="' . esc_url( $url ) . '">';
          echo '<figure class="tr-tile-media">';
            if ( $thumb ) {
                echo $thumb; // already escaped by WP
            } else {
                // Placeholder with the first letter of the title
                $first = mb_strtoupper( mb_substr( wp_strip_all_tags( $title ), 0, 1 ) );
                echo '<div class="tr-tile-placeholder" aria-hidden="true">' . esc_html( $first ) . '</div>';
            }
          echo '</figure>';
          echo '<div class="tr-tile-body">';
            echo '<h3 class="tr-tile-title">' . esc_html( $title ) . '</h3>';
            if ( $syn ) {
                echo '<div class="tr-tile-excerpt">' . esc_html( $syn ) . '</div>';
            }
          echo '</div>';
        echo '</a>';
    }
    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'tr_page_tiles', 'tr199x_page_tiles_shortcode' );
