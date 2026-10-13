<?php
/**
 * CT Core API Class
 *
 * Provides hooks and filters for theme integration
 *
 * @package Ct_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Class
 */
class Ct_Core_API {

	/**
	 * Single instance
	 *
	 * @var Ct_Core_API
	 */
	private static $instance = null;

	/**
	 * Registered features
	 *
	 * @var array
	 */
	private $features = array();

	/**
	 * Get singleton instance
	 *
	 * @return Ct_Core_API
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_default_features' ) );
	}

	/**
	 * Register default features
	 */
	public function register_default_features() {
		$this->register_feature(
			'premium_blocks',
			array(
				'label'       => __( 'Premium Blocks', 'ct-core' ),
				'description' => __( 'Access to exclusive block patterns and blocks', 'ct-core' ),
				'requires'    => 'license',
			)
		);

		$this->register_feature(
			'demo_import',
			array(
				'label'       => __( 'Demo Import', 'ct-core' ),
				'description' => __( 'One-click demo content import', 'ct-core' ),
				'requires'    => 'license',
			)
		);

		$this->register_feature(
			'advanced_customization',
			array(
				'label'       => __( 'Advanced Customization', 'ct-core' ),
				'description' => __( 'Extended theme customization options', 'ct-core' ),
				'requires'    => 'license',
			)
		);

		/**
		 * Allow themes to register custom features
		 *
		 * @param Ct_Core_API $api API instance
		 */
		do_action( 'ct-core_register_features', $this );
	}

	/**
	 * Register a feature
	 *
	 * @param string $feature_id Feature ID.
	 * @param array  $args       Feature arguments.
	 */
	public function register_feature( $feature_id, $args = array() ) {
		$defaults = array(
			'label'       => '',
			'description' => '',
			'requires'    => 'license', // Options: license, none, theme_support.
			'callback'    => null,
		);

		$this->features[ $feature_id ] = wp_parse_args( $args, $defaults );
	}

	/**
	 * Check if feature is available
	 *
	 * @param string $feature_id Feature ID.
	 * @return bool
	 */
	public function is_feature_available( $feature_id ) {
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return false;
		}

		$feature = $this->features[ $feature_id ];

		// Check requirements.
		switch ( $feature['requires'] ) {
			case 'license':
				// Allow Pro plugin to hook in and check license.
				return apply_filters( 'ct-core_is_feature_available', false, $feature_id, $feature );

			case 'theme_support':
				return current_theme_supports( 'ct-core-integration' );

			case 'none':
			default:
				return true;
		}
	}

	/**
	 * Get all registered features
	 *
	 * @return array
	 */
	public function get_features() {
		return apply_filters( 'ct-core_get_features', $this->features );
	}

	/**
	 * Get feature details
	 *
	 * @param string $feature_id Feature ID.
	 * @return array|false
	 */
	public function get_feature( $feature_id ) {
		return isset( $this->features[ $feature_id ] ) ? $this->features[ $feature_id ] : false;
	}

	/**
	 * Check if current theme declares CT Core support
	 *
	 * @return bool
	 */
	public function theme_has_support() {
		return current_theme_supports( 'ct-core-integration' );
	}

	/**
	 * Get theme support features
	 *
	 * @return array
	 */
	public function get_theme_support_features() {
		if ( ! $this->theme_has_support() ) {
			return array();
		}

		$support = get_theme_support( 'ct-core-integration' );
		return isset( $support[0]['features'] ) ? $support[0]['features'] : array();
	}
}
