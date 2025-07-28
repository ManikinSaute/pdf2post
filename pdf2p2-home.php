<?php

function render_pdf2p2_home_page() {
	$settings_url   = admin_url('admin.php?page=pdf2p2_settings');
	$feed_url       = admin_url('admin.php?page=pdf2p2_rss_feed');
	$import_url     = admin_url('admin.php?page=pdf2p2_import');
	$imported_url   = admin_url('edit.php?post_type=pdf2p2_import');
	$md_convert_url = admin_url('admin.php?page=pdf2p2-md-convert');
	$gutenberg_url  = admin_url('edit.php?post_type=pdf2p2_gutenberg');
	$github_url	= 'https://github.com/ManikinSaute/pdf2p2';

	echo '<div class="wrap">';
	echo '<h1>pdf2p2 - Welcome Home!</h1>';
	echo '<p>Follow these steps to get started with the pdf2p2 plugin:</p>';
	echo '<ol> ';
	echo '<li>Go to the <a href="' . esc_url($settings_url) . '">Settings page</a> and add an RSS feed URL.</li>';
	echo '<li>Visit the <a href="' . esc_url($feed_url) . '">Feed page</a> to check for PDFs that need importing.</li>';
	echo '<li>Add PDF files manually on the <a href="' . esc_url($import_url) . '">Import page</a>.</li>';
	echo '<li>View your imported PDF files <a href="' . esc_url($imported_url) . '">here</a>.</li>';
	echo '<li>Once volunteers have verified a PDF, set its status to <strong>Human Verified</strong>.</li>';
	echo '<li>After staff review, set the post status to <strong>Staff Verified</strong>.</li>';
	echo '<li>Convert files from Markdown to HTML and Gutenberg blocks on the <a href="' . esc_url($md_convert_url) . '">MD Convert page</a>.</li>';
	echo '<li>Staff should check that Markdown has been processed to HTML and Gutenberg successfully <a href="' . esc_url($gutenberg_url) . '">here</a>.</li>';
	echo '<li>When everything is verified, set the post to <strong>Staff Verified</strong>.</li>';
	echo '<li>Go to the <a href="' . esc_url($settings_url) . '">Settings page</a> and add an Cron schedule to automate the above processes.</li>';
	echo '</ol>';
	echo '<p>TO DP: There are still many things to so, one is the ability to get the MD content from the actul PDF content from the OCR API. More info can be seen <a href="' . esc_url($github_url) . '">here</a>.</li>';
	echo '</div>';
}
