<?php
/**
 * The template used to display a message on the email verification endpoint
 * when the email is verified.
 *
 * This template is only used when all of the following are true:
 *
 * 1) The email is verified. If email was not verified, the contents of account/email-not-verified.php would be shown.
 * 2) No redirect URL was set. If one was set, the user would be redirected to that page.
 * 3) No Profile page was set. If one is set, the user would be redirected to that page.
 *
 * Override this template by copying it to yourtheme/charitable/account/email-verified.php
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

?>
<p><?php _e( 'Your email address has been verified.', 'charitable' ) ?></p>
<p><a href="<?php home_url() ?>"><?php _e( 'Return home', 'charitable' ) ?></a></p>