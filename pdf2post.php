<?php
/*
Plugin Name: pdf2post
Description: Registers custom post types for Import, Markdown, and Gutenberg content, and adds an Import PDF admin page.
Version: 1.1
Author: Thomas Parsons
Requires at least: 6.7
Tested up to: 6.7
Requires PHP: 8.2
Author URI: https://github.com/ManikinSaute/PDF2Post
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Register Custom Post Types
function pdf2post_register_cpts() {
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
            'supports'     => ['title','editor','custom-fields'],
        ] );
    }
}
add_action( 'init', 'pdf2post_register_cpts' );

// Add admin page under Tools
function pdf2post_add_admin_page() {
    add_submenu_page(
        'tools.php',
        'pdf2post',
        'pdf2post',
        'manage_options',
        'pdf2post',
        'pdf2post_render_page'
    );
}
add_action( 'admin_menu', 'pdf2post_add_admin_page' );

// Render admin page content
function pdf2post_render_page() {
    ?>
    <div class="wrap">
        <h1>Import PDF</h1>

        <!-- Step 1: URL form -->
        <form method="post">
            <?php wp_nonce_field( 'pdf2post_upload', 'pdf2post_nonce' ); ?>
            <input type="url" name="pdf_url" placeholder="Enter PDF URL" style="width:400px;" required />
            <input type="submit" name="pdf_url_submit" class="button button-primary" value="Upload PDF" />
        </form>

    <?php
    // Handle URL submission
    if ( isset( $_POST['pdf_url_submit'] ) && wp_verify_nonce( $_POST['pdf2post_nonce'], 'pdf2post_upload' ) ) {

        // Sanitize and fetch URL
        $pdf_url = esc_url_raw( $_POST['pdf_url'] );
        echo '<h2>Processing…</h2>';

        // Include required WP files
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Download to temp
        $tmp_file = download_url( $pdf_url );
        if ( is_wp_error( $tmp_file ) ) {
            echo '<p style="color:red;">Error downloading file: ' . esc_html( $tmp_file->get_error_message() ) . '</p>';
        } else {
            // Prepare array similar to $_FILES
            $file_array = [
                'name'     => basename( $pdf_url ),
                'tmp_name' => $tmp_file,
            ];
            // Sideload into Media Library (attach to no post)
            $attach_id = media_handle_sideload( $file_array, 0 );
            // On error, cleanup
            if ( is_wp_error( $attach_id ) ) {
                @unlink( $file_array['tmp_name'] );
                echo '<p style="color:red;">Upload error: ' . esc_html( $attach_id->get_error_message() ) . '</p>';
            } else {
                // Success! Get the new URL
                $attach_url = wp_get_attachment_url( $attach_id );
                ?>
                <div style="margin-top:20px; padding:15px; background:#f9f9f9; border:1px solid #ddd;">
                    <h2>PDF Uploaded</h2>
                    <p><strong>Original file name:</strong> <?php echo esc_html( basename( $pdf_url ) ); ?></p>
                    <p><strong>Original URL:</strong> <a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank"><?php echo esc_html( $pdf_url ); ?></a></p>
                    <p><strong>New Media URL:</strong> <a href="<?php echo esc_url( $attach_url ); ?>" target="_blank"><?php echo esc_html( $attach_url ); ?></a></p>
                </div>

                <!-- Step 2: Import Post button -->
                <form method="post" style="margin-top:20px;">
                    <?php wp_nonce_field( 'pdf2post_import', 'pdf2post_import_nonce' ); ?>
                    <input type="hidden" name="pdf_attachment_id" value="<?php echo esc_attr( $attach_id ); ?>" />
                    <input type="submit" name="import_post_submit" class="button button-secondary" value="Import Post" />
                </form>
                <?php
            }
        }
    }

    // (Optional) Handle the Import Post button here
    if ( isset( $_POST['import_post_submit'] ) && wp_verify_nonce( $_POST['pdf2post_import_nonce'], 'pdf2post_import' ) ) {
        $attach_id = intval( $_POST['pdf_attachment_id'] );
        // TODO: your import logic goes here.
        echo '<p style="color:green; margin-top:15px;">Import Post button clicked. Attachment ID is ' . $attach_id . '.</p>';
    }

    echo '</div>';
}
