<?php
/**
 * Displays the credit card expiration select boxes.
 *
 * Override this template by copying it to yourtheme/charitable/cc-expiration.php
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

$form           = $view_args['form'];
$field          = $view_args['field'];
$classes        = $view_args['classes'];
$is_required    = isset( $field['required'] ) ? $field['required'] : false;
$current_year   = date( 'Y' );

?>
<div id="charitable_field_<?php echo $field['key'] ?>" class="<?php echo $classes ?>">
	<fieldset class="charitable-fieldset-field-wrapper">
		<?php if ( isset( $field['label'] ) ) : ?>
			<div class="charitable-fieldset-field-header" id="charitable_field_<?php echo esc_attr( $field['key'] ) ?>_label">
				<?php echo $field['label'] ?>
				<?php if ( $is_required ) : ?>
					<abbr class="required" title="required">*</abbr>
				<?php endif ?>
			</div>
		<?php endif ?>
		<select name="<?php echo $field['key'] ?>[month]" class="month" aria-describedby="charitable_field_<?php echo esc_attr( $field['key'] ) ?>_label">
			<?php foreach ( range( 1, 12 ) as $month ) :
				$padded_month = sprintf( '%02d', $month );
				?>
				<option value="<?php echo $padded_month ?>"><?php echo $padded_month ?></option>
			<?php endforeach ?>
		</select>
		<select name="<?php echo $field['key'] ?>[year]" class="year" aria-describedby="charitable_field_<?php echo esc_attr( $field['key'] ) ?>_label">
			<?php for ( $i = 0; $i < 15; $i++ ) :
				$year = $current_year + $i;
				?>
				<option value="<?php echo $year ?>"><?php echo $year ?></option>
			<?php endfor ?>
		</select>
	</fieldset><!-- .charitable-field-wrapper -->
</div><!-- #charitable_field_<?php echo $field['key'] ?> -->
