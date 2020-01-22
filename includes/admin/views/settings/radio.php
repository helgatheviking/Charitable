<?php
/**
 * Display radio field.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.7
 */

$default = array_key_exists( 'default', $view_args ) ? $view_args['default'] : false;
$value   = charitable_get_option( $view_args['key'], $default );

?>
<ul class="charitable-radio-list <?php echo esc_attr( $view_args['classes'] ); ?>">
	<?php foreach ( $view_args['options'] as $option => $label ) : ?>
		<li><input type="radio"
				id="<?php printf( 'charitable_settings_%s_%s', implode( '_', $view_args['key'] ), $option ); ?>"
				name="<?php printf( 'charitable_settings[%s]', $view_args['name'] ); ?>"
				value="<?php echo esc_attr( $option ); ?>"
				<?php checked( $value, $option ); ?>
				<?php echo charitable_get_arbitrary_attributes( $view_args ); ?>
			/>
			<?php echo $label; ?>
		</li>
	<?php endforeach ?>
</ul>
<?php if ( isset( $view_args['help'] ) ) : ?>
	<div class="charitable-help"><?php echo $view_args['help']; ?></div>
<?php
endif;
