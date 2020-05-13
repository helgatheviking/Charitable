<?php
/**
 * Class that manages the hook functions for the forgot password form.
 *
 * @package     Charitable/User Management/User Management
 * @author      Rafe Colton
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.0
 * @version     1.6.32
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_User_Management' ) ) :

	/**
	 * Charitable_User_Management class
	 *
	 * @since   1.4.0
	 */
	class Charitable_User_Management {

		/**
		 * The class instance.
		 *
		 * @var     Charitable_User_Management
		 * @since   1.4.0
		 */
		private static $instance;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since   1.4.0
		 *
		 * @return  Charitable_User_Management
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Set up the class.
		 *
		 * @since   1.4.0
		 */
		private function __construct() {
		}

		/**
		 * Check whether we have clicked on a password reset link.
		 *
		 * If so, redirect to the password reset page without the query string.
		 *
		 * @since   1.4.0
		 *
		 * @return  false|void False if no redirect takes place.
		 */
		public function maybe_redirect_to_password_reset() {

			if ( ! charitable_is_page( 'reset_password_page' ) ) {
				return false;
			}

			if ( ! isset( $_GET['key'] ) || ! isset( $_GET['login'] ) ) {
				return false;
			}

			$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );

			$this->set_reset_cookie( $value );

			wp_safe_redirect( esc_url_raw( charitable_get_permalink( 'reset_password_page' ) ) );

			exit();

		}

		/**
		 * Check if user is attempting to access the default reset password page
		 *
		 * If so, and charitable_disable_wp_login is set, redirect them to the custom reset password page
		 *
		 * @since   1.4.0
		 *
		 * @return  void
		 */
		public function maybe_redirect_to_custom_password_reset_page() {

			if ( ! apply_filters( 'charitable_disable_wp_login', false ) ) {
				return;
			}

			if ( 'wp' == charitable_get_option( 'login_page', 'wp' ) ) {
				return;
			}

			$redirect_url = charitable_get_permalink( 'reset_password_page' );
			$redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
			$redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );

			wp_safe_redirect( esc_url_raw( $redirect_url ) );

			exit();
		}

		/**
		 * Check if a failed user login attempt originated from Charitable login form.
		 *
		 * If so redirect user to Charitable login page.
		 *
		 * @since   1.4.0
		 *
		 * @param   WP_User|WP_Error $user_or_error
		 * @param   string           $username
		 * @return  WP_User|void
		 */
		public function maybe_redirect_at_authenticate( $user_or_error, $username ) {
			if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
				return $user_or_error;
			}

			if ( ! is_wp_error( $user_or_error ) ) {
				return $user_or_error;
			}

			if ( ! isset( $_POST['charitable'] ) || ! $_POST['charitable'] ) {
				return $user_or_error;
			}

			foreach ( $user_or_error->errors as $code => $error ) {

				/* Make sure the error messages link to our forgot password page, not WordPress' */
				switch ( $code ) {

					case 'invalid_email':
						$error = __( '<strong>ERROR</strong>: Invalid email address.', 'charitable' ) .
							' <a href="' . esc_url( charitable_get_permalink( 'forgot_password_page' ) ) . '">' .
							__( 'Lost your password?' ) .
							'</a>';
						break;

					case 'incorrect_password':
						$error = sprintf(
							/* translators: %s: email address */
							__( '<strong>ERROR</strong>: The password you entered for %s is incorrect.' ),
							'<strong>' . $username . '</strong>'
						) .
						' <a href="' . esc_url( charitable_get_permalink( 'forgot_password_page' ) ) . '">' .
						__( 'Lost your password?' ) .
						'</a>';
						break;

					default:
						$error = $error[0];
				}

				charitable_get_notices()->add_error( $error );
			}

			charitable_get_session()->add_notices();

			$redirect_url = charitable_get_permalink( 'login_page' );

			if ( strlen( $username ) ) {
				$redirect_url = add_query_arg( 'username', $username, $redirect_url );
			}

			wp_safe_redirect( esc_url_raw( $redirect_url ) );

			exit();
		}

		/**
		 * Check if user is attempting to access the forgot password page
		 *
		 * If so, and charitable_disable_wp_login is set, redirect them to the custom forgot password page
		 *
		 * @since   1.4.0
		 *
		 * @return  void
		 */
		public function maybe_redirect_to_custom_lostpassword() {

			/**
			 * Set whether the WP login should be disabled.
			 *
			 * @since 1.4.0
			 *
			 * @param boolean $disable Whether to disable the WP login.
			 */
			if ( ! apply_filters( 'charitable_disable_wp_login', false ) ) {
				return;
			}

			if ( 'wp' == charitable_get_option( 'login_page', 'wp' ) ) {
				return;
			}

			if ( $_SERVER['REQUEST_METHOD'] != 'GET' ) {
				return;
			}

			wp_safe_redirect( esc_url_raw( charitable_get_permalink( 'forgot_password_page' ) ) );

			exit();
		}

		/**
		 * Set the password reset cookie.
		 *
		 * This is based on the WC_Shortcode_My_Account::set_reset_password_cookie()
		 * method in WooCommerce, which in turn is based on the core implementation
		 * in wp-login.php.
		 *
		 * @since   1.4.0
		 *
		 * @param   string $value
		 * @return  void
		 */
		public function set_reset_cookie( $value = '' ) {
			$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
			$rp_path   = current( explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) ) );

			if ( $value ) {
				setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
			} else {
				setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
			}

		}

		/**
		 * Hides WP Admin bar if the user is not allowed to see it.
		 *
		 * Uses the builtin show_admin_bar function.
		 *
		 * @see     show_admin_bar()
		 *
		 * @since   1.4.0
		 */
		public function maybe_remove_admin_bar() {

			/**
			 * To enable the admin bar for users without admin bar access,
			 * you can use this one-liner:
			 *
			 * add_filter( 'charitable_disable_admin_bar', '__return_true' );
			 */
			if ( ! apply_filters( 'charitable_disable_admin_bar', true ) ) {
				return;
			}

			if ( ! $this->user_has_admin_access() ) {
				show_admin_bar( false );
			}

		}

		/**
		 * Redirects the user away from /wp-admin if they are not authorized to access it.
		 *
		 * @since   1.4.0
		 */
		public function maybe_redirect_away_from_admin() {

			/* Leave AJAX requests alone. */
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			/**
			 * To enable admin access for users without admin access,
			 * you can use this one-liner:
			 *
			 * add_filter( 'charitable_disable_admin_access', '__return_true' );
			 */
			if ( ! apply_filters( 'charitable_disable_admin_access', true ) ) {
				return;
			}

			if ( $this->user_has_admin_access() ) {
				return;
			}

			/**
			 * Specify a custom URL that users should be redirected to.
			 *
			 * @since 1.4.0
			 *
			 * @param false|string Return false to use the default. Otherwise this
			 *                     must return a URL on the same domain.
			 */
			$redirect_url = apply_filters( 'charitable_admin_redirect_url', false );

			if ( ! $redirect_url ) {

				$redirect_url = charitable_get_permalink( 'profile_page' );

				if ( false === $redirect_url ) {
					$redirect_url = home_url();
				}
			}

			wp_safe_redirect( esc_url_raw( $redirect_url ) );

			exit();

		}

		/**
		 * Redirect the user to the Charitable login page.
		 *
		 * @since   1.4.0
		 *
		 * @return  void
		 */
		public function maybe_redirect_to_charitable_login() {
			if ( ! apply_filters( 'charitable_disable_wp_login', false ) ) {
				return;
			}

			if ( 'wp' == charitable_get_option( 'login_page', 'wp' ) ) {
				return;
			}

			/* Don't prevent logging out. */
			if ( 'GET' != $_SERVER['REQUEST_METHOD'] ) {
				return;
			}

			wp_safe_redirect( esc_url_raw( charitable_get_permalink( 'login_page' ) ) );

			exit();
		}

		/**
		 * Send the user verification email.
		 *
		 * @since  1.5.0
		 * @since  1.6.32
		 *
		 * @param  WP_User      $user         An instance of `WP_User`.
		 * @param  string       $redirect_url Where the user should be redirected to after verifying their email.
		 * @param  boolean|null $force_send   Whether the link should include an argument to force
		 *                                    resending the email, even if it was sent recently.
		 * @return boolean True if the email is sent. False otherwise.
		 */
		public function send_verification_email( $user = '', $redirect_url = '', $force_send = null ) {
			if ( empty( $user ) && array_key_exists( 'user', $_GET ) ) {
				$user = get_user_by( 'id', $_GET['user'] );
			}

			if ( ! is_a( $user, 'WP_User' ) ) {
				return false;
			}

			if ( is_null( $force_send ) ) {
				$force_send = array_key_exists( 'force_send', $_GET ) && $_GET['force_send'];
			}

			if ( empty( $redirect_url ) && array_key_exists( 'redirect_url', $_GET ) ) {
				$redirect_url = $_GET['redirect_url'];
			}

			/* Prepare the email. */
			$email = new Charitable_Email_Email_Verification( array( 'user' => $user ) );

			if ( ! $force_send && $email->sent_recently() ) {
				return false;
			}

			if ( ! empty( $redirect_url ) ) {
				/* Ensure that the redirect URL is encoded, but not double-encoded. */
				$redirect_url = urlencode( urldecode( $redirect_url ) );

				$email->set_redirect_url( $redirect_url );
			}

			/* If the confirmation link is generated correctly and the email is sent, set a notice. */
			if ( is_wp_error( $email->get_confirmation_url() ) ) {
				return false;
			}

			return $email->send();
		}

		/**
		 * Check whether the user has admin access.
		 *
		 * @since   1.4.0
		 *
		 * @return  boolean
		 */
		private function user_has_admin_access() {
			if ( ! is_user_logged_in() ) {
				return false;
			}

			$has_access = current_user_can( 'edit_posts' )
				|| current_user_can( 'manage_charitable_settings' )
				|| current_user_can( 'edit_products' );

			/**
			 * Filter whether the user should have access to the WordPress dashboard.
			 *
			 * @since 1.4.0
			 *
			 * @param boolean $has_access Whether the user has access.
			 */
			return apply_filters( 'charitable_user_has_admin_access', $has_access );
		}
	}

endif;
