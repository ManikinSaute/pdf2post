<?php
/*
Plugin Name: pdf2p2
Description: added a settings page
Version: 1.3
Author: Thomas Parsons
Requires at least: 6.7
Tested up to: 6.7
Requires PHP: 8.2
Author URI: https://github.com/ManikinSaute/pdf2p2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Include settings page if present
if ( file_exists( __DIR__ . '/settings.php' ) ) {
    require_once __DIR__ . '/settings.php';
}

// Register Custom Post Types
function pdf2p2_register_cpts() {
    $cpts = [
        'import'    => ['singular' => 'Import',   'plural' => 'Imports'],
        'markdown'  => ['singular' => 'MD Post',   'plural' => 'MD Posts'],
        'gutenberg' => ['singular' => 'GB Post',   'plural' => 'GB Posts'],
    ];

    foreach ( $cpts as $slug => $labels ) {
        register_post_type( $slug, [
            'labels'      => [
                'name'          => $labels['plural'],
                'singular_name'=> $labels['singular'],
            ],
            'public'      => false,
            'show_ui'     => true,
            'has_archive' => false,
            'menu_position'=> 20,
            'supports'    => ['title', 'editor', 'custom-fields'],
        ]);
    }
}
add_action( 'init', 'pdf2p2_register_cpts' );

// Add admin page under Tools
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

// Render admin page content
function pdf2p2_render_page() {
    ?>
    <div class="wrap">
        <h1>Import PDF</h1>
        <p>Add a URL to the full path of a PDF file, then click Submit. Your PDF will be copied to the Media Library, basic info will be shown (including SHA‑256 hash), and you can create an Import post.</p>
        <form method="post">
            <?php wp_nonce_field( 'pdf2p2_upload', 'pdf2p2_nonce' ); ?>
            <input type="url" name="pdf_url" placeholder="Enter PDF URL" style="width:400px;" required />
            <input type="submit" name="pdf_url_submit" class="button button-primary" value="Upload PDF" />
        </form>
    <?php

    if ( isset( $_POST['pdf_url_submit'] ) && wp_verify_nonce( $_POST['pdf2p2_nonce'], 'pdf2p2_upload' ) ) {
        $pdf_url    = esc_url_raw( $_POST['pdf_url'] );
        $file_name  = basename( $pdf_url );
        $force      = ! empty( $_POST['force_import'] );

        // Duplicate check by URL or file name
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

            // Continue Upload form
            echo '<form method="post" style="display:inline-block; margin-right:1em;">';
            wp_nonce_field( 'pdf2p2_upload', 'pdf2p2_nonce' );
            echo '<input type="hidden" name="pdf_url" value="' . esc_url( $pdf_url ) . '">';
            echo '<input type="hidden" name="force_import" value="1">';
            echo '<button type="submit" name="pdf_url_submit" class="button button-primary">'
               . esc_html__( 'Continue Upload', 'pdf2p2' ) . '</button>';
            echo '</form>';

            // Cancel link
            echo '<a href="' . esc_url( admin_url('tools.php?page=pdf2p2') ) . '" class="button">'
               . esc_html__( 'Cancel', 'pdf2p2' ) . '</a>';

            echo '</div>';
            return;
        }

        // Download & side-load includes
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
                    <p><strong>Original URL:</strong> <a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank"><?php echo esc_html( $pdf_url ); ?></a></p>
                    <p><strong>New Media URL:</strong> <a href="<?php echo esc_url( $attach_url ); ?>" target="_blank"><?php echo esc_html( $attach_url ); ?></a></p>
                    <p><strong>SHA-256 Hash:</strong> <?php echo esc_html( $file_hash ); ?></p>
                </div>
                <h2>Import Data</h2>
                <p>Clicking this button will create a post in the Import Custom Post type, saving file info to post meta.</p>
                <form method="post" style="margin-top:20px;">
                    <?php wp_nonce_field( 'pdf2p2_import', 'pdf2p2_import_nonce' ); ?>
                    <input type="hidden" name="pdf_attachment_id" value="<?php echo esc_attr( $attach_id ); ?>" />
                    <input type="hidden" name="pdf_original_url"    value="<?php echo esc_url( $pdf_url ); ?>" />
                    <input type="hidden" name="pdf_new_url"         value="<?php echo esc_url( $attach_url ); ?>" />
                    <input type="hidden" name="pdf_file_hash"       value="<?php echo esc_attr( $file_hash ); ?>" />
                    <input type="hidden" name="pdf_file_name"       value="<?php echo esc_attr( $file_name ); ?>" />
                    <input type="submit" name="import_post_submit" class="button button-secondary" value="Import Post" />
                </form>
                <?php
            }
        }
    }

    // Handle the Import Post submission
    if ( isset( $_POST['import_post_submit'] ) && wp_verify_nonce( $_POST['pdf2p2_import_nonce'], 'pdf2p2_import' ) ) {
        $attach_id    = intval( $_POST['pdf_attachment_id'] );
        $original_url = esc_url_raw( $_POST['pdf_original_url'] );
        $new_url      = esc_url_raw( $_POST['pdf_new_url'] );
        $file_path    = get_attached_file( $attach_id );
        $file_name    = sanitize_text_field( $_POST['pdf_file_name'] );
        $file_hash    = sanitize_text_field( $_POST['pdf_file_hash'] );

        // Insert the import post
        $post_data = [
            'post_title'   => $file_name,
            'post_content' => 'In the future we will move in the actual content from the PDF but will will need to run the OCR type tools first',
            'post_status'  => 'publish',
            'post_type'    => 'import',
        ];
        $post_id = wp_insert_post( $post_data );

        if ( ! is_wp_error( $post_id ) ) {
            // Save all meta including hash & file name
            update_post_meta( $post_id, 'pdf2p2_original_file_path', $original_url );
            update_post_meta( $post_id, 'pdf2p2_new_file_url',      $new_url );
            update_post_meta( $post_id, 'pdf2p2_file_path',         $file_path );
            update_post_meta( $post_id, 'pdf2p2_attachment_id',     $attach_id );
            update_post_meta( $post_id, 'pdf2p2_file_hash',         $file_hash );
            update_post_meta( $post_id, 'pdf2p2_file_name',         $file_name );

            echo '<p style="color:green; margin-top:15px;">Import post created! (Post ID: ' . esc_html( $post_id ) . ')</p>';
        } else {
            echo '<p style="color:red;">Error creating import post: ' . esc_html( $post_id->get_error_message() ) . '</p>';
        }
    }

    echo '</div>';
}

// Admin notice for current WP_POST_REVISIONS setting
function pdf2p2_check_revisions_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $revisions = WP_POST_REVISIONS;
    if ( $revisions === false ) {
        $display = 'disabled';
    } elseif ( is_int( $revisions ) ) {
        $display = $revisions;
    } else {
        $display = 'default';
    }
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>pdf2p2:</strong> Post revisions are currently <em>' . esc_html( $display ) . '</em>.</p>';
    echo '</div>';
}
add_action( 'admin_notices', 'pdf2p2_check_revisions_notice' );
