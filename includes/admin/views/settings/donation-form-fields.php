<?php
/**
 * Display the donation form fields options.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

if ( ! isset( $view_args['form'] ) || ! isset( $view_args['field'] ) ) {
	return;
}

$form        = $view_args['form'];
$field       = $view_args['field'];
$classes     = $view_args['classes'];
$options     = isset( $field['options'] ) ? $field['options'] : array();
$value       = isset( $field['value'] ) ? (array) $field['value'] : array();
$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

if ( empty( $options ) ) {
	return;
}
?>
<div id="charitable_field_<?php echo esc_attr( $field['key'] ); ?>" class="<?php echo esc_attr( $classes ); ?>">
	<?php if ( isset( $field['label'] ) ) : ?>
		<label for="charitable_field_<?php echo $field['key']; ?>">
			<?php echo $field['label']; ?>
		</label>
	<?php endif ?>
	<ul class="options">
	<?php foreach ( $options as $val => $label ) : ?>
		<li>
			<input type="checkbox" name="<?php echo $field['key']; ?>[]" value="<?php echo $val; ?>" <?php checked( in_array( $val, $value ) ); ?> />
			<?php echo $label; ?>
		</li>
	<?php endforeach ?>
	</ul>
</div>
