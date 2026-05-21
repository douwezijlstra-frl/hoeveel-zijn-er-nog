<?php
/**
 * Block render callback for hoeveel-zijn-er-nog/hzen-compare.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$atts = shortcode_atts(
	array( 'models' => '', 'metric' => 'total', 'range' => '12m', 'height' => 80 ),
	$attributes
);

$sc = '[hzen_compare'
	. ' models="' . esc_attr( $atts['models'] ) . '"'
	. ' metric="' . esc_attr( $atts['metric'] ) . '"'
	. ' range="' . esc_attr( $atts['range'] ) . '"'
	. ' height="' . (int) $atts['height'] . '"'
	. ']';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo do_shortcode( $sc );
