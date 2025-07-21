<?php
/*
Plugin Name: pdf2p2
Description: PDF import with OCR settings + Status taxonomy
Version:     1.4
Author:      Thomas Parsons
Requires at least: 6.7
Tested up to:      6.7
Requires PHP:      8.2
Author URI:  https://github.com/ManikinSaute/pdf2p2
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Load settings page if present
if ( file_exists( __DIR__ . '/settings.php' ) ) {
    require_once __DIR__ . '/settings.php';
}
if ( file_exists( __DIR__ . '/logs.php' ) ) {
    require_once __DIR__ . '/logs.php';
}


// Register Custom Post Types
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
        ]);
    }
}
add_action( 'init', 'pdf2p2_register_cpts' );

// Register single‑select “Status” taxonomy + auto‑create terms
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
    ]);

    // Ensure our four terms exist
    $terms = [
        'unprocessed'    => 'Unprocessed',
        'ocr_processed'  => 'OCR Processed',
        'human_verified' => 'Human Verified',
        'staff_verified'       => 'Staff Verified',
    ];
    foreach ( $terms as $slug => $name ) {
        if ( ! term_exists( $slug, $tax ) ) {
            wp_insert_term( $name, $tax, [ 'slug' => $slug ] );
        }
    }
}
add_action( 'init', 'pdf2p2_register_status_taxonomy' );

//  Meta‑box callback: render “Status” as radio buttons
function pdf2p2_status_meta_box_cb( $post, $box ) {
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
        // register the column header
        add_filter( "manage_{$pt}_posts_columns", function( $cols ) {
            $cols['status'] = __( 'Status', 'pdf2p2' );
            return $cols;
        } );
        //  fill in each row
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

// Save the “Status” selection on post save
add_action( 'save_post', 'pdf2p2_save_status_taxonomy', 10, 2 );
function pdf2p2_save_status_taxonomy( $post_id, $post ) {
    // Skip autosaves/revisions
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    // Only for our CPTs
    if ( ! in_array( $post->post_type, [ 'import', 'markdown', 'gutenberg' ], true ) ) {
        return;
    }
    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( ! empty( $_POST['status'] ) && is_array( $_POST['status'] ) ) {
        $term = sanitize_text_field( wp_unslash( $_POST['status'][0] ) );
        wp_set_object_terms( $post_id, $term, 'status', false );
    } else {
        // Clear if nothing selected
        wp_set_object_terms( $post_id, [], 'status', false );
    }
}

// Add “pdf2p2” page under Tools
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

//  Render Tools pdf2p2 page
function pdf2p2_render_page() {
    ?>
    <div class="wrap">
      <h1>Import PDF</h1>
      <p>Enter a PDF URL to sideload into the Media Library, compute its SHA‑256 hash, and then create an “Import” post.</p>
      <form method="post">
        <?php wp_nonce_field( 'pdf2p2_upload', 'pdf2p2_nonce' ); ?>
        <input type="url" name="pdf_url" placeholder="Enter PDF URL" style="width:400px;" required>
        <input type="submit" name="pdf_url_submit" class="button button-primary" value="Upload PDF">
      </form>
    <?php

    // Handle URL submission + duplicate check
    if ( isset( $_POST['pdf_url_submit'] ) && wp_verify_nonce( $_POST['pdf2p2_nonce'], 'pdf2p2_upload' ) ) {
        $pdf_url   = esc_url_raw( $_POST['pdf_url'] );
        $file_name = basename( $pdf_url );
        $force     = ! empty( $_POST['force_import'] );

        // Duplicate check
        $existing = get_posts([
            'post_type'   => 'import',
            'numberposts' => 1,
            'meta_query'  => [
                'relation' => 'OR',
                [ 'key' => 'pdf2p2_original_file_path', 'value' => $pdf_url ],
                [ 'key' => 'pdf2p2_file_name',          'value' => $file_name ],
            ],
        ]);

        if ( $existing && ! $force ) {
            echo '<div class="notice notice-warning"><p>';
            esc_html_e( 'An import already exists with that PDF URL or file name.', 'pdf2p2' );
            echo '</p>';
            // Continue button
            echo '<form method="post" style="display:inline-block; margin-right:1em;">';
            wp_nonce_field( 'pdf2p2_upload', 'pdf2p2_nonce' );
            echo '<input type="hidden" name="pdf_url" value="' . esc_url( $pdf_url ) . '">';
            echo '<input type="hidden" name="force_import" value="1">';
            echo '<button type="submit" name="pdf_url_submit" class="button button-primary">'
               . esc_html__( 'Continue Upload', 'pdf2p2' )
               . '</button>';
            echo '</form>';
            // Cancel link
            echo '<a href="' . esc_url( admin_url('tools.php?page=pdf2p2') ) . '" class="button">'
               . esc_html__( 'Cancel', 'pdf2p2' )
               . '</a>';
            echo '</div>';
            return;
        }

        // Download & sideload
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp_file = download_url( $pdf_url );
        if ( is_wp_error( $tmp_file ) ) {
            echo '<p style="color:red;">Error downloading file: ' . esc_html( $tmp_file->get_error_message() ) . '</p>';
        } else {
            $file_array = [ 'name' => $file_name, 'tmp_name' => $tmp_file ];
            $attach_id  = media_handle_sideload( $file_array, 0 );
            if ( is_wp_error( $attach_id ) ) {
                @unlink( $file_array['tmp_name'] );
                echo '<p style="color:red;">Upload error: ' . esc_html( $attach_id->get_error_message() ) . '</p>';
            } else {
                $file_path  = get_attached_file( $attach_id );
                $file_hash  = hash_file( 'sha256', $file_path );
                $attach_url = wp_get_attachment_url( $attach_id );
                ?>
                <div style="margin-top:20px; padding:15px; background:#f9f9f9; border:1px solid #ddd;">
                  <h2>PDF Uploaded</h2>
                  <p><strong>Original filename:</strong> <?php echo esc_html( $file_name ); ?></p>
                  <p><strong>Original URL:</strong>
                    <a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank">
                      <?php echo esc_html( $pdf_url ); ?>
                    </a>
                  </p>
                  <p><strong>New Media URL:</strong>
                    <a href="<?php echo esc_url( $attach_url ); ?>" target="_blank">
                      <?php echo esc_html( $attach_url ); ?>
                    </a>
                  </p>
                  <p><strong>SHA‑256 Hash:</strong> <?php echo esc_html( $file_hash ); ?></p>
                </div>
                <h2>Import Data</h2>
                <p>Click to create an Import post with this file’s metadata.</p>
                <form method="post" style="margin-top:20px;">
                  <?php wp_nonce_field( 'pdf2p2_import', 'pdf2p2_import_nonce' ); ?>
                  <input type="hidden" name="pdf_attachment_id" value="<?php echo esc_attr( $attach_id ); ?>">
                  <input type="hidden" name="pdf_original_url"    value="<?php echo esc_url( $pdf_url ); ?>">
                  <input type="hidden" name="pdf_new_url"         value="<?php echo esc_url( $attach_url ); ?>">
                  <input type="hidden" name="pdf_file_hash"       value="<?php echo esc_attr( $file_hash ); ?>">
                  <input type="hidden" name="pdf_file_name"       value="<?php echo esc_attr( $file_name ); ?>">
                  <input type="submit" name="import_post_submit"
                         class="button button-secondary" value="Import Post">
                </form>
                <?php
            }
        }
    }

    // Handle the Import Post creation
    if ( isset( $_POST['import_post_submit'] ) 
      && wp_verify_nonce( $_POST['pdf2p2_import_nonce'], 'pdf2p2_import' ) ) {

        $attach_id    = intval( $_POST['pdf_attachment_id'] );
        $original_url = esc_url_raw( $_POST['pdf_original_url'] );
        $new_url      = esc_url_raw( $_POST['pdf_new_url'] );
        $file_path    = get_attached_file( $attach_id );
        $file_name    = sanitize_text_field( $_POST['pdf_file_name'] );
        $file_hash    = sanitize_text_field( $_POST['pdf_file_hash'] );

        $post_id = wp_insert_post([
            'post_title'   => $file_name,
            'post_content' => 'In the future we will move in the actual content from the PDF but will need to run the OCR tools first.',
            'post_status'  => 'publish',
            'post_type'    => 'import',
        ]);

        if ( ! is_wp_error( $post_id ) ) {
            wp_set_object_terms( $post_id, 'unprocessed', 'status', false );
            update_post_meta( $post_id, 'pdf2p2_original_file_path', $original_url );
            update_post_meta( $post_id, 'pdf2p2_new_file_url',      $new_url );
            update_post_meta( $post_id, 'pdf2p2_file_path',         $file_path );
            update_post_meta( $post_id, 'pdf2p2_attachment_id',     $attach_id );
            update_post_meta( $post_id, 'pdf2p2_file_hash',         $file_hash );
            update_post_meta( $post_id, 'pdf2p2_file_name',         $file_name );
            echo '<p style="color:green; margin-top:15px;">Import post created! (Post ID: ' 
               . esc_html( $post_id ) . ')</p>';
        } else {
            echo '<p style="color:red;">Error creating import post: ' 
               . esc_html( $post_id->get_error_message() ) . '</p>';
        }
    }

    echo '</div>';
}

//  Admin notice: current WP_POST_REVISIONS
function pdf2p2_check_revisions_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $rev = WP_POST_REVISIONS;
    if ( $rev === false ) {
        $display = 'disabled';
    } elseif ( is_int( $rev ) ) {
        $display = $rev;
    } else {
        $display = 'default';
    }
    echo '<div class="notice notice-info is-dismissible">'
       . '<p><strong>pdf2p2:</strong> Post revisions are currently <em>' . esc_html( $display ) . '</em>.</p>'
       . '</div>';
}
add_action( 'admin_notices', 'pdf2p2_check_revisions_notice' );
