<?php
/*
Plugin Name: WPChill KB
Description: Custom Knowledge Base management plugin
Version: 1.0.0
Author: WPChill
Author URI: https://wpchill.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define plugin constants
define( 'WPCHILL_KB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPCHILL_KB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCHILL_KB_VERSION', '1.0.0' );

// Error collection array
$wpchill_kb_errors = array();

// Custom error handler
function wpchill_kb_error_handler( $errno, $errstr, $errfile, $errline ) {
	global $wpchill_kb_errors;
	$wpchill_kb_errors[] = "Error [$errno] $errstr in $errfile on line $errline";
	return true; // Don't execute PHP internal error handler
}

// Set custom error handler
set_error_handler( 'wpchill_kb_error_handler' );

// Autoloader with error logging and class- prefix
spl_autoload_register(
	function ( $class ) {
		global $wpchill_kb_errors;

		$prefix   = 'WPChill\\KB\\';
		$base_dir = WPCHILL_KB_PLUGIN_DIR . 'includes/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = $base_dir . 'class-' . strtolower( str_replace( '\\', '-', $relative_class ) ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		} else {
			$wpchill_kb_errors[] = "Unable to load class file: $file";
		}
	}
);

// Initialize the plugin
function wpchill_kb_initialize_plugin() {
	global $wpchill_kb_errors;

	try {
		if ( class_exists( 'WPChill\\KB\\Plugin' ) ) {
			$plugin = new WPChill\KB\Plugin();
			$plugin->run();
		} else {
			throw new Exception( 'Plugin class not found' );
		}
	} catch ( Exception $e ) {
		$wpchill_kb_errors[] = 'Exception: ' . $e->getMessage();
	}
}

add_action( 'plugins_loaded', 'wpchill_kb_initialize_plugin' );

// Display errors in admin notice
function wpchill_kb_display_errors() {
	global $wpchill_kb_errors;

	if ( ! empty( $wpchill_kb_errors ) ) {
		echo '<div class="error"><p><strong>WPChill KB Errors:</strong></p><ul>';
		foreach ( $wpchill_kb_errors as $error ) {
			echo '<li>' . esc_html( $error ) . '</li>';
		}
		echo '</ul></div>';
	}
}

add_action( 'admin_notices', 'wpchill_kb_display_errors' );

// Restore default error handler
restore_error_handler();
