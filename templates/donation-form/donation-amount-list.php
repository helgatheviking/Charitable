<?php
/**
 * The template used to display the donation amount inputs.
 *
 * Override this template by copying it to yourtheme/charitable/donation-form/donation-amount-list.php
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Donation Form
 * @since   1.5.0
 * @version 1.6.38
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! array_key_exists( 'form_id', $view_args ) || ! array_key_exists( 'campaign', $view_args ) ) {
	return;
}

/* @var Charitable_Campaign */
$campaign      = $view_args['campaign'];
$form_id       = $view_args['form_id'];
$suggested     = $campaign->get_suggested_donations();
$custom        = $campaign->get( 'allow_custom_donations' );
$amount        = $campaign->get_donation_amount_in_session();
$active_period = 'once' == $campaign->get_initial_donation_period() || 'variable' == $campaign->get( 'recurring_donation_mode' );

if ( 0 == $amount ) {
	$amount = $campaign->get_default_donation_amount();
}

if ( empty( $suggested ) && ! $custom ) {
	return;
}

if ( count( $suggested ) ) :

	$amount_is_suggestion = false;
	?>
	<ul class="donation-amounts">
		<?php
		foreach ( $suggested as $suggestion ) :
			$checked  = $active_period ? checked( $suggestion['amount'], $amount, false ) : '';
			$field_id = esc_attr(
				sprintf(
					'form-%s-field-%s',
					$form_id,
					$suggestion['amount']
				)
			);

			if ( strlen( $checked ) ) :
				$amount_is_suggestion = true;
			endif;
			?>
			<li class="donation-amount suggested-donation-amount <?php echo strlen( $checked ) ? 'selected' : ''; ?>">
				<label for="<?php echo $field_id; ?>">
					<input
						id="<?php echo $field_id; ?>"
						type="radio"
						name="donation_amount"
						value="<?php echo esc_attr( charitable_get_currency_helper()->sanitize_database_amount( $suggestion['amount'] ) ); ?>" <?php echo $checked; ?>
					/>
					<?php
						printf(
							'<span class="amount">%s</span> <span class="description">%s</span>',
							charitable_format_money( $suggestion['amount'], false, true ),
							isset( $suggestion['description'] ) ? $suggestion['description'] : ''
						);
					?>
				</label>
			</li>
			<?php
		endforeach;

		if ( $custom ) :
			$has_custom_donation_amount = $active_period && ( ! $amount_is_suggestion && $amount );
			?>
			<li class="donation-amount custom-donation-amount">
				<span class="custom-donation-amount-wrapper">
					<label for="form-<?php echo esc_attr( $form_id ); ?>-field-custom-amount">
						<input
							id="form-<?php echo esc_attr( $form_id ); ?>-field-custom-amount"
							type="radio"
							name="donation_amount"
							value="custom" <?php checked( $has_custom_donation_amount ); ?>
						/><span class="description"><?php echo apply_filters( 'charitable_donation_amount_form_custom_amount_text', __( 'Custom amount', 'charitable' ) ); ?></span>
					</label>
					<input
						type="text"
						class="custom-donation-input"
						name="custom_donation_amount"
						value="<?php echo $has_custom_donation_amount ? $amount : ''; ?>"
					/>
				</span>
			</li>
		<?php endif ?>
	</ul><!-- .donation-amounts -->
<?php elseif ( $custom ) : ?>
	<div id="custom-donation-amount-field" class="charitable-form-field charitable-custom-donation-field-alone">
		<input
			type="text"
			class="custom-donation-input"
			name="custom_donation_amount"
			placeholder="<?php esc_attr_e( 'Enter donation amount', 'charitable' ); ?>"
			value="<?php echo $amount ? esc_attr( $amount ) : ''; ?>"
		/>
	</div><!-- #custom-donation-amount-field -->
<?php endif ?>
