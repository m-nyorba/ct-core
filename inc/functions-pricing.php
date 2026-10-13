<?php
/**
 * Pricing functions
 *
 * @package Ct_Core
 * @since 1.0.0
 */

/**
 * Get the currency label used in price display.
 *
 * @return string
 */
function ct_core_get_currency_label() {
	return apply_filters( 'ct_core_currency_label', _x( 'UAH', 'currency label', 'ct-core' ) );
}
