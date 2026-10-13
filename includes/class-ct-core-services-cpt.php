<?php
/**
 * CT Core Services CPT
 *
 * @package Ct_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ct_Core_Services_CPT {

	/**
	 * Single instance.
	 *
	 * @var Ct_Core_Services_CPT|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Ct_Core_Services_CPT
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
		add_action( 'init', array( $this, 'register_post_type_and_taxonomies' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'edit_form_after_title', array( $this, 'render_translation_titles' ) );
		add_filter( 'gettext', array( $this, 'filter_service_excerpt_label' ), 10, 3 );
		add_action( 'service_appliance_type_add_form_fields', array( $this, 'render_taxonomy_add_fields' ) );
		add_action( 'service_work_type_add_form_fields', array( $this, 'render_taxonomy_add_fields' ) );
		add_action( 'service_price_unit_add_form_fields', array( $this, 'render_taxonomy_add_fields' ) );
		add_action( 'service_appliance_type_edit_form_fields', array( $this, 'render_taxonomy_edit_fields' ), 10, 2 );
		add_action( 'service_work_type_edit_form_fields', array( $this, 'render_taxonomy_edit_fields' ), 10, 2 );
		add_action( 'service_price_unit_edit_form_fields', array( $this, 'render_taxonomy_edit_fields' ), 10, 2 );
		add_action( 'created_term', array( $this, 'save_taxonomy_translation' ), 10, 3 );
		add_action( 'edited_term', array( $this, 'save_taxonomy_translation' ), 10, 3 );
		add_action( 'save_post_service', array( $this, 'save_service_meta' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register service CPT and taxonomies.
	 */
	public function register_post_type_and_taxonomies() {
		$labels = array(
			'name'               => __( 'Services', 'ct-core' ),
			'singular_name'      => __( 'Service', 'ct-core' ),
			'add_new_item'       => __( 'Add New Service', 'ct-core' ),
			'edit_item'          => __( 'Edit Service', 'ct-core' ),
			'new_item'           => __( 'New Service', 'ct-core' ),
			'all_items'          => __( 'All Services', 'ct-core' ),
			'view_item'          => __( 'View Service', 'ct-core' ),
			'search_items'       => __( 'Search Services', 'ct-core' ),
			'not_found'          => __( 'No services found', 'ct-core' ),
			'not_found_in_trash' => __( 'No services found in Trash', 'ct-core' ),
			'menu_name'          => __( 'Services', 'ct-core' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => array( 'title', 'excerpt', 'page-attributes' ),
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-hammer',
			'rewrite'             => false,
			'show_in_rest'        => true,
		);

		register_post_type( 'service', $args );

		$service_meta_fields = array(
			'_service_price_type',
			'_service_price_min',
			'_service_price_max',
			'_service_price_custom',
		);

		foreach ( $service_meta_fields as $meta_field ) {
			register_post_meta(
				'service',
				$meta_field,
				array(
					'single'            => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => true,
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}

		foreach ( $this->get_translation_languages() as $language ) {
			foreach ( array( 'title', 'excerpt', 'price_custom' ) as $field ) {
				register_post_meta(
					'service',
					'_service_' . $field . '_' . $language,
					array(
						'single'            => true,
						'type'              => 'string',
						'sanitize_callback' => 'excerpt' === $field ? 'wp_kses_post' : 'sanitize_text_field',
						'show_in_rest'      => true,
						'auth_callback'     => function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}

		$taxonomy_labels = array(
			'name'              => __( 'Appliance Types', 'ct-core' ),
			'singular_name'     => __( 'Appliance Type', 'ct-core' ),
			'search_items'      => __( 'Search Appliance Types', 'ct-core' ),
			'all_items'         => __( 'All Appliance Types', 'ct-core' ),
			'parent_item'       => __( 'Parent Appliance Type', 'ct-core' ),
			'parent_item_colon' => __( 'Parent Appliance Type:', 'ct-core' ),
			'edit_item'         => __( 'Edit Appliance Type', 'ct-core' ),
			'update_item'       => __( 'Update Appliance Type', 'ct-core' ),
			'add_new_item'      => __( 'Add New Appliance Type', 'ct-core' ),
			'new_item_name'     => __( 'New Appliance Type Name', 'ct-core' ),
			'menu_name'         => __( 'Appliance Types', 'ct-core' ),
		);

		register_taxonomy(
			'service_appliance_type',
			'service',
			array(
				'labels'            => $taxonomy_labels,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'appliance-type' ),
			)
		);

		$taxonomy_labels = array(
			'name'              => __( 'Work Types', 'ct-core' ),
			'singular_name'     => __( 'Work Type', 'ct-core' ),
			'search_items'      => __( 'Search Work Types', 'ct-core' ),
			'all_items'         => __( 'All Work Types', 'ct-core' ),
			'parent_item'       => __( 'Parent Work Type', 'ct-core' ),
			'parent_item_colon' => __( 'Parent Work Type:', 'ct-core' ),
			'edit_item'         => __( 'Edit Work Type', 'ct-core' ),
			'update_item'       => __( 'Update Work Type', 'ct-core' ),
			'add_new_item'      => __( 'Add New Work Type', 'ct-core' ),
			'new_item_name'     => __( 'New Work Type Name', 'ct-core' ),
			'menu_name'         => __( 'Work Types', 'ct-core' ),
		);

		register_taxonomy(
			'service_work_type',
			'service',
			array(
				'labels'            => $taxonomy_labels,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'work-type' ),
			)
		);

		// Register Price Unit Taxonomy
		$unit_labels = array(
			'name'          => __( 'Price Units', 'ct-core' ),
			'singular_name' => __( 'Price Unit', 'ct-core' ),
			'search_items'  => __( 'Search Price Units', 'ct-core' ),
			'all_items'     => __( 'All Price Units', 'ct-core' ),
			'edit_item'     => __( 'Edit Price Unit', 'ct-core' ),
			'update_item'   => __( 'Update Price Unit', 'ct-core' ),
			'add_new_item'  => __( 'Add New Price Unit', 'ct-core' ),
			'new_item_name' => __( 'New Price Unit Name', 'ct-core' ),
			'menu_name'     => __( 'Price Units', 'ct-core' ),
		);

		register_taxonomy(
			'service_price_unit',
			'service',
			array(
				'labels'            => $unit_labels,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'price-unit' ),
			)
		);

		foreach ( $this->get_translation_languages() as $language ) {
			foreach ( array( 'service_appliance_type', 'service_work_type', 'service_price_unit' ) as $taxonomy ) {
				register_term_meta(
					$taxonomy,
					'_service_term_name_' . $language,
					array(
						'single'            => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'show_in_rest'      => true,
						'auth_callback'     => function () {
							return current_user_can( 'manage_categories' );
						},
					)
				);
			}
		}
	}

	/**
	 * Register custom REST API fields for the service post type.
	 */
	public function register_rest_fields() {
		register_rest_field(
			'service',
			'localized_title',
			array(
				'get_callback'    => function ( $post ) {
					return ct_core_get_localized_service_text( $post['id'], 'title' );
				},
				'update_callback' => null,
				'schema'          => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
			)
		);

		register_rest_field(
			'service',
			'localized_excerpt',
			array(
				'get_callback'    => function ( $post ) {
					return ct_core_get_localized_service_text( $post['id'], 'excerpt' );
				},
				'update_callback' => null,
				'schema'          => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
			)
		);

		register_rest_field(
			'service',
			'formatted_price',
			array(
				'get_callback'    => function ( $post ) {
					return function_exists( 'ct_core_get_formatted_price' )
						? ct_core_get_formatted_price( $post['id'] )
						: '';
				},
				'update_callback' => null,
				'schema'          => array(
					'type'        => 'string',
					'description' => __( 'Formatted service price string', 'ct-core' ),
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		// Register taxonomy fields with term objects (name + id)
		register_rest_field(
			'service',
			'service_appliance_type',
			array(
				'get_callback'    => function ( $post ) {
					$terms = ct_core_get_localized_service_terms( $post['id'], 'service_appliance_type' );
					return array_map(
						function ( $term ) {
							return array(
								'id'   => $term->term_id,
								'name' => $term->name,
							);
						},
						$terms
					);
				},
				'update_callback' => null,
				'schema'          => array(
					'type'        => 'array',
					'description' => __( 'Service appliance types', 'ct-core' ),
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		register_rest_field(
			'service',
			'service_work_type',
			array(
				'get_callback'    => function ( $post ) {
					$terms = ct_core_get_localized_service_terms( $post['id'], 'service_work_type' );
					return array_map(
						function ( $term ) {
							return array(
								'id'   => $term->term_id,
								'name' => $term->name,
							);
						},
						$terms
					);
				},
				'update_callback' => null,
				'schema'          => array(
					'type'        => 'array',
					'description' => __( 'Service work types', 'ct-core' ),
					'context'     => array( 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Register meta boxes for service price.
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'ct-core-service-price',
			__( 'Service Price', 'ct-core' ),
			array( $this, 'render_price_meta_box' ),
			'service',
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue admin scripts for service meta box.
	 */
	public function enqueue_admin_assets( $hook ) {
		global $post;

		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		if ( empty( $post ) || 'service' !== $post->post_type ) {
			return;
		}

		wp_enqueue_script(
			'ct-core-service-admin',
			CT_CORE_PLUGIN_URL . 'admin/assets/js/service-admin.js',
			array( 'jquery' ),
			CT_CORE_VERSION,
			true
		);
	}

	/**
	 * Render price meta box.
	 */
	public function render_price_meta_box( $post ) {
		wp_nonce_field( 'ct_core_service_price_nonce', 'ct_core_service_price_nonce' );

		$price_type   = get_post_meta( $post->ID, '_service_price_type', true );
		$price_min    = get_post_meta( $post->ID, '_service_price_min', true );
		$price_max    = get_post_meta( $post->ID, '_service_price_max', true );
		$price_custom = get_post_meta( $post->ID, '_service_price_custom', true );

		if ( ! in_array( $price_type, array( 'fixed', 'from', 'range', 'custom' ), true ) ) {
			$price_type = 'from';
		}

		// Get price unit terms from taxonomy
		$price_unit_terms = get_terms(
			array(
				'taxonomy'   => 'service_price_unit',
				'hide_empty' => false,
			)
		);

		// Get current price unit terms
		$price_units   = wp_get_post_terms( $post->ID, 'service_price_unit', array( 'fields' => 'ids' ) );
		$selected_unit = ! empty( $price_units ) ? $price_units[0] : '';
		?>
		<div class="ct-core-service-price-field">
			<p>
				<label>
					<input type="radio" name="_service_price_type" value="from" <?php checked( $price_type, 'from' ); ?> />
					<?php esc_html_e( 'Price from', 'ct-core' ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" name="_service_price_type" value="fixed" <?php checked( $price_type, 'fixed' ); ?> />
					<?php esc_html_e( 'Fixed price', 'ct-core' ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" name="_service_price_type" value="range" <?php checked( $price_type, 'range' ); ?> />
					<?php esc_html_e( 'Price range', 'ct-core' ); ?>
				</label>
			</p>
			<p>
				<label>
					<input type="radio" name="_service_price_type" value="custom" <?php checked( $price_type, 'custom' ); ?> />
					<?php esc_html_e( 'Custom text', 'ct-core' ); ?>
				</label>
			</p>

			<div class="ct-core-price-fields ct-core-price-from" style="display: <?php echo 'from' === $price_type ? 'block' : 'none'; ?>;">
				<label for="ct-core-service-price-from"><?php esc_html_e( 'Price from', 'ct-core' ); ?></label>
				<input type="number" step="1" min="0" id="ct-core-service-price-from" name="_service_price_min" value="<?php echo esc_attr( $price_min ); ?>" class="widefat" />
			</div>

			<div class="ct-core-price-fields ct-core-price-fixed" style="display: <?php echo 'fixed' === $price_type ? 'block' : 'none'; ?>;">
				<label for="ct-core-service-price-fixed"><?php esc_html_e( 'Price', 'ct-core' ); ?></label>
				<input type="number" step="1" min="0" id="ct-core-service-price-fixed" name="_service_price_max" value="<?php echo esc_attr( $price_max ); ?>" class="widefat" />
			</div>

			<div class="ct-core-price-fields ct-core-price-range" style="display: <?php echo 'range' === $price_type ? 'block' : 'none'; ?>;">
				<label for="ct-core-service-price-range-min"><?php esc_html_e( 'Price from', 'ct-core' ); ?></label>
				<input type="number" step="1" min="0" id="ct-core-service-price-range-min" name="_service_price_min" value="<?php echo esc_attr( $price_min ); ?>" class="widefat" />
				<label for="ct-core-service-price-range-max"><?php esc_html_e( 'Price to', 'ct-core' ); ?></label>
				<input type="number" step="1" min="0" id="ct-core-service-price-range-max" name="_service_price_max" value="<?php echo esc_attr( $price_max ); ?>" class="widefat" />
			</div>

			<div class="ct-core-price-fields ct-core-price-custom" style="display: <?php echo 'custom' === $price_type ? 'block' : 'none'; ?>;">
				<label for="_service_price_custom"><?php esc_html_e( 'Custom price text', 'ct-core' ); ?></label>
				<input type="text" id="_service_price_custom" name="_service_price_custom" value="<?php echo esc_attr( $price_custom ); ?>" class="widefat" />
				<?php foreach ( $this->get_translation_languages() as $language ) : ?>
					<?php $custom_key = '_service_price_custom_' . $language; ?>
					<p class="ct-core-service-price-translation" style="margin: 8px 0 0;">
						<label for="<?php echo esc_attr( $custom_key ); ?>">
							<?php echo esc_html( strtoupper( $language ) . ' ' . __( 'Custom price text', 'ct-core' ) ); ?>
						</label>
						<input type="text" id="<?php echo esc_attr( $custom_key ); ?>" name="<?php echo esc_attr( $custom_key ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, $custom_key, true ) ); ?>" class="widefat" />
					</p>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Price Unit Selection (After Prices) -->
		<div class="ct-core-service-price-unit-section" style="margin-top: 20px; border-top: 1px solid #e0e0e0;">
			<h4><?php esc_html_e( 'Price Unit', 'ct-core' ); ?></h4>
			<select id="ct-core-service-price-unit" name="service_price_unit" class="widefat">
				<option value=""><?php esc_html_e( '— Select Price Unit —', 'ct-core' ); ?></option>
				<?php if ( ! is_wp_error( $price_unit_terms ) ) : ?>
					<?php foreach ( $price_unit_terms as $term ) : ?>
						<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $selected_unit, $term->term_id ); ?>>
							<?php echo esc_html( $term->name ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</div>

		<?php foreach ( $this->get_translation_languages() as $language ) : ?>
			<?php
			$excerpt_key = '_service_excerpt_' . $language;
			?>
			<div class="ct-core-service-translation" style="margin-top: 20px; border-top: 1px solid #e0e0e0; padding-top: 12px;">
				<h4><?php echo esc_html( strtoupper( $language ) . ' ' . __( 'Translation', 'ct-core' ) ); ?></h4>
				<p>
					<label for="<?php echo esc_attr( $excerpt_key ); ?>"><?php esc_html_e( 'Service description', 'ct-core' ); ?></label>
					<textarea id="<?php echo esc_attr( $excerpt_key ); ?>" name="<?php echo esc_attr( $excerpt_key ); ?>" rows="4" class="widefat"><?php echo esc_textarea( get_post_meta( $post->ID, $excerpt_key, true ) ); ?></textarea>
				</p>
			</div>
		<?php endforeach; ?>

		<?php
	}

	/**
	 * Render translated service titles below the native post title field.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_translation_titles( $post ) {
		if ( ! $post instanceof WP_Post || 'service' !== $post->post_type ) {
			return;
		}

		foreach ( $this->get_translation_languages() as $language ) {
			$meta_key = '_service_title_' . $language;
			?>
			<p class="ct-core-service-translation-title" style="margin: 8px 0 16px;">
				<label for="<?php echo esc_attr( $meta_key ); ?>">
					<strong><?php echo esc_html( strtoupper( $language ) . ' ' . __( 'Service title', 'ct-core' ) ); ?></strong>
				</label>
				<input type="text" id="<?php echo esc_attr( $meta_key ); ?>" name="<?php echo esc_attr( $meta_key ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, $meta_key, true ) ); ?>" class="widefat" />
			</p>
			<?php
		}
	}

	/**
	 * Rename the native excerpt meta box for the service CPT.

	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 * @param string $domain      Translation domain.
	 * @return string
	 */
	public function filter_service_excerpt_label( $translation, $text, $domain ) {
		if ( 'Excerpt' !== $text || 'default' !== $domain || ! function_exists( 'get_current_screen' ) ) {
			return $translation;
		}

		$screen = get_current_screen();
		if ( $screen && 'service' === $screen->post_type ) {
			return __( 'Description', 'ct-core' );
		}

		return $translation;
	}

	/**
	 * Render translation fields on the taxonomy term creation screen.
	 *
	 * @param string $taxonomy Taxonomy name.
	 */
	public function render_taxonomy_add_fields( $taxonomy ) {
		if ( ! in_array( $taxonomy, array( 'service_appliance_type', 'service_work_type', 'service_price_unit' ), true ) ) {
			return;
		}

		wp_nonce_field( 'ct_core_taxonomy_translation_nonce', 'ct_core_taxonomy_translation_nonce' );
		foreach ( $this->get_translation_languages() as $language ) {
			?>
			<div class="form-field">
				<label for="_service_term_name_<?php echo esc_attr( $language ); ?>">
					<?php echo esc_html( strtoupper( $language ) . ' ' . __( 'Translation', 'ct-core' ) ); ?>
				</label>
				<input type="text" name="_service_term_name[<?php echo esc_attr( $language ); ?>]" id="_service_term_name_<?php echo esc_attr( $language ); ?>" value="" />
			</div>
			<?php
		}
	}

	/**
	 * Render translation fields on the taxonomy term edit screen.
	 *
	 * @param WP_Term $term     Term object.
	 * @param string  $taxonomy Taxonomy name.
	 */
	public function render_taxonomy_edit_fields( $term, $taxonomy ) {
		if ( ! in_array( $taxonomy, array( 'service_appliance_type', 'service_work_type', 'service_price_unit' ), true ) ) {
			return;
		}

		wp_nonce_field( 'ct_core_taxonomy_translation_nonce', 'ct_core_taxonomy_translation_nonce' );
		foreach ( $this->get_translation_languages() as $language ) {
			$meta_key = '_service_term_name_' . $language;
			?>
			<tr class="form-field">
				<th scope="row"><label for="_service_term_name_<?php echo esc_attr( $language ); ?>"><?php echo esc_html( strtoupper( $language ) . ' ' . __( 'Translation', 'ct-core' ) ); ?></label></th>
				<td><input type="text" name="_service_term_name[<?php echo esc_attr( $language ); ?>]" id="_service_term_name_<?php echo esc_attr( $language ); ?>" value="<?php echo esc_attr( get_term_meta( $term->term_id, $meta_key, true ) ); ?>" class="regular-text" /></td>
			</tr>
			<?php
		}
	}

	/**
	 * Save taxonomy term translations.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy name.
	 */
	public function save_taxonomy_translation( $term_id, $tt_id, $taxonomy ) {
		if ( ! in_array( $taxonomy, array( 'service_appliance_type', 'service_work_type', 'service_price_unit' ), true ) ) {
			return;
		}

		if ( ! isset( $_POST['ct_core_taxonomy_translation_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ct_core_taxonomy_translation_nonce'] ) ), 'ct_core_taxonomy_translation_nonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$translations = isset( $_POST['_service_term_name'] ) && is_array( $_POST['_service_term_name'] ) ? wp_unslash( $_POST['_service_term_name'] ) : array();
		foreach ( $this->get_translation_languages() as $language ) {
			$meta_key = '_service_term_name_' . $language;
			$value    = isset( $translations[ $language ] ) ? sanitize_text_field( $translations[ $language ] ) : '';
			if ( '' === $value ) {
				delete_term_meta( $term_id, $meta_key );
			} else {
				update_term_meta( $term_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Get configured Polylang language slugs for translatable fields.
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
	 * Save service price meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function save_service_meta( $post_id, $post ) {
		if ( ! isset( $_POST['ct_core_service_price_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ct_core_service_price_nonce'] ) ), 'ct_core_service_price_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( $this->get_translation_languages() as $language ) {
			foreach ( array( 'title', 'excerpt', 'price_custom' ) as $field ) {
				$meta_key = '_service_' . $field . '_' . $language;
				$value    = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : '';
				$value    = 'excerpt' === $field ? wp_kses_post( $value ) : sanitize_text_field( $value );

				if ( '' === $value ) {
					delete_post_meta( $post_id, $meta_key );
				} else {
					update_post_meta( $post_id, $meta_key, $value );
				}
			}
		}

		$price_type = isset( $_POST['_service_price_type'] ) ? sanitize_text_field( wp_unslash( $_POST['_service_price_type'] ) ) : 'from';
		if ( ! in_array( $price_type, array( 'fixed', 'from', 'range', 'custom' ), true ) ) {
			$price_type = 'from';
		}
		update_post_meta( $post_id, '_service_price_type', $price_type );

		$price_min    = isset( $_POST['_service_price_min'] ) ? sanitize_text_field( wp_unslash( $_POST['_service_price_min'] ) ) : '';
		$price_max    = isset( $_POST['_service_price_max'] ) ? sanitize_text_field( wp_unslash( $_POST['_service_price_max'] ) ) : '';
		$price_custom = isset( $_POST['_service_price_custom'] ) ? sanitize_text_field( wp_unslash( $_POST['_service_price_custom'] ) ) : '';

		if ( 'from' === $price_type ) {
			update_post_meta( $post_id, '_service_price_min', $price_min );
			delete_post_meta( $post_id, '_service_price_max' );
			delete_post_meta( $post_id, '_service_price_custom' );
		} elseif ( 'fixed' === $price_type ) {
			update_post_meta( $post_id, '_service_price_max', $price_max );
			delete_post_meta( $post_id, '_service_price_min' );
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

		// Save price unit as taxonomy term
		$price_unit = isset( $_POST['service_price_unit'] ) ? absint( wp_unslash( $_POST['service_price_unit'] ) ) : 0;
		if ( ! empty( $price_unit ) ) {
			wp_set_post_terms( $post_id, array( $price_unit ), 'service_price_unit' );
		} else {
			wp_set_post_terms( $post_id, array(), 'service_price_unit' );
		}
	}
}
