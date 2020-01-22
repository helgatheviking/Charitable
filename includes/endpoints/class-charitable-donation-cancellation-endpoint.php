<?php
/**
 * Donation cancellation endpoint.
 *
 * @package   Charitable/Classes/Charitable_Donation_Cancellation_Endpoint
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Cancellation_Endpoint' ) ) :

	/**
	 * Charitable_Donation_Cancellation_Endpoint
	 *
	 * @since 1.5.0
	 */
	class Charitable_Donation_Cancellation_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'donation_cancellation';

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
		 * @since 1.5.0
		 */
		public function setup_rewrite_rules() {
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

			/* A donation ID must be provided. */
			if ( ! array_key_exists( 'donation_id', $args ) ) {
				return $url;
			}

			/* Grab the first campaign donation. */
			$campaign_donation = current( charitable_get_donation( $args['donation_id'] )->get_campaign_donations() );

			$donation_page = charitable_get_permalink( 'campaign_donation_page', array(
				'campaign_id' => $campaign_donation->campaign_id,
			) );

			return esc_url_raw(
				add_query_arg(
					array(
						'donation_id' => $args['donation_id'],
						'cancel'      => true,
					),
					$donation_page
				)
			);
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

			return charitable_is_page( 'campaign_donation_page' )
				&& array_key_exists( 'donation_id', $wp_query->query_vars )
				&& array_key_exists( 'cancel', $wp_query->query_vars )
				&& $wp_query->query_vars['cancel'];
		}
	}

endif;
