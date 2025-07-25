<?php

add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Process Staff Verified Markdown',
        'Process MD Imports',
        'manage_options',
        'pdf2p2-md-convert',
        'pdf2p2_render_md_processor_page'
    );
});
 
function pdf2p2_render_md_processor_page() {
    if (isset($_POST['convert_post_id'])) {
        $post_id = (int) $_POST['convert_post_id'];
        pdf2p2_convert_markdown_to_gutenberg($post_id);
        echo '<div class="notice notice-success"><p>Post converted successfully!</p></div>';
    }

    $staff_verified = get_posts([
        'post_type'   => 'pdf2p2_import',
        'posts_per_page' => -1,
        'tax_query'   => [[
            'taxonomy' => 'status',
            'field'    => 'slug',
            'terms'    => 'staff_verified',
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

function pdf2p2_convert_markdown_to_gutenberg($post_id) {
    require_once __DIR__ . '/Parsedown.php';

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'pdf2p2_import') return;

    $markdown = $post->post_content;
    $parsedown = new Parsedown();
    $html = $parsedown->text($markdown);

    if (empty($html)) {
        error_log("Empty HTML generated from Markdown (Post ID: $post_id)");
        return;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $body = $dom->getElementsByTagName('body')->item(0);

    if (!$body) {
        error_log("DOMDocument could not find <body> (Post ID: $post_id)");
        return;
    }

    $blocks = [];
    foreach ($body->childNodes as $node) {
        if ($node->nodeType !== XML_ELEMENT_NODE) continue;
        $blocks[] = pdf2p2_convert_node_to_block($node);
    }

    $gutenberg_content = implode("\n\n", array_filter($blocks));

    $new_id = wp_insert_post([
        'post_type'    => 'pdf2p2_gutenberg',
        'post_status'  => 'publish',
        'post_title'   => $post->post_title,
        'post_content' => $gutenberg_content,
    ]);

    if (is_wp_error($new_id)) {
        error_log("wp_insert_post failed: " . $new_id->get_error_message());
        return;
    }

    wp_set_object_terms($new_id, 'human-verified', 'status', false);
    wp_delete_post($post_id, true);
}
