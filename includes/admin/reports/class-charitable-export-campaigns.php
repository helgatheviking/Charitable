<?php
/**
 * Class that is responsible for generating a CSV export of campaigns.
 *
 * @package   Charitable/Classes/Charitable_Export_Campaigns
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.39
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Export_Campaigns' ) ) :

	/**
	 * Charitable_Export_Campaigns
	 *
	 * @since 1.6.0
	 */
	class Charitable_Export_Campaigns extends Charitable_Export {

		/* The type of export. */
		const EXPORT_TYPE = 'campaigns';

		/**
		 * Default arguments.
		 *
		 * @since 1.6.0
		 *
		 * @var   mixed[]
		 */
		protected $defaults;

		/**
		 * List of donation statuses.
		 *
		 * @since 1.6.0
		 *
		 * @var   string[]
		 */
		protected $statuses;

		/**
		 * Exportable campaign fields.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		protected $fields;

		/**
		 * Set Charitable_Campaign objects.
		 *
		 * @since 1.6.0
		 *
		 * @var   Charitable_Campaign[]
		 */
		protected $campaigns = array();

		/**
		 * Set Charitable_User objects.
		 *
		 * @since 1.6.0
		 *
		 * @var   Charitable_User[]
		 */
		protected $creators = array();

		/**
		 * Create class object.
		 *
		 * @since 1.6.0
		 *
		 * @param mixed[] $args Arguments for the report.
		 */
		public function __construct( $args ) {
			/**
			 * Filter the default campaign export arguments.
			 *
			 * @since 1.6.36
			 *
			 * @param array $defaults Default arguments.
			 */
			$this->defaults = apply_filters(
				'charitable_export_campaigns_default_args',
				array(
					'start_date_from' => '',
					'start_date_to'   => '',
					'end_date_from'   => '',
					'end_date_to'     => '',
					'status'          => '',
					'start_date'      => '', // Kept for backwards compatibility, @since 1.6.36.
					'end_date'        => '', // Kept for backwards compatibility, @since 1.6.36.
				)
			);

			$this->fields = array_map( array( $this, 'get_field_label' ), charitable()->campaign_fields()->get_export_fields() );

			parent::__construct( $args );
		}

		/**
		 * Filter the cell values.
		 *
		 * @since  1.6.0
		 *
		 * @param  mixed  $value The value to set.
		 * @param  string $key   The key to set.
		 * @param  array  $data  The data array, which just contains the campaign ID.
		 * @return mixed
		 */
		public function set_custom_field_data( $value, $key, $data ) {
			$campaign_id = current( $data );

			if ( array_key_exists( $key, $this->fields ) ) {
				$value = $this->get_campaign( $campaign_id )->get( $key );
			}

			return $value;
		}

		/**
		 * Parse the arguments.
		 *
		 * @since  1.6.36
		 *
		 * @return array
		 */
		protected function parse_args( $args ) {
			$parsed = wp_parse_args( $args, $this->defaults );

			if ( empty( $parsed['start_date_from'] ) && strlen( $parsed['start_date'] ) ) {
				$parsed['start_date_from'] = $parsed['start_date'];
			}

			if ( empty( $parsed['start_date_to'] ) && strlen( $parsed['end_date'] ) ) {
				$parsed['start_date_to'] = $parsed['end_date'];
			}

			unset(
				$parsed['start_date'],
				$parsed['end_date']
			);

			return $parsed;
		}

		/**
		 * Return the CSV column headers.
		 *
		 * The columns are set as a key=>label array, where the key is used to retrieve the data for that column.
		 *
		 * @since  1.6.0
		 *
		 * @return string[]
		 */
		protected function get_csv_columns() {
			$default_columns = array(
				'ID'                     => __( 'Campaign ID', 'charitable' ),
				'post_title'             => __( 'Title', 'charitable' ),
				'post_date'              => __( 'Date Created', 'charitable' ),
				'end_date'               => __( 'End Date', 'charitable' ),
				'goal'                   => __( 'Goal', 'charitable' ),
				'donated_amount'         => __( 'Amount Donated', 'charitable' ),
				'percent_donated_raw'    => __( 'Percentage of Goal Raised', 'charitable' ),
				'status'                 => __( 'Status', 'charitable' ),
				'campaign_creator_name'  => __( 'Campaign Creator', 'charitable' ),
				'campaign_creator_id'    => __( 'Campaign Creator ID', 'charitable' ),
				'campaign_creator_email' => __( 'Campaign Creator Email', 'charitable' ),
			);

			/**
			 * Filter the list of columns in the export.
			 *
			 * The recommended way to add or remove columns to the campaign export
			 * is through the Campaign Fields API. This filter is provided as a way
			 * to change export column headers without changing the label for the
			 * Campaign Field.
			 *
			 * @since 1.6.0
			 *
			 * @param array $columns List of columns.
			 * @param array $args    Export args.
			 */
			$filtered = apply_filters( 'charitable_export_campaigns_columns', $default_columns, $this->args );

			return $this->get_merged_fields( $default_columns, $this->fields, array(), $filtered );
		}

		/**
		 * Return a Campaign object for the given ID.
		 *
		 * @since  1.6.0
		 *
		 * @param  int $campaign_id The campaign ID.
		 * @return Charitable_Campaign
		 */
		protected function get_campaign( $campaign_id ) {
			if ( ! array_key_exists( $campaign_id, $this->campaigns ) ) {
				$this->campaigns[ $campaign_id ] = charitable_get_campaign( $campaign_id );
			}

			return $this->campaigns[ $campaign_id ];
		}

		/**
		 * Return a User object for a campaign creator.
		 *
		 * @since  1.6.0
		 *
		 * @param  int $campaign_id The campaign ID.
		 * @return Charitable_User
		 */
		public function get_campaign_creator( $campaign_id ) {
			$creator = $this->get_campaign( $campaign_id )->get_campaign_creator();

			if ( ! array_key_exists( $creator, $this->creators ) ) {
				$this->creators[ $creator ] = charitable_get_user( $creator );
			}

			return $this->creators[ $creator ];
		}

		/**
		 * Get the data to be exported.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		protected function get_data() {
			$query_args = array(
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_query'     => array(),
				'date_query'     => array(),
				'perm'           => 'readable',
			);

			if ( strlen( $this->args['start_date_from'] ) || strlen( $this->args['start_date_to'] ) ) {
				$query_args['date_query']['inclusive'] = true;

				if ( strlen( $this->args['start_date_from'] ) ) {
					$query_args['date_query']['after'] = charitable_sanitize_date( $this->args['start_date_from'], 'Y-m-d' );
				}

				if ( strlen( $this->args['start_date_to'] ) ) {
					$query_args['date_query']['before'] = charitable_sanitize_date( $this->args['start_date_to'], 'Y-m-d' );
				}
			}

			if ( strlen( $this->args['end_date_from'] ) ) {
				$query_args['meta_query'][] = array(
					'key'     => '_campaign_end_date',
					'value'   => charitable_sanitize_date( $this->args['end_date_from'], 'Y-m-d 00:00:00' ),
					'compare' => '>=',
					'type'    => 'datetime',
				);
			}

			if ( strlen( $this->args['end_date_to'] ) ) {
				$query_args['meta_query'][] = array(
					'key'     => '_campaign_end_date',
					'value'   => charitable_sanitize_date( $this->args['end_date_to'], 'Y-m-d 00:00:00' ),
					'compare' => '<=',
					'type'    => 'datetime',
				);
			}

			if ( ! empty( $this->args['status'] ) ) {
				switch ( $this->args['status'] ) {
					case 'any':
						$query_args['post_status'] = array( 'draft', 'pending', 'private', 'publish' );
						break;

					case 'active':
						$query_args['post_status'] = 'publish';
						$query_args['meta_query']  = array(
							'relation' => 'OR',
							array(
								'key'     => '_campaign_end_date',
								'value'   => date( 'Y-m-d H:i:s' ),
								'compare' => '>',
								'type'    => 'datetime',
							),
							array(
								'key'     => '_campaign_end_date',
								'value'   => 0,
								'compare' => '=',
							),
						);
						break;

					case 'finish':
						$query_args['post_status'] = 'publish';
						$query_args['meta_query']  = array(
							array(
								'key'     => '_campaign_end_date',
								'value'   => date( 'Y-m-d H:i:s' ),
								'compare' => '<=',
								'type'    => 'datetime',
							),
						);
						break;

					default:
						$query_args['post_status'] = $this->args['status'];
				}
			}

			if ( ! current_user_can( 'edit_others_campaigns' ) ) {
				$query_args['author'] = get_current_user_id();
			}

			/**
			 * Filter the campaigns export query arguments.
			 *
			 * @since 1.6.0
			 *
			 * @param array $query_args The query arguments.
			 * @param array $args       The export arguments.
			 */
			$query_args = apply_filters( 'charitable_export_campaigns_query_args', $query_args, $this->args );

			return Charitable_Campaigns::query( $query_args )->posts;
		}

		/**
		 * Return the field label for a registered Campaign Field.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Campaign_Field $field Instance of `Charitable_Campaign_Field`.
		 * @return string
		 */
		protected function get_field_label( Charitable_Campaign_Field $field ) {
			return $field->label;
		}
	}

endif;
