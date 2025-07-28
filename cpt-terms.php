<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1) Register Custom Post Types
 */
function pdf2p2_register_cpts() {
    $cpts = [
        'pdf2p2_import'    => [ 'singular' => 'pdf2p2 Import',  'plural' => 'pdf2p2 Imports' ],
        'pdf2p2_gutenberg' => [ 'singular' => 'pdf2p2 GB Post', 'plural' => 'pdf2p2 GB Posts' ],
    ];

    foreach ( $cpts as $slug => $labels ) {
        $args = [
            'labels'        => [
                'name'          => $labels['plural'],
                'singular_name' => $labels['singular'],
            ],
            // Set 'public' => true for 'pdf2p2_gutenberg' to enable Gutenberg editor
            'public'        => ( $slug === 'pdf2p2_gutenberg' ) ? true : false,
            'show_ui'       => true,
            'has_archive'   => false,
            'menu_position' => 20,
            'supports'      => [ 'title', 'editor', 'custom-fields' ],
            // Explicitly enable REST API for Gutenberg
            'show_in_rest'  => ( $slug === 'pdf2p2_gutenberg' ) ? true : false,
        ];

        register_post_type( $slug, $args );
    }
}
add_action( 'init', 'pdf2p2_register_cpts' );

/**
 * 2) Register “Status” Taxonomy with a <select multiple> meta box
 */
function pdf2p2_register_status_taxonomy() {
    $tax    = 'status';
    $cpts   = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ];
    $labels = [
        'name'          => 'Statuses',
        'singular_name' => 'Status',
        'menu_name'     => 'Status',
        'all_items'     => 'All Statuses',
        'add_new_item'  => 'Add New Status',
        'edit_item'     => 'Edit Status',
    ];

    register_taxonomy( $tax, $cpts, [
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'show_admin_column' => true,
        'hierarchical'      => false,            // non-hierarchical, so no indented checkboxes
        'meta_box_cb'       => 'pdf2p2_status_meta_box_cb',
        'rewrite'           => false,
        'show_in_rest'      => true,
    ] );

    // Create the four terms if they don't exist
    $terms = [
        'unprocessed'    => 'Unprocessed',
        'ocr_processed'  => 'OCR Processed',
        'human_verified' => 'Human Verified',
        'staff_verified' => 'Staff Verified',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $tax ) ) {
            wp_insert_term( $name, $tax, [ 'slug' => $slug ] );
        }
    }
}
add_action( 'init', 'pdf2p2_register_status_taxonomy', 11 );


/**
 * 3) Meta-box callback: renders a <select multiple> for Status
 */
function pdf2p2_status_meta_box_cb( $post, $box ) {
    $tax     = 'status';
    $terms   = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] );
    $current = wp_get_object_terms( $post->ID, $tax, [ 'fields' => 'slugs' ] );

    echo '<select name="status[]" multiple style="width:100%; height: auto;">';
    foreach ( $terms as $term ) {
        $sel = in_array( $term->slug, (array) $current, true ) ? ' selected' : '';
        printf(
            '<option value="%1$s"%2$s>%3$s</option>',
            esc_attr( $term->slug ),
            $sel,
            esc_html( $term->name )
        );
    }
    echo '</select>';
}


/**
 * 4) Save the selected status term(s)
 */
function pdf2p2_save_status_taxonomy( $post_id, $post ) {
    // Bail on autosave/revision
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    // Only our CPTs
    if ( ! in_array( $post->post_type, [ 'pdf2p2_import', 'pdf2p2_gutenberg' ], true ) ) {
        return;
    }
    // Check capability
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( ! empty( $_POST['status'] ) && is_array( $_POST['status'] ) ) {
        // Sanitize & set all selected terms
        $to_set = array_map( 'sanitize_text_field', wp_unslash( $_POST['status'] ) );
        wp_set_object_terms( $post_id, $to_set, 'status', false );
    } else {
        // None selected → clear it
        wp_set_object_terms( $post_id, [], 'status', false );
    }
}
add_action( 'save_post', 'pdf2p2_save_status_taxonomy', 10, 2 );


/**
 * 5) Show “Status” in the admin list table
 */
add_action( 'admin_init', function() {
    $post_types = [ 'pdf2p2_import', 'pdf2p2_gutenberg' ];
    foreach ( $post_types as $pt ) {
        // Column header
        add_filter( "manage_{$pt}_posts_columns", function( $cols ) {
            $cols['status'] = __( 'Status', 'pdf2p2' );
            return $cols;
        } );
        // Column content
        add_action( "manage_{$pt}_posts_custom_column", function( $column, $post_id ) {
            if ( 'status' !== $column ) {
                return;
            }
            $terms = get_the_terms( $post_id, 'status' );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                $names = wp_list_pluck( $terms, 'name' );
                echo esc_html( implode( ', ', $names ) );
            } else {
                echo '—';
            }
        }, 10, 2 );
    }
} );
