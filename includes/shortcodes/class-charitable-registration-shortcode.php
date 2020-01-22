<?php
/**
 * Registration shortcode class.
 *
 * @package   Charitable/Shortcodes/Registration
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

if ( ! class_exists( 'Charitable_Registration_Shortcode' ) ) :

	/**
	 * Charitable_Registration_Shortcode class.
	 *
	 * @since 1.0.0
	 */
	class Charitable_Registration_Shortcode {

		/**
		 * The callback method for the campaigns shortcode.
		 *
		 * This receives the user-defined attributes and passes the logic off to the class.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $atts User-defined shortcode attributes.
		 * @return string
		 */
		public static function display( $atts = array() ) {
			$defaults = array(
				'logged_in_message' => __( 'You are already logged in!', 'charitable' ),
				'redirect'          => false,
				'login_link_text'   => __( 'Signed up already? Login instead.', 'charitable' ),
			);

			$args = shortcode_atts( $defaults, $atts, 'charitable_registration' );

			ob_start();

			if ( is_user_logged_in() ) {
				charitable_template( 'shortcodes/logged-in.php', $args );
				return ob_get_clean();
			}

			$args['form'] = new Charitable_Registration_Form( $args );

			charitable_template( 'shortcodes/registration.php', $args );

			return apply_filters( 'charitable_registration_shortcode', ob_get_clean() );
		}
	}

endif;
