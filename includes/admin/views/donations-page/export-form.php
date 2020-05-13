<?php
/**
 * Display the export button in the donation filters box.
 *
 * @author  Studio 164a
 * @package Charitable/Admin View/Donations Page
 * @since   1.0.0
 * @version 1.6.39
 */

/**
 * Filter the class to use for the modal window.
 *
 * @since 1.0.0
 *
 * @param string $class The class name.
 */
$modal_class  = apply_filters( 'charitable_modal_window_class', 'charitable-modal' );

$start_date   = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : null;
$end_date     = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : null;
$post_status  = isset( $_GET['post_status'] ) ? $_GET['post_status'] : 'all';
$report_type  = isset( $_GET['report_type'] ) ? $_GET['report_type'] : 'donations';

/**
 * Filter the type of exportable report types.
 *
 * @since 1.6.0
 *
 * @param array $types Types of reports.
 */
$report_types = apply_filters(
	'charitable_donation_export_report_types',
	array(
		'donations' => __( 'Donations', 'charitable' ),
	)
);
?>
<div id="charitable-donations-export-modal" style="display: none;" class="charitable-donations-modal <?php echo esc_attr( $modal_class ); ?>" tabindex="0">
	<a class="modal-close"></a>
	<h3><?php _e( 'Export Donations', 'charitable' ); ?></h3>
	<form class="charitable-donations-modal-form charitable-modal-form" method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
		<?php wp_nonce_field( 'charitable_export_donations', '_charitable_export_nonce' ); ?>
		<input type="hidden" name="charitable_action" value="export_donations" />
		<input type="hidden" name="page" value="charitable-donations-table" />
		<fieldset>
			<legend><?php _e( 'Filter by Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-export-start_date" name="start_date" class="charitable-datepicker" value="<?php echo $start_date; ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-export-end_date" name="end_date" class="charitable-datepicker" value="<?php echo $end_date; ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<label for="charitable-donations-export-status"><?php _e( 'Filter by Status', 'charitable' ); ?></label>
		<select id="charitable-donations-export-status" name="post_status">
			<option value="all" <?php selected( $post_status, 'all' ); ?>><?php _e( 'All', 'charitable' ); ?></option>
			<?php foreach ( charitable_get_valid_donation_statuses() as $key => $status ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $post_status, $key ); ?>><?php echo $status; ?></option>
			<?php endforeach ?>
		</select>
		<label for="charitable-donations-export-campaign"><?php _e( 'Filter by Campaign', 'charitable' ); ?></label>
		<select id="charitable-donations-export-campaign" name="campaign_id">
			<option value="all"><?php _e( 'All Campaigns', 'charitable' ); ?></option>
			<?php
			foreach ( get_posts( array(
				'post_type'      => 'campaign',
				'post_status'    => array( 'draft', 'pending', 'private', 'publish' ),
				'perm'           => 'readable',
				'posts_per_page' => -1,
			) ) as $campaign ) :
			?>
				<option value="<?php echo $campaign->ID; ?>"><?php echo get_the_title( $campaign->ID ); ?></option>
			<?php endforeach ?>
		</select>
		<?php if ( count( $report_types ) > 1 ) : ?>
			<label for="charitable-donations-export-report-type"><?php _e( 'Type of Report', 'charitable' ); ?></label>
			<select id="charitable-donations-export-report-type" name="report_type">
			<?php foreach ( $report_types as $key => $report_label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo $report_label; ?></option>
			<?php endforeach; ?>
			</select>
		<?php else : ?>
			<input type="hidden" name="report_type" value="<?php echo esc_attr( key( $report_types ) ); ?>" />
		<?php endif ?>
		<?php do_action( 'charitable_export_donations_form' ); ?>
		<button name="charitable-export-donations" class="button button-primary"><?php _e( 'Export', 'charitable' ); ?></button>
	</form>
</div>
