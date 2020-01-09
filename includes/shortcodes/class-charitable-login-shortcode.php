<?php
/**
 * Login shortcode class.
 *
 * @package   Charitable/Shortcodes/Login
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.31
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Login_Shortcode' ) ) :

	/**
	 * Charitable_Login_Shortcode class.
	 *
	 * @since 1.0.0
	 */
	class Charitable_Login_Shortcode {

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
				'logged_in_message'      => __( 'You are already logged in!', 'charitable' ),
				'redirect'               => esc_url( charitable_get_login_redirect_url() ),
				'registration_link_text' => __( 'Register', 'charitable' ),
			);

			$args                    = shortcode_atts( $defaults, $atts, 'charitable_login' );
			$args['login_form_args'] = self::get_login_form_args( $args );

			if ( is_user_logged_in() ) {
				ob_start();
				charitable_template( 'shortcodes/logged-in.php', $args );
				return ob_get_clean();
			}

			if ( false == $args['registration_link_text'] || 'false' == $args['registration_link_text'] ) {
				$args['registration_link'] = false;
			} else {
				$registration_link = charitable_get_permalink( 'registration_page' );

				if ( charitable_get_permalink( 'login_page' ) === $registration_link ) {
					$args['registration_link'] = false;
				} else {
					$args['registration_link'] = add_query_arg(
						'redirect_to',
						urlencode( urldecode( $args['redirect'] ) ),
						$registration_link
					);
				}
			}

			ob_start();

			charitable_template( 'shortcodes/login.php', $args );

			/**
			 * Filter the default output of the login shortcode.
			 *
			 * @since 1.0.0
			 *
			 * @param string $content The default login shortcode output.
			 */
			return apply_filters( 'charitable_login_shortcode', ob_get_clean() );
		}

		/**
		 * Fingerprint the login form with our charitable=true hidden field.
		 *
		 * @see wp_login_form
		 *
		 * @since  1.4.0
		 *
		 * @param  string $content The default output.
		 * @param  array  $args    Arguments passed to wp_login_form.
		 * @return string
		 */
		public static function add_hidden_field_to_login_form( $content, $args ) {
			if ( isset( $args['charitable'] ) && $args['charitable'] ) {
				$content .= '<input type="hidden" name="charitable" value="1" />';
			}

			return $content;
		}

		/**
		 * Return donations to display with the shortcode.
		 *
		 * @see wp_login_form
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Arguments to pass to wp_login_form.
		 * @return mixed[] $args
		 */
		protected static function get_login_form_args( $args ) {
			$default = array(
				'redirect'   => $args['redirect'],
				'charitable' => true,
			);

			if ( isset( $_GET['username'] ) ) {
				$default['value_username'] = $_GET['username'];
			}

			/**
			 * Filter the arguments to pass to wp_login_form().
			 *
			 * @since 1.0.0
			 *
			 * @param array $default The default arguments.
			 * @param array $args    Passed in arguments.
			 */
			return apply_filters( 'charitable_login_form_args', $default, $args );
		}
	}

endif;
