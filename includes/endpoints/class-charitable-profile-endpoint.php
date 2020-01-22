<?php
/**
 * Profile endpoint.
 *
 * @package   Charitable/Classes/Charitable_Profile_Endpoint
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.29
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Profile_Endpoint' ) ) :

	/**
	 * Charitable_Profile_Endpoint
	 *
	 * @since 1.5.0
	 */
	class Charitable_Profile_Endpoint extends Charitable_Endpoint {

		/** Endpoint ID. */
		const ID = 'profile';

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
		 * Return the endpoint URL.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Rewrite $wp_rewrite
		 * @param  array $args Mixed args.
		 * @return string
		 */
		public function get_page_url( $args = array() ) {
			$page = charitable_get_option( 'profile_page', false );

			return $page ? get_permalink( $page ) : '';
		}

		/**
		 * Return whether we are currently viewing the endpoint.
		 *
		 * @since  1.5.0
		 *
		 * @global WP_Post  $post
		 * @global WP_Query $wp_query
		 * @param  array $args Mixed args.
		 * @return boolean
		 */
		public function is_page( $args = array() ) {
			global $post, $wp_query;

			if ( is_null( $post ) || ! $wp_query->is_main_query() ) {
				return false;
			}

			$page = charitable_get_option( 'profile_page', false );

			if ( ! $page || $page != $post->ID ) {
				return false;
			}

			return ! $this->is_descendent_page();
		}

		/**
		 * Check that this is not a descendant page.
		 *
		 * For example, the email verification endpoint uses /profile/ as
		 * its base, so we need to make sure that `email_verification` is
		 * not set in the query vars.
		 *
		 * @since  1.6.23
		 *
		 * @global WP_Query $wp_query
		 * @return boolean
		 */
		public function is_descendent_page() {
			global $wp_query;

			/**
			 * Filter the query vars that indicate a descendent page.
			 *
			 * @since 1.6.23
			 *
			 * @param array List of query vars.
			 */
			$descendent_query_vars = apply_filters(
				'charitable_profile_endpoint_descendent_query_vars',
				array( 'email_verification' )
			);

			/* If the query vars keys contains one of the descendent query vars, return true. */
			$found_vars = array_intersect( array_keys( $wp_query->query_vars ), $descendent_query_vars );

			return ! empty( $found_vars );
		}

		/**
		 * Get the nav menu object.
		 *
		 * @since  1.6.29
		 *
		 * @return object
		 */
		public function nav_menu_object() {
			return charitable_get_option( 'profile_page', false ) ? $this->get_nav_menu_object( __( 'Profile', 'charitable' ) ) : null;
		}
	}

endif;
