<?php
/**
 * CT Core Admin Class
 *
 * Handles admin interface and settings
 *
 * @package Ct_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Class
 */
class Ct_Core_Admin {

	/**
	 * Single instance
	 *
	 * @var Ct_Core_Admin
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Ct_Core_Admin
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_export_services_csv' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'CT Core', 'ct-core' ),
			__( 'CT Core', 'ct-core' ),
			'manage_options',
			'ct-core',
			array( $this, 'render_ct_core_dashboard' ),
			'dashicons-art',
			30
		);

		add_submenu_page(
			'ct-core',
			__( 'Settings', 'ct-core' ),
			__( 'Settings', 'ct-core' ),
			'manage_options',
			'ct-core-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'edit.php?post_type=service',
			__( 'Import / Export', 'ct-core' ),
			__( 'Import / Export', 'ct-core' ),
			'manage_options',
			'ct-core-import-export',
			array( $this, 'render_import_export_page' )
		);
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		register_setting(
			'ct-core_settings',
			'ct-core_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'ct-core_svg_support',
			__( 'SVG Upload Support Settings', 'ct-core' ),
			array( $this, 'render_svg_support_section' ),
			'ct-core_settings'
		);

		add_settings_field(
			'ct-core_enable_svg_support',
			__( 'SVG Upload Support', 'ct-core' ),
			array( $this, 'render_svg_support_field' ),
			'ct-core_settings',
			'ct-core_svg_support'
		);

		add_settings_section(
			'ct-core_cf7_phone_mask',
			__( 'CF7 Phone Mask Settings', 'ct-core' ),
			array( $this, 'render_cf7_phone_mask_section' ),
			'ct-core_settings'
		);

		if ( $this->is_cf7_active() ) {
			add_settings_field(
				'ct-core_enable_cf7_phone_mask',
				__( 'Enable Phone Mask in CF7 Plugin Fields', 'ct-core' ),
				array( $this, 'render_cf7_phone_mask_field' ),
				'ct-core_settings',
				'ct-core_cf7_phone_mask'
			);

			add_settings_field(
				'ct-core_cf7_phone_mask_pattern',
				__( 'Phone Mask Pattern', 'ct-core' ),
				array( $this, 'render_cf7_phone_mask_pattern_field' ),
				'ct-core_settings',
				'ct-core_cf7_phone_mask',
			);

			add_settings_field(
				'ct-core_cf7_phone_mask_digits',
				__( 'Phone Mask Digits', 'ct-core' ),
				array( $this, 'render_cf7_phone_mask_digits_field' ),
				'ct-core_settings',
				'ct-core_cf7_phone_mask'
			);
		}   }

	/**
	 * Sanitize settings
	 *
	 * @param array $input Raw input values.
	 * @return array Sanitized values.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Base plugin features.
		if ( isset( $input['enable_features'] ) ) {
			$sanitized['enable_features'] = (bool) $input['enable_features'];
		}

		$sanitized['enable_svg_support']     = ! empty( $input['enable_svg_support'] );
		$sanitized['enable_cf7_phone_mask']  = ! empty( $input['enable_cf7_phone_mask'] );
		$sanitized['cf7_phone_mask_pattern'] = isset( $input['cf7_phone_mask_pattern'] ) ? sanitize_text_field( $input['cf7_phone_mask_pattern'] ) : '+38 (XXX) XXX-XX-XX';
		$sanitized['cf7_phone_mask_digits']  = isset( $input['cf7_phone_mask_digits'] ) ? absint( $input['cf7_phone_mask_digits'] ) : 10;

		/**
		 * Filter to allow other plugins (like Pro) to sanitize their own settings.
		 */
		return apply_filters( 'ct-core_sanitize_settings', $sanitized, $input );
	}

	/**
	 * Render general settings section
	 */
	public function render_svg_support_section() {
		echo '<p>' . esc_html__( 'Setting up support for loading SVG files.', 'ct-core' ) . '</p>';
	}

	/**
	 * Render CF7 phone mask settings section
	 */
	public function render_cf7_phone_mask_section() {
		if ( ! $this->is_cf7_active() ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'The Contact Form 7 plugin is inactive. Install and activate the plugin to use the phone number mask settings.', 'ct-core' ) . '</p></div>';
			return;
		}

		echo '<p>' . esc_html__( 'Configure Contact Form 7 phone mask options.', 'ct-core' ) . '</p>';
		$options = get_option( 'ct-core_options', array() );
		$enabled = isset( $options['enable_cf7_phone_mask'] ) ? (bool) $options['enable_cf7_phone_mask'] : true;

		if ( $enabled ) {
			echo '<div class="notice notice-warning inline ct-core-phone-mask-notice"><p>' . wp_kses( __( 'To correctly apply a mask to the contact form, you need to add the <code>phone-mask</code> class to the phone number input field.', 'ct-core' ), array( 'code' => array() ) ) . '</p></div>';
		}
	}

	/**
	 * Render SVG support checkbox.
	 */
	public function render_svg_support_field() {
		$options = get_option( 'ct-core_options', array() );
		$enabled = isset( $options['enable_svg_support'] ) ? (bool) $options['enable_svg_support'] : true;

		printf(
			'<label><input type="checkbox" name="ct-core_options[enable_svg_support]" value="1" %s /> %s</label>',
			checked( 1, $enabled, false ),
			esc_html__( 'Enable SVG upload support.', 'ct-core' )
		);
	}

	/**
	 * Render CF7 phone mask checkbox.
	 */
	public function render_cf7_phone_mask_field() {
		$options = get_option( 'ct-core_options', array() );
		$enabled = isset( $options['enable_cf7_phone_mask'] ) ? (bool) $options['enable_cf7_phone_mask'] : true;

		printf(
			'<label><input id="ct-core_options_enable_cf7_phone_mask" type="checkbox" name="ct-core_options[enable_cf7_phone_mask]" value="1" %s /> %s</label>',
			checked( 1, $enabled, false ),
			esc_html__( 'Enable Contact Form 7 phone mask.', 'ct-core' )
		);
	}

	/**
	 * Check if Contact Form 7 is active.
	 *
	 * @return bool
	 */
	public function is_cf7_active() {
		return class_exists( 'WPCF7' );
	}

	/**
	 * Render CF7 phone mask pattern field.
	 */
	public function render_cf7_phone_mask_pattern_field() {
		$options = get_option( 'ct-core_options', array() );
		$enabled = isset( $options['enable_cf7_phone_mask'] ) ? (bool) $options['enable_cf7_phone_mask'] : true;
		$pattern = isset( $options['cf7_phone_mask_pattern'] ) && $options['cf7_phone_mask_pattern'] !== '' ? $options['cf7_phone_mask_pattern'] : '+38 (XXX) XXX-XX-XX';
		$style   = $enabled ? '' : ' style="display:none;"';

		echo '<div class="ct-core-phone-mask-field"' . $style . '>';
		printf(
			'<label for="ct-core_options_cf7_phone_mask_pattern"><input type="text" id="ct-core_options_cf7_phone_mask_pattern" name="ct-core_options[cf7_phone_mask_pattern]" value="%s" class="regular-text" /></label><p class="description">%s</p>',
			esc_attr( $pattern ),
			esc_html__( 'Use X characters as digit placeholders, for example +38 (XXX) XXX-XX-XX.', 'ct-core' )
		);
		echo '</div>';
	}

	/**
	 * Render CF7 phone mask digits field.
	 */
	public function render_cf7_phone_mask_digits_field() {
		$options = get_option( 'ct-core_options', array() );
		$enabled = isset( $options['enable_cf7_phone_mask'] ) ? (bool) $options['enable_cf7_phone_mask'] : true;
		$digits  = isset( $options['cf7_phone_mask_digits'] ) ? absint( $options['cf7_phone_mask_digits'] ) : 10;
		$style   = $enabled ? '' : ' style="display:none;"';

		echo '<div class="ct-core-phone-mask-field"' . $style . '>';
		printf(
			'<label for="ct-core_options_cf7_phone_mask_digits"><input type="number" id="ct-core_options_cf7_phone_mask_digits" name="ct-core_options[cf7_phone_mask_digits]" value="%s" min="1" max="20" class="small-text" /></label><p class="description">%s</p>',
			esc_attr( $digits ),
			esc_html__( 'Enter the required number of digits for phone input fields.', 'ct-core' )
		);
		echo '</div>';
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ct-core' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'ct-core-admin',
			CT_CORE_PLUGIN_URL . 'admin/assets/css/admin-built.css',
			array(),
			CT_CORE_VERSION
		);

		wp_enqueue_script(
			'ct-core-admin',
			CT_CORE_PLUGIN_URL . 'admin/assets/js/admin.js',
			array( 'jquery' ),
			CT_CORE_VERSION,
			true
		);
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$loader = Ct_Core_Loader::instance();
		include CT_CORE_PLUGIN_DIR . 'admin/views/settings.php';
	}

	/**
	 * Render import/export page
	 */
	public function render_import_export_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$import_message = '';
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['ct_core_import_services_nonce'] ) ) {
			$import_message = $this->process_import_services();
		}

		include CT_CORE_PLUGIN_DIR . 'admin/views/import-export.php';
	}

	/**
	 * Maybe export services CSV on admin init.
	 */
	public function maybe_export_services_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['ct_core_export_services'] ) && isset( $_GET['post_type'] ) && 'service' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) {
			$this->export_services_csv();
		}
	}

	/**
	 * Export service CPT data to CSV.
	 */
	private function export_services_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$services = get_posts(
			array(
				'post_type'      => 'service',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		if ( empty( $services ) ) {
			wp_die( esc_html__( 'No service entries found to export.', 'ct-core' ) );
		}

		$filename = 'ct-core-services-export-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		$languages = $this->get_translation_languages();
		$headers   = array( 'appliance_type' );
		foreach ( $languages as $language ) {
			$headers[] = 'appliance_type_' . $language;
		}
		$headers[] = 'work_type';
		foreach ( $languages as $language ) {
			$headers[] = 'work_type_' . $language;
		}
		$headers[] = 'title';
		foreach ( $languages as $language ) {
			$headers[] = 'title_' . $language;
		}
		$headers[] = 'excerpt';
		foreach ( $languages as $language ) {
			$headers[] = 'excerpt_' . $language;
		}
		$headers[] = 'price_min';
		$headers[] = 'price_max';
		$headers[] = 'price_custom';
		foreach ( $languages as $language ) {
			$headers[] = 'price_custom_' . $language;
		}
		$headers[] = 'price_unit';
		foreach ( $languages as $language ) {
			$headers[] = 'price_unit_' . $language;
		}
		fputcsv( $output, $headers );

		$service_groups = array();
		foreach ( $services as $service ) {
			$appliance_terms = wp_get_post_terms( $service->ID, 'service_appliance_type', array( 'fields' => 'names' ) );
			$work_terms      = wp_get_post_terms( $service->ID, 'service_work_type', array( 'fields' => 'names' ) );
			if ( is_wp_error( $appliance_terms ) ) {
				$appliance_terms = array();
			}
			if ( is_wp_error( $work_terms ) ) {
				$work_terms = array();
			}
			$appliance_key = ! empty( $appliance_terms ) && ! is_wp_error( $appliance_terms ) ? $appliance_terms[0] : __( 'Uncategorized', 'ct-core' );
			$work_key      = ! empty( $work_terms ) && ! is_wp_error( $work_terms ) ? $work_terms[0] : __( 'Uncategorized', 'ct-core' );

			if ( ! isset( $service_groups[ $appliance_key ] ) ) {
				$service_groups[ $appliance_key ] = array();
			}
			if ( ! isset( $service_groups[ $appliance_key ][ $work_key ] ) ) {
				$service_groups[ $appliance_key ][ $work_key ] = array();
			}
			$service_groups[ $appliance_key ][ $work_key ][] = array(
				'service'         => $service,
				'appliance_terms' => $appliance_terms,
				'work_terms'      => $work_terms,
			);
		}

		ksort( $service_groups, SORT_NATURAL | SORT_FLAG_CASE );
		foreach ( $service_groups as &$work_groups ) {
			ksort( $work_groups, SORT_NATURAL | SORT_FLAG_CASE );
		}
		unset( $work_groups );

		foreach ( $service_groups as $work_groups ) {
			foreach ( $work_groups as $group_services ) {
				foreach ( $group_services as $group_service ) {
					$service         = $group_service['service'];
					$appliance_terms = $group_service['appliance_terms'];
					$work_terms      = $group_service['work_terms'];
					$price_units     = wp_get_post_terms( $service->ID, 'service_price_unit', array( 'fields' => 'names' ) );
					$price_unit      = ! empty( $price_units ) ? $price_units[0] : '';

					$row = array( implode( ',', $appliance_terms ) );
					foreach ( $languages as $language ) {
						$row[] = $this->get_term_translation_names( $service->ID, 'service_appliance_type', $language );
					}
					$row[] = implode( ',', $work_terms );
					foreach ( $languages as $language ) {
						$row[] = $this->get_term_translation_names( $service->ID, 'service_work_type', $language );
					}
					$row[] = $service->post_title;
					foreach ( $languages as $language ) {
						$row[] = get_post_meta( $service->ID, '_service_title_' . $language, true );
					}
					$row[] = $service->post_excerpt;
					foreach ( $languages as $language ) {
						$row[] = get_post_meta( $service->ID, '_service_excerpt_' . $language, true );
					}
					$row[] = get_post_meta( $service->ID, '_service_price_min', true );
					$row[] = get_post_meta( $service->ID, '_service_price_max', true );
					$row[] = get_post_meta( $service->ID, '_service_price_custom', true );
					foreach ( $languages as $language ) {
						$row[] = get_post_meta( $service->ID, '_service_price_custom_' . $language, true );
					}
					$row[] = $price_unit;
					foreach ( $languages as $language ) {
						$row[] = $this->get_term_translation_names( $service->ID, 'service_price_unit', $language );
					}
					fputcsv( $output, $row );
				}
			}
		}

		fclose( $output );
		exit;
	}

	/**
	 * Process imported services CSV.
	 *
	 * @return string Message to display.
	 */
	private function process_import_services() {
		if ( ! isset( $_FILES['ct_core_import_services_file'] ) || empty( $_FILES['ct_core_import_services_file']['tmp_name'] ) ) {
			return esc_html__( 'No import file uploaded.', 'ct-core' );
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ct_core_import_services_nonce'] ) ), 'ct_core_import_services' ) ) {
			return esc_html__( 'Invalid nonce. Please try again.', 'ct-core' );
		}

		$file = $_FILES['ct_core_import_services_file'];
		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			return esc_html__( 'Upload error occurred.', 'ct-core' );
		}

		$handle = fopen( $file['tmp_name'], 'r' );
		if ( false === $handle ) {
			return esc_html__( 'Unable to open uploaded file.', 'ct-core' );
		}

		$header = fgetcsv( $handle, 0, ',', '"', '\\' );
		if ( ! $header || count( $header ) < 7 ) {
			fclose( $handle );
			return esc_html__( 'Invalid CSV format.', 'ct-core' );
		}

		$header    = array_map( 'trim', $header );
		$header[0] = str_replace( "\xEF\xBB\xBF", '', $header[0] );
		$header    = array_map( 'sanitize_key', $header );

		$required_columns = array( 'appliance_type', 'work_type', 'title', 'excerpt', 'price_min', 'price_max', 'price_custom', 'price_unit' );
		if ( count( array_intersect( $required_columns, $header ) ) !== count( $required_columns ) ) {
			fclose( $handle );
			return esc_html__( 'Invalid CSV header columns.', 'ct-core' );
		}

		$created   = 0;
		$languages = $this->get_translation_languages();
		while ( false !== ( $row = fgetcsv( $handle, 0, ',', '"', '\\' ) ) ) {
			if ( ! $row || empty( array_filter( $row ) ) ) {
				continue;
			}

			$row = array_map( 'trim', $row );
			if ( count( $row ) < count( $header ) ) {
				continue;
			}

			$data = array_combine( $header, $row );
			if ( ! $data || empty( $data['title'] ) ) {
				continue;
			}

			$title   = sanitize_text_field( $data['title'] );
			$excerpt = wp_kses_post( $data['excerpt'] ?? '' );
			// $price_type = sanitize_text_field( $data['price_type'] );
			$price_min    = sanitize_text_field( $data['price_min'] );
			$price_max    = sanitize_text_field( $data['price_max'] );
			$price_custom = sanitize_text_field( $data['price_custom'] );
			$price_unit   = sanitize_text_field( $data['price_unit'] ?? '' );

			// Set price type based on price fields for compatibility with CSV files without price_type.
			$price_type = match ( true ) {
				! empty( $price_custom ) => 'custom',
				! empty( $price_min ) && ! empty( $price_max )  => 'range',
				! empty( $price_max ) => 'fixed',
				! empty( $price_min ) => 'from',
				default => 'fixed',
			};

			$service_appliance_type = array_filter( array_map( 'trim', explode( ',', $data['appliance_type'] ?? '' ) ) );
			$service_work_type      = array_filter( array_map( 'trim', explode( ',', $data['work_type'] ?? '' ) ) );

			$existing  = $this->get_service_by_title( $title );
			$post_data = array(
				'post_title'   => $title,
				'post_excerpt' => $excerpt,
				'post_status'  => 'publish',
				'post_type'    => 'service',
			);

			if ( $existing ) {
				$post_data['ID'] = $existing->ID;
				$post_id         = wp_update_post( $post_data );
			} else {
				$post_id = wp_insert_post( $post_data );
			}

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			foreach ( $languages as $language ) {
				$this->save_service_translation_meta( $post_id, $data, $language );
			}

			update_post_meta( $post_id, '_service_price_type', $price_type );
			if ( 'fixed' === $price_type ) {
				update_post_meta( $post_id, '_service_price_max', $price_max );
				delete_post_meta( $post_id, '_service_price_min' );
				delete_post_meta( $post_id, '_service_price_custom' );
			} elseif ( 'from' === $price_type ) {
				update_post_meta( $post_id, '_service_price_min', $price_min );
				delete_post_meta( $post_id, '_service_price_max' );
				delete_post_meta( $post_id, '_service_price_custom' );
			} elseif ( 'range' === $price_type ) {
				update_post_meta( $post_id, '_service_price_min', $price_min );
				update_post_meta( $post_id, '_service_price_max', $price_max );
				delete_post_meta( $post_id, '_service_price_custom' );
			} else {
				update_post_meta( $post_id, '_service_price_custom', $price_custom );
				delete_post_meta( $post_id, '_service_price_min' );
				delete_post_meta( $post_id, '_service_price_max' );
			}

			if ( ! empty( $service_appliance_type ) ) {
				$terms = array();
				foreach ( $service_appliance_type as $term_name ) {
					$term_id = $this->get_term_id_or_create_by_name( $term_name, 'service_appliance_type' );
					if ( $term_id ) {
						$terms[] = $term_id;
					}
				}
				if ( ! empty( $terms ) ) {
					wp_set_object_terms( $post_id, $terms, 'service_appliance_type' );
				}
			} else {
				// Clear appliance type terms if empty
				wp_set_object_terms( $post_id, array(), 'service_appliance_type' );
			}
			$this->save_imported_term_translations( $service_appliance_type, $data, 'service_appliance_type', $languages );

			if ( ! empty( $service_work_type ) ) {
				$terms = array();
				foreach ( $service_work_type as $term_name ) {
					$term_id = $this->get_term_id_or_create_by_name( $term_name, 'service_work_type' );
					if ( $term_id ) {
						$terms[] = $term_id;
					}
				}
				if ( ! empty( $terms ) ) {
					wp_set_object_terms( $post_id, $terms, 'service_work_type' );
				}
			} else {
				// Clear work type terms if empty
				wp_set_object_terms( $post_id, array(), 'service_work_type' );
			}
			$this->save_imported_term_translations( $service_work_type, $data, 'service_work_type', $languages );

			// Set price unit if provided, otherwise clear it
			if ( ! empty( $price_unit ) ) {
				$term_id = $this->get_term_id_or_create_by_name( $price_unit, 'service_price_unit' );
				if ( $term_id ) {
					wp_set_object_terms( $post_id, array( $term_id ), 'service_price_unit' );
				}
			} else {
				// Clear price unit terms if price_unit is empty
				wp_set_object_terms( $post_id, array(), 'service_price_unit' );
			}
			$this->save_imported_term_translations( array( $price_unit ), $data, 'service_price_unit', $languages );

			++$created;
		}

		fclose( $handle );

		/* translators: %d: number of imported service items. */
		return sprintf( esc_html__( 'Imported %d service items.', 'ct-core' ), $created );
	}

	/**
	 * Get configured Polylang language slugs.
	 *
	 * @return string[]
	 */
	private function get_translation_languages() {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return array();
		}

		$languages = pll_languages_list( array( 'fields' => 'slug' ) );
		$default   = function_exists( 'pll_default_language' ) ? pll_default_language( 'slug' ) : '';

		return array_values(
			array_filter(
				array_map( 'sanitize_key', (array) $languages ),
				function ( $language ) use ( $default ) {
					return '' !== $language && $language !== $default;
				}
			)
		);
	}

	/**
	 * Get translated term names assigned to a service.
	 *
	 * @param int    $post_id  Service ID.
	 * @param string $taxonomy Taxonomy name.
	 * @param string $language Language slug.
	 * @return string
	 */
	private function get_term_translation_names( $post_id, $taxonomy, $language ) {
		$terms = wp_get_post_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$names = array();
		foreach ( $terms as $term ) {
			$translation = get_term_meta( $term->term_id, '_service_term_name_' . $language, true );
			$names[]     = '' !== $translation ? $translation : $term->name;
		}

		return implode( ',', $names );
	}

	/**
	 * Save translated service title and excerpt from an import row.
	 *
	 * @param int    $post_id  Service ID.
	 * @param array  $data     Imported row.
	 * @param string $language Language slug.
	 */
	private function save_service_translation_meta( $post_id, $data, $language ) {
		foreach ( array( 'title', 'excerpt', 'price_custom' ) as $field ) {
			$column = $field . '_' . $language;
			if ( ! array_key_exists( $column, $data ) ) {
				continue;
			}

			$meta_key = '_service_' . $field . '_' . $language;
			$value    = 'excerpt' === $field ? wp_kses_post( $data[ $column ] ) : sanitize_text_field( $data[ $column ] );
			if ( '' === $value ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Save translated taxonomy term names from an import row.
	 *
	 * @param array    $term_names Base-language term names.
	 * @param array    $data       Imported row.
	 * @param string   $taxonomy    Taxonomy name.
	 * @param string[] $languages   Language slugs.
	 */
	private function save_imported_term_translations( $term_names, $data, $taxonomy, $languages ) {
		$term_names = array_values( array_filter( array_map( 'trim', (array) $term_names ) ) );
		if ( empty( $term_names ) ) {
			return;
		}

		$term_ids = array();
		foreach ( $term_names as $term_name ) {
			$term_id = $this->get_term_id_by_name( $term_name, $taxonomy );
			if ( $term_id ) {
				$term_ids[] = $term_id;
			}
		}

		foreach ( $languages as $language ) {
			$column = $this->get_taxonomy_translation_column( $taxonomy, $language );
			if ( ! array_key_exists( $column, $data ) ) {
				continue;
			}

			$translations = array_map( 'trim', explode( ',', $data[ $column ] ) );
			foreach ( $term_ids as $index => $term_id ) {
				$meta_key = '_service_term_name_' . $language;
				$value    = isset( $translations[ $index ] ) ? sanitize_text_field( $translations[ $index ] ) : '';
				if ( '' === $value ) {
					delete_term_meta( $term_id, $meta_key );
				} else {
					update_term_meta( $term_id, $meta_key, $value );
				}
			}
		}
	}

	/**
	 * Get a CSV column name for translated taxonomy terms.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $language Language slug.
	 * @return string
	 */
	private function get_taxonomy_translation_column( $taxonomy, $language ) {
		$columns = array(
			'service_appliance_type' => 'appliance_type_',
			'service_work_type'      => 'work_type_',
			'service_price_unit'     => 'price_unit_',
		);

		return ( $columns[ $taxonomy ] ?? '' ) . $language;
	}

	/**
	 * Get existing service by title.
	 *
	 * @param string $title Service title.
	 * @return WP_Post|null
	 */
	private function get_service_by_title( $title ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'service',
				'post_status'    => 'any',
				'title'          => $title,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( $query->have_posts() ) {
			return get_post( $query->posts[0] );
		}

		return null;
	}

	/**
	 * Get term ID by name if the term exists.
	 *
	 * @param string $term_name Term name.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int|null
	 */
	private function get_term_id_by_name( $term_name, $taxonomy ) {
		$term_name = sanitize_text_field( $term_name );
		if ( empty( $term_name ) ) {
			return null;
		}

		$term = get_term_by( 'name', $term_name, $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}

		$term = get_term_by( 'slug', sanitize_title( $term_name ), $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}

		return null;
	}

	/**
	 * Get term ID by name, creating the term if needed.
	 *
	 * @param string $term_name Term name.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int|null
	 */
	private function get_term_id_or_create_by_name( $term_name, $taxonomy ) {
		$term_name = sanitize_text_field( $term_name );
		if ( empty( $term_name ) ) {
			return null;
		}

		$term_id = $this->get_term_id_by_name( $term_name, $taxonomy );
		if ( $term_id ) {
			return $term_id;
		}

		$term = wp_insert_term( $term_name, $taxonomy );
		if ( ! is_wp_error( $term ) ) {
			return is_array( $term ) ? (int) $term['term_id'] : (int) $term[0];
		}

		return null;
	}

	/**
	 * Render CT Core dashboard page.
	 */
	public function render_ct_core_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include CT_CORE_PLUGIN_DIR . 'admin/views/dashboard.php';
	}
}
