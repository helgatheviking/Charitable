<?php
/**
 * Display the export button in the campaign filters box.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin View/Campaigns Page
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

	$modal_class  = apply_filters( 'charitable_modal_window_class', 'charitable-modal' );
	$start_date   = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : null;
	$end_date     = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : null;
	$status       = isset( $_GET['status'] ) ? $_GET['status'] : '';
	$report_type  = isset( $_GET['report_type'] ) ? $_GET['report_type'] : 'campaigns';
	$report_types = apply_filters( 'charitable_campaign_export_report_types', array(
		'campaigns' => __( 'Campaigns', 'charitable' ),
	) );
	$statuses     = array(
		''        => __( 'Any', 'charitable' ),
		'pending' => __( 'Pending', 'charitable' ),
		'draft'   => __( 'Draft', 'charitable' ),
		'active'  => __( 'Active', 'charitable' ),
		'finish'  => __( 'Finished', 'charitable' ),
		'publish' => __( 'Published', 'charitable' ),
	);

	?>
<div id="charitable-campaigns-export-modal" style="display: none;" class="charitable-campaigns-modal <?php echo esc_attr( $modal_class ); ?>" tabindex="0">
	<a class="modal-close"></a>
	<h3><?php _e( 'Export Campaigns', 'charitable' ); ?></h3>
	<form class="charitable-campaigns-modal-form charitable-modal-form" method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
		<?php wp_nonce_field( 'charitable_export_campaigns', '_charitable_export_nonce' ); ?>
		<input type="hidden" name="charitable_action" value="export_campaigns" />
		<input type="hidden" name="page" value="charitable-campaigns-table" />
		<fieldset>
			<legend><?php _e( 'Filter by Date Created', 'charitable' ); ?></legend>
			<input type="text" id="charitable-export-start_date" name="start_date" class="charitable-datepicker" value="<?php echo $start_date; ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-export-end_date" name="end_date" class="charitable-datepicker" value="<?php echo $end_date; ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<label for="charitable-campaigns-export-campaign"><?php _e( 'Filter by Status', 'charitable' ); ?></label>
		<select id="charitable-campaigns-export-campaign" name="status">
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $status ); ?>><?php echo $label; ?></option>
			<?php endforeach ?>
		</select>
		<?php if ( count( $report_types ) > 1 ) : ?>
			<label for="charitable-campaigns-export-report-type"><?php _e( 'Type of Report', 'charitable' ); ?></label>
			<select id="charitable-campaigns-export-report-type" name="report_type">
			<?php foreach ( $report_types as $key => $report_label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo $report_label; ?></option>
			<?php endforeach; ?>
			</select>
		<?php else : ?>
			<input type="hidden" name="report_type" value="<?php echo esc_attr( key( $report_types ) ); ?>" />
		<?php endif ?>
		<?php do_action( 'charitable_export_campaigns_form' ); ?>
		<button name="charitable-export-campaigns" class="button button-primary"><?php _e( 'Export', 'charitable' ); ?></button>
	</form>
</div>
