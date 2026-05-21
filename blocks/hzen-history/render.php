<?php
/**
 * Block render callback for hoeveel-zijn-er-nog/hzen-history.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sc = '[hzen_history';
$defaults = array(
	'merk'            => '',
	'handelsbenaming' => '',
	'voertuigsoort'   => 'P',
	'inrichting'      => '',
	'range'           => '12m',
	'metric'          => 'total',
	'height'          => 80,
);
$atts = shortcode_atts( $defaults, $attributes );
foreach ( $atts as $key => $val ) {
	if ( '' !== (string) $val ) {
		$sc .= ' ' . esc_attr( $key ) . '="' . esc_attr( $val ) . '"';
	}
}
$sc .= ']';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo do_shortcode( $sc );
