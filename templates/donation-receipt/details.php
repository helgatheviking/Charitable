<?php
/**
 * Displays the donation details.
 *
 * Override this template by copying it to yourtheme/charitable/donation-receipt/details.php
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Donation Receipt
 * @since   1.0.0
 * @version 1.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* @var Charitable_Donation */
$donation = $view_args['donation'];
$amount   = $donation->get_total_donation_amount();

?>
<h3 class="charitable-header"><?php _e( 'Your Donation', 'charitable' ); ?></h3>
<table class="donation-details charitable-table">
	<thead>
		<tr>
			<th><?php _e( 'Campaign', 'charitable' ); ?></th>
			<th><?php _e( 'Total', 'charitable' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $donation->get_campaign_donations() as $campaign_donation ) : ?>
		<tr>
			<td class="campaign-name">
				<?php
					echo $campaign_donation->campaign_name;

					/**
					 * Do something after displaying the campaign name.
					 *
					 * @since 1.3.0
					 *
					 * @param object              $campaign_donation Database record for the campaign donation.
					 * @param Charitable_Donation $donation          The Donation object.
					 */
					do_action( 'charitable_donation_receipt_after_campaign_name', $campaign_donation, $donation );
				?>
			</td>
			<td class="donation-amount"><?php echo charitable_format_money( $campaign_donation->amount ); ?></td>
		</tr>
	<?php endforeach ?>
	</tbody>
	<tfoot>
		<?php
			/**
			 * Do something before displaying the total.
			 *
			 * If you add markup, make sure it's a table row with two cells.
			 *
			 * @since 1.5.0
			 *
			 * @param Charitable_Donation $donation The Donation object.
			 */
			do_action( 'charitable_donation_receipt_before_donation_total', $donation );
		?>
		<tr>
			<td><?php _e( 'Total', 'charitable' ); ?></td>
			<td>
				<?php
					/**
					 * Filter the total donation amount.
					 *
					 * @since  1.5.0
					 *
					 * @param  string              $amount   The default amount to display.
					 * @param  float               $total    The total, unformatted.
					 * @param  Charitable_Donation $donation The Donation object.
					 * @param  string              $context  The context in which this is being shown.
					 * @return string
					 */
					echo apply_filters( 'charitable_donation_receipt_donation_amount', charitable_format_money( $amount ), $amount, $donation, 'details' )
				?>
			</td>
		</tr>
		<?php
			/**
			 * Do something after displaying the total.
			 *
			 * If you add markup, make sure it's a table row with two cells.
			 *
			 * @since 1.5.0
			 *
			 * @param Charitable_Donation $donation The Donation object.
			 */
			do_action( 'charitable_donation_receipt_after_donation_total', $donation );
		?>
	</tfoot>
</table>
