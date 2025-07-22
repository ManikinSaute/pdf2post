<?php

// 2. Register settings, sections, and fields
add_action( 'admin_init', 'pdf2p2_register_settings' );
function pdf2p2_register_settings() {
    // Register each setting (stored in wp_options)
	register_setting(
	  'pdf2p2_settings_group',
	  'pdf2p2_debug_mode',
	  [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
		'default'           => 0,
	  ]
	);
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_total_docs',
        [ 'sanitize_callback' => 'absint' ]
    );
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_cron_schedule',
        [ 'sanitize_callback' => 'sanitize_text_field' ]
    );
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_import_rssfeed_url',
        [ 'sanitize_callback' => 'esc_url_raw' ]
    );
    // Add a section
    add_settings_section(
        'pdf2p2_main_section',
        'OCR & Ingestion Settings',
        '__return_false',
        'pdf2p2-settings'
    );
	
    // RSS feild 
    add_settings_field(
        'pdf2p2_import_rssfeed_url',
        'RSS feed URL',
        'pdf2p2_import_rssfeed_url_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
	
    // API Key field
    add_settings_field(
        'pdf2p2_api_key',
        'OCR API Key',
        'pdf2p2_api_key_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );

    // Total docs field
    add_settings_field(
        'pdf2p2_total_docs',
        'Total Documents to Ingest',
        'pdf2p2_total_docs_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );

    // Cron schedule field
    add_settings_field(
        'pdf2p2_cron_schedule',
        'Cron Schedule (cron expression)',
        'pdf2p2_cron_schedule_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
	    // Debug mode
    add_settings_field(
        'pdf2p2_debug_mode',
        'De-Bug  Mode',
        'pdf2p2_debug_mode_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
}

// 3. Render the Settings page
function pdf2p2_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>pdf2p2 Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'pdf2p2_settings_group' );
            do_settings_sections( 'pdf2p2-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// 4. Field callbacks
function pdf2p2_api_key_field_cb() {
    $key = get_option( 'pdf2p2_api_key', '' );
    // Always show empty; mask if exists
    printf(
        '<input type="password" name="pdf2p2_api_key" value="" class="regular-text" />'
        . ( $key ? '<p class="description">(An API key is already saved.)</p>' : '<p class="description">Enter your OCR service API key.</p>' ),
    );
}

function pdf2p2_import_rssfeed_url_field_cb() {
    $pdf2p2_import_rssfeed_url = get_option( 'pdf2p2_import_rssfeed_url', '' );
    printf(
        '<input type="url" name="pdf2p2_import_rssfeed_url" value="%s" class="regular-text" />'
        . '<p class="description">Enter your import RSS feed here.</p>',
        esc_attr( $pdf2p2_import_rssfeed_url )
    );
}


function pdf2p2_total_docs_field_cb() {
    $total = get_option( 'pdf2p2_total_docs', 0 );
    printf(
        '<input type="number" min="0" name="pdf2p2_total_docs" value="%s" class="regular-text" />',
        esc_attr( $total )
    );
}

function pdf2p2_cron_schedule_field_cb() {
    $cron = get_option( 'pdf2p2_cron_schedule', '0 2 * * *' );
    printf(
        '<input type="text" name="pdf2p2_cron_schedule" value="%s" class="regular-text" />'
        . '<p class="description">Enter a valid cron expression for ingestion runs.</p>',
        esc_attr( $cron )
    );
}

function pdf2p2_debug_mode_cb() {
    // Read the saved debug‐mode flag (0 or 1)
    $enabled = (int) get_option( 'pdf2p2_debug_mode', 0 );

    // Print a single checkbox
    printf(
        '<label for="pdf2p2_debug_mode">
            <input type="checkbox" id="pdf2p2_debug_mode" name="pdf2p2_debug_mode" value="1" %s />
            %s
        </label>
        <p class="description">%s</p>',
        checked( 1, $enabled, false ),               // <-- now uses $enabled
        esc_html__( 'Enable debug mode', 'pdf2p2' ), // label text
        esc_html__( 'Toggle pdf2p2 debug logging on or off.', 'pdf2p2' ) // description
    );
}


// 5. Sanitization for API key (preserve existing if left blank)
function pdf2p2_sanitize_api_key( $input ) {
    $old = get_option( 'pdf2p2_api_key', '' );
    if ( empty( $input ) && $old ) {
        return $old;
    }
    return sanitize_text_field( $input );
}
