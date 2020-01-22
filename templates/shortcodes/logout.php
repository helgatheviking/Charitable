<?php
/**
 * The template used to display the logout link. Provided here primarily as a way to make
 * it easier to override using theme templates.
 *
 * Override this template by copying it to yourtheme/charitable/shortcodes/logout.php
 *
 * @author  Eric Daams
 * @package Charitable/Templates/Account
 * @since   1.5.0
 * @version 1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! array_key_exists( 'redirect', $view_args ) || ! array_key_exists( 'text', $view_args ) ) {
	return;
}

?>
<a href="<?php echo wp_logout_url( $view_args['redirect'] ) ?>"><?php echo $view_args['text'] ?></a>