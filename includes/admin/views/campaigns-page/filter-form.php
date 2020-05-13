<?php
/**
 * Display the Filters modal.
 *
 * @author  Studio 164a
 * @package Charitable/Admin View/Campaigns Page
 * @since   1.6.36
 * @version 1.6.39
 */

/**
 * Filter the class to use for the modal window.
 *
 * @since 1.0.0
 *
 * @param string $class The modal window class.
 */
$modal_class = apply_filters( 'charitable_modal_window_class', 'charitable-modal' );

$start_date_from = isset( $_GET['start_date_from'] ) ? sanitize_text_field( $_GET['start_date_from'] ) : null;
$start_date_to   = isset( $_GET['start_date_to'] ) ? sanitize_text_field( $_GET['start_date_to'] ) : null;
$end_date_from   = isset( $_GET['end_date_from'] ) ? sanitize_text_field( $_GET['end_date_from'] ) : null;
$end_date_to     = isset( $_GET['end_date_to'] ) ? sanitize_text_field( $_GET['end_date_to'] ) : null;
$status          = isset( $_GET['status'] ) ? $_GET['status'] : 'all';

$statuses = array(
	'all'     => __( 'All', 'charitable' ),
	'pending' => __( 'Pending', 'charitable' ),
	'draft'   => __( 'Draft', 'charitable' ),
	'active'  => __( 'Active', 'charitable' ),
	'finish'  => __( 'Finished', 'charitable' ),
	'publish' => __( 'Published', 'charitable' ),
);
?>
<div id="charitable-campaigns-filter-modal" style="display: none" class="charitable-campaigns-modal <?php echo esc_attr( $modal_class ); ?>" tabindex="0">
	<a class="modal-close"></a>
	<h3><?php _e( 'Filter Campaigns', 'charitable' ); ?></h3>
	<form class="charitable-campaigns-modal-form charitable-modal-form" method="get" action="">
		<input type="hidden" name="post_type" class="post_type_page" value="campaign">
		<fieldset>
			<legend><?php _e( 'Filter by Start Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-filter-start_date_from" name="start_date_from" class="charitable-datepicker" value="<?php echo $start_date_from; ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-filter-start_date_to" name="start_date_to" class="charitable-datepicker" value="<?php echo $start_date_to; ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<fieldset>
			<legend><?php _e( 'Filter by End Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-filter-end_date_from" name="end_date_from" class="charitable-datepicker" value="<?php echo $end_date_from; ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-filter-end_date_to" name="end_date_to" class="charitable-datepicker" value="<?php echo $end_date_to; ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<label for="charitable-campaigns-filter-status"><?php _e( 'Filter by Status', 'charitable' ); ?></label>
		<select id="charitable-campaigns-filter-status" name="post_status">
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo $label; ?></option>
			<?php endforeach ?>
		</select>
		<?php
		/**
		 * Add additional filters to the form.
		 *
		 * @since 1.6.36
		 */
		do_action( 'charitable_filter_campaigns_form' );
		?>
		<button type="submit" class="button button-primary"><?php _e( 'Filter', 'charitable' ); ?></button>
	</form>
</div>
