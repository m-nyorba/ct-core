<?php
/**
 * CT Core Services Helper
 *
 * @package Ct_Core
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the current Polylang language slug.
 *
 * @return string
 */
function ct_core_get_current_language() {
	if ( function_exists( 'pll_current_language' ) ) {
		$language = pll_current_language( 'slug' );
		if ( is_string( $language ) && '' !== $language ) {
			return sanitize_key( $language );
		}
	}

	return '';
}

/**
 * Get a localized service text value with a default-language fallback.
 *
 * @param int    $post_id Service post ID.
 * @param string $field   Either title or excerpt.
 * @return string
 */
function ct_core_get_localized_service_text( $post_id, $field ) {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$language = ct_core_get_current_language();
	if ( '' !== $language ) {
		$translated = get_post_meta( $post_id, '_service_' . sanitize_key( $field ) . '_' . $language, true );
		if ( '' !== $translated ) {
			return 'excerpt' === $field ? wp_kses_post( $translated ) : sanitize_text_field( $translated );
		}
	}

	return 'excerpt' === $field ? $post->post_excerpt : $post->post_title;
}

/**
 * Get service taxonomy terms with the current language's term metadata.
 *
 * @param int    $post_id  Service post ID.
 * @param string $taxonomy Taxonomy name.
 * @return WP_Term[]
 */
function ct_core_get_localized_service_terms( $post_id, $taxonomy ) {
	$terms = wp_get_post_terms( $post_id, $taxonomy );
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	$language = ct_core_get_current_language();
	if ( '' === $language ) {
		return $terms;
	}

	$localized_terms = array();
	foreach ( $terms as $term ) {
		$localized_terms[] = ct_core_get_localized_term( $term, $language );
	}

	return $localized_terms;
}

/**
 * Get a taxonomy term with the current language's term metadata.
 *
 * @param WP_Term $term     Term object.
 * @param string  $language Language slug.
 * @return WP_Term
 */
function ct_core_get_localized_term( $term, $language = '' ) {
	if ( ! $term instanceof WP_Term ) {
		return $term;
	}

	$language = '' !== $language ? $language : ct_core_get_current_language();
	if ( '' === $language ) {
		return $term;
	}

	$translation = get_term_meta( $term->term_id, '_service_term_name_' . $language, true );
	if ( '' === $translation ) {
		return $term;
	}

	$localized_term       = clone $term;
	$localized_term->name = sanitize_text_field( $translation );

	return $localized_term;
}

/**
 * Get formatted service price string.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function ct_core_get_formatted_price( $post_id ) {
	$price_type   = get_post_meta( $post_id, '_service_price_type', true );
	$price_min    = get_post_meta( $post_id, '_service_price_min', true );
	$price_max    = get_post_meta( $post_id, '_service_price_max', true );
	$price_custom = get_post_meta( $post_id, '_service_price_custom', true );
	$language     = ct_core_get_current_language();
	if ( 'custom' === $price_type && '' !== $language ) {
		$translated_price_custom = get_post_meta( $post_id, '_service_price_custom_' . $language, true );
		if ( '' !== $translated_price_custom ) {
			$price_custom = $translated_price_custom;
		}
	}

	// Use number_format() with a plain space to avoid HTML entities (&nbsp;)
	// that number_format_i18n() produces for some locales (including Ukrainian).
	// Plain space renders correctly in both HTML output and REST API JSON strings.
	$fmt = static function ( $value ) {
		return number_format( floatval( $value ), 0, '.', "\u{00A0}" );
	};

	// Get price unit dynamically from taxonomy
	$price_unit  = ct_core_get_price_unit( $post_id );
	$unit_suffix = ! empty( $price_unit ) ? ' за ' . esc_html( $price_unit ) : '';

	$price_string = '';

	switch ( $price_type ) {
		case 'fixed':
			$price_string = $fmt( $price_max ) . ' ' . ct_core_get_currency_label() . $unit_suffix;
			break;

		case 'from':
			$price_string = sprintf(
			/* translators: 1: minimum price with currency, 2: price unit suffix */
				__( 'from %1$s%2$s', 'ct-core' ),
				$fmt( $price_min ) . ' ' . ct_core_get_currency_label(),
				$unit_suffix
			);
			break;

		case 'range':
			$price_string = sprintf(
			/* translators: 1: minimum price, 2: maximum price with currency, 3: price unit suffix */
				__( 'from %1$s to %2$s%3$s', 'ct-core' ),
				$fmt( $price_min ),
				$fmt( $price_max ) . ' ' . ct_core_get_currency_label(),
				$unit_suffix
			);
			break;

		case 'custom':
			$price_string = sanitize_text_field( $price_custom ) . $unit_suffix;
			break;

		default:
			$price_string = '';
	}

	return $price_string;
}

/**
 * Get price unit for a service.
 *
 * @param int $post_id Post ID.
 * @return string Price unit label or empty string.
 */
function ct_core_get_price_unit( $post_id ) {
	$terms = ct_core_get_localized_service_terms( $post_id, 'service_price_unit' );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}

	// Return the name of the first assigned term
	return $terms[0]->name ?? '';
}
/**
 * Group services by taxonomy.
 *
 * @param array  $services Array of WP_Post objects.
 * @param string $appliance_term Appliance type slug filter.
 * @param string $work_term Work type slug filter.
 * @return array Grouped services array.
 */
function ct_core_group_services( $services, $appliance_term = '', $work_term = '' ) {
	$services_list = array();

	foreach ( $services as $service ) {
		$post_id = $service->ID;

		// Get taxonomy terms for this service
		$appliance_terms = ct_core_get_localized_service_terms( $post_id, 'service_appliance_type' );
		$work_terms      = ct_core_get_localized_service_terms( $post_id, 'service_work_type' );
		$appliance_types = wp_list_pluck( $appliance_terms, 'name' );
		$work_types      = wp_list_pluck( $work_terms, 'name' );

		// Handle empty arrays
		if ( empty( $appliance_types ) || is_wp_error( $appliance_types ) ) {
			$appliance_types = array( 'Uncategorized' );
		}
		if ( empty( $work_types ) || is_wp_error( $work_types ) ) {
			$work_types = array( 'Uncategorized' );
		}

		$services_list[] = array(
			'post_id'         => $post_id,
			'title'           => ct_core_get_localized_service_text( $post_id, 'title' ),
			'appliance_types' => $appliance_types,
			'work_types'      => $work_types,
			'price'           => function_exists( 'ct_core_get_formatted_price' ) ? ct_core_get_formatted_price( $post_id ) : '',
		);
	}

	// If both filters are selected, no grouping
	if ( '' !== $appliance_term && '' !== $work_term ) {
		return array( array( 'items' => $services_list ) );
	}

	// If only appliance type is selected, group by work type
	if ( '' !== $appliance_term && '' === $work_term ) {
		$groups = array();
		foreach ( $services_list as $service ) {
			$group_key = $service['work_types'][0] ?? 'Uncategorized';
			if ( ! isset( $groups[ $group_key ] ) ) {
				$groups[ $group_key ] = array();
			}
			$groups[ $group_key ][] = $service;
		}
		$result = array();
		foreach ( $groups as $title => $items ) {
			$result[] = array(
				'title' => $title,
				'items' => $items,
			);
		}
		return $result;
	}

	// If only work type is selected, group by appliance type
	if ( '' === $appliance_term && '' !== $work_term ) {
		$groups = array();
		foreach ( $services_list as $service ) {
			$group_key = $service['appliance_types'][0] ?? 'Uncategorized';
			if ( ! isset( $groups[ $group_key ] ) ) {
				$groups[ $group_key ] = array();
			}
			$groups[ $group_key ][] = $service;
		}
		$result = array();
		foreach ( $groups as $title => $items ) {
			$result[] = array(
				'title' => $title,
				'items' => $items,
			);
		}
		return $result;
	}

	// If no filters, group by both (two-level grouping)
	$primary_groups = array();
	foreach ( $services_list as $service ) {
		foreach ( $service['appliance_types'] as $app_type ) {
			if ( ! isset( $primary_groups[ $app_type ] ) ) {
				$primary_groups[ $app_type ] = array();
			}
			foreach ( $service['work_types'] as $work_type ) {
				if ( ! isset( $primary_groups[ $app_type ][ $work_type ] ) ) {
					$primary_groups[ $app_type ][ $work_type ] = array();
				}
				$primary_groups[ $app_type ][ $work_type ][] = $service;
			}
		}
	}

	$result = array();
	foreach ( $primary_groups as $app_type => $work_groups ) {
		$sub_groups = array();
		foreach ( $work_groups as $work_type => $items ) {
			$sub_groups[] = array(
				'title' => $work_type,
				'items' => $items,
			);
		}
		$result[] = array(
			'title'     => $app_type,
			'subGroups' => $sub_groups,
		);
	}
	return $result;
}
