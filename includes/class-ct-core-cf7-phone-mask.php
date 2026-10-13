<?php
/**
 * CT Core Contact Form 7 Phone Mask
 *
 * Enqueues a phone mask script when Contact Form 7 is active and a form appears on the page.
 *
 * @package Ct_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CF7 phone mask class.
 */
class Ct_Core_CF7_Phone_Mask {

	/**
	 * Single instance.
	 *
	 * @var Ct_Core_CF7_Phone_Mask|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Ct_Core_CF7_Phone_Mask
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue frontend phone mask script when CF7 is present.
	 */
	public function enqueue_scripts() {
		if ( is_admin() ) {
			return;
		}

		if ( ! class_exists( 'WPCF7' ) ) {
			return;
		}

		if ( ! $this->phone_mask_enabled() ) {
			return;
		}

		if ( ! $this->page_has_cf7_form() ) {
			return;
		}

		wp_enqueue_script(
			'ct-core-cf7-phone-mask',
			CT_CORE_PLUGIN_URL . 'assets/js/cf7-phone-mask.js',
			array( 'jquery' ),
			CT_CORE_VERSION,
			true
		);

		$settings = $this->get_phone_mask_settings();
		wp_localize_script( 'ct-core-cf7-phone-mask', 'ctCoreCf7PhoneMask', $settings );
	}

	/**
	 * Check if CF7 phone mask option is enabled.
	 *
	 * @return bool
	 */
	private function phone_mask_enabled() {
		$options = get_option( 'ct-core_options', array() );
		if ( ! is_array( $options ) ) {
			return true;
		}

		return isset( $options['enable_cf7_phone_mask'] ) ? (bool) $options['enable_cf7_phone_mask'] : true;
	}

	/**
	 * Get phone mask settings for frontend script.
	 *
	 * @return array
	 */
	private function get_phone_mask_settings() {
		$options = get_option( 'ct-core_options', array() );
		$pattern = isset( $options['cf7_phone_mask_pattern'] ) && $options['cf7_phone_mask_pattern'] !== '' ? $options['cf7_phone_mask_pattern'] : '+38 (XXX) XXX-XX-XX';
		$digits  = isset( $options['cf7_phone_mask_digits'] ) ? absint( $options['cf7_phone_mask_digits'] ) : 10;

		if ( $digits <= 0 ) {
			$digits = 10;
		}

		return array(
			'pattern'  => $pattern,
			'digits'   => $digits,
			'messages' => array(
				'invalid_phone' => esc_html__( 'Please enter a complete phone number.', 'ct-core' ),
			),
		);
	}

	/**
	 * Detect if the current page contains a Contact Form 7 form with a phone field.
	 *
	 * @return bool
	 */
	private function page_has_cf7_form() {
		if ( ! is_singular() ) {
			return false;
		}

		global $post;
		if ( empty( $post ) || ! is_a( $post, 'WP_Post' ) ) {
			return false;
		}

		$content = $post->post_content;

		if ( $this->content_has_cf7_phone_field( $content ) ) {
			return true;
		}

		if ( has_shortcode( $content, 'contact-form-7' ) ) {
			return true;
		}

		if ( function_exists( 'has_block' ) && ( has_block( 'contact-form-7/contact-form', $post ) || has_block( 'wpcf7/contact-form', $post ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Detect if CF7 content contains a phone field or a phone-mask class.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	private function content_has_cf7_phone_field( $content ) {
		if ( preg_match( '/\[(?:tel|tel\*|phone|phone\*)(?:[^\]]*)\]/i', $content ) ) {
			return true;
		}

		if ( preg_match( '/<input[^>]+type=["\']tel["\'][^>]*>/i', $content ) ) {
			return true;
		}

		if ( stripos( $content, 'phone-mask' ) !== false ) {
			return true;
		}

		return false;
	}
}
