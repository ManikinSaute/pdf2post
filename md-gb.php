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
            'terms'    => 'staff-verified',
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
    require_once __DIR__ . '/parsedown/Parsedown.php';
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'pdf2p2_import') return;

    $markdown = $post->post_content;
    $parsedown = new Parsedown();
    $html = $parsedown->text($markdown);

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $body = $dom->getElementsByTagName('body')->item(0);

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

    if ($new_id && !is_wp_error($new_id)) {
        wp_set_object_terms($new_id, 'human-verified', 'status', false);
        wp_delete_post($post_id, true);
    }
}

function pdf2p2_convert_node_to_block($node) {
    $html = inner_html($node);
    switch ($node->nodeName) {
        case 'h1': case 'h2': case 'h3':
            $level = substr($node->nodeName, 1);
            return "<!-- wp:heading {\"level\":{$level}} -->\n<h{$level}>{$node->textContent}</h{$level}>\n<!-- /wp:heading -->";
        case 'p':
            return "<!-- wp:paragraph -->\n<p>{$html}</p>\n<!-- /wp:paragraph -->";
        case 'ul': case 'ol':
            $ordered = ($node->nodeName === 'ol') ? 'true' : 'false';
            return "<!-- wp:list {\"ordered\":{$ordered}} -->\n<{$node->nodeName}>{$html}</{$node->nodeName}>\n<!-- /wp:list -->";
        case 'blockquote':
            return "<!-- wp:quote -->\n<blockquote>{$html}</blockquote>\n<!-- /wp:quote -->";
        case 'pre':
            $code = $node->getElementsByTagName('code')->item(0);
            $code_html = $code ? esc_html($code->nodeValue) : '';
            return "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>{$code_html}</code></pre>\n<!-- /wp:code -->";
        case 'img':
            $src = $node->getAttribute('src');
            $alt = $node->getAttribute('alt');
            return "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"{$src}\" alt=\"{$alt}\" /></figure>\n<!-- /wp:image -->";
        default:
            return "<!-- wp:html -->\n{$html}\n<!-- /wp:html -->";
    }
}

function inner_html($node) {
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }
    return $html;
}
