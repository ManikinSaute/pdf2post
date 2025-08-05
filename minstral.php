<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}   

add_action('rest_api_init', function () {
    register_rest_route('pdf2p2/v1', '/check-files', [
        'methods'  => 'POST',
        'callback' => 'pdf2p2_check_files',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        }
    ]);
});

function pdf2p2_check_files($request) {
    $post_ids = $request->get_param('post_ids');
    if (!is_array($post_ids) || empty($post_ids)) {
        return new WP_Error('invalid_post_ids', 'No post IDs provided.', ['status' => 400]);
    }

    $processed = [];
    $not_processed = [];

    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);
        if (!$post) continue;

        // Get file name and hash from post meta (adjust meta keys as needed)
        $file_name = get_post_meta($post_id, 'pdf2p2_file_name', true);
        $file_hash = get_post_meta($post_id, 'pdf2p2_file_hash', true);

        if (!$file_name && !$file_hash) {
            $not_processed[] = [
                'post_id'   => $post_id,
                'file_name' => '',
                'file_hash' => '',
                'reason'    => 'No file name or hash found.'
            ];
            continue;
        }

        // Query for existing processed posts in both post types
        $args = [
            'post_type'      => ['pdf2p2_imports', 'pdf2p2_gutenberg'],
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'   => 'pdf2p2_file_name',
                    'value' => $file_name,
                    'compare' => '='
                ],
                [
                    'key'   => 'pdf2p2_file_hash',
                    'value' => $file_hash,
                    'compare' => '='
                ]
            ]
        ];
        $query = new WP_Query($args);

        $already_processed = false;
        if ($query->have_posts()) {
            foreach ($query->posts as $matched_post) {
                $processed_flag = get_post_meta($matched_post->ID, 'minstral_processed', true);
                if ($processed_flag === '1') {
                    $already_processed = true;
                    break;
                }
            }
        }

        if ($already_processed) {
            $processed[] = [
                'post_id'   => $post_id,
                'file_name' => $file_name,
                'file_hash' => $file_hash
            ];
        } else {
            $not_processed[] = [
                'post_id'   => $post_id,
                'file_name' => $file_name,
                'file_hash' => $file_hash
            ];
        }
    }

    return [
        'processed'     => $processed,
        'not_processed' => $not_processed
    ];
}



function pdf2p2_render_minstral_page() {
    echo '<div class="wrap">';
    echo '<h1>pdf2p&sup2; Minstral Processor</h1>';
    echo '<p>This tell us what has or has not been processed by the OCR tool.</p>';

 $query = new WP_Query([
  'post_type'      => ['pdf2p2_import','pdf2p2_gutenberg'],
  'posts_per_page' => -1,
  'meta_key'       => 'minstral_processed',    
  'orderby'        => 'meta_value_num title',  
  'order'          => 'ASC',
]);

if ( $query->have_posts() ) {
    $all_posts = $query->posts;
    wp_reset_postdata();
    echo '<h2>Processed Documents</h2>';
    echo '<ul>';
    foreach ( $all_posts as $post ) {
        $status = intval( get_post_meta( $post->ID, 'minstral_processed', true ) );
        if ( $status === 1 ) {
            printf(
                '<li><a href="%s">%s</a> (ID: %d, Type: %s)</li>',
                esc_url( get_edit_post_link( $post->ID ) ),
                esc_html( get_the_title( $post ) ),
                $post->ID,
                esc_html( get_post_type( $post ) )
            );
        }
    }
    echo '</ul>';
    echo '<h2>Unprocessed Documents</h2>';
    echo '<ul>';
    foreach ( $all_posts as $post ) {
        $status = intval( get_post_meta( $post->ID, 'minstral_processed', true ) );
        if ( $status === 0 ) {
            printf(
                '<li><a href="%s">%s</a> (ID: %d, Type: %s)</li>',
                esc_url( get_edit_post_link( $post->ID ) ),
                esc_html( get_the_title( $post ) ),
                $post->ID,
                esc_html( get_post_type( $post ) )
            );
        }
    }
    echo '</ul>';
} else {
    echo '<p>No documents found.</p>';
}



echo '<div class="wrap">';
echo '<h2>Check a file</h2>';
echo '<p>Enter one or more post IDs (comma-separated):</p>';
echo '<form method="post" style="margin-top:20px;">';
echo '  <label for="check_post_ids">Post IDs:</label> ';
echo '  <input type="text" name="check_post_ids" id="check_post_ids" ';
echo '         placeholder="e.g. 12,34,56" style="width:200px;" />';
echo '  <button type="submit" class="button button-primary">Check Posts</button>';
echo '</form>';
echo '</div>';

if ( isset($_POST['check_post_ids']) ) {
    // turn "12, 34,56" into [12,34,56]
    $raw   = sanitize_text_field( $_POST['check_post_ids'] );
    $parts = array_filter( array_map( 'intval', explode( ',', $raw ) ) );

    foreach ( $parts as $post_id ) {
        // TODO: run your OCR dispatch on $post_id
    }
}

    if ( ! empty( $_POST['check_post_ids'] ) ) {
        // Sanitize & turn into array of ints
        $raw   = sanitize_text_field( wp_unslash( $_POST['check_post_ids'] ) );
        $ids   = array_filter( array_map( 'intval', explode( ',', $raw ) ) );

        if ( ! empty( $ids ) ) {
            echo '<h2 style="margin-top:30px;">Preview of Selected Posts</h2>';
            echo '<ul>';

            foreach ( $ids as $post_id ) {
                $post = get_post( $post_id );
                if ( ! $post ) {

                    continue;
                }

                $title   = get_the_title( $post );
                $edit_url= get_edit_post_link( $post_id );
                $type_obj= get_post_type_object( $post->post_type );
                $type    = $type_obj ? $type_obj->labels->singular_name : $post->post_type;
                $date    = get_the_date( 'F j, Y', $post );
                

                    $processed_raw = get_post_meta( $post_id, 'minstral_processed', true );
                    $processed_label = 'Unknown';
                    if ( '0' === $processed_raw ) {
                    $processed_label = 'No';
                    } elseif ( '1' === $processed_raw ) {
                    $processed_label = 'Yes';
                    }


                printf(
                    '<li><strong><a href="%1$s">%2$s</a></strong> (ID: %3$d)<br />
                     &mdash; Type: %4$s | Published: %5$s | Process Status: %6$s</li>',
                    esc_url( $edit_url ),
                    esc_html( $title ),
                    $post_id,
                    esc_html( $type ),
                    esc_html( $date ),
                    esc_html( $processed_label )
                );
            }

            echo '</ul>';
        } else {
            echo '<p><em>No valid post IDs found.</em></p>';
        }
    }

    echo '</div>';

}