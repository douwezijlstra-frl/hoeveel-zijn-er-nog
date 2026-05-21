<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin template: Add Tracked Model form.
 *
 * Variables:
 *   $notice — HTML string with success/error notice (may be empty).
 */
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Add Tracked Model', 'hoeveel-zijn-er-nog' ); ?></h1>

	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $notice;
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'hzen_add_model' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="merk"><?php esc_html_e( 'Merk', 'hoeveel-zijn-er-nog' ); ?> <span aria-hidden="true">*</span></label>
				</th>
				<td>
					<input type="text" id="merk" name="merk" class="regular-text hzen-autocomplete"
						data-field="merk" autocomplete="off" required
						value="<?php echo isset( $_POST['merk'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['merk'] ) ) ) : ''; ?>">
					<p class="description"><?php esc_html_e( 'Brand name as registered in RDW, e.g. TESLA.', 'hoeveel-zijn-er-nog' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="handelsbenaming"><?php esc_html_e( 'Handelsbenaming', 'hoeveel-zijn-er-nog' ); ?></label>
				</th>
				<td>
					<input type="text" id="handelsbenaming" name="handelsbenaming" class="regular-text hzen-autocomplete"
						data-field="handelsbenaming" autocomplete="off"
						value="<?php echo isset( $_POST['handelsbenaming'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['handelsbenaming'] ) ) ) : ''; ?>">
					<p class="description"><?php esc_html_e( 'Model name, e.g. MODEL 3. Leave blank to track all models of this brand.', 'hoeveel-zijn-er-nog' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="voertuigsoort"><?php esc_html_e( 'Voertuigsoort', 'hoeveel-zijn-er-nog' ); ?></label>
				</th>
				<td>
					<select id="voertuigsoort" name="voertuigsoort">
						<?php
						$selected_type = isset( $_POST['voertuigsoort'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['voertuigsoort'] ) ) ) : 'P';
						$types         = array(
							'P'  => __( 'P - Personenauto', 'hoeveel-zijn-er-nog' ),
							'B'  => __( 'B - Bus', 'hoeveel-zijn-er-nog' ),
							'M'  => __( 'M - Motor', 'hoeveel-zijn-er-nog' ),
							'C'  => __( 'C - Camper', 'hoeveel-zijn-er-nog' ),
							'O'  => __( 'O - Oplegger', 'hoeveel-zijn-er-nog' ),
							'T'  => __( 'T - Trekker', 'hoeveel-zijn-er-nog' ),
							''   => __( '(any)', 'hoeveel-zijn-er-nog' ),
						);
						foreach ( $types as $value => $label ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $value ),
								selected( $selected_type, $value, false ),
								esc_html( $label )
							);
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="inrichting"><?php esc_html_e( 'Inrichting', 'hoeveel-zijn-er-nog' ); ?></label>
				</th>
				<td>
					<input type="text" id="inrichting" name="inrichting" class="regular-text"
						value="<?php echo isset( $_POST['inrichting'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['inrichting'] ) ) ) : ''; ?>">
					<p class="description"><?php esc_html_e( 'Optional body style filter, e.g. STATIONWAGEN.', 'hoeveel-zijn-er-nog' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="label"><?php esc_html_e( 'Label', 'hoeveel-zijn-er-nog' ); ?></label>
				</th>
				<td>
					<input type="text" id="label" name="label" class="regular-text"
						value="<?php echo isset( $_POST['label'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['label'] ) ) ) : ''; ?>">
					<p class="description"><?php esc_html_e( 'Display name shown in the admin and shortcodes (optional, auto-generated from merk + handelsbenaming).', 'hoeveel-zijn-er-nog' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Add Model', 'hoeveel-zijn-er-nog' ) ); ?>
	</form>
</div>
