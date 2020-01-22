<?php
/**
 * Forgot password endpoint.
 *
 * @package   Charitable/Classes/Charitable_Forgot_Password_Endpoint
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Forgot_Password_Endpoint' ) ) :

	/**
	 * Charitable_Forgot_Password_Endpoint
	 *
	 * @since 1.5.0
	 */
	class Charitable_Forgot_Password_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'forgot_password';

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
			add_rewrite_endpoint( 'forgot_password', EP_PERMALINK );
			add_rewrite_rule( '(.?.+?)(?:/([0-9]+))?/forgot-password/?$', 'index.php?pagename=$matches[1]&page=$matches[2]&forgot_password=1', 'top' );
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

			$login_page = charitable_get_permalink( 'login_page' );

			/* If we are using the default WordPress login process, return the lostpassword URL. */
			if ( wp_login_url() == $login_page ) {
				return wp_lostpassword_url();
			}

			if ( $wp_rewrite->using_permalinks() ) {
				return trailingslashit( $login_page ) . 'forgot-password/';
			}

			return esc_url_raw( add_query_arg( array( 'forgot_password' => 1 ), $login_page ) );
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Query $wp_query
		 * @param  array $args Mixed arguments.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			global $wp_query;

			$login_page = charitable_get_option( 'login_page', 'wp' );
			$strict     = ! array_key_exists( 'strict', $args ) || $args['strict'];

			if ( 'wp' == $login_page ) {
				return $strict ? false : wp_lostpassword_url() == charitable_get_current_url();
			}

			return $wp_query->is_main_query()
				&& array_key_exists( 'forgot_password', $wp_query->query_vars );
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
			$login = charitable_get_option( 'login_page', 'wp' );

			if ( 'wp' == $login ) {
				return $template;
			}

			new Charitable_Ghost_Page(
				'forgot-password-page',
				array(
					'title'   => __( 'Forgot Password', 'charitable' ),
					'content' => '<!-- Silence is golden -->',
				)
			);

			return charitable_splice_template( get_page_template_slug( $login ), array( 'forgot-password-page.php', 'page.php', 'singular.php', 'index.php' ) );
		}

		/**
		 * Get the content to display for the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $content Default content.
		 * @return string
		 */
		public function get_content( $content ) {
			ob_start();

			if ( isset( $_GET['email_sent'] ) ) {
				charitable_template( 'account/forgot-password-sent.php' );
			} else {
				charitable_template(
					'account/forgot-password.php',
					array(
						'form' => new Charitable_Forgot_Password_Form(),
					)
				);
			}

			return ob_get_clean();
		}
	}

endif;
