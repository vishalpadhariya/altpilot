<?php
/**
 * AltPilot Generator Class
 *
 * Handles ALT text generation logic and strategies.
 *
 * @package AltPilot
 * @subpackage Core
 */

namespace AltPilot\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generator class for ALT text generation.
 */
class Generator {

	/**
	 * Build ALT text for an attachment based on configured mode.
	 *
	 * @param int    $attachment_id The attachment ID.
	 * @param string $title         The title/name of the attachment.
	 * @param string $mode          The generation mode (title_only, title_site, filename_clean).
	 *
	 * @return string The generated ALT text.
	 */
	public function build_alt( $attachment_id, $title, $mode = 'title_only' ) {
		$alt = '';

		if ( 'title_only' === $mode ) {
			$alt = $this->generate_from_title( $title );
		} elseif ( 'title_site' === $mode ) {
			$site = get_bloginfo( 'name' );
			$alt_title = $this->generate_from_title( $title );
			$alt = trim( $alt_title . ' – ' . $site );
		} elseif ( 'filename_clean' === $mode ) {
			// If title empty, fallback to filename.
			if ( empty( $title ) ) {
				$filename = basename( get_attached_file( $attachment_id ) );
				$alt = $this->clean_filename_to_text( $filename );
			} else {
				$alt = $this->clean_filename_to_text( $title );
			}
		}

		/**
		 * Filter to allow other plugins/themes to override or tweak the generated alt.
		 *
		 * @param string $alt             The generated ALT text.
		 * @param int    $attachment_id   The attachment ID.
		 */
		$alt = apply_filters( 'altpilot_generated_alt', $alt, $attachment_id );

		// Final sanitize.
		$alt = sanitize_text_field( $alt );
		$alt = trim( $alt );

		return $alt;
	}

	/**
	 * Generate ALT text from a title using cleaning and capitalization.
	 *
	 * @param string $title The title/name.
	 *
	 * @return string The cleaned and formatted title.
	 */
	private function generate_from_title( $title ) {
		$title = wp_strip_all_tags( $title );
		$title = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, get_bloginfo( 'charset' ) );

		// If title looks like filename with extension, strip extension.
		$title = preg_replace( '/\.[a-z0-9]{1,6}$/i', '', $title );
		$title = str_replace( array( '-', '_' ), ' ', $title );
		$title = preg_replace( '/\s+/', ' ', $title );
		$title = $this->clean_common_noise( $title );
		$title = mb_convert_case( $title, MB_CASE_TITLE, get_bloginfo( 'charset' ) );

		/**
		 * Filter to allow modifications to cleaned title.
		 *
		 * @param string $title The cleaned title.
		 */
		$title = apply_filters( 'altpilot_clean_title', $title );

		return $title;
	}

	/**
	 * Clean filename-like strings to readable text.
	 *
	 * Removes prefixes, versions, numeric noise, and common file-related terms.
	 *
	 * @param string $name The filename or name to clean.
	 *
	 * @return string The cleaned filename as readable text.
	 */
	private function clean_filename_to_text( $name ) {
		// If looks like path, take basename.
		$name = wp_basename( $name );

		// Remove extension.
		$name = preg_replace( '/\.[a-z0-9]{1,6}$/i', '', $name );

		// Replace separators.
		$name = str_replace( array( '-', '_', '.' ), ' ', $name );

		// Common noisy tokens to remove.
		$noise = array(
			'img',
			'image',
			'photo',
			'pic',
			'copy',
			'final',
			'v1',
			'v2',
			'v3',
			'ver',
			'version',
			'edited',
			'screen',
			'screenshot',
			'shot',
			'hdr',
			'raw',
			'jpeg',
			'jpg',
			'png',
		);
		$pattern = '/\b(' . implode( '|', array_map( 'preg_quote', $noise ) ) . ')\b/i';
		$name = preg_replace( $pattern, ' ', $name );

		// Remove long numeric sequences (timestamps).
		$name = preg_replace( '/\b\d{4,}\b/', ' ', $name );

		// Remove short numeric sequences like 2024 or 12345.
		$name = preg_replace( '/\b\d{3,4}\b/', ' ', $name );

		// Collapse spaces.
		$name = preg_replace( '/\s+/', ' ', $name );
		$name = trim( $name );

		// If empty, fallback to generic.
		if ( '' === $name ) {
			$name = __( 'Image', 'altpilot' );
		}

		$name = mb_convert_case( $name, MB_CASE_TITLE, get_bloginfo( 'charset' ) );

		/**
		 * Filter to allow modifications to cleaned filename.
		 *
		 * @param string $name The cleaned filename.
		 */
		$name = apply_filters( 'altpilot_clean_filename', $name );

		return $name;
	}

	/**
	 * Remove repeated words, extra punctuation, etc.
	 *
	 * @param string $text The text to clean.
	 *
	 * @return string The cleaned text.
	 */
	private function clean_common_noise( $text ) {
		$text = preg_replace( '/\b(copy|final|v\d+|ver|version|edited)\b/i', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		return $text;
	}
}
