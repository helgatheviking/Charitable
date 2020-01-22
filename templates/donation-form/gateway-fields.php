<?php
/**
 * The template used to display the gateway fields.
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Donation Form
 * @since   1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form     = $view_args['form'];
$field    = $view_args['field'];
$classes  = $view_args['classes'];
$gateways = $field['gateways'];
$default  = isset( $field['default'] ) && isset( $gateways[ $field['default'] ] ) ? $field['default'] : key( $gateways );

?>
<fieldset id="charitable-gateway-fields" class="charitable-fieldset">
	<?php
	if ( isset( $field['legend'] ) ) : ?>

		<div class="charitable-form-header"><?php echo $field['legend'] ?></div>

	<?php
	endif;

	if ( count( $gateways ) > 1 ) :
	?>
		<fieldset class="charitable-fieldset-field-wrapper">
			<div class="charitable-fieldset-field-header" id="charitable-gateway-selector-header"><?php _e( 'Choose Your Payment Method', 'charitable' ) ?></div>
			<ul id="charitable-gateway-selector" class="charitable-radio-list charitable-form-field">
				<?php foreach ( $gateways as $gateway_id => $details ) : ?>
					<li><input type="radio"
							id="gateway-<?php echo esc_attr( $gateway_id ) ?>"
							name="gateway"
							value="<?php echo esc_attr( $gateway_id ) ?>"
							aria-describedby="charitable-gateway-selector-header"
							<?php checked( $default, $gateway_id ) ?> />
						<label for="gateway-<?php echo esc_attr( $gateway_id ) ?>"><?php echo $details['label'] ?></label>
					</li>
				<?php endforeach ?>
			</ul>
		</fieldset>
	<?php
	endif;

	foreach ( $gateways as $gateway_id => $details ) :

		if ( ! isset( $details['fields'] ) || empty( $details['fields'] ) ) :
			continue;
		endif;

		?>
		<div id="charitable-gateway-fields-<?php echo $gateway_id ?>" class="charitable-gateway-fields charitable-form-fields cf" data-gateway="<?php echo $gateway_id ?>">
			<?php $form->view()->render_fields( $details['fields'] ) ?>
		</div><!-- #charitable-gateway-fields-<?php echo $gateway_id ?> -->
	<?php endforeach ?>
</fieldset><!-- .charitable-fieldset -->
