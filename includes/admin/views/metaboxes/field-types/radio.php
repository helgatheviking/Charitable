<?php
/**
 * Display radio field.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @since     1.6.7
 * @version   1.6.24
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

$is_required = array_key_exists( 'required', $view_args ) && $view_args['required'];

?>
<div id="<?php echo esc_attr( $view_args['wrapper_id'] ); ?>" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>" <?php echo charitable_get_arbitrary_attributes( $view_args ); ?>>
	<?php if ( isset( $view_args['label'] ) ) : ?>
		<label for="<?php echo esc_attr( $view_args['id'] ); ?>" id="charitable_field_<?php echo esc_attr( $view_args['key'] ); ?>_label">
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
	<ul class="charitable-radio-list">
	<?php foreach ( $view_args['options'] as $key => $option ) : ?>
		<li><input type="radio"
				id="<?php echo esc_attr( $view_args['key'] . '-' . $key ); ?>"
				name="<?php echo esc_attr( $view_args['key'] ); ?>"
				value="<?php echo esc_attr( $key ); ?>"
				aria-describedby="charitable_field_<?php echo esc_attr( $view_args['key'] ); ?>_label"
				<?php checked( $view_args['value'], $key ); ?> />
			<label for="<?php echo esc_attr( $view_args['key'] . '-' . $key ); ?>"><?php echo $option; ?></label>
		</li>
	<?php endforeach ?>
	</ul>
	<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
</div><!-- #<?php echo $view_args['wrapper_id']; ?> -->
