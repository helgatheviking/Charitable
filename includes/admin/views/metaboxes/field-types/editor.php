<?php
/**
 * Display rich text editor field.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2019, Studio 164a
 * @since     1.7.0
 * @version   1.7.0
 */

if ( ! array_key_exists( 'form_view', $view_args ) || ! $view_args['form_view']->field_has_required_args( $view_args ) ) {
	return;
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

	<?php
	wp_editor(
		$view_args['value'],
		$view_args['id'],
		array(
			'textarea_name' => $view_args['key'],
			'tabindex'      => $view_args['tabindex'],
			'editor_class'  => $is_required ? 'required' : '',
		)
	);
	?>

<?php if ( isset( $view_args['description'] ) ) : ?>
		<span class="charitable-helper"><?php echo esc_html( $view_args['description'] ); ?></span>
	<?php endif ?>
</div><!-- #<?php echo $view_args['wrapper_id']; ?> -->
