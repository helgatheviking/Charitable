<?php
/**
 * Display multi select field.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @since     1.6.5
 * @version   1.6.24
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$value       = is_array( $view_args['value'] ) ? $view_args['value'] : array( $view_args['value'] );
$is_required = array_key_exists( 'required', $view_args ) && $view_args['required'];
$field_attrs = array_key_exists( 'field_attrs', $view_args ) ? $view_args['field_attrs'] : array();

?>
<div id="<?php echo esc_attr( $view_args['wrapper_id'] ); ?>" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>" <?php echo charitable_get_arbitrary_attributes( $view_args ); ?>>
	<?php if ( isset( $view_args['label'] ) ) : ?>
		<label for="<?php echo esc_attr( $view_args['id'] ); ?>">
			<?php
			echo esc_html( $view_args['label'] );
			if ( $is_required ) :
				?>
				<abbr class="required" title="required">*</abbr>
				<?php
			endif;
			?>
		</label>
	<?php endif ?>
	<select
		id="<?php echo esc_attr( $view_args['id'] ); ?>"
		name="<?php echo esc_attr( $view_args['key'] ); ?>"
		tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>"
		multiple="true"
		<?php echo charitable_get_arbitrary_attributes( $field_attrs ); ?>
	>
	<?php
	foreach ( $view_args['options'] as $key => $option ) :
		if ( is_array( $option ) ) :
			$label = isset( $option['label'] ) ? $option['label'] : '';
			?>
			<optgroup label="<?php echo esc_attr( $label ); ?>">
			<?php foreach ( $option['options'] as $k => $opt ) : ?>
				<option value="<?php echo esc_attr( $k ); ?>" <?php selected( in_array( $k, $value ) ); ?>><?php echo $opt; ?></option>
			<?php endforeach ?>
			</optgroup>
		<?php else : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( in_array( $key, $value ) ); ?>><?php echo $option; ?></option>
			<?php
		endif;
	endforeach;
	?>
	</select>
	<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
</div><!-- #<?php echo $view_args['wrapper_id']; ?> -->
