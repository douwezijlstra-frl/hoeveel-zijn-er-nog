<?php
/**
 * Block render callback for hoeveel-zijn-er-nog/hzen-count.
 *
 * Variables injected by WordPress:
 *   $attributes — block attributes.
 *   $content    — inner content (unused).
 *   $block      — WP_Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$atts = shortcode_atts(
	array(
		'merk'            => '',
		'handelsbenaming' => '',
		'voertuigsoort'   => 'P',
		'inrichting'      => '',
	),
	$attributes
);

// Reuse the existing [hzen_count] shortcode handler.
$sc = '[hzen_count';
foreach ( $atts as $key => $val ) {
	if ( '' !== $val ) {
		$sc .= ' ' . esc_attr( $key ) . '="' . esc_attr( $val ) . '"';
	}
}
$sc .= ']';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo do_shortcode( $sc );
