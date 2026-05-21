<?php
/**
 * Block render callback for hoeveel-zijn-er-nog/hzen-model-page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$atts = shortcode_atts(
	array( 'slug' => '', 'range' => '12m', 'metric' => 'total' ),
	$attributes
);

$sc = '[hzen_model_page'
	. ' slug="' . esc_attr( $atts['slug'] ) . '"'
	. ' range="' . esc_attr( $atts['range'] ) . '"'
	. ' metric="' . esc_attr( $atts['metric'] ) . '"'
	. ']';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo do_shortcode( $sc );
