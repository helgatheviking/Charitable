<?php
/**
 * Donation processing endpoint.
 *
 * @package   Charitable/Classes/Charitable_Donation_Processing_Endpoint
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.25
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Processing_Endpoint' ) ) :

	/**
	 * Charitable_Donation_Processing_Endpoint
	 *
	 * @since 1.5.0
	 */
	class Charitable_Donation_Processing_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'donation_processing';

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
		 */
		public function setup_rewrite_rules() {
			add_rewrite_endpoint( 'donation_processing', EP_ROOT );
			add_rewrite_rule( 'donation-processing/([0-9]+)/?$', 'index.php?donation_id=$matches[1]&donation_processing=1', 'top' );
		}

		/**
		 * Return the endpoint URL.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Rewrite $wp_rewrite
		 * @param  array $args Mixed args.
		 * @return string
		 */
		public function get_page_url( $args = array() ) {
			global $wp_rewrite;

			$donation_id = array_key_exists( 'donation_id', $args ) ? $args['donation_id'] : get_the_ID();

			if ( $wp_rewrite->using_permalinks() ) {
				$url = sprintf( '%s/donation-processing/%d', untrailingslashit( home_url() ), $donation_id );
			} else {
				$url = esc_url_raw(
					add_query_arg(
						array(
							'donation_processing' => 1,
							'donation_id' => $donation_id,
						),
						home_url()
					)
				);
			}

			return $url;
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Query $wp_query
		 * @param  array $args Mixed args.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			global $wp_query;

			return is_main_query()
				&& array_key_exists( 'donation_processing', $wp_query->query_vars )
				&& array_key_exists( 'donation_id', $wp_query->query_vars );
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
			new Charitable_Ghost_Page(
				'donation-processing-page',
				array(
					'title'   => __( 'Thank you for your donation', 'charitable' ),
					'content' => sprintf( '<p>%s</p>', __( 'You will shortly be redirected to the payment gateway to complete your donation.', 'charitable' ) ),
				)
			);

			return array( 'donation-processing-page.php', 'page.php', 'singular.php', 'index.php' );
		}

		/**
		 * Get the content to display for the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $content The default content to show.
		 * @return string
		 */
		public function get_content( $content ) {
			$donation = charitable_get_current_donation();

			if ( ! $donation ) {
				return $content;
			}

			return apply_filters( 'charitable_processing_donation_' . $donation->get_gateway(), $content, $donation );
		}

		/**
		 * Return the body class to add for the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_body_class() {
			return 'campaign-donation-processing';
		}
	}

endif;
