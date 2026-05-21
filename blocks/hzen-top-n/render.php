<?php
/**
 * Block render callback for hoeveel-zijn-er-nog/hzen-top-n.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$atts = shortcode_atts(
	array( 'metric' => 'total', 'limit' => 10, 'voertuigsoort' => '' ),
	$attributes
);

$sc = '[hzen_top_n metric="' . esc_attr( $atts['metric'] ) . '" limit="' . (int) $atts['limit'] . '"';
if ( $atts['voertuigsoort'] ) {
	$sc .= ' voertuigsoort="' . esc_attr( $atts['voertuigsoort'] ) . '"';
}
$sc .= ']';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo do_shortcode( $sc );
