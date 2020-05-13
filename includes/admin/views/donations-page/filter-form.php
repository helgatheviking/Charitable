<?php
/**
 * Display the Filters modal.
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
 * @param string $class The modal window class.
 */
$modal_class = apply_filters( 'charitable_modal_window_class', 'charitable-modal' );

$campaign_id = isset( $_GET['campaign_id'] ) ? intval( $_GET['campaign_id'] ) : '';
$campaigns   = get_posts(
	array(
		'post_type'   => Charitable::CAMPAIGN_POST_TYPE,
		'nopaging'    => true,
		'post_status' => array( 'draft', 'pending', 'private', 'publish' ),
		'perm'        => 'readable',
	)
);

$start_date  = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : null;
$end_date    = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : null;
$post_status = isset( $_GET['post_status'] ) ? $_GET['post_status'] : 'all';

?>
<div id="charitable-donations-filter-modal" style="display: none" class="charitable-donations-modal <?php echo esc_attr( $modal_class ); ?>" tabindex="0">
	<a class="modal-close"></a>
	<h3><?php _e( 'Filter Donations', 'charitable' ); ?></h3>
	<form class="charitable-donations-modal-form charitable-modal-form" method="get" action="">
		<input type="hidden" name="post_type" class="post_type_page" value="donation">
		<fieldset>
			<legend><?php _e( 'Filter by Date', 'charitable' ); ?></legend>
			<input type="text" id="charitable-filter-start_date" name="start_date" class="charitable-datepicker" value="<?php echo $start_date; ?>" placeholder="<?php esc_attr_e( 'From:', 'charitable' ); ?>" />
			<input type="text" id="charitable-filter-end_date" name="end_date" class="charitable-datepicker" value="<?php echo $end_date; ?>" placeholder="<?php esc_attr_e( 'To:', 'charitable' ); ?>" />
		</fieldset>
		<label for="charitable-donations-filter-status"><?php _e( 'Filter by Status', 'charitable' ); ?></label>
		<select id="charitable-donations-filter-status" name="post_status">
			<option value="all" <?php selected( $post_status, 'all' ); ?>><?php _e( 'All', 'charitable' ); ?></option>
			<?php foreach ( charitable_get_valid_donation_statuses() as $key => $status ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $post_status, $key ); ?>><?php echo $status; ?></option>
			<?php endforeach ?>
		</select>
		<label for="charitable-donations-filter-campaign"><?php _e( 'Filter by Campaign', 'charitable' ); ?></label>
		<select id="charitable-donations-filter-campaign" name="campaign_id">
			<option value="all"><?php _e( 'All Campaigns', 'charitable' ); ?></option>
			<?php foreach ( $campaigns as $campaign ) : ?>
				<option value="<?php echo $campaign->ID; ?>" <?php selected( $campaign_id, $campaign->ID ); ?>><?php echo get_the_title( $campaign->ID ); ?></option>
			<?php endforeach ?>
		</select>
		<?php do_action( 'charitable_filter_donations_form' ); ?>
		<button type="submit" class="button button-primary"><?php _e( 'Filter', 'charitable' ); ?></button>
	</form>
</div>
