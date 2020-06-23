<?php
/**
 * The template used to display file form fields.
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Form Fields
 * @since   1.0.0
 * @version 1.0.0
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form    = $view_args['form'];
$field   = $view_args['field'];
$classes = $view_args['classes'];
$value   = isset( $field['value'] ) ? $field['value'] : '';
?>
<div id="charitable_field_<?php echo $field['key']; ?>" class="<?php echo $classes; ?>">
	<?php if ( isset( $field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo $field['key']; ?>_element">
			<?php echo $field['label']; ?>
		</label>
	<?php endif ?>
	<input type="file" name="<?php echo $field['key']; ?>" id="charitable_field_<?php echo $field['key']; ?>_element" value="<?php echo $value; ?>" <?php echo charitable_get_arbitrary_attributes( $field ); ?>/>
</div>
