<?php
/**
 * Display datepicker field.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @since     1.5.0
 * @version   1.6.29
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
}

if ( array_key_exists( 'value', $view_args ) ) {
	$date = 'data-date="' . esc_attr( date_i18n( 'F d, Y', strtotime( $view_args['value'] ) ) ) . '"';
} elseif ( array_key_exists( 'default', $view_args ) ) {
	$date = 'data-date="' . esc_attr( $view_args['default'] ) . '"';
} else {
	$date = '';
}

$is_required = array_key_exists( 'required', $view_args ) && $view_args['required'];

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
	<input type="text"
		id="<?php echo esc_attr( $view_args['id'] ); ?>"
		name="<?php echo esc_attr( $view_args['key'] ); ?>"
		class="charitable-datepicker"
		tabindex="<?php echo esc_attr( $view_args['tabindex'] ); ?>"
		<?php echo $date; ?>
	/>
	<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
</div><!-- #<?php echo $view_args['wrapper_id']; ?> -->