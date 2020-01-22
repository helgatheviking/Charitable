<?php
/**
 * Password reset endpoint.
 *
 * @package   Charitable/Classes/Charitable_Reset_Password_Endpoint
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Reset_Password_Endpoint' ) ) :

	/**
	 * Charitable_Reset_Password_Endpoint
	 *
	 * @since 1.5.0
	 */
	class Charitable_Reset_Password_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'reset_password';

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
			add_rewrite_endpoint( 'reset_password', EP_PERMALINK );
			add_rewrite_rule( '(.?.+?)(?:/([0-9]+))?/reset-password/?$', 'index.php?pagename=$matches[1]&page=$matches[2]&reset_password=1', 'top' );
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

			/* If we are using the default WordPress login process, return false. */
			if ( wp_login_url() == $login_page ) {
				charitable_get_deprecated()->doing_it_wrong(
					__FUNCTION__,
					__( 'Password reset link should not be called when using the default WordPress login.', 'charitable' ),
					'1.4.0'
				);

				return false;
			}

			/* Get the base URL. */
			if ( $wp_rewrite->using_permalinks() ) {
				return trailingslashit( $login_page ) . 'reset-password/';
			}

			return esc_url_raw( add_query_arg( array( 'reset_password' => 1 ), $login_page ) );
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

			$login_page = charitable_get_option( 'login_page', 'wp' );

			if ( 'wp' == $login_page ) {
				return false;
			}

			return $wp_query->is_main_query()
				&& array_key_exists( 'reset_password', $wp_query->query_vars );
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
				'reset-password-page',
				array(
					'title'   => __( 'Reset Password', 'charitable' ),
					'content' => '<!-- Silence is golden -->',
				)
			);

			return charitable_splice_template( get_page_template_slug( $login ), array( 'reset-password-page.php', 'page.php', 'singular.php', 'index.php' ) );
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
			ob_start();

			charitable_template(
				'account/reset-password.php',
				array(
					'form' => new Charitable_Reset_Password_Form(),
				)
			);

			return ob_get_clean();
		}
	}

endif;
