<?php
// menu.php

/**
 * Register the top‐level and sub‐menus for pdf2p2.
 */
function pdf2p2_admin_menu() {
    // Top‐level page
    add_menu_page(
        'pdf2p2',               // page title
        'pdf2p2',               // menu title
        'manage_options',       // capability
        'pdf2p2_home',          // menu slug
        'render_pdf2p2_home_page'
    );

    // Sub‐menus
    $subs = [
        ['slug' => 'pdf2p2_rss_feed',       'title' => 'pdf2p2 Feed',          'callback' => 'pdf2p2_render_rss_feed'],
        ['slug' => 'pdf2p2_settings',       'title' => 'pdf2p2 Settings',      'callback' => 'pdf2p2_render_settings_page'],
        ['slug' => 'pdf2p2_logs',           'title' => 'pdf2p2 Logs',          'callback' => 'pdf2p2_render_logs_page'],
        ['slug' => 'pdf2p2_import',         'title' => 'pdf2p2 Import',        'callback' => 'pdf2p2_render_import_page'],
    ];

    foreach ( $subs as $sub ) {
        add_submenu_page(
            'pdf2p2_home',
            $sub['title'],      // page title
            $sub['title'],      // menu title
            'manage_options',
            $sub['slug'],
            $sub['callback']
        );
    }
}
add_action( 'admin_menu', 'pdf2p2_admin_menu' );
