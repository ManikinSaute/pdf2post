<?php
/*
Plugin Name: pdf2p2
Description: PDF import with OCR settings + Status taxonomy + Admin log viewer
Version:     1.4
Author:      Thomas Parsons
Requires at least: 6.7
Tested up to:      6.7
Requires PHP:      8.2
Author URI:  https://github.com/ManikinSaute/pdf2p2
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// 0) Always load our log helper first (so pdf2p2_log() is available everywhere)
if ( file_exists( __DIR__ . '/logs.php' ) ) {
    require_once __DIR__ . '/logs.php';
}

// 1) Load settings page if present
if ( file_exists( __DIR__ . '/settings.php' ) ) {
    require_once __DIR__ . '/settings.php';
}

// Optional test log (remove or comment out once confirmed working)
// pdf2p2_log( 'Plugin pdf2p2 initialized.', 'INFO' );

// 2) Register Custom Post Types
function pdf2p2_register_cpts() {
    $cpts = [
        'import'    => ['singular' => 'Import',    'plural' => 'Imports'],
        'markdown'  => ['singular' => 'MD Post',   'plural' => 'MD Posts'],
        'gutenberg' => ['singular' => 'GB Post',   'plural' => 'GB Posts'],
    ];
    foreach ( $cpts as $slug => $labels ) {
        register_post_type( $slug, [
            'labels'       => [
                'name'          => $labels['plural'],
                'singular_name' => $labels['singular'],
            ],
            'public'       => false,
            'show_ui'      => true,
            'has_archive'  => false,
            'menu_position'=> 20,
            'supports'     => [ 'title', 'editor', 'custom-fields' ],
        ] );
    }
}
add_action( 'init', 'pdf2p2_register_cpts' );

// 3) Register “Status” taxonomy + auto-create terms
function pdf2p2_register_status_taxonomy() {
    $tax   = 'status';
    $cpts  = [ 'import', 'markdown', 'gutenberg' ];
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
        'hierarchical'      => false,
        'meta_box_cb'       => 'pdf2p2_status_meta_box_cb',
        'rewrite'           => false,
    ] );

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
add_action( 'init', 'pdf2p2_register_status_taxonomy' );

// Meta‑box callback: render “Status” as radio buttons
function pdf2p2_status_meta_box_cb( $post ) {
    $tax     = 'status';
    $terms   = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] );
    $current = wp_get_object_terms( $post->ID, $tax, [ 'fields' => 'slugs' ] );
    $current = $current ? $current[0] : '';
    echo '<div>';
    foreach ( $terms as $term ) {
        printf(
            '<label style="display:block; margin-bottom:4px;">
               <input type="radio" name="%1$s[]" value="%2$s" %3$s> %4$s
             </label>',
            esc_attr( $tax ),
            esc_attr( $term->slug ),
            checked( $current, $term->slug, false ),
            esc_html( $term->name )
        );
    }
    echo '</div>';
}

// Show “Status” in the CPT list tables
add_action( 'admin_init', function() {
    $post_types = [ 'import', 'markdown', 'gutenberg' ];
    foreach ( $post_types as $pt ) {
        add_filter( "manage_{$pt}_posts_columns", function( $cols ) {
            $cols['status'] = __( 'Status', 'pdf2p2' );
            return $cols;
        } );
        add_action( "manage_{$pt}_posts_custom_column", function( $column, $post_id ) {
            if ( 'status' !== $column ) {
                return;
            }
            $terms = get_the_terms( $post_id, 'status' );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
            } else {
                echo '—';
            }
        }, 10, 2 );
    }
} );

// Save the “Status” selection on post save
add_action( 'save_post', 'pdf2p2_save_status_taxonomy', 10, 2 );
function pdf2p2_save_status_taxonomy( $post_id, $post ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! in_array( $post->post_type, [ 'import', 'markdown', 'gutenberg' ], true ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( ! empty( $_POST['status'] ) && is_array( $_POST['status'] ) ) {
        $term = sanitize_text_field( wp_unslash( $_POST['status'][0] ] ) );
        wp_set_object_terms( $post_id, $term, 'status', false );
    } else {
        wp_set_object_terms( $post_id, [], 'status', false );
    }
}

// 5) Add “pdf2p2” page under Tools
function pdf2p2_add_admin_page() {
    add_submenu_page(
        'tools.php',
        'pdf2p2',
        'pdf2p2',
        'manage_options',
        'pdf2p2',
        'pdf2p2_render_page'
    );
}
add_action( 'admin_menu', 'pdf2p2_add_admin_page' );

// 6) Render Tools → pdf2p2 page (unchanged from before)
function pdf2p2_render_page() {
    // ... your existing import form + handlers ...
}

// 7) Admin notice: current WP_POST_REVISIONS
function pdf2p2_check_revisions_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $rev = WP_POST_REVISIONS;
    $display = $rev === false ? 'disabled' : ( is_int( $rev ) ? $rev : 'default' );
    echo '<div class="notice notice-info is-dismissible">'
       . '<p><strong>pdf2p2:</strong> Post revisions are currently <em>' . esc_html( $display ) . '</em>.</p>'
       . '</div>';
}
add_action( 'admin_notices', 'pdf2p2_check_revisions_notice' );
