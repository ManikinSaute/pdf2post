<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Send a PDF post through Mistral OCR and update its content.
 *
 * @param int $post_id ID of the post with 'pdf2p2_original_file_path' meta.
 * @return true|\WP_Error True on success, WP_Error on failure.
 */
function pdf2p2_send_post_to_mistral_ocr( $post_id ) {
    // 1) Checks
    $api_key = get_option( 'pdf2p2_api_key', '' );
    if ( ! $api_key ) {
        return new WP_Error( 'no_api_key', 'Mistral OCR API key not configured.' );
    }
    $post = get_post( $post_id );
    if ( ! $post ) {
        return new WP_Error( 'invalid_post', 'Invalid post ID.' );
    }
    $file_url = get_post_meta( $post_id, 'pdf2p2_original_file_path', true );
    if ( ! $file_url ) {
        return new WP_Error( 'no_url', 'No original PDF URL found in post meta.' );
    }

    // 2) Call the OCR API
    $payload = [
        'model'                => 'mistral-ocr-latest',
        'document'             => [
            'type'         => 'document_url',
            'document_url' => $file_url,
        ],
        'include_image_base64' => true,
    ];
    $ch = curl_init( 'https://api.mistral.ai/v1/ocr' );
    curl_setopt_array( $ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => wp_json_encode( $payload ),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key,
        ],
    ] );
    $response = curl_exec( $ch );
    $err      = curl_error( $ch );
    curl_close( $ch );

    if ( $err ) {
        return new WP_Error( 'curl_error', $err );
    }

    $data = json_decode( $response, true );
    if ( ! is_array( $data ) || empty( $data['pages'] ) ) {
        return new WP_Error( 'invalid_response', 'Invalid or empty OCR response.' );
    }

    // 3) Build new post content
    $new_content = '';
    foreach ( $data['pages'] as $page ) {
        $idx = isset( $page['index'] ) ? intval( $page['index'] ) + 1 : '';
        if ( $idx !== '' ) {
            $new_content .= "\n\n### Page {$idx}\n\n";
        }
        if ( ! empty( $page['markdown'] ) ) {
            $new_content .= $page['markdown'] . "\n\n";
        }
        if ( ! empty( $page['images'] ) && is_array( $page['images'] ) ) {
            foreach ( $page['images'] as $img ) {
                $raw  = is_array( $img )
                    ? ( $img['data'] ?? $img['image_base64'] ?? '' )
                    : ( is_string( $img ) ? $img : '' );
                $mime = is_array( $img )
                    ? ( $img['mime_type'] ?? 'image/jpeg' )
                    : 'image/jpeg';
                if ( ! $raw ) {
                    continue;
                }
                if ( strpos( $raw, 'data:' ) !== 0 ) {
                    $clean = preg_replace( '#\s+#', '', $raw );
                    $raw   = 'data:' . $mime . ';base64,' . $clean;
                }
                $new_content .= '![](' . esc_attr( $raw ) . ")\n\n";
            }
        }
    }

    // 4) Update post if changed
    if ( $new_content && $new_content !== $post->post_content ) {
        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => wp_slash( $new_content ),
        ] );
    }

    // 5) Flag as processed
    update_post_meta( $post_id, 'minstral_processed', true );

    return true;
}

/**
 * Add our admin submenu under Tools.
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'tools.php',
        __( 'Send PDF to OCR', 'pdf2p2' ),
        __( 'Send PDF to OCR', 'pdf2p2' ),
        'manage_options',
        'pdf2p2_send_to_ocr',
        'pdf2p2_render_minstral_send_page'
    );
} );

/**
 * Render the admin page form and handle submissions.
 */
function pdf2p2_render_minstral_send_page() {
    echo '<div class="wrap">';

$unprocessed = pdf2p2_get_unprocessed_post_ids();
if ( ! empty( $unprocessed ) ) {
    foreach ( $unprocessed as $post_id ) {
        // your output for each post
        printf(
            '<p>Unprocessed: <a href="%1$s">%2$s</a> (ID %3$d)</p>',
            esc_url( get_edit_post_link( $post_id ) ),
            esc_html( get_the_title( $post_id ) ),
            intval( $post_id )
        );
    }
} else {
    echo '<p>' . esc_html__( 'No documents to process', 'pdf2p2' ) . '</p>';
}

    
    echo '<h1>' . esc_html__( 'Send PDF to Mistral OCR', 'pdf2p2' ) . '</h1>';
    echo '<p>' . esc_html__( 'Enter one or more post IDs (comma-separated):', 'pdf2p2' ) . '</p>';

    echo '<form method="post">';
    wp_nonce_field( 'pdf2p2_send_ocr', 'pdf2p2_send_ocr_nonce' );
    echo '<input type="text" name="send_minstral_post_ids" style="width:300px;" '
       . 'placeholder="e.g. 12,34,56" '
       . 'value="' . ( isset( $_POST['send_minstral_post_ids'] ) 
            ? esc_attr( $_POST['send_minstral_post_ids'] ) 
            : '' ) . '">';
    submit_button( __( 'Send to OCR', 'pdf2p2' ), 'primary', 'send_ocr' );
    echo '</form>';

    if ( ! empty( $_POST['send_ocr'] )
      && check_admin_referer( 'pdf2p2_send_ocr', 'pdf2p2_send_ocr_nonce' )
    ) {
        $raw = sanitize_text_field( wp_unslash( $_POST['send_minstral_post_ids'] ) );
        $ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );

        if ( $ids ) {
            echo '<h2>' . esc_html__( 'OCR Results', 'pdf2p2' ) . '</h2>';
            foreach ( $ids as $post_id ) {
                $result = pdf2p2_send_post_to_mistral_ocr( $post_id );
                if ( is_wp_error( $result ) ) {
                    echo '<p style="color:red;"><strong>' 
                       . esc_html( $result->get_error_message() ) 
                       . '</strong></p>';
                } else {
                    echo '<p>' . sprintf(
                        /* translators: 1: Post title, 2: Post ID */
                        esc_html__( '%1$s (ID %2$d) processed successfully.', 'pdf2p2' ),
                        esc_html( get_the_title( $post_id ) ),
                        intval( $post_id )
                    ) . '</p>';
                }
            }
        } else {
            echo '<p><em>' . esc_html__( 'No valid post IDs provided.', 'pdf2p2' ) . '</em></p>';
        }
    }

    echo '</div>';
}
