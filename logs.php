<?php
die( 'logs.php was loaded and parsed correctly' );
/**
 * Append a message to our plugin log.
 *
 * @param string $message
 * @param string $level   One of 'INFO', 'WARNING', 'ERROR'
 */
function pdf2p2_log( $message, $level = 'INFO' ) {
    // Where to store logs: wp-content/uploads/pdf2p2/logs/plugin.log
    $upload_dir = wp_upload_dir();
    $log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'pdf2p2/logs';
    if ( ! file_exists( $log_dir ) ) {
        wp_mkdir_p( $log_dir );
    }
    $log_file = $log_dir . '/plugin.log';

    $ts = date_i18n( 'Y-m-d H:i:s' );
    $line = sprintf( "[%s] [%s] %s\n", $ts, strtoupper( $level ), $message );
    error_log( $line, 3, $log_file );
}

// Hook PHP warnings/notices into our log
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
    $level = 'ERROR';
    if ( in_array( $errno, [ E_WARNING, E_USER_WARNING ], true ) ) {
        $level = 'WARNING';
    }
    pdf2p2_log( "{$errstr} in {$errfile} on line {$errline}", $level );
    // Let WP handle it as well if WP_DEBUG is on
    return false;
} );

// Example usage in your import routine, wrap critical sections:
// pdf2p2_log( "Starting OCR for post #{$post_id}", 'INFO' );
// pdf2p2_log( "OCR API responded with error: {$api_error}", 'ERROR' );


/**
 * Add “PDF2P2 Logs” under Tools.
 */
function pdf2p2_add_logs_admin_page() {
    add_submenu_page(
        'tools.php',
        'PDF2P2 Logs',
        'PDF2P2 Logs',
        'manage_options',
        'pdf2p2-logs',
        'pdf2p2_render_logs_page'
    );
}
add_action( 'admin_menu', 'pdf2p2_add_logs_admin_page' );

/**
 * Render the Tools → PDF2P2 Logs page.
 */
function pdf2p2_render_logs_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $upload_dir = wp_upload_dir();
    $log_file   = trailingslashit( $upload_dir['basedir'] ) . 'pdf2p2/logs/plugin.log';

    // Handle “Clear log” action
    if ( isset( $_POST['pdf2p2_clear_logs'] ) && check_admin_referer( 'pdf2p2_clear_logs' ) ) {
        file_put_contents( $log_file, '' );
        echo '<div class="notice notice-success"><p>Log file cleared.</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>PDF2P2 Logs</h1>';

    if ( ! file_exists( $log_file ) ) {
        echo '<p>No log file found. Nothing logged yet.</p>';
    } else {
        // Read last 200 lines
        $lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $tail  = array_slice( $lines, -200 );

        echo '<form method="post" style="margin-bottom:1em;">';
        wp_nonce_field( 'pdf2p2_clear_logs' );
        echo '<input type="submit" name="pdf2p2_clear_logs" class="button button-secondary" value="Clear log">';
        echo '</form>';

        echo '<div style="max-height:600px; overflow:auto; background:#fff; border:1px solid #ddd; padding:1em; font-family:monospace; font-size:13px;">';
        foreach ( $tail as $line ) {
            // Simple syntax coloring
            if ( strpos( $line, '[ERROR]' ) !== false ) {
                echo '<div style="color:#c00;">' . esc_html( $line ) . '</div>';
            } elseif ( strpos( $line, '[WARNING]' ) !== false ) {
                echo '<div style="color:#e67e22;">' . esc_html( $line ) . '</div>';
            } else {
                echo '<div>' . esc_html( $line ) . '</div>';
            }
        }
        echo '</div>';
    }

    echo '</div>';
}
