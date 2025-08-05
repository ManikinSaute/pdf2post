<?php

add_action( 'admin_init', 'pdf2p2_register_settings' );
function pdf2p2_register_settings() {
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
        [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'daily', 
        ]
    );
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_import_rssfeed_url',
        [ 'sanitize_callback' => 'esc_url_raw',
          'default'           => 'https://www.amnesty.org/en/latest/feed/',
 ]
    );
    register_setting(
        'pdf2p2_settings_group',
        'pdf2p2_api_key',
        [
            'type'              => 'string',
            'sanitize_callback' => 'pdf2p2_sanitize_api_key',
            'default'           => '',
        ]
    );
    add_settings_section(
        'pdf2p2_main_section',
        'OCR & Ingestion Settings',
        '__return_false',
        'pdf2p2-settings'
    );
	    add_settings_field(
        'pdf2p2_import_rssfeed_url',
        'RSS feed URL',
        'pdf2p2_import_rssfeed_url_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
	    add_settings_field(
        'pdf2p2_api_key',
        'OCR API Key',
        'pdf2p2_api_key_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
    add_settings_field(
        'pdf2p2_total_docs',
        'Total Documents to Ingest',
        'pdf2p2_total_docs_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
    add_settings_field(
        'pdf2p2_cron_schedule',
        'Cron Schedule',
        'pdf2p2_cron_schedule_field_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
    add_settings_field(
        'pdf2p2_debug_mode',
        'De-Bug  Mode',
        'pdf2p2_debug_mode_cb',
        'pdf2p2-settings',
        'pdf2p2_main_section'
    );
}


function pdf2p2_api_key_field_cb() {
    $key = get_option( 'pdf2p2_api_key', '' );
    printf(
        '<input type="password"
                name="pdf2p2_api_key"
                value=""
                placeholder="%s"
                class="regular-text" />',
        esc_attr( $key ? '••••••••' : '' )
    );

    if ( $key ) {
        echo '<p class="description">An API key is already saved.</p>';
    } else {
        echo '<p class="description">Enter your OCR service API key.</p>';
    }
}

function pdf2p2_import_rssfeed_url_field_cb() {
    $pdf2p2_import_rssfeed_url = get_option( 'pdf2p2_import_rssfeed_url', '' );
    printf(
        '<input type="url" name="pdf2p2_import_rssfeed_url" value="%s" class="regular-text" />'
        . '<p class="description">Enter your import RSS feed here. <br /> Leaving this blank can cause issues https://www.amnesty.org/en/latest/feed/.</p>',
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

    $schedules = wp_get_schedules();
    $current  = get_option( 'pdf2p2_cron_schedule', 'daily' );

    echo '<select name="pdf2p2_cron_schedule">';
    foreach ( $schedules as $key => $sched ) {
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr( $key ),
            selected( $current, $key, false ),
            esc_html( $sched['display'] )
        );
    }
    echo '</select>';
    echo '<p class="description">Select how often to run the ingestion job.</p>';
}

function pdf2p2_debug_mode_cb() {
    $enabled = (int) get_option( 'pdf2p2_debug_mode', 0 );


    printf(
        '<label for="pdf2p2_debug_mode">
            <input type="checkbox" id="pdf2p2_debug_mode" name="pdf2p2_debug_mode" value="1" %s />
            %s
        </label>
        <p class="description">%s</p>',
        checked( 1, $enabled, false ),              
        esc_html__( 'Enable debug mode', 'pdf2p2' ), 
        esc_html__( 'Toggle pdf2p2 debug logging on or off.', 'pdf2p2' ) 
    );
}


function pdf2p2_sanitize_api_key( $input ) {
    $old = get_option( 'pdf2p2_api_key', '' );
    if ( empty( $input ) && $old ) {
        return $old;
    }
    return sanitize_text_field( $input );
}



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