<?php
/**
 * Display the campaign donations table for adding/editing donations to campaigns.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @since     1.5.0
 * @version   1.6.39
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$campaigns = get_posts(
	array(
		'post_type'      => 'campaign',
		'posts_per_page' => -1,
		'post_status'    => array( 'draft', 'pending', 'private', 'publish' ),
		'fields'         => 'ids',
		'perm'           => 'readable',
	)
);

$campaign_donations = $view_args['value'];

if ( empty( $campaign_donations ) ) {
	$campaign_donations = array(
		(object) array(
			'campaign_id' => '',
			'amount'      => '',
		),
	);
}

$currency_helper = Charitable_Currency::get_instance();

?>
<div id="charitable-campaign-donations-metabox-wrap" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>">
	<table id="charitable-campaign-donations" class="widefat">
		<thead>
			<tr class="table-header">
				<th><label id="<?php echo esc_attr( $view_args['id'] ); ?>-campaign-label"><?php _e( 'Campaign', 'charitable' ); ?></label></th>
				<th><label id="<?php echo esc_attr( $view_args['id'] ); ?>-amount-label"><?php _e( 'Amount', 'charitable' ); ?></label></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $campaign_donations as $i => $campaign_donation ) : ?>
			<tr>
				<td>
					<select name="<?php echo esc_attr( sprintf( '%s[%d][campaign_id]', $view_args['key'], $i ) ); ?>" labelledby="<?php echo esc_attr( $view_args['id'] ); ?>-campaign-label" tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>">
						<option value=""><?php _e( 'Select a campaign', 'charitable' ); ?></option>
						<?php foreach ( $campaigns as $campaign_id ) : ?>
							<option value="<?php echo $campaign_id; ?>" <?php selected( $campaign_id, $campaign_donation->campaign_id ); ?>><?php echo get_the_title( $campaign_id ); ?></option>
						<?php endforeach ?>
					</select>
				</td>
				<td>
					<input type="text"
						class="currency-input"
						name="<?php echo esc_attr( sprintf( '%s[%d][amount]', $view_args['key'], $i ) ); ?>"
						labelledby="<?php echo esc_attr( $view_args['id'] ); ?>-amount-label"
						tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>"
						value="<?php echo empty( $campaign_donation->amount ) ? '' : esc_attr( $currency_helper->get_sanitized_and_localized_amount( $campaign_donation->amount ) ); ?>"
					/>
					<?php if ( isset( $campaign_donation->campaign_donation_id ) ) : ?>
						<input type="hidden"
							name="<?php echo esc_attr( sprintf( '%s[%d][campaign_donation_id]', $view_args['key'], $i ) ); ?>"
							value="<?php echo $campaign_donation->campaign_donation_id; ?>"
						/>
					<?php endif ?>
				</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
