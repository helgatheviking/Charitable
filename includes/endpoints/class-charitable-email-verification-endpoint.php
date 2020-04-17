<?php
/**
 * Email verification endpoint.
 *
 * @package   Charitable/Classes/Charitable_Email_Verification_Endpoint
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.26
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Email_Verification_Endpoint' ) ) :

	/**
	 * Charitable_Email_Verification_Endpoint
	 *
	 * @since 1.5.0
	 */
	class Charitable_Email_Verification_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'email_verification';

		/** Set priority */
		const PRIORITY = 9;

		/**
		 * The verification result.
		 *
		 * @since 1.5.0
		 *
		 * @var   false|WP_User|WP_Error
		 */
		protected $verification_result;

		/**
		 * Object instantiation.
		 *
		 * @since 1.5.4
		 */
		public function __construct() {
			$this->cacheable = false;
		}

		/**
		 * Return the endpoint ID.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public static function get_endpoint_id() {
			return self::ID;
		}

		/**
		 * Add rewrite rules for the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function setup_rewrite_rules() {
			add_rewrite_endpoint( 'email_verification', EP_PERMALINK | EP_ROOT );
			add_rewrite_rule( '(.?.+?)(?:/([0-9]+))?/email-verification/?$', 'index.php?pagename=$matches[1]&page=$matches[2]&email_verification=1', 'top' );
			add_rewrite_rule( 'email-verification/?$', 'index.php?&email_verification=1', 'top' );
		}

		/**
		 * Return the endpoint URL.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Rewrite $wp_rewrite
		 * @param  array $args Mixed arguments.
		 * @return string
		 */
		public function get_page_url( $args = array() ) {
			global $wp_rewrite;

			$base = $this->get_base_page();

			/* Get the base URL. */
			if ( $wp_rewrite->using_permalinks() ) {
				return trailingslashit( $base ) . 'email-verification/';
			}

			return esc_url_raw( add_query_arg( array( 'email_verification' => 1 ), $base ) );
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Query $wp_query
		 * @param  array $args Mixed set of arguments.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			global $wp_query;

			return $wp_query->is_main_query()
				&& array_key_exists( 'email_verification', $wp_query->query_vars );
		}

		/**
		 * If the page should redirect, return the URL it should redirect to.
		 *
		 * @since  1.6.26
		 *
		 * @return false|string
		 */
		public function get_redirect() {
			$result = $this->get_verification_check_result();

			/* After successful verification, set a notice and redirect. */
			if ( is_a( $result, 'WP_User' ) ) {
				charitable_get_notices()->add_success( __( 'Your email address has been verified.', 'charitable' ) );
				charitable_get_session()->add_notices();

				if ( array_key_exists( 'redirect_to', $_GET ) ) {
					return $_GET['redirect_to'];
				}
			}

			return false;
		}

		/**
		 * Return the template to display for this endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $template The default template.
		 * @return string
		 */
		public function get_template( $template ) {
			$profile = charitable_get_option( 'profile_page', false );

			new Charitable_Ghost_Page(
				'email-verification-page',
				array(
					'title'   => __( 'Email Verification', 'charitable' ),
					'content' => '<!-- Silence is golden -->',
				)
			);

			$templates = array( 'email-verification-page.php', 'page.php', 'singular.php', 'index.php' );

			if ( $profile ) {
				return charitable_splice_template( get_page_template_slug( $profile ), $templates );
			}

			return $templates;
		}

		/**
		 * Get the content to display for the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $content The page content.
		 * @return string
		 */
		public function get_content( $content ) {
			$template = $this->is_verified() ? 'account/email-verified.php' : 'account/email-not-verified.php';

			ob_start();

			charitable_template(
				$template,
				array(
					'result' => $this->get_verification_check_result(),
				)
			);

			return ob_get_clean();
		}

		/**
		 * Checks whether the email address is verified.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		protected function is_verified() {
			return is_a( $this->get_verification_check_result(), 'WP_User' );
		}

		/**
		 * Check whether a key and login were provided and are valid.
		 *
		 * @since  1.5.0
		 *
		 * @return false|WP_User|WP_Error False if the key or login are missing.
		 *                                WP_User if they are and the combo is valid.
		 *                                WP_Error in case of failure.
		 */
		protected function get_verification_check_result() {
			if ( ! isset( $this->verification_result ) ) {
				if ( ! isset( $_GET['key'] ) || ! isset( $_GET['login'] ) ) {
					$this->verification_result = false;
				} else {
					$this->verification_result = check_password_reset_key( wp_unslash( $_GET['key'] ), wp_unslash( $_GET['login'] ) );
				}

				/* The user is logged in but the verification was for a different user. */
				if ( is_user_logged_in() && get_current_user_id() !== $this->verification_result->ID ) {
					$this->verification_result = false;
				}

				/* If everything checks out, mark the user as verified. */
				if ( is_a( $this->verification_result, 'WP_User' ) ) {
					charitable_get_user( get_user_by( 'login', $_GET['login'] ) )->mark_as_verified( true );
				}
			}

			return $this->verification_result;
		}

		/**
		 * Return the base page for the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		protected function get_base_page() {
			$profile = charitable_get_permalink( 'profile' );

			return empty( $profile ) ? home_url() : $profile;
		}
	}

endif;
