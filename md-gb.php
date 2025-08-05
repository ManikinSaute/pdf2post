<?php



function pdf2p2_render_md_processor_page() {
    if (isset($_POST['convert_post_id'])) {
        $post_id = (int) $_POST['convert_post_id'];
        pdf2p2_move_post_to_gutenberg($post_id);
        echo '<div class="notice notice-success"><p>Post moved successfully!</p></div>';
    }

    $staff_verified = get_posts([
        'post_type'   => 'pdf2p2_import',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'tax_query'   => [[
            'taxonomy' => 'status',
            'field'    => 'slug',
            'terms'    => 'staff_verified',
        ]],
        'meta_query' => [[
            'key'     => 'minstral_processed',
            'value'   => '0',
            'compare' => '!=',
        ]],
    ]);

    echo '<div class="wrap"><h1>Staff Verified Imports</h1>';
    if (empty($staff_verified)) {
        echo '<p>No posts found.</p></div>';
        return;
    }
    echo '<ul>';
    foreach ($staff_verified as $post) {
        echo '<li>' . esc_html($post->post_title) . ' - ';
        echo '<form method="post" style="display:inline;">';
        echo '<input type="hidden" name="convert_post_id" value="' . esc_attr($post->ID) . '">';
        submit_button('Process', 'small', '', false);
        echo '</form></li>';
    }
    echo '</ul></div>';
}

function pdf2p2_move_post_to_gutenberg($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'pdf2p2_import') {
        return;
    }

    // Make sure Parsedown is available
    if ( ! class_exists( 'Parsedown' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'Parsedown.php';
    }
    $Parsedown = new Parsedown();

    // Convert Markdown to HTML
    $html_content = $Parsedown->text($post->post_content);

    // Convert HTML paragraphs to Gutenberg paragraph blocks
    $blocks_content = preg_replace(
        '/<p>(.*?)<\/p>/is',
        "<!-- wp:paragraph -->\n<p>$1</p>\n<!-- /wp:paragraph -->",
        $html_content
    );

    // Update post with Gutenberg block content and change post type
    wp_update_post([
        'ID'           => $post_id,
        'post_type'    => 'pdf2p2_gutenberg',
        'post_status'  => 'draft',
        'post_content' => $blocks_content,
    ]);

    wp_set_object_terms($post_id, 'human_verified', 'status', false);
    wp_set_object_terms($post_id, 'staff_verified', 'status', false);
}


 