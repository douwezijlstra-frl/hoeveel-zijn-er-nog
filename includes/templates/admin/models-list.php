<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin template: Tracked Models list.
 *
 * Variables available:
 *   $list_table  — instance of Models_List_Table (already prepared).
 */
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Tracked Models', 'hoeveel-zijn-er-nog' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hzen-add' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'hoeveel-zijn-er-nog' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php
	// Show history view inline if requested.
	$hzen_view = isset( $_GET['hzen_view'] ) ? sanitize_key( $_GET['hzen_view'] ) : '';
	$model_id  = isset( $_GET['model_id'] ) ? (int) $_GET['model_id'] : 0;

	if ( 'history' === $hzen_view && $model_id ) {
		$model = \Hoeveel_Zijn_Er_Nog\Db\Tracked_Models_Repo::find( $model_id );
		if ( $model ) {
			?>
			<h2><?php echo esc_html( sprintf( __( 'History: %s', 'hoeveel-zijn-er-nog' ), $model['label'] ) ); ?></h2>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hzen' ) ); ?>">
					&larr; <?php esc_html_e( 'Back to list', 'hoeveel-zijn-er-nog' ); ?>
				</a>
			</p>
			<div id="hzen-history-chart-wrap">
				<canvas id="hzen-history-chart" height="80"></canvas>
			</div>
			<?php
			$snapshots = \Hoeveel_Zijn_Er_Nog\Db\Snapshots_Repo::for_model( $model_id );
			if ( $snapshots ) {
				?>
				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'hoeveel-zijn-er-nog' ); ?></th>
							<th><?php esc_html_e( 'Total', 'hoeveel-zijn-er-nog' ); ?></th>
							<th><?php esc_html_e( 'Valid APK', 'hoeveel-zijn-er-nog' ); ?></th>
							<th><?php esc_html_e( 'Oldest', 'hoeveel-zijn-er-nog' ); ?></th>
							<th><?php esc_html_e( 'Newest', 'hoeveel-zijn-er-nog' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $snapshots as $snap ) : ?>
							<tr>
								<td><?php echo esc_html( $snap['snapshot_date'] ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $snap['total'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $snap['apk_valid'] ) ); ?></td>
								<td><?php echo esc_html( $snap['oldest_date'] ?: '—' ); ?></td>
								<td><?php echo esc_html( $snap['newest_date'] ?: '—' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<script>
				(function() {
					var snapshots = <?php echo wp_json_encode( $snapshots ); ?>;
					var labels    = snapshots.map(function(s){ return s.snapshot_date; });
					var totals    = snapshots.map(function(s){ return parseInt(s.total, 10); });
					var apk       = snapshots.map(function(s){ return parseInt(s.apk_valid, 10); });

					document.addEventListener('DOMContentLoaded', function() {
						if (typeof Chart === 'undefined' || !document.getElementById('hzen-history-chart')) return;
						new Chart(document.getElementById('hzen-history-chart'), {
							type: 'line',
							data: {
								labels: labels,
								datasets: [
									{
										label: '<?php echo esc_js( __( 'Total', 'hoeveel-zijn-er-nog' ) ); ?>',
										data: totals,
										borderColor: '#0073aa',
										backgroundColor: 'rgba(0,115,170,0.1)',
										fill: true,
										tension: 0.3
									},
									{
										label: '<?php echo esc_js( __( 'Valid APK', 'hoeveel-zijn-er-nog' ) ); ?>',
										data: apk,
										borderColor: '#00a32a',
										backgroundColor: 'rgba(0,163,42,0.08)',
										fill: true,
										tension: 0.3
									}
								]
							},
							options: { responsive: true, plugins: { legend: { position: 'top' } } }
						});
					});
				})();
				</script>
				<?php
			} else {
				echo '<p>' . esc_html__( 'No snapshots yet.', 'hoeveel-zijn-er-nog' ) . '</p>';
			}
			?>
			<?php
		}
	} else {
		?>
		<form method="get">
			<input type="hidden" name="page" value="hzen">
			<?php $list_table->display(); ?>
		</form>
		<?php
	}
	?>
</div>
