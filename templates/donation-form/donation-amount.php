<?php
/**
 * The template used to display the donation amount inputs.
 *
 * Override this template by copying it to yourtheme/charitable/donation-form/donation-amount.php
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) ) {
	return;
}

/* @var Charitable_Donation_Form */
$form     = $view_args['form'];
$form_id  = $form->get_form_identifier();
$campaign = $form->get_campaign();

if ( is_null( $campaign ) ) {
	return;
}

$suggested       = $campaign->get_suggested_donations();
$currency_helper = charitable_get_currency_helper();

if ( empty( $suggested ) && ! $campaign->get( 'allow_custom_donations' ) ) {
	return;
}

/**
 * Do something before the donation options fields.
 *
 * @since 1.0.0
 *
 * @param Charitable_Donation_Form $form An instance of `Charitable_Donation_Form`.
 */
do_action( 'charitable_donation_form_before_donation_amount', $form );

?>
<div class="charitable-donation-options">
	<?php
	/**
	 * Do something before the donation amounts are listed.
	 *
	 * @since 1.0.0
	 *
	 * @param Charitable_Donation_Form $form An instance of `Charitable_Donation_Form`.
	 */
	do_action( 'charitable_donation_form_before_donation_amounts', $form );

	charitable_template_from_session(
		'donation-form/donation-amount-list.php',
		array(
			'campaign' => $campaign,
			'form_id'  => $form_id,
		),
		'donation_form_amount_field',
		array(
			'campaign_id' => $campaign->ID,
			'form_id'     => $form_id,
		)
	);

	/**
	 * Do something after the donation amounts are listed.
	 *
	 * @since 1.0.0
	 *
	 * @param Charitable_Donation_Form $form An instance of `Charitable_Donation_Form`.
	 */
	do_action( 'charitable_donation_form_after_donation_amounts', $form );
	?>
</div><!-- .charitable-donation-options -->
<?php

/**
 * Do something after the donation options fields.
 *
 * @since 1.0.0
 *
 * @param Charitable_Donation_Form $form An instance of `Charitable_Donation_Form`.
 */
do_action( 'charitable_donation_form_after_donation_amount', $form );
