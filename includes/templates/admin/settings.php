<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin template: Plugin Settings.
 *
 * Variables:
 *   $notice — HTML notice string (may be empty).
 */

$current_token = sanitize_text_field( get_option( 'hzen_app_token', '' ) );
$current_ttl   = (int) get_option( 'hzen_cache_ttl', DAY_IN_SECONDS );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Hoeveel Zijn Er Nog — Settings', 'hoeveel-zijn-er-nog' ); ?></h1>

	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $notice;
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'hzen_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="hzen_app_token"><?php esc_html_e( 'RDW / Socrata App Token', 'hoeveel-zijn-er-nog' ); ?></label>
				</th>
				<td>
					<input type="text" id="hzen_app_token" name="hzen_app_token"
						class="regular-text" value="<?php echo esc_attr( $current_token ); ?>">
					<p class="description">
						<?php
						printf(
							/* translators: %s: link to Socrata app token page */
							esc_html__( 'Optional. Get a free token at %s to increase the RDW API rate limit.', 'hoeveel-zijn-er-nog' ),
							'<a href="https://data.socrata.com/profile/app_tokens" target="_blank" rel="noreferrer noopener">data.socrata.com</a>'
						);
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="hzen_cache_ttl"><?php esc_html_e( 'Cache TTL (seconds)', 'hoeveel-zijn-er-nog' ); ?></label>
				</th>
				<td>
					<input type="number" id="hzen_cache_ttl" name="hzen_cache_ttl"
						class="small-text" min="60" value="<?php echo esc_attr( $current_ttl ); ?>">
					<p class="description"><?php echo esc_html( sprintf( __( 'Default: %d (24 hours). Minimum: 60.', 'hoeveel-zijn-er-nog' ), DAY_IN_SECONDS ) ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Cron Status', 'hoeveel-zijn-er-nog' ); ?></h2>
		<table class="widefat fixed" style="max-width:600px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Event', 'hoeveel-zijn-er-nog' ); ?></th>
					<th><?php esc_html_e( 'Next Run', 'hoeveel-zijn-er-nog' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>hzen_daily_fetch</code></td>
					<td>
						<?php
						$next = wp_next_scheduled( 'hzen_daily_fetch' );
						if ( $next ) {
							echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) );
						} else {
							esc_html_e( 'Not scheduled', 'hoeveel-zijn-er-nog' );
						}
						?>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Settings', 'hoeveel-zijn-er-nog' ) ); ?>
	</form>
</div>
