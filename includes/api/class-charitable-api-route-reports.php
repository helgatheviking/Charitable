<?php
/**
 * Sets up the /reports/ API route.
 *
 * @package   Charitable/Classes/Charitable_API_Route_Reports
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_API_Route_Reports' ) ) :

	/**
	 * Charitable_API_Route_Reports
	 *
	 * @since 1.6.0
	 */
	class Charitable_API_Route_Reports extends Charitable_API_Route {

		/**
		 * Route base.
		 *
		 * @since 1.6.0
		 *
		 * @var   string
		 */
		protected $base;

		/**
		 * Set up class instance.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function __construct() {
			parent::__construct();

			$this->base = 'reports';
		}

		/**
		 * Register the routes for this controller.
		 *
		 * @since 1.6.0
		 */
		public function register_routes() {
			register_rest_route(
				$this->namespace,
				'/' . $this->base,
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_reports' ),
					'permission_callback' => array( $this, 'user_can_get_charitable_reports' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->base . '/donations/',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_donations_report' ),
					'permission_callback' => array( $this, 'user_can_get_charitable_reports' ),
					'args'                => array(
						'campaigns' => array(
							'default'           => 'all',
							'validate_callback' => array( $this, 'validate_campaigns_arg' ),
							'sanitize_callback' => array( $this, 'sanitize_campaigns_arg' ),
						),
					),
				)
			);
		}

		/**
		 * Return a response with all registered reports.
		 *
		 * @since  1.6.0
		 *
		 * @return WP_REST_Response|mixed If response generated an error, WP_Error, if response
		 *                                is already an instance, WP_HTTP_Response, otherwise
		 *                                returns a new WP_REST_Response instance.
		 */
		public function get_reports() {
			return rest_ensure_response(
				array(
					'slug'        => 'donations',
					'description' => __( 'List of donation reports.', 'charitable' ),
					'_links'      => array(
						'self' => array(
							'href' => get_site_url( null, '/wp-json/' . $this->namespace . '/' . $this->base . '/donations/' ),
						),
					),
				)
			);
		}

		/**
		 * Return a response with the donations report.
		 *
		 * @since  1.6.0
		 *
		 * @param  WP_REST_Request $request The API request object.
		 * @return WP_REST_Response|mixed If response generated an error, WP_Error, if response
		 *                                is already an instance, WP_HTTP_Response, otherwise
		 *                                returns a new WP_REST_Response instance.
		 */
		public function get_donations_report( $request ) {
			$report = new Charitable_Donation_Report(
				array(
					'campaigns' => $request->get_param( 'campaigns' ),
				)
			);

			return rest_ensure_response( $report->get_reports() );
		}

		/**
		 * Validate the 'campaigns' argument.
		 *
		 * @since  1.6.0
		 *
		 * @param  mixed $param The parameter value.
		 * @return boolean
		 */
		public function validate_campaigns_arg( $param ) {
			return 'all' == $param || is_numeric( $param ) || is_array( $param );
		}

		/**
		 * Sanitize the 'campaigns' argument, ensuring either 'all' or an array is returned.
		 *
		 * @since  1.6.0
		 *
		 * @param  mixed $param The parameter value.
		 * @return string|array
		 */
		public function sanitize_campaigns_arg( $param ) {
			if ( 'all' == $param || is_array( $param ) ) {
				return $param;
			}

			return array( $param );
		}
	}

endif;
