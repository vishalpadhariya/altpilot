<?php
/**
 * AltPilot Options Class
 *
 * Handles plugin settings and options.
 *
 * @package AltPilot
 * @subpackage Core
 */

namespace AltPilot\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options handler for plugin settings.
 */
class Options {

	const OPTION_NAME = 'altpilot_options';

	/**
	 * Default plugin options.
	 *
	 * @var array
	 */
	private $defaults = array(
		'auto_generate_on_upload' => 1,
		'mode' => 'title_only', // title_only | title_site | filename_clean.
		'enable_logging' => 0,
		'allowed_mimes' => array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
			'image/avif',
			'image/svg+xml',
		),
		'batch_size' => 30,
	);

	/**
	 * Get default options.
	 *
	 * @return array The default options.
	 */
	public function get_defaults() {
		return $this->defaults;
	}

	/**
	 * Initialize options on plugin activation.
	 */
	public function initialize() {
		if ( false === get_option( self::OPTION_NAME ) ) {
			add_option( self::OPTION_NAME, $this->defaults );
		} else {
			// Ensure new keys exist.
			$options = wp_parse_args( get_option( self::OPTION_NAME, array() ), $this->defaults );
			update_option( self::OPTION_NAME, $options );
		}
	}

	/**
	 * Get all options.
	 *
	 * @return array The plugin options.
	 */
	public function get_all() {
		return wp_parse_args( get_option( self::OPTION_NAME, array() ), $this->defaults );
	}

	/**
	 * Get a specific option value.
	 *
	 * @param string $key     The option key.
	 * @param mixed  $default The default value if key doesn't exist.
	 *
	 * @return mixed The option value.
	 */
	public function get( $key, $default = null ) {
		$options = $this->get_all();
		if ( null === $default && isset( $this->defaults[ $key ] ) ) {
			$default = $this->defaults[ $key ];
		}
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	/**
	 * Update options.
	 *
	 * @param array $new_options The options to update/merge.
	 *
	 * @return array The sanitized options.
	 */
	public function update( $new_options ) {
		$current = $this->get_all();
		$merged = wp_parse_args( $new_options, $current );
		$sanitized = $this->sanitize( $merged );
		update_option( self::OPTION_NAME, $sanitized );
		return $sanitized;
	}

	/**
	 * Sanitize options input.
	 *
	 * @param array $input The input options to sanitize.
	 *
	 * @return array The sanitized options.
	 */
	public function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$san = array();

		// Auto generate on upload.
		$san['auto_generate_on_upload'] = isset( $input['auto_generate_on_upload'] ) && intval( $input['auto_generate_on_upload'] ) === 1 ? 1 : 0;

		// Mode.
		$mode = isset( $input['mode'] ) ? sanitize_text_field( $input['mode'] ) : $this->defaults['mode'];
		$san['mode'] = in_array( $mode, array( 'title_only', 'title_site', 'filename_clean' ), true ) ? $mode : $this->defaults['mode'];

		// Enable logging.
		$san['enable_logging'] = isset( $input['enable_logging'] ) && intval( $input['enable_logging'] ) === 1 ? 1 : 0;

		// Allowed MIME types.
		$allowed = array();
		if ( isset( $input['allowed_mimes'] ) && is_array( $input['allowed_mimes'] ) ) {
			foreach ( $input['allowed_mimes'] as $m ) {
				$m = sanitize_text_field( $m );
				if ( in_array( $m, $this->defaults['allowed_mimes'], true ) ) {
					$allowed[] = $m;
				}
			}
		}
		$san['allowed_mimes'] = ! empty( $allowed ) ? $allowed : $this->defaults['allowed_mimes'];

		// Batch size.
		$batch = isset( $input['batch_size'] ) ? intval( $input['batch_size'] ) : $this->defaults['batch_size'];
		$san['batch_size'] = max( 5, min( 200, $batch ) );

		return $san;
	}
}
