<?php

/**
 * Get all pdf2p2_import posts that have been OCR-processed
 * but not yet converted to Gutenberg.
 *
 * @return int[] Array of post IDs.
 */
function pdf2p2_get_gutenberg_candidates(): array {
    $args = [
        'post_type'      => 'pdf2p2_import',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'minstral_processed',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ];
    return get_posts( $args );
}


function pdf2p2_render_md_gb_page() {
    echo '<div class="wrap">';
    echo '<h1>Imports Ready for Gutenberg</h1>';

    // Handle the “Process” button
    if ( isset( $_POST['convert_post_id'] ) ) {
        $post_id = absint( wp_unslash( $_POST['convert_post_id'] ) );
        pdf2p2_move_post_to_gutenberg( $post_id );
        echo '<div class="notice notice-success"><p>Post moved successfully!</p></div>';
    }

    // === Use the helper instead of duplicating the query ===
    $to_convert = pdf2p2_get_gutenberg_candidates();

    if ( empty( $to_convert ) ) {
        echo '<p>No processed imports to convert.</p>';
    } else {
        echo '<ul>';
        foreach ( $to_convert as $post_id ) {
            $title = get_the_title( $post_id );
            echo '<li>' . esc_html( $title ) . ' &nbsp;';

            // Inline form to process this one post
            echo '<form method="post" style="display:inline">';
            echo '<input type="hidden" name="convert_post_id" value="' . esc_attr( $post_id ) . '">';
            submit_button( 'Process', 'small', '', false );
            echo '</form>';

            echo '</li>';
        }
        echo '</ul>';
    }

    echo '</div>';
}

function pdf2p2_move_post_to_gutenberg( $post_id ) {
    // 1) Load & Validate the Post
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'pdf2p2_import' ) {
        return;
    }

    // 2) Ensure the Markdown Parser Is Available
    if ( ! class_exists( 'Parsedown' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'Parsedown.php';
    }
    $Parsedown = new Parsedown();

    // 3) Convert Markdown → HTML
    $html_content = $Parsedown->text( $post->post_content );

    // 4) Wrap HTML Paragraphs in Gutenberg Blocks
    $blocks_content = preg_replace(
        '/<p>(.*?)<\/p>/is',
        "<!-- wp:paragraph -->\n<p>$1</p>\n<!-- /wp:paragraph -->",
        $html_content
    );

    // 5) Update the Post Record
    wp_update_post( [
        'ID'           => $post_id,
        'post_type'    => 'pdf2p2_gutenberg',
        'post_status'  => 'draft',
        'post_content' => $blocks_content,
    ] );

    // 6) Mark Its “Status” Terms
wp_set_object_terms( $post_id, [], 'status', false );
}
