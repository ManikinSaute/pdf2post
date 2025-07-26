<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Activation: schedule at saved interval
register_activation_hook( __FILE__, 'pdf2p2_activate' );
function pdf2p2_activate() {
    $schedule = get_option( 'pdf2p2_cron_schedule', 'daily' );
    if ( ! wp_next_scheduled( 'pdf2p2_import_event' ) ) {
        wp_schedule_event( time(), $schedule, 'pdf2p2_import_event' );
    }
}

// Deactivation: clear it
register_deactivation_hook( __FILE__, 'pdf2p2_deactivate' );
function pdf2p2_deactivate() {
    wp_clear_scheduled_hook( 'pdf2p2_import_event' );
}

// When the schedule-setting changes, reschedule
add_action( 'update_option_pdf2p2_cron_schedule', 'pdf2p2_reschedule', 10, 2 );
function pdf2p2_reschedule( $old, $new ) {
    if ( $old === $new ) {
        return;
    }
    wp_clear_scheduled_hook( 'pdf2p2_import_event' );
    $schedules = wp_get_schedules();
    if ( isset( $schedules[ $new ] ) ) {
        wp_schedule_event( time(), $new, 'pdf2p2_import_event' );
    }
}

// Cron callback: fetch feed, diff, import
add_action( 'pdf2p2_import_event', 'pdf2p2_cron_import_event' );
function pdf2p2_cron_import_event() {
    // Include our helpers
    require_once plugin_dir_path( __FILE__ ) . 'feed.php';
    require_once plugin_dir_path( __FILE__ ) . 'import.php';

    $feed_url = trim( get_option( 'pdf2p2_import_rssfeed_url', '' ) );
    $urls     = pdf2p2_get_feed_not_imported( $feed_url );
    if ( ! empty( $urls ) ) {
        // false = don’t force duplicates
        pdf2p2_process_pdf_urls( $urls, false );
    }
}
