<?php
/**
 * Display WP Editor in a settings field.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.28
 */

$value = charitable_get_option( $view_args['key'] );

if ( empty( $value ) ) :
	$value = isset( $view_args['default'] ) ? $view_args['default'] : '';
endif;

$editor_args         = isset( $view_args['editor'] ) ? $view_args['editor'] : array();
$default_editor_args = array(
	'textarea_name' => sprintf( 'charitable_settings[%s]', $view_args['name'] ),
);
$editor_args         = wp_parse_args( $editor_args, $default_editor_args );
?>
<div <?php echo charitable_get_arbitrary_attributes( $view_args ); ?>>
	<?php
	wp_editor( $value, sprintf( 'charitable_settings_%s', implode( '_', $view_args['key'] ) ), $editor_args );

	if ( isset( $view_args['help'] ) ) :
		?>
		<div class="charitable-help"><?php echo $view_args['help']; ?></div>
		<?php
	endif;
	?>
</div>
