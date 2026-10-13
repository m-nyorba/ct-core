<?php
/**
 * CT Core Plugin Loader
 *
 * Handles autoloading, module registration, and plugin initialization
 *
 * @package Ct_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Loader Class
 */
class Ct_Core_Loader {

	/**
	 * Single instance
	 *
	 * @var Ct_Core_Loader
	 */
	private static $instance = null;

	/**
	 * List of registered block script handles for translation attachment
	 *
	 * @var array
	 */
	private $block_script_handles = array();



	/**
	 * Get singleton instance
	 *
	 * @return Ct_Core_Loader
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files
	 */
	private function load_dependencies() {
		// Core classes.
		require_once CT_CORE_PLUGIN_DIR . 'includes/class-ct-core-api.php';
		require_once CT_CORE_PLUGIN_DIR . 'includes/class-ct-core-admin.php';
		require_once CT_CORE_PLUGIN_DIR . 'includes/class-ct-core-svg-support.php';
		require_once CT_CORE_PLUGIN_DIR . 'includes/class-ct-core-cf7-phone-mask.php';
		require_once CT_CORE_PLUGIN_DIR . 'includes/class-ct-core-services-cpt.php';
		require_once CT_CORE_PLUGIN_DIR . 'includes/class-ct-core-services-helper.php';

		// Procedural helpers and registrations.
		$this->load_inc_files();
	}

	/**
	 * Load all PHP files from inc directory.
	 *
	 * Keeps plugin bootstrap file minimal and centralizes include logic.
	 * Files are sorted for deterministic loading order.
	 */
	private function load_inc_files() {
		$inc_files = glob( CT_CORE_PLUGIN_DIR . 'inc/*.php' );
		if ( empty( $inc_files ) ) {
			return;
		}

		sort( $inc_files );
		foreach ( $inc_files as $file ) {
			require_once $file;
		}
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Load text domain early so CPT/taxonomy labels are translated.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Initialize blocks.
		add_action( 'init', array( $this, 'register_blocks' ) );

		// Ensure JS translations are attached to editor scripts (run after block registration).
		add_action( 'init', array( $this, 'set_script_translations' ), 20 );

		// Initialize feature classes.
		Ct_Core_SVG_Support::instance();
		Ct_Core_CF7_Phone_Mask::instance();

		// Initialize CPT and taxonomies.
		Ct_Core_Services_CPT::instance();

		// Initialize API.
		Ct_Core_API::instance();

		// Initialize admin.
		if ( is_admin() ) {
			Ct_Core_Admin::instance();
		}
	}

	/**
	 * Attach JS translations to registered block script handles.
	 */
	public function set_script_translations() {
		if ( ! function_exists( 'wp_set_script_translations' ) || empty( $this->block_script_handles ) ) {
			return;
		}

		$locale_path = CT_CORE_PLUGIN_DIR . 'languages';

		// Attach translations to all registered block script handles.
		foreach ( $this->block_script_handles as $handle ) {
			wp_set_script_translations( $handle, 'ct-core', $locale_path );
		}
	}

	/**
	 * Load plugin text domain
	 */
	public function load_textdomain() {
		// Load standard plugin textdomain (uses a path relative to WP plugins dir).
		load_plugin_textdomain(
			'ct-core',
			false,
			dirname( plugin_basename( CT_CORE_PLUGIN_FILE ) ) . '/languages'
		);

		// Load the translation file matching the current WordPress locale.
		$locale  = apply_filters( 'plugin_locale', get_locale(), 'ct-core' );
		$mo_file = CT_CORE_PLUGIN_DIR . 'languages/' . $locale . '.mo';
		if ( file_exists( $mo_file ) ) {
			load_textdomain( 'ct-core', $mo_file );
		}
	}

	/**
	 * Register Gutenberg blocks
	 */
	public function register_blocks() {
		// Register basic blocks for the plugin.
		$this->register_basic_blocks();

		// Allow additional blocks to be registered via filter.
		do_action( 'ct-core_register_blocks' );
	}

	/**
	 * Register basic blocks
	 */
	private function register_basic_blocks() {
		$blocks_dir = CT_CORE_PLUGIN_DIR . 'build/';
		if ( ! is_dir( $blocks_dir ) ) {
			return;
		}

		// Use RecursiveDirectoryIterator to find all block.json files.
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $blocks_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'block.json' === $file->getFilename() ) {
				$block_dir  = $file->getPath();
				$block_name = basename( $block_dir );

				// Check if this block has an index.asset.php (compiled from src with wp-scripts).
				$asset_file = $block_dir . '/index.asset.php';
				if ( file_exists( $asset_file ) ) {
					// Register the script explicitly so we can attach translations.
					$script_file   = CT_CORE_PLUGIN_URL . 'build/' . $block_name . '/index.js';
					$asset         = include $asset_file;
					$script_handle = 'ct-core-' . $block_name;

					wp_register_script(
						$script_handle,
						$script_file,
						isset( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
						isset( $asset['version'] ) ? $asset['version'] : false
					);

					// Track this script handle so we can attach translations later.
					$this->block_script_handles[] = $script_handle;

					// Register the block with the explicit script handle.
					register_block_type( $block_dir, array( 'editor_script' => $script_handle ) );
				} else {
					// Fallback: register block normally using block.json metadata.
					register_block_type( $file->getPathname() );
				}
			}
		}
	}
}
