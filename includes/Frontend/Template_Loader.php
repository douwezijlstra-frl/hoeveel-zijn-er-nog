<?php
namespace Hoeveel_Zijn_Er_Nog\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Template_Loader {

	public static function render( $slug, array $vars = array() ) {
		$file     = $slug . '.php';
		$theme    = locate_template( array( 'hoeveel-zijn-er-nog/' . $file ) );
		$fallback = HOEVEEL_ZIJN_ER_NOG_PLUGIN_DIR . 'includes/templates/' . $file;
		$path     = $theme ? $theme : $fallback;
		$path     = (string) apply_filters( 'hzen_template_path', $path, $slug, $vars );

		if ( ! is_readable( $path ) ) {
			return '';
		}

		ob_start();
		extract( $vars, EXTR_SKIP );
		include $path;
		return ob_get_clean();
	}
}
