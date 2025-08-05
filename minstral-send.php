<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pdf2p2_render_minstral_send_page() {
    echo '<div class="wrap">';
    echo '<h1>pdf2p&sup2; Send PDF to Minstal OCR</h1>';
    echo '<p>Send post IDs for OCRing</p>';

    add_action('rest_api_init', function () {
        register_rest_route('pdf2p2/v1', '/check-files', [
            'methods'  => 'POST',
            'callback' => 'pdf2p2_check_files',
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            }
        ]);
    });

    $api_key     = get_option( 'pdf2p2_api_key', '' );
    $settings_url = admin_url( 'admin.php?page=pdf2p2_settings' );
    if ( $api_key ) {
        echo 'Looks like you’ve set your Mistral OCR API key. ';
        printf( '<a href="%s">Change it on the settings page</a>.', esc_url( $settings_url ) );
    } else {
        echo 'Don’t forget to set your Mistral OCR API key. ';
        printf( '<a href="%s">Go to settings now</a>.', esc_url( $settings_url ) );
    }

    echo '<p>Enter one or more post IDs (comma-separated):</p>';
    echo '<form method="post" style="margin-top:20px;">';
    echo '  <label for="send_minstral_post_ids">Post IDs:</label> ';
    echo '  <input type="text" name="send_minstral_post_ids" id="send_minstral_post_ids" ';
    echo '         placeholder="e.g. 12,34,56" style="width:200px;" ';
    echo '         value="' . ( isset( $_POST['send_minstral_post_ids'] ) ? esc_attr( $_POST['send_minstral_post_ids'] ) : '' ) . '" />';
    echo '  <button type="submit" class="button button-primary">Send to OCR</button>';
    echo '</form>';

    if ( ! empty( $_POST['send_minstral_post_ids'] ) && $api_key ) {
        $raw = sanitize_text_field( wp_unslash( $_POST['send_minstral_post_ids'] ) );
        $ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );

        if ( ! empty( $ids ) ) {
            echo '<h2 style="margin-top:30px;">OCR Results</h2>';

            foreach ( $ids as $post_id ) {
                $post = get_post( $post_id );
                if ( ! $post ) {
                    echo '<p><strong>ID ' . $post_id . ':</strong> Invalid post ID.</p>';
                    continue;
                }

                $file_url = get_post_meta( $post_id, 'pdf2p2_original_file_path', true );
                if ( ! $file_url ) {
                    echo '<p><strong>' . esc_html( get_the_title( $post ) ) . ' (ID ' . $post_id . '):</strong> No original file URL found.</p>';
                    continue;
                }

                // Call Mistral OCR API
                $payload        = [
                    'model'                => 'mistral-ocr-latest',
                    'document'             => [
                        'type'         => 'document_url',
                        'document_url' => $file_url,
                    ],
                    'include_image_base64' => true,
                ];
                $json_payload   = wp_json_encode( $payload );
                $ch             = curl_init( 'https://api.mistral.ai/v1/ocr' );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch, CURLOPT_POST,           true );
                curl_setopt( $ch, CURLOPT_POSTFIELDS,     $json_payload );
                curl_setopt( $ch, CURLOPT_HTTPHEADER,     [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $api_key,
                ] );
                $response = curl_exec( $ch );
                $err      = curl_error( $ch );
                curl_close( $ch );

                echo '<h3>' . esc_html( get_the_title( $post ) ) . ' (ID ' . $post_id . ')</h3>';

                if ( $err ) {
                    echo '<p style="color:red;"><strong>cURL error:</strong> ' . esc_html( $err ) . '</p>';
                    continue;
                }

                $data = json_decode( $response, true );
                if ( null === $data ) {
                    echo '<p style="color:red;"><strong>Invalid JSON response.</strong></p>';
                    echo '<pre>' . esc_html( $response ) . '</pre>';
                    continue;
                }

                // === NEW: In-place update of post content & processed flag ===
                if ( ! empty( $data['pages'] ) && is_array( $data['pages'] ) ) {
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

                    // Only update if changed
                    if ( $new_content && $new_content !== $post->post_content ) {
                        wp_update_post([
                            'ID'           => $post_id,
                            'post_content' => wp_slash( $new_content ),
                        ]);
                    }
                    update_post_meta( $post_id, 'minstral_processed', true );
                }
                // === End of in-place update ===

            } // end foreach IDs

        } else {
            echo '<p><em>No valid post IDs provided.</em></p>';
        }
    }

    echo '</div>'; // .wrap
}