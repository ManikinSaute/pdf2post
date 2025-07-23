<?php
function pdf2p2_render_rss_feed() {
    $feed_url = get_option(
        'pdf2p2_import_rssfeed_url',
        'https://www.amnesty.org/en/latest/feed/'
    );
    $response = wp_remote_get( $feed_url );

    if ( is_wp_error( $response ) ) {
        echo '<div class="notice notice-error"><p>Request failed: '
           . esc_html( $response->get_error_message() ) . '</p></div>';
        return;
    }

    $body = wp_remote_retrieve_body( $response );
    if ( empty( $body ) ) {
        echo '<div class="notice notice-warning"><p>Empty feed response.</p></div>';
        return;
    }

    libxml_use_internal_errors( true );
    $xml = simplexml_load_string( $body );
    if ( false === $xml ) {
        echo '<div class="notice notice-error"><p>Failed to parse XML.</p></div>';
        foreach ( libxml_get_errors() as $error ) {
            echo '<div><code>' . esc_html( $error->message ) . '</code></div>';
        }
        libxml_clear_errors();
        return;
    }

    if ( empty( $xml->channel->item ) ) {
        echo '<div class="notice notice-warning"><p>No items found in feed.</p></div>';
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>RSS Feed: All Items</h1>';
    echo '<p><strong>Feed URL:</strong> <a href="' . esc_url( $feed_url )
       . '" target="_blank">' . esc_html( $feed_url ) . '</a></p>';

    echo '<div style="max-height:500px; overflow:auto; padding:0.5em; border:1px solid #ddd; background:#fff;">';
    echo '<ul style="list-style:disc inside; margin:0; padding:0;">';

    foreach ( $xml->channel->item as $item ) {
        $title = sanitize_text_field( (string) $item->title );
        $link  = esc_url( (string) $item->link );
        $pubDate = isset( $item->pubDate )
            ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( (string) $item->pubDate ) )
            : '';

        echo '<li style="margin-bottom:1em;">';
        echo '<a href="' . $link . '" target="_blank"><strong>' . esc_html( $title ) . '</strong></a>';
        if ( $pubDate ) {
            echo ' <em>(' . esc_html( $pubDate ) . ')</em>';
        }
        echo '</li>';
    }

    echo '</ul>';
    echo '</div>';  // scrollable container
	
  // Now display **all** the <guid> URLs
    echo '<div style="margin-top:2em;">';
    echo '<h2>PDF File Paths</h2>';

    foreach ( $xml->channel->item as $item ) {
        if ( isset( $item->guid ) ) {
            $pdf_url = esc_url_raw( (string) $item->guid );
            // Only show if it looks like a PDF
            if ( false !== stripos( $pdf_url, '.pdf' ) ) {
                echo '<p><code>' . esc_html( $pdf_url ) . '</code></p>';
            }
        }
    }
    echo '<a href="' . esc_url( menu_page_url( 'pdf2p2_bulk_import', false ) ) . '" class="button">Bulk Import</a>';
    echo '</div>';  // end GUIDs container
    echo '</div>';  // wrap
}
