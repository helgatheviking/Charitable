<?php
/**
 * Displays a table of the user's donations, with links to the donation receipts.
 *
 * Override this template by copying it to yourtheme/charitable/shortcodes/my-donations.php
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Account
 * @since   1.4.0
 * @version 1.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user      = array_key_exists( 'user', $view_args ) ? $view_args['user'] : charitable_get_user( get_current_user_id() );
$donations = $view_args['donations'];

/**
 * Do something before rendering the donations.
 *
 * @param  object[] $donations An array of donations as a simple object.
 * @param  array    $view_args All args passed to template.
 */
do_action( 'charitable_my_donations_before', $donations, $view_args );

if ( $donations instanceof Charitable_Donations_Query && $donations->count() ) :
	?>
	<table class="charitable-my-donations charitable-table">
		<thead>
			<tr>
				<th scope="col"><?php _e( 'Date', 'charitable' ); ?></th>
				<?php
					/**
					 * Add a column header after the donation date header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.0
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 * @param array    $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_header_after_date', $donations, $view_args );
				?>
				<th scope="col"><?php _e( 'Amount', 'charitable' ); ?></th>
				<?php
					/**
					 * Add a column header after the amount header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.0
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 * @param array    $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_header_after_amount', $donations, $view_args );
				?>
				<th scope="col"><?php _e( 'Campaign', 'charitable' ); ?></th>
				<?php
					/**
					 * Add a column header after the campaign header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.0
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 * @param array    $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_header_after_campaigns', $donations, $view_args );
				?>
				<?php
					/**
					 * Add a column header after the status header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.4
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 */
					do_action( 'charitable_my_donations_table_header_after_status', $donations, $view_args );
				?>
				<?php
					/**
					 * Add a column header after the receipt header. Any output should be wrapped in <th></th>.
					 *
					 * @since 1.5.0
					 *
					 * @param object[] $donations An array of donations as a simple object.
					 * @param array    $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_header_after_receipt', $donations, $view_args );
				?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $donations as $donation ) : ?>
			<tr>
				<td class="date" data-title="<?php esc_attr_e( 'Date', 'charitable' ); ?>"><?php echo mysql2date( 'F j, Y', get_post_field( 'post_date', $donation->ID ) ); ?></td>
				<?php
					/**
					 * Add a cell after the donation date. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.0
					 *
					 * @param object $donation  The donation as a simple object.
					 * @param array  $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_after_date', $donation, $view_args );
				?>
				<td class="amount" data-title="<?php esc_attr_e( 'Amount', 'charitable' ); ?>">
					<?php
						/**
						 * Filter the total donation amount.
						 *
						 * @since 1.6.19
						 *
						 * @param string $amount   The total donation amount.
						 * @param object $donation The donation as a simple object.
						 */
						echo apply_filters( 'charitable_my_donation_total_amount', charitable_format_money( $donation->amount ), $donation );
					?>
				</td>
				<?php
					/**
					 * Add a cell after the donation amount. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.0
					 *
					 * @param object $donation  The donation as a simple object.
					 * @param array  $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_after_amount', $donation, $view_args );
				?>
				<td class="campaign" data-title="<?php esc_attr_e( 'Campaign', 'charitable' ); ?>">

					<?php echo $donation->campaigns; ?>

					<div class="small actions">
						<a href="<?php echo esc_url( charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation->ID ) ) ); ?>"><?php _e( 'View Receipt', 'charitable' ); ?></a>
						<?php
						/**
						 * Add action links.
						 *
						 * @since 1.7.0
						 *
						 * @param object $donation  The donation as a simple object.
						 * @param array  $view_args All args passed to template.
						 */
						do_action( 'charitable_my_donations_table_donation_actions', $donation, $view_args );
					?>
					</div>

				</td>
				<?php
					/**
					 * Add a cell after the list of campaigns. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.0
					 *
					 * @param object $donation  The donation as a simple object.
					 * @param array  $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_after_campaigns', $donation, $view_args );
				?>
				<?php
					/**
					 * Add a cell after the donation status. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.4
					 *
					 * @param object $donation The donation as a simple object.
					 */
					do_action( 'charitable_my_donations_table_after_status', $donation, $view_args );
				?>
				<?php
					/**
					 * Add a cell after the link to the receipt. Any output should be wrapped in <td></td>.
					 *
					 * @since 1.5.0
					 *
					 * @param object $donation  The donation as a simple object.
					 * @param array  $view_args All args passed to template.
					 */
					do_action( 'charitable_my_donations_table_after_receipt', $donation, $view_args );
				?>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
	<?php
else :
	?>
	<p><?php _e( 'You have not made any donations yet.', 'charitable' ); ?></p>
	<?php
endif;

/**
 * Do something after rendering the donations.
 *
 * @param  object[] $donations An array of donations as a simple object.
 * @param  array    $view_args All args passed to template.
 */
do_action( 'charitable_my_donations_after', $donations, $view_args );
