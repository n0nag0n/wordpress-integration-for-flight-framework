<?php
/*
Plugin Name: Flight Framework Integration
Description: Integrates the Flight framework with WordPress.
Version: 1.0.0
Author: n0nag0n
License: GPLv2 or later
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FLIGHT_INTEGRATION_DIR', plugin_dir_path(__FILE__));

// Load plugin options
$flight_options = get_option('flight_integration_options', [
    'vendor_path' => FLIGHT_INTEGRATION_DIR . 'vendor/autoload.php',
	'app_folder_path' => dirname(ABSPATH) . '/app',
    'terminate_request' => true, // Default to terminating the request
    'use_wp_db' => true, // Default to using WordPress DB
]);

// Download and install Flight if not already installed when activated
register_activation_hook(__FILE__, function() {
	// Download and unzip the latest version of Flight from GitHub
	$flight_zip_url = 'https://github.com/flightphp/core/archive/refs/heads/master.zip';
	$flight_zip_path = FLIGHT_INTEGRATION_DIR . 'flight.zip';
	
	// check if we can download the file
	$response = wp_remote_get($flight_zip_url);
	if (is_wp_error($response)) {
		// Handle error
		add_settings_error('flight_integration', 'flight_integration_error', 'Failed to download Flight framework.', 'error');
	} else {
		// Save the zip file
		file_put_contents($flight_zip_path, wp_remote_retrieve_body($response));
	}

	// Initialize WordPress Filesystem
	global $wp_filesystem;
	if (empty($wp_filesystem)) {
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		WP_Filesystem();
	}

	// extract zip with WP functions
	$unzip_result = unzip_file($flight_zip_path, FLIGHT_INTEGRATION_DIR);
	if ($unzip_result === true) {
		// Delete the zip file
		wp_delete_file($flight_zip_path);
		add_settings_error('flight_integration', 'flight_integration_success', 'Flight framework downloaded and extracted successfully.', 'updated');
	} else {
		// Handle error
		add_settings_error('flight_integration', 'flight_integration_error', 'Failed to extract Flight framework: ' . (is_wp_error($unzip_result) ? $unzip_result->get_error_message() : 'Unknown error'), 'error');
	}
});

// Load Flight framework
$vendor_path = $flight_options['vendor_path'];
if (file_exists($vendor_path)) {
    require_once $vendor_path;
} else {
    // Fallback to bundled Flight if available (optional, see notes below)
    $bundled_flight = FLIGHT_INTEGRATION_DIR . 'core-master/flight/Flight.php';
    if (file_exists($bundled_flight)) {
        require_once $bundled_flight;
    } else {
        // wp_die('Flight framework not found. Please install it via Composer or provide a valid vendor path in settings.');
    }
}

// Load the plugin settings
require __DIR__ . '/flight-framework-integration-admin-settings.php';
