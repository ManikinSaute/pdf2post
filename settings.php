<?php
// 1. Add the Settings page under Settings → PDF2Post
add_action( 'admin_menu', 'pdf2post_add_settings_page' );
function pdf2post_add_settings_page() {
    add_options_page(
        'PDF2Post Settings',
        'PDF2Post Settings',
        'manage_options',
        'pdf2post-settings',
        'pdf2post_render_settings_page'
    );
}

// 2. Register settings, sections, and fields
add_action( 'admin_init', 'pdf2post_register_settings' );
function pdf2post_register_settings() {
    // Register each setting (stored in wp_options)
    register_setting(
        'pdf2post_settings_group',
        'pdf2post_api_key',
        [ 'sanitize_callback' => 'pdf2post_sanitize_api_key' ]
    );
    register_setting(
        'pdf2post_settings_group',
        'pdf2post_total_docs',
        [ 'sanitize_callback' => 'absint' ]
    );
    register_setting(
        'pdf2post_settings_group',
        'pdf2post_cron_schedule',
        [ 'sanitize_callback' => 'sanitize_text_field' ]
    );

    // Add a section
    add_settings_section(
        'pdf2post_main_section',
        'OCR & Ingestion Settings',
        '__return_false',
        'pdf2post-settings'
    );

    // API Key field
    add_settings_field(
        'pdf2post_api_key',
        'OCR API Key',
        'pdf2post_api_key_field_cb',
        'pdf2post-settings',
        'pdf2post_main_section'
    );

    // Total docs field
    add_settings_field(
        'pdf2post_total_docs',
        'Total Documents to Ingest',
        'pdf2post_total_docs_field_cb',
        'pdf2post-settings',
        'pdf2post_main_section'
    );

    // Cron schedule field
    add_settings_field(
        'pdf2post_cron_schedule',
        'Cron Schedule (cron expression)',
        'pdf2post_cron_schedule_field_cb',
        'pdf2post-settings',
        'pdf2post_main_section'
    );
}

// 3. Render the Settings page
function pdf2post_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>PDF2Post Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'pdf2post_settings_group' );
            do_settings_sections( 'pdf2post-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// 4. Field callbacks
function pdf2post_api_key_field_cb() {
    $key = get_option( 'pdf2post_api_key', '' );
    // Always show empty; mask if exists
    printf(
        '<input type="password" name="pdf2post_api_key" value="" class="regular-text" />'
        . ( $key ? '<p class="description">(An API key is already saved.)</p>' : '<p class="description">Enter your OCR service API key.</p>' ),
    );
}

function pdf2post_total_docs_field_cb() {
    $total = get_option( 'pdf2post_total_docs', 0 );
    printf(
        '<input type="number" min="0" name="pdf2post_total_docs" value="%s" class="regular-text" />',
        esc_attr( $total )
    );
}

function pdf2post_cron_schedule_field_cb() {
    $cron = get_option( 'pdf2post_cron_schedule', '0 2 * * *' );
    printf(
        '<input type="text" name="pdf2post_cron_schedule" value="%s" class="regular-text" />'
        . '<p class="description">Enter a valid cron expression for ingestion runs.</p>',
        esc_attr( $cron )
    );
}

// 5. Sanitization for API key (preserve existing if left blank)
function pdf2post_sanitize_api_key( $input ) {
    $old = get_option( 'pdf2post_api_key', '' );
    if ( empty( $input ) && $old ) {
        return $old;
    }
    return sanitize_text_field( $input );
}
