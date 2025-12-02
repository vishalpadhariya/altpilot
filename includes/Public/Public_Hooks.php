<?php
/**
 * AltPilot Public (Frontend) Hooks Class
 *
 * Handles public-facing functionality and hooks.
 *
 * @package AltPilot
 * @subpackage Public
 */

namespace AltPilot\Public_Hooks;

use AltPilot\Core\Generator;
use AltPilot\Core\Logger;
use AltPilot\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public hooks handler.
 */
class Public_Hooks {

	/**
	 * Options manager.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Generator instance.
	 *
	 * @var Generator
	 */
	private $generator;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param Options   $options   Options manager.
	 * @param Generator $generator Generator instance.
	 * @param Logger    $logger    Logger instance.
	 */
	public function __construct( Options $options, Generator $generator, Logger $logger ) {
		$this->options = $options;
		$this->generator = $generator;
		$this->logger = $logger;

		// Generate on upload hook.
		add_action( 'add_attachment', array( $this, 'maybe_generate_alt_on_upload' ) );
	}

	/**
	 * Maybe generate ALT text when an attachment is uploaded.
	 *
	 * @param int $attachment_id The attachment post ID.
	 */
	public function maybe_generate_alt_on_upload( $attachment_id ) {
		if ( ! $this->options->get( 'auto_generate_on_upload' ) ) {
			return;
		}

		$mime = get_post_mime_type( $attachment_id );
		$allowed = $this->options->get( 'allowed_mimes' );

		if ( ! in_array( $mime, $allowed, true ) ) {
			return;
		}

		$existing_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( ! empty( $existing_alt ) ) {
			return;
		}

		$title = get_the_title( $attachment_id );
		$mode = $this->options->get( 'mode' );
		$alt = $this->generator->build_alt( $attachment_id, $title, $mode );

		if ( ! empty( $alt ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', wp_strip_all_tags( $alt ) );
			$this->logger->log( "Upload: Attachment $attachment_id ALT set to: $alt", $this->options->get( 'enable_logging' ) );
		}
	}
}
