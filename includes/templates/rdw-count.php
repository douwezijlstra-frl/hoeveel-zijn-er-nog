<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vars provided by Shortcode_Rdw_Count: total, apk_valid,
 * oldest_plate, oldest_date, newest_plate, newest_date.
 */
?>
<div class="hoeveel-zijn-er-nog-wrapper">
	<div class="rdw-stats-grid">
		<div class="rdw-stat-item">
			<span class="rdw-stat-label"><?php esc_html_e( 'Geregistreerd', 'hoeveel-zijn-er-nog' ); ?></span>
			<span class="rdw-stat-value"><?php echo esc_html( number_format_i18n( $total ) ); ?></span>
		</div>
		<div class="rdw-stat-item">
			<span class="rdw-stat-label"><?php esc_html_e( 'Geldige APK', 'hoeveel-zijn-er-nog' ); ?></span>
			<span class="rdw-stat-value"><?php echo esc_html( number_format_i18n( $apk_valid ) ); ?></span>
		</div>
		<div class="rdw-stat-item">
			<span class="rdw-stat-label"><?php esc_html_e( 'Oudste', 'hoeveel-zijn-er-nog' ); ?></span>
			<span class="rdw-stat-value">
				<?php if ( $oldest_plate ) : ?>
					<?php echo esc_html( $oldest_plate ); ?><br>
					<small><?php echo esc_html( $oldest_date ); ?></small>
				<?php else : ?>
					<?php echo esc_html_x( 'N/A', 'no data', 'hoeveel-zijn-er-nog' ); ?>
				<?php endif; ?>
			</span>
		</div>
		<div class="rdw-stat-item">
			<span class="rdw-stat-label"><?php esc_html_e( 'Nieuwste', 'hoeveel-zijn-er-nog' ); ?></span>
			<span class="rdw-stat-value">
				<?php if ( $newest_plate ) : ?>
					<?php echo esc_html( $newest_plate ); ?><br>
					<small><?php echo esc_html( $newest_date ); ?></small>
				<?php else : ?>
					<?php echo esc_html_x( 'N/A', 'no data', 'hoeveel-zijn-er-nog' ); ?>
				<?php endif; ?>
			</span>
		</div>
	</div>
</div>
