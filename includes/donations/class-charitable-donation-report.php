<?php
/**
 * Model for generating a donation report.
 *
 * @package   Charitable/Classes/Charitable_Donation_Report
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

if ( ! class_exists( 'Charitable_Donation_Report' ) ) :

	/**
	 * Charitable_Donation_Report
	 *
	 * @since 1.6.0
	 */
	class Charitable_Donation_Report {

		/**
		 * Mixed set of arguments for the query.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		private $args;

		/**
		 * Types of reports.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		private $types = array(
			'amount',
			'donations',
			'donors',
		);

		/**
		 * Reports.
		 *
		 * @since 1.6.0
		 *
		 * @var   array|false
		 */
		private $reports = false;

		/**
		 * Create class object.
		 *
		 * @since 1.6.0
		 *
		 * @param array $args Mixed set of arguments for the query.
		 */
		public function __construct( $args = array() ) {
			$this->args = $this->parse_args( $args );
		}

		/**
		 * Get the reports for each report type.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_reports() {
			if ( false === $this->reports ) {
				$this->reports = array();

				if ( false === $this->args['report_type'] ) {
					return $this->reports;
				}

				foreach ( $this->types as $type ) {
					if ( in_array( $type, $this->args['report_type'] ) ) {
						$this->reports[ $type ] = $this->run_report_query( $type );
					}
				}
			}

			return $this->reports;
		}

		/**
		 * Get a single report result.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $report_type The report type to get.
		 * @return mixed
		 */
		public function get_report( $report_type ) {
			if ( is_array( $this->reports ) ) {
				return $this->reports[ $report_type ];
			}

			return $this->run_report_query( $report_type );
		}

		/**
		 * Run a particular report query.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $type The type of report.
		 * @return mixed
		 */
		private function run_report_query( $type ) {
			switch ( $type ) {
				case 'amount':
					return $this->run_amount_query();

				case 'donors':
					return $this->run_donor_query();

				case 'donations':
					return $this->run_donation_query();
			}
		}

		/**
		 * Generate a total query type.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		private function run_amount_query() {
			if ( empty( $this->args['campaigns'] ) ) {
				return charitable_get_table( 'campaign_donations' )->get_total();
			}

			return charitable_get_table( 'campaign_donations' )->get_campaign_donated_amount( $this->args['campaigns'] );
		}

		/**
		 * Generate a donations query type.
		 *
		 * @since  1.6.0
		 *
		 * @return int
		 */
		private function run_donation_query() {
			$query = new Charitable_Donations_Query(
				array(
					'output'   => 'count',
					'campaign' => $this->args['campaigns'],
					'status'   => $this->args['status'],
				)
			);

			return $query->count();
		}

		/**
		 * Generate a donors query type.
		 *
		 * @since  1.6.0
		 *
		 * @return int
		 */
		private function run_donor_query() {
			$query = new Charitable_Donor_Query(
				array(
					'output'   => 'count',
					'campaign' => $this->args['campaigns'],
					'status'   => $this->args['status'],
				)
			);

			return $query->count();
		}

		/**
		 * Parse arguments.
		 *
		 * @since  1.6.0
		 *
		 * @param  array $args User defined arguments.
		 * @return array
		 */
		private function parse_args( $args ) {
			$defaults = array(
				'report_type' => 'all',
				'campaigns'   => array(),
				'status'      => array( 'charitable-completed', 'charitable-preapproved' ),
			);

			$args                = array_merge( $defaults, $args );
			$args['campaigns']   = $this->parse_campaigns( $args['campaigns'] );
			$args['report_type'] = $this->parse_report_type( $args['report_type'] );

			return $args;
		}

		/**
		 * Parse campaigns argument.
		 *
		 * @since  1.6.0
		 *
		 * @param  mixed $campaigns An array of campaigns.
		 * @return array
		 */
		private function parse_campaigns( $campaigns ) {
			if ( empty( $campaigns ) ) {
				return array();
			}

			return array_filter( $campaigns, 'intval' );
		}

		/**
		 * Parse the passed report type.
		 *
		 * @since  1.6.0
		 *
		 * @param  string|array $report_type The type of report to get. May be a string or an array of strings.
		 * @return array|false
		 */
		private function parse_report_type( $report_type ) {
			if ( 'all' == $report_type ) {
				return $this->types;
			}

			if ( is_array( $report_type ) ) {
				return $report_type;
			}

			if ( ! in_array( $report_type, $this->types ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					/* translators: %s: report type */
					sprintf( __( '%s is not a valid donation report type.', 'charitable' ), $report_type ),
					'1.6.0'
				);

				return false;
			}

			return array( $report_type );
		}
	}

endif;
