<?php
/**
 * Block registration and category functions
 *
 * @package Ct_Core
 * @since 1.0.0
 */

/**
 * Register a custom block category so our blocks appear under a named group.
 *
 * Uses the newer `block_categories_all` filter which replaces the deprecated
 * `block_categories` in Gutenberg 14+.  We append our category to existing
 * ones to avoid overwriting core/vendor categories.
 *
 * @param array $categories Existing block categories.
 * @return array Updated block categories.
 */
function ct_core_block_categories( $categories ) {
	return array_merge(
		array(
			array(
				'slug'  => 'ct-core',
				'title' => __( 'CT Core Blocks', 'ct-core' ),
			),
		),
		$categories
	);
}
add_filter( 'block_categories_all', 'ct_core_block_categories', 10 );
