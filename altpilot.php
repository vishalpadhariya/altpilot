<?php
/**
 * Plugin Name: AltPilot
 * Plugin URI:  https://github.com/vishalpadhariya/altpilot
 * Description: Auto-generate missing ALT text for image attachments based on the image title. Includes bulk actions, settings, AJAX bulk-run, logging, and developer hooks.
 * Version:     1.1.0
 * Author:      Vishal Padhariya
 * Author URI:  https://vishalpadhariya.github.io
 * Text Domain: altpilot
 * Domain Path: /languages
 *
 * License: GPLv2 or later
 *
 * @package AltPilot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ALTPILOT_VERSION', '1.1.0' );
define( 'ALTPILOT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALTPILOT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load core classes.
require_once ALTPILOT_PLUGIN_DIR . 'includes/Core/Generator.php';
require_once ALTPILOT_PLUGIN_DIR . 'includes/Core/Logger.php';
require_once ALTPILOT_PLUGIN_DIR . 'includes/Core/Options.php';
require_once ALTPILOT_PLUGIN_DIR . 'includes/Admin/Admin.php';
require_once ALTPILOT_PLUGIN_DIR . 'includes/Public/Public_Hooks.php';

use AltPilot\Core\Generator;
use AltPilot\Core\Logger;
use AltPilot\Core\Options;
use AltPilot\Admin\Admin;
use AltPilot\Public_Hooks\Public_Hooks;

// Plugin initialization.
register_activation_hook( __FILE__, 'altpilot_activate' );
register_deactivation_hook( __FILE__, 'altpilot_deactivate' );

/**
 * Plugin activation.
 */
function altpilot_activate() {
	$options = new Options();
	$options->initialize();
}

/**
 * Plugin deactivation (keep settings and logs).
 */
function altpilot_deactivation() {
	// Keep settings and logs on deactivation.
}

// Initialize plugin on plugins_loaded.
add_action( 'plugins_loaded', 'altpilot_init' );

/**
 * Initialize AltPilot plugin.
 */
function altpilot_init() {
	$options = new Options();
	$generator = new Generator();
	$logger = new Logger();

	// Initialize admin.
	if ( is_admin() ) {
		new Admin( $options, $generator, $logger );
	}

	// Initialize public hooks.
	new Public_Hooks( $options, $generator, $logger );
}
