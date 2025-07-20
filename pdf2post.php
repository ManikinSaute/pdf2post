<?php
/*
Plugin Name: pdf2post
Description: added a settings page
Version: 1.2
Author: Thomas Parsons
Requires at least: 6.7
Tested up to: 6.7
Requires PHP: 8.2
Author URI: https://github.com/ManikinSaute/PDF2Post
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( file_exists( __DIR__ . '/settings.php' ) ) {
    require_once __DIR__ . '/settings.php';
}

// Register Custom Post Types
function pdf2post_register_cpts() {
    $cpts = [
        'import' => ['singular' => 'Import', 'plural' => 'Imports'],
        'markdown' => ['singular' => 'MD Post', 'plural' => 'MD Posts'],
        'gutenberg' => ['singular' => 'GB Post', 'plural' => 'GB Posts'],
    ];

    foreach ($cpts as $slug => $labels) {
        register_post_type($slug, [
            'labels' => [
                'name' => $labels['plural'],
                'singular_name' => $labels['singular'],
            ],
            'public' => false,
            'show_ui' => true,
            'has_archive' => false,
            'menu_position' => 20,
            'supports' => ['title', 'editor', 'custom-fields'],
        ]);
    }
}
add_action('init', 'pdf2post_register_cpts');

// Add admin page under Appearance → Tools
function pdf2post_add_admin_page() {
    add_submenu_page(
		'tools.php',   // add to the tools area
        'pdf2post',             // Page title
        'pdf2post',             // Menu title
        'manage_options',         // Capability
        'pdf2post',             // Menu slug
        'pdf2post_render_page'// Callback function
    );
}
add_action('admin_menu', 'pdf2post_add_admin_page');

// Render admin page content
function pdf2post_render_page() {
    ?>
    <div class="wrap">
        <h1>Import PDF</h1>
<p>Add a URL to the full path of a PDF file, when you click submit your PDF will be coppied to the WordPress media libary and you will be shown some basic information about your file and be given the option to create an Import post in the Import Custom Post Type. 
        <!-- Step 1: URL form -->
        <form method="post">
            <?php wp_nonce_field( 'pdf2post_upload', 'pdf2post_nonce' ); ?>
            <input type="url" name="pdf_url" placeholder="Enter PDF URL" style="width:400px;" required />
            <input type="submit" name="pdf_url_submit" class="button button-primary" value="Upload PDF" />
        </form>

    <?php
    // Handle URL submission
    if ( isset( $_POST['pdf_url_submit'] ) && wp_verify_nonce( $_POST['pdf2post_nonce'], 'pdf2post_upload' ) ) {

        $pdf_url = esc_url_raw( $_POST['pdf_url'] );

        // WP includes for download & media handling
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Download to temp
        $tmp_file = download_url( $pdf_url );
        if ( is_wp_error( $tmp_file ) ) {
            echo '<p style="color:red;">Error downloading file: ' . esc_html( $tmp_file->get_error_message() ) . '</p>';
        } else {
            // Prepare for sideload
            $file_array = [
                'name'     => basename( $pdf_url ),
                'tmp_name' => $tmp_file,
            ];
            $attach_id = media_handle_sideload( $file_array, 0 );
            if ( is_wp_error( $attach_id ) ) {
                @unlink( $file_array['tmp_name'] );
                echo '<p style="color:red;">Upload error: ' . esc_html( $attach_id->get_error_message() ) . '</p>';
            } else {
                $attach_url  = wp_get_attachment_url( $attach_id );
                ?>
                <div style="margin-top:20px; padding:15px; background:#f9f9f9; border:1px solid #ddd;">
                    <h2>PDF Uploaded</h2>
                    <p><strong>Original filename:</strong> <?php echo esc_html( basename( $pdf_url ) ); ?></p>
                    <p><strong>Original URL:</strong> <a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank"><?php echo esc_html( $pdf_url ); ?></a></p>
                    <p><strong>New Media URL:</strong> <a href="<?php echo esc_url( $attach_url ); ?>" target="_blank"><?php echo esc_html( $attach_url ); ?></a></p>
                </div>
                <h2> Import Data</h2>
				<p>Clicking this button will create a post in the Import Custom Post type, and it will save some data to post meta.</p>
                <!-- Step 2: Import Post button + hidden data -->
                <form method="post" style="margin-top:20px;">
                    <?php wp_nonce_field( 'pdf2post_import', 'pdf2post_import_nonce' ); ?>
                    <input type="hidden" name="pdf_attachment_id" value="<?php echo esc_attr( $attach_id ); ?>" />
                    <input type="hidden" name="pdf_original_url" value="<?php echo esc_url( $pdf_url ); ?>" />
                    <input type="hidden" name="pdf_new_url" value="<?php echo esc_url( $attach_url ); ?>" />
                    <input type="submit" name="import_post_submit" class="button button-secondary" value="Import Post" />
                </form>
                <?php
            }
        }
    }

    // Handle the Import Post button
    if ( isset( $_POST['import_post_submit'] ) && wp_verify_nonce( $_POST['pdf2post_import_nonce'], 'pdf2post_import' ) ) {
        $attach_id    = intval( $_POST['pdf_attachment_id'] );
        $original_url = esc_url_raw( $_POST['pdf_original_url'] );
        $new_url      = esc_url_raw( $_POST['pdf_new_url'] );
        $file_path    = get_attached_file( $attach_id );
        $file_name    = basename( $original_url );

        // Create a new import‐type post
        $post_data = [
            'post_title'   => $file_name,
            'post_content' => 'In the future we will move in the actual content from the PDF but will will need to run the OCR type tools first',
            'post_status'  => 'publish',
            'post_type'    => 'import',
        ];
        $post_id = wp_insert_post( $post_data );

        if ( ! is_wp_error( $post_id ) ) {
            // Save all the meta
            update_post_meta( $post_id, 'p2p_original_file_path', $original_url );
            update_post_meta( $post_id, 'p2p_new_file_url',       $new_url );
            update_post_meta( $post_id, 'p2p_file_path',          $file_path );
            update_post_meta( $post_id, 'p2p_attachment_id',      $attach_id );
            echo '<p style="color:green; margin-top:15px;">Import post created! (Post ID: ' . esc_html( $post_id ) . ')</p>';
        } else {
            echo '<p style="color:red;">Error creating import post: ' . esc_html( $post_id->get_error_message() ) . '</p>';
        }
    }

    echo '</div>';
}


// Show an admin notice with the current WP_POST_REVISIONS setting
function pdf2post_check_revisions_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Grab the constant
    $revisions = WP_POST_REVISIONS;

    // Normalize for display
    if ( $revisions === false ) {
        $display = 'disabled';
    } elseif ( is_int( $revisions ) ) {
        $display = $revisions;
    } else {
        $display = 'default';
    }

    // Output the notice
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p><strong>pdf2post:</strong> Post revisions are currently <em>' . esc_html( $display ) . '</em>.</p>';
    echo '</div>';
}
add_action( 'admin_notices', 'pdf2post_check_revisions_notice' );

