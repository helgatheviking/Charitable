<?php 
/**
 * Renders the donation details meta box for the Donation post type.
 *
 * @author  Studio 164a
 * @since   1.0.0
 */
global $post;

$donation = charitable_get_donation( $post->ID );
$donor = $donation->get_donor();

?>
<div id="charitable-donation-overview-metabox" class="charitable-metabox">
    <div id="donor" class="charitable-media-block">
        <div class="donor-avatar charitable-media-image">
            <?php echo $donor->get_avatar( 80 ) ?>
        </div>
        <div class="donor-facts charitable-media-body">
            <h3 class="donor-name"><?php echo $donor->get_name() ?></h3>
            <span class="donor-email"><?php echo $donor->get_email() ?></span>
            <?php 
            /**
             * @hook charitable_donation_details_donor_facts
             */
            do_action( 'charitable_donation_details_donor_facts', $donor, $donation );
            ?>
        </div>
    </div>
    <div id="donation-summary">
        <h3 class="donation-number"><?php printf( '%s #%d', 
            __( 'Donation', 'charitable' ), 
            $donation->get_number() ) ?></h3>
        <span class="donation-date"><?php echo $donation->get_date() ?></span>
        <span class="donation-status"><?php printf( '%s: <span class="status">%s</span>', 
            __( 'Status', 'charitable' ), 
            $donation->get_status( true ) ) ?></span>
    </div>
</div>
<table id="charitable-campaign-donations" class="charitable-metabox">
    <thead>
        <tr>
            <th class="col-campaign-name"><?php _e( 'Campaign', 'charitable' ) ?></th>
            <th class="col-campaign-donation-amount"><?php _e( 'Total', 'charitable' ) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $donation->get_campaign_donations() as $campaign_donation ) : ?>
        <tr>
            <td class="campaign-name"><?php echo $campaign_donation->campaign_name ?></td>
            <td class="campaign-donation-amount"><?php echo charitable_format_money( $campaign_donation->amount ) ?></td>
        </tr>
    <?php endforeach ?>
    </tbody>
    <tfoot>
        <tr>
            <th><?php _e( 'Total', 'charitable' ) ?></th>
            <td><?php echo charitable_format_money( $donation->get_total_donation_amount() ) ?></td>
        </tr>
        <tr>
            <th><?php _e( 'Payment Method', 'charitable' ) ?></th>
            <td><?php echo $donation->get_gateway_label() ?></td>
        </tr>
        <tr>
            <th><?php _e( 'Change Status', 'charitable' ) ?></th>
            <td>
                <select id="change-donation-status" name="post_status">
                <?php foreach ( charitable_get_valid_donation_statuses() as $status => $label ) : ?>
                    <option value="<?php echo $status ?>" <?php selected( $status, $donation->get_status() ) ?>><?php echo $label ?></option>
                <?php endforeach ?>
                </select>
            </td>
        </tr>
        <tr class="hide-if-js">
            <td colspan="2">
                <input type="submit" name="update" class="button button-primary" value="<?php _e( 'Update Status', 'charitable' ) ?>" />
            </td>
        </tr>
    </tfoot>
</table>