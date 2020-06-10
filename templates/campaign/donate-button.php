<?php
/**
 * Displays the donate button to be displayed on campaign pages.
 *
 * Override this template by copying it to yourtheme/charitable/campaign/donate-button.php
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Campaign Page
 * @since   1.3.0
 * @version 1.6.42
 */

$campaign = $view_args['campaign'];

?>
<form class="campaign-donation" method="post">
	<?php wp_nonce_field( 'charitable-donate', 'charitable-donate-now' ); ?>
	<input type="hidden" name="charitable_action" value="start_donation" />
	<input type="hidden" name="campaign_id" value="<?php echo $campaign->ID; ?>" />
	<button type="submit" name="charitable_submit" class="<?php echo esc_attr( charitable_get_button_class( 'donate' ) ); ?>"><?php esc_attr_e( 'Donate', 'charitable' ); ?></button>
</form>
