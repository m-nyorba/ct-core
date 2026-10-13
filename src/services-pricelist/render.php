<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
$appliance_term              = isset( $attributes['applianceTerm'] ) ? sanitize_title( $attributes['applianceTerm'] ) : '';
$work_term                   = isset( $attributes['workTerm'] ) ? sanitize_title( $attributes['workTerm'] ) : '';
$price_notice                = isset( $attributes['priceNotice'] ) ? $attributes['priceNotice'] : '';
$price_notice_align          = isset( $attributes['priceNoticeAlign'] ) ? sanitize_key( $attributes['priceNoticeAlign'] ) : 'left';
$price_notice_font_size      = isset( $attributes['priceNoticeFontSize'] ) ? (string) $attributes['priceNoticeFontSize'] : '';
$price_notice_is_preset      = 0 === strpos( $price_notice_font_size, 'var:preset|font-size|' );
$price_notice_font_size_slug = $price_notice_is_preset ? (string) substr( strrchr( $price_notice_font_size, '|' ), 1 ) : '';
$price_notice_font_size_slug = sanitize_html_class( $price_notice_font_size_slug );
$price_notice_class          = '' !== $price_notice_font_size_slug ? ' class="has-' . $price_notice_font_size_slug . '-font-size"' : '';
$price_notice_preset_size    = '';

if ( '' !== $price_notice_font_size_slug && function_exists( 'wp_get_global_settings' ) ) {
	$font_sizes = wp_get_global_settings( array( 'typography', 'fontSizes' ) );

	foreach ( is_array( $font_sizes ) ? $font_sizes : array() as $font_size ) {
		if ( isset( $font_size['slug'], $font_size['size'] ) && $font_size['slug'] === $price_notice_font_size_slug && is_string( $font_size['size'] ) ) {
			$price_notice_preset_size = $font_size['size'];
			break;
		}
	}
}

$price_notice_style = '';

if ( '' !== $price_notice_font_size ) {
	$font_size_value    = $price_notice_is_preset
		? ( '' !== $price_notice_preset_size ? $price_notice_preset_size : 'var(--wp--preset--font-size--' . $price_notice_font_size_slug . ')' )
		: $price_notice_font_size;
	$price_notice_style = ' style="font-size: ' . esc_attr( $font_size_value ) . ';"';
}

// Default number of items to show when no filters are applied
if ( ! defined( 'CT_CORE_DEFAULT_PER_PAGE' ) ) {
	define( 'CT_CORE_DEFAULT_PER_PAGE', 15 );
}

$tax_query = array();

if ( '' !== $appliance_term ) {
	$tax_query[] = array(
		'taxonomy' => 'service_appliance_type',
		'field'    => 'slug',
		'terms'    => $appliance_term,
	);
}

if ( '' !== $work_term ) {
	$tax_query[] = array(
		'taxonomy' => 'service_work_type',
		'field'    => 'slug',
		'terms'    => $work_term,
	);
}

// Determine if filters are selected
$has_filters    = '' !== $appliance_term || '' !== $work_term;
$posts_per_page = $has_filters ? -1 : CT_CORE_DEFAULT_PER_PAGE;

$query_args = array(
	'post_type'      => 'service',
	'post_status'    => 'publish',
	'posts_per_page' => $posts_per_page,
	'orderby'        => array(
		'menu_order' => 'ASC',
		'title'      => 'ASC',
	),
);

if ( ! empty( $tax_query ) ) {
	$query_args['tax_query'] = $tax_query;
}

$services = new WP_Query( $query_args );

$selected_terms = array();

if ( '' !== $appliance_term ) {
	$term = get_term_by( 'slug', $appliance_term, 'service_appliance_type' );
	if ( $term && ! is_wp_error( $term ) ) {
		$selected_terms[] = ct_core_get_localized_term( $term )->name;
	}
}

if ( '' !== $work_term ) {
	$term = get_term_by( 'slug', $work_term, 'service_work_type' );
	if ( $term && ! is_wp_error( $term ) ) {
		$selected_terms[] = ct_core_get_localized_term( $term )->name;
	}
}
?>
<section <?php echo get_block_wrapper_attributes(); ?>>
	<?php
	// Render first inner block (heading)
	if ( ! empty( trim( $content ) ) ) {
		echo wp_kses_post( $content );
	}
	?>

	<?php if ( ! $has_filters ) : ?>
			<div class="ct-services-pricelist__notice">
			<?php
			printf(
				/* translators: %d: number of services shown */
				esc_html__( 'Showing first %d services. Select filters to see more.', 'ct-core' ),
				CT_CORE_DEFAULT_PER_PAGE
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( $services->have_posts() ) : ?>
		<?php
		$services_array = $services->get_posts();
		$grouped        = ct_core_group_services( $services_array, $appliance_term, $work_term );
		$is_two_level   = ! empty( $grouped[0]['subGroups'] );
		?>
		<figure class="wp-block-table is-style-stripes">
			<table class="has-fixed-layout ct-services-pricelist__table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Service', 'ct-core' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Price', 'ct-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $grouped as $group ) {
						if ( $is_two_level ) {
							// Two-level grouping
							?>
							<tr class="ct-services-pricelist__group-row">
								<td colspan="2" class="ct-services-pricelist__group-title">
									<?php echo esc_html( $group['title'] ); ?>
								</td>
							</tr>
							<?php
							foreach ( $group['subGroups'] as $subgroup ) {
								?>
								<tr class="ct-services-pricelist__subgroup-row">
									<td colspan="2" class="ct-services-pricelist__subgroup-title">
										<?php echo esc_html( $subgroup['title'] ); ?>
									</td>
								</tr>
								<?php
								foreach ( $subgroup['items'] as $service ) {
									?>
									<tr>
										<td><?php echo esc_html( $service['title'] ); ?></td>
										<td class="has-text-align-center" data-align="center"><?php echo esc_html( $service['price'] ); ?></td>
									</tr>
									<?php
								}
							}
						} else {
							// Single-level grouping or no grouping
							if ( ! empty( $group['title'] ) ) {
								?>
								<tr class="ct-services-pricelist__group-row">
									<td colspan="2" class="ct-services-pricelist__group-title">
										<?php echo esc_html( $group['title'] ); ?>
									</td>
								</tr>
								<?php
							}
							foreach ( $group['items'] as $service ) {
								?>
								<tr>
									<td><?php echo esc_html( $service['title'] ); ?></td>
									<td class="has-text-align-center" data-align="center"><?php echo esc_html( $service['price'] ); ?></td>
								</tr>
								<?php
							}
						}
					}
					?>
				</tbody>
			</table>
			<?php if ( ! empty( $price_notice ) ) : ?>
				<figcaption style="text-align: <?php echo esc_attr( $price_notice_align ); ?>;"><p<?php echo $price_notice_class . $price_notice_style; ?>><?php echo wp_kses_post( $price_notice ); ?></p></figcaption>
			<?php endif; ?>
		</figure>
	<?php else : ?>
		<p class="ct-services-pricelist__empty"><?php esc_html_e( 'Services not found.', 'ct-core' ); ?></p>
	<?php endif; ?>
</section>
<?php
wp_reset_postdata();
