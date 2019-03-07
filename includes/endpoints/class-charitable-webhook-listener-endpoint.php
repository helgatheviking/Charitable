<?php
/**
 * Webhook listener endpoint.
 *
 * @package   Charitable Authorize.Net/Classes/Charitable_Webhook_Listener_Endpoint
 * @author    Eric Daams
 * @copyright Copyright (c) 2019, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.14
 * @version   1.6.14
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Webhook_Listener_Endpoint' ) ) :

	/**
	 * Charitable_Webhook_Listener_Endpoint
	 *
	 * @since 1.6.14
	 */
	class Charitable_Webhook_Listener_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID */
		const ID = 'webhook_listener';

		/**
		 * Object instantiation.
		 *
		 * @since 1.6.14
		 */
		public function __construct() {
			$this->cacheable = false;
		}

		/**
		 * Return the endpoint ID.
		 *
		 * @since  1.6.14
		 *
		 * @return string
		 */
		public static function get_endpoint_id() {
			return self::ID;
		}

		/**
		 * Add rewrite rules for the endpoint.
		 *
		 * @since  1.6.14
		 */
		public function setup_rewrite_rules() {
			add_rewrite_endpoint( 'charitable-listener', EP_ROOT );
			add_rewrite_rule( '^charitable-listener/([^/]+)/$', 'index.php?charitable-listener=$matches[1]', 'bottom' );
		}

		/**
		 * Return the endpoint URL.
		 *
		 * @since  1.6.14
		 *
		 * @global WP_Rewrite $wp_rewrite
		 * @param  array $args Mixed args.
		 * @return string
		 */
		public function get_page_url( $args = array() ) {
			global $wp_rewrite;

			if ( $wp_rewrite->using_permalinks() ) {
				$url = sprintf( '%s/charitable-listener/%s', untrailingslashit( home_url() ), $args['gateway'] );
			} else {
				$url = esc_url_raw( add_query_arg( array(
					'charitable_listener' => $args['gateway'],
				), home_url() ) );
			}

			return $url;
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.6.14
		 *
		 * @global WP_Query $wp_query
		 * @param  array $args Mixed args.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			global $wp_query;

			return is_main_query()
				&& array_key_exists( 'charitable-listener', $wp_query->query_vars );
		}

		/**
		 * Return the template to display for this endpoint.
		 *
		 * @since  1.6.14
		 *
		 * @param  string $template The default template.
		 * @return string
		 */
		public function get_template( $template ) {
			$gateway = get_query_var( 'charitable-listener', false );

			if ( $gateway ) {

				/**
				 * Handle a gateway's IPN.
				 *
				 * @since 1.0.0
				 */
				do_action( 'charitable_process_ipn_' . $gateway );

			}

			return $template;
		}
	}

endif;