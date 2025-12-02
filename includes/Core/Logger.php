<?php
/**
 * AltPilot Logger Class
 *
 * Handles logging of ALT text generation operations.
 *
 * @package AltPilot
 * @subpackage Core
 */

namespace AltPilot\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger class for plugin operations.
 */
class Logger {

	const LOG_DIR  = 'altpilot-logs';
	const LOG_FILE = 'altpilot.log';

	/**
	 * Log a message if logging is enabled.
	 *
	 * @param string $message The message to log.
	 * @param bool   $enabled Whether logging is enabled.
	 *
	 * @return bool True if logged successfully, false otherwise.
	 */
	public function log( $message, $enabled = true ) {
		if ( ! $enabled ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$dir = trailingslashit( $upload_dir['basedir'] ) . self::LOG_DIR;

		// Attempt to create directory.
		if ( ! wp_mkdir_p( $dir ) && ! is_dir( $dir ) ) {
			return false; // Cannot create directory.
		}

		$logfile = trailingslashit( $dir ) . self::LOG_FILE;
		$time = date_i18n( 'Y-m-d H:i:s' );
		$line = sprintf( "[%s] %s\n", $time, $message );

		// Attempt to write to file.
		if ( ! @file_put_contents( $logfile, $line, FILE_APPEND | LOCK_EX ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the log directory path.
	 *
	 * @return string The absolute path to the log directory.
	 */
	public function get_log_dir() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . self::LOG_DIR;
	}

	/**
	 * Get the log file path.
	 *
	 * @return string The absolute path to the log file.
	 */
	public function get_log_file() {
		return trailingslashit( $this->get_log_dir() ) . self::LOG_FILE;
	}

	/**
	 * Clear all logs.
	 *
	 * @return bool True if cleared successfully, false otherwise.
	 */
	public function clear_logs() {
		$file = $this->get_log_file();

		if ( ! file_exists( $file ) ) {
			return true;
		}

		return wp_delete_file( $file );
	}
}
