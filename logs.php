<?php
/**
 * Append a message to our plugin log.
 *
 * @param string $message
 * @param string $level   One of 'INFO', 'WARNING', 'ERROR'
 */
function pdf2p2_log( $message, $level = 'INFO' ) {
    $upload  = wp_upload_dir();
    $log_dir = trailingslashit( $upload['basedir'] ) . 'pdf2p2/logs';
    if ( ! file_exists( $log_dir ) ) {
        wp_mkdir_p( $log_dir );
    }
    $file  = $log_dir . '/plugin.log';
    $ts    = date_i18n( 'Y-m-d H:i:s' );
    $entry = sprintf( "[%s] [%s] %s\n", $ts, strtoupper( $level ), $message );
    error_log( $entry, 3, $file );
}

// Hook PHP warnings/notices into our log
set_error_handler( function( $errno, $errstr, $errfile, $errline ) {
    $lvl = in_array( $errno, [E_WARNING, E_USER_WARNING], true ) ? 'WARNING' : 'ERROR';
    pdf2p2_log( "{$errstr} in {$errfile} on line {$errline}", $lvl );
    return false; // Let WP handle it too if WP_DEBUG is on
} );

// Add “PDF2P2 Logs” under Tools
add_action( 'admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'PDF2P2 Logs',
        'PDF2P2 Logs',
        'manage_options',
        'pdf2p2-logs',
        'pdf2p2_render_logs_page'
    );
} );

/**
 * Render the Tools → PDF2P2 Logs page.
 */
function pdf2p2_render_logs_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $upload   = wp_upload_dir();
    $log_file = trailingslashit( $upload['basedir'] ) . 'pdf2p2/logs/plugin.log';

    // Clear log action
    if ( isset( $_POST['pdf2p2_clear_logs'] ) && check_admin_referer( 'pdf2p2_clear_logs' ) ) {
        file_put_contents( $log_file, '' );
        echo '<div class="notice notice-success"><p>Log file cleared.</p></div>';
    }

    echo '<div class="wrap"><h1>PDF2P2 Logs</h1>';

    if ( ! file_exists( $log_file ) ) {
        echo '<p>No log file found. Nothing has been logged yet.</p>';
    } else {
        $lines = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $tail  = array_slice( $lines, -200 );

        echo '<form method="post" style="margin-bottom:1em;">'
           . wp_nonce_field( 'pdf2p2_clear_logs', '_wpnonce', true, false )
           . '<input type="submit" name="pdf2p2_clear_logs" class="button button-secondary" value="Clear log">'
           . '</form>';

        echo '<pre style="max-height:600px; overflow:auto; background:#fff; border:1px solid #ddd; padding:1em;"><code>';
        foreach ( $tail as $line ) {
            if ( strpos( $line, '[ERROR]' ) !== false ) {
                echo '<span style="color:#c00;">' . esc_html( $line ) . '</span>' . "\n";
            } elseif ( strpos( $line, '[WARNING]' ) !== false ) {
                echo '<span style="color:#e67e22;">' . esc_html( $line ) . '</span>' . "\n";
            } else {
                echo esc_html( $line ) . "\n";
            }
        }
        echo '</code></pre>';
    }

    echo '</div>';
}
