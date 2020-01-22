<?php
/**
 * Display fieldset.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @since     1.5.0
 * @version   1.5.0
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

?>
<fieldset id="<?php echo esc_attr( $view_args['wrapper_id'] ); ?>" class="<?php echo esc_attr( $view_args['wrapper_class'] ); ?>" <?php echo charitable_get_arbitrary_attributes( $view_args ); ?>>
	<?php if ( array_key_exists( 'legend', $view_args ) ) : ?>
		<h4 class="charitable-metabox-header charitable-fieldset-header"><?php echo esc_html( $view_args['legend'] ); ?></h4>
	<?php endif; ?>
	<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
	<?php
	$view_args['form_view']->render_fields( $view_args['fields'] );
	?>
</fieldset><!-- #<?php echo $view_args['wrapper_id']; ?> -->
