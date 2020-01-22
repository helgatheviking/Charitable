<?php
/**
 * Profile shortcode class.
 *
 * @package   Charitable/Shortcodes/Profile
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.5.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Profile_Shortcode' ) ) :

	/**
	 * Charitable_Profile_Shortcode class.
	 *
	 * @since   1.0.0
	 */
	class Charitable_Profile_Shortcode {

		/**
		 * The callback method for the campaigns shortcode.
		 *
		 * This receives the user-defined attributes and passes the logic off to the class.
		 *
		 * @since   1.0.0
		 *
		 * @param   array $atts User-defined shortcode attributes.
		 * @return  string
		 */
		public static function display( $atts ) {
			$defaults = array(
				'hide_login' => false,
			);

			$args = shortcode_atts( $defaults, $atts, 'charitable_profile' );

			ob_start();

			/* If the user is logged out, show the login form. */
			if ( ! is_user_logged_in() ) {

				if ( false == $args['hide_login'] ) {
					$args['redirect'] = charitable_get_current_url();

					echo Charitable_Login_Shortcode::display( $args );
				}

				return ob_get_clean();
			}

			$args['form'] = new Charitable_Profile_Form( $args );

			/* If the user is logged in, show the profile template. */
			charitable_template( 'shortcodes/profile.php', $args );

			return apply_filters( 'charitable_profile_shortcode', ob_get_clean() );
		}
	}

endif;
