<?php
/*
Plugin Name: pdf2post
Description: Registers custom post types for Import, Markdown, and Gutenberg content, and adds an Import PDF admin page.
Version: 1.0
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
        <p>Yooooooo to do.</p>
    
    </div>
    <?php
}
