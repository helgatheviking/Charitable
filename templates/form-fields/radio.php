<?php
/**
 * The template used to display radio form fields.
 *
 * Override this template by copying it to yourtheme/charitable/form-fields/radio.php
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Form Fields
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

$form        = $view_args['form'];
$field       = $view_args['field'];
$classes     = $view_args['classes'];
$is_required = isset( $field['required'] ) ? $field['required'] : false;
$options     = isset( $field['options'] ) ? $field['options'] : array();
$value       = isset( $field['value'] ) ? $field['value'] : '';

if ( empty( $options ) ) {
	return;
}

?>
<div id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>" class="<?php echo $classes; ?>">
	<fieldset class="charitable-fieldset-field-wrapper">
		<?php if ( isset( $field['label'] ) ) : ?>
			<div class="charitable-fieldset-field-header" id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_label">
				<?php echo $field['label']; ?>
				<?php if ( $is_required ) : ?>
					<abbr class="required" title="required">*</abbr>
				<?php endif ?>
			</div>
		<?php endif ?>
		<ul class="charitable-radio-list <?php echo esc_attr( $view_args['classes'] ); ?>">
			<?php foreach ( $options as $option => $label ) : ?>
				<li><input type="radio"
						id="<?php echo esc_attr( $field['key'] . '-' . $option ); ?>"
						name="<?php echo esc_attr( $field['key'] ); ?>"
						value="<?php echo esc_attr( $option ); ?>"
						aria-describedby="charitable_field_<?php echo esc_attr( $field['key'] ); ?>_label"
						<?php checked( $value, $option ); ?>
						<?php echo charitable_get_arbitrary_attributes( $field ); ?> />
					<label for="<?php echo esc_attr( $field['key'] . '-' . $option ); ?>"><?php echo $label; ?></label>
				</li>
			<?php endforeach ?>
		</ul>
	</fieldset>
</div>
