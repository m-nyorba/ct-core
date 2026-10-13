<?php
/**
 * CT Core SVG Support
 *
 * Handles SVG upload support based on plugin settings.
 *
 * @package Ct_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SVG support class.
 */
class Ct_Core_SVG_Support {

	/**
	 * Single instance.
	 *
	 * @var Ct_Core_SVG_Support|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Ct_Core_SVG_Support
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'maybe_register_svg_support' ) );
	}

	/**
	 * Register SVG support hooks if enabled.
	 */
	public function maybe_register_svg_support() {
		if ( ! $this->svg_support_enabled() ) {
			return;
		}

		add_filter( 'upload_mimes', array( $this, 'enable_svg_mime_types' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_svg_mime_type' ), 10, 4 );
	}

	/**
	 * Check if SVG support is enabled in plugin settings.
	 *
	 * @return bool
	 */
	private function svg_support_enabled() {
		$options = get_option( 'ct-core_options', array() );
		if ( ! is_array( $options ) ) {
			return true;
		}

		return isset( $options['enable_svg_support'] ) ? (bool) $options['enable_svg_support'] : true;
	}

	/**
	 * Allow SVG mime types.
	 *
	 * @param array $mimes Allowed mime types.
	 * @return array
	 */
	public function enable_svg_mime_types( $mimes ) {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Correct SVG mime type on upload.
	 *
	 * @param array  $data     File type data.
	 * @param string $file     Full path to file.
	 * @param string $filename File name.
	 * @param array  $mimes    Allowed mime types.
	 * @return array
	 */
	public function fix_svg_mime_type( $data, $file, $filename, $mimes ) {
		$ext = pathinfo( $filename, PATHINFO_EXTENSION );
		if ( 'svg' === $ext ) {
			$data['type'] = 'image/svg+xml';
			$data['ext']  = 'svg';
		}

		return $data;
	}
}
