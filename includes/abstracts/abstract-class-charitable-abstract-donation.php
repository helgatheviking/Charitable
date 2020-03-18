<?php
/**
 * Donation model.
 *
 * @package   Charitable/Classes/Charitable_Donation
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.4.0
 * @version   1.6.35
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Abstract_Donation' ) ) :

	/**
	 * Donation Model
	 *
	 * @since 1.4.0
	 *
	 * @property int    $ID
	 * @property int    $post_author
	 * @property string $post_date
	 * @property string $post_date_gmt
	 * @property string $post_content
	 * @property string $post_title
	 * @property string $post_excerpt
	 * @property string $post_status
	 * @property string $comment_status
	 * @property string $ping_status
	 * @property string $post_password
	 * @property string $post_name
	 * @property string $to_ping
	 * @property string $pinged
	 * @property string $post_modified
	 * @property string $post_modified_gmt
	 * @property string $post_content_filtered
	 * @property int    $post_parent
	 * @property string $guid
	 * @property int    $menu_order
	 * @property string $post_type
	 * @property string $post_mime_type
	 * @property int    $comment_count
	 * @property string $filter
	 */
	abstract class Charitable_Abstract_Donation {

		/**
		 * The donation ID.
		 *
		 * @var int
		 */
		protected $donation_id;

		/**
		 * The donation type.
		 *
		 * @var string
		 */
		protected $donation_type;

		/**
		 * Charitable_Donation donation data for the donation plan this donation is part of.
		 *
		 * @since 1.4.5
		 *
		 * @var false|Charitable_Donation
		 */
		protected $donation_plan;

		/**
		 * The database record for this donation from the Posts table.
		 *
		 * @var WP_Post
		 */
		protected $donation_data;

		/**
		 * The Campaign Donations table.
		 *
		 * @var Charitable_Campaign_Donations_DB
		 */
		protected $campaign_donations_db;

		/**
		 * The payment gateway used to process the donation.
		 *
		 * @var Charitable_Gateway_Interface
		 */
		protected $gateway;

		/**
		 * Gateway transaction ID.
		 *
		 * @since 1.4.6
		 *
		 * @var   mixed
		 */
		protected $gateway_transaction_id;

		/**
		 * The campaign donations made as part of this donation.
		 *
		 * @var Object
		 */
		protected $campaign_donations;

		/**
		 * The Charitable_Donor object of the person who donated.
		 *
		 * @var Charitable_Donor
		 */
		protected $donor;

		/**
		 * The Charitable_Donation_Log object for this donation.
		 *
		 * @since 1.5.4
		 *
		 * @var   Charitable_Donation_Log
		 */
		protected $log;

		/**
		 * The Charitable_Object_Fields object for this donation.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Object_Fields
		 */
		protected $fields;

		/**
		 * Instantiate a new donation object based off the ID.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $donation The donation ID or WP_Post object.
		 */
		public function __construct( $donation ) {
			if ( ! is_a( $donation, 'WP_Post' ) ) {
				$donation = get_post( $donation );
			}

			$this->donation_id   = $donation->ID;
			$this->donation_data = $donation;
		}

		/**
		 * Magic getter.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key The key of the field we're looking for.
		 * @return mixed
		 */
		public function __get( $key ) {
			if ( method_exists( $this, 'get_' . $key ) ) {
				$method = 'get_' . $key;
				return $this->$method;
			}

			return $this->donation_data->$key;
		}

		/**
		 * Return a field value.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key The key of the field we're looking for.
		 * @return mixed
		 */
		public function get( $key ) {
			if ( $this->fields()->has_value_callback( $key ) ) {
				return $this->fields()->get( $key );
			}

			/* Look for a local method. */
			if ( method_exists( $this, 'get_' . $key ) ) {
				$method = 'get_' . $key;
				return $this->$method();
			}

			return $this->__get( $key );
		}

		/**
		 * Limit properties to be serialized.
		 *
		 * @since  1.6.18
		 *
		 * @return array
		 */
		public function __sleep() {
			return array(
				'donation_id',
				'donation_type',
				'donation_plan',
				'donation_data',
				'campaign_donations_db',
				'gateway_transaction_id',
				'campaign_donations',
				'donor',
			);
		}

		/**
		 * Return the Charitable_Object_Fields instance.
		 *
		 * @since  1.5.0
		 *
		 * @return Charitable_Object_Fields
		 */
		public function fields() {
			if ( ! isset( $this->fields ) ) {
				$this->fields = new Charitable_Object_Fields( charitable()->donation_fields(), $this );
			}

			return $this->fields;
		}

		/**
		 * Checks whether the donation has a donor's email address.
		 *
		 * @since  1.6.0
		 *
		 * @return boolean
		 */
		public function has_valid_email() {
			return $this->get_donor()->has_valid_email();
		}

		/**
		 * Return the donation number. By default, this is the ID, but it can be filtered.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_number() {
			/**
			 * Filter the donation number.
			 *
			 * @since 1.0.0
			 *
			 * @param int $donation_id The donation ID.
			 */
			return apply_filters( 'charitable_donation_number', $this->donation_id );
		}

		/**
		 * Get the donation data.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Campaign_Donations_DB
		 */
		public function get_campaign_donations_db() {
			if ( ! isset( $this->campaign_donations_db ) ) {
				$this->campaign_donations_db = new Charitable_Campaign_Donations_DB();
			}

			return $this->campaign_donations_db;
		}

		/**
		 * The amount donated on this donation.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $sanitize Whether the value should be sanitized as a monetary amount.
		 * @return float
		 */
		public function get_total_donation_amount( $sanitize = false ) {
			$amount = $this->get_campaign_donations_db()->get_donation_total_amount( $this->donation_id );

			if ( $sanitize ) {
				$amount = Charitable_Currency::get_instance()->sanitize_monetary_amount( (string) $amount );
			}

			return $amount;
		}

		/**
		 * Return the formatted donation amount.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_amount_formatted() {
			return charitable_format_money( $this->get_total_donation_amount() );
		}

		/**
		 * Return the campaigns donated to in this donation.
		 *
		 * @since  1.0.0
		 *
		 * @return object[]
		 */
		public function get_campaign_donations() {
			if ( ! isset( $this->campaign_donations ) ) {
				$this->campaign_donations = $this->get_campaign_donations_db()->get_donation_records( $this->donation_id );
			}

			return $this->campaign_donations;
		}

		/**
		 * Returns an array of the campaigns that were donated to.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_campaigns() {
			return array_map( array( $this, 'get_campaign_name' ), $this->get_campaign_donations() );
		}

		/**
		 * Returns the campaign name from a campaign donation record.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_campaign_name( $campaign_donation ) {
			return $campaign_donation->campaign_name;
		}

		/**
		 * Return a comma separated list of the campaigns that were donated to.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $linked Whether to return the campaigns with links to the campaign pages.
		 * @return string
		 */
		public function get_campaigns_donated_to( $linked = false ) {
			$campaigns = $linked ? $this->get_campaigns_links() : $this->get_campaigns();

			return implode( ', ', $campaigns );
		}

		/**
		 * Return a comma separated list of the campaigns that were donated to, with links to the campaigns.
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_campaigns_links() {
			$links = array();

			foreach ( $this->get_campaign_donations() as $campaign ) {

				if ( ! isset( $links[ $campaign->campaign_id ] ) ) {

					$links[ $campaign->campaign_id ] = sprintf(
						'<a href="%s" aria-label="%s">%s</a>',
						get_permalink( $campaign->campaign_id ),
						sprintf( '%s %s', _x( 'Go to', 'go to campaign', 'charitable' ), get_the_title( $campaign->campaign_id ) ),
						get_the_title( $campaign->campaign_id )
					);

				}
			}

			return $links;
		}

		/**
		 * Return an array of the categories of the campaigns that were donated to.
		 *
		 * @uses   wp_get_object_terms
		 * @uses   wp_list_pluck
		 * @uses   Charitable_Donation::get_campaign_donations
		 *
		 * @since  1.4.2
		 *
		 * @param  string $taxonomy The taxonomy. Defaults to 'campaign_category'.
		 * @param  array  $args     Optional arguments to pass to `wp_get_object_terms`.
		 * @return array|WP_Error The requested term data or empty array if no terms found.
		 *                        WP_Error if any of the $taxonomies don't exist.
		 */
		public function get_campaign_categories_donated_to( $taxonomy = 'campaign_category', $args = array() ) {
			$campaigns = wp_list_pluck( $this->get_campaign_donations(), 'campaign_id' );

			return wp_get_object_terms( $campaigns, $taxonomy, $args );
		}

		/**
		 * Return a comma separated list of categories for the campaigns that were donated to.
		 *
		 * @uses   Charitable_Donation::get_campaign_categories_donated_to
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_campaign_categories_list() {
			$categories = $this->get_campaign_categories_donated_to(
				'campaign_category',
				array(
					'fields' => 'names',
				)
			);

			if ( is_wp_error( $categories ) ) {
				return '';
			}

			return implode( ', ', $categories );
		}

		/**
		 * Return a simple donation summary showing the campaigns that were donated to,
		 * and the amount of the donation.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function get_donation_summary() {
			return implode( '', array_map( array( $this, 'get_donation_summary_line' ), $this->get_campaign_donations() ) );
		}

		/**
		 * Return a single line of a donation summary.
		 *
		 * @since  1.5.0
		 *
		 * @param  object $campaign_donation A campaign donation object.
		 * @return string
		 */
		protected function get_donation_summary_line( $campaign_donation ) {
			$line_item = sprintf(
				'%s: %s%s',
				$campaign_donation->campaign_name,
				charitable_format_money( $campaign_donation->amount ),
				PHP_EOL
			);

			/**
			 * Filter the line item.
			 *
			 * @since 1.0.0
			 * @since 1.5.0 Filter was previously defined in Charitable_Email::get_donation_summary()
			 *              and included an array of shortcode arguments and a Charitable_Email object.
			 *
			 * @param string $line_item         The line of text.
			 * @param object $campaign_donation A campaign donation object.
			 */
			return apply_filters( 'charitable_donation_summary_line_item_email', $line_item, $campaign_donation );
		}

		/**
		 * Return the date of the donation.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $format The specified date format.
		 * @return string
		 */
		public function get_date( $format = '' ) {
			if ( empty( $format ) ) {
				$format = get_option( 'date_format' );
			}

			return date_i18n( $format, strtotime( $this->donation_data->post_date ) );
		}

		/**
		 * Return the time of the donation.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $format Time format.
		 * @return string
		 */
		public function get_time( $format = '' ) {
			if ( empty( $format ) ) {
				$format = get_option( 'time_format' );
			}

			return mysql2date( $format, $this->donation_data->post_date );
		}

		/**
		 * The name of the gateway used to process the donation.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_gateway() {
			return get_post_meta( $this->donation_id, 'donation_gateway', true );
		}

		/**
		 * Return the unique donation key.
		 *
		 * @since  1.0.0
		 *
		 * @return string The key identifier of the donation.
		 */
		public function get_donation_key() {
			return get_post_meta( $this->donation_id, 'donation_key', true );
		}

		/**
		 * Return the donor data.
		 *
		 * @since  1.2.0
		 *
		 * @return array The donor data.
		 */
		public function get_donor_data() {
			return get_post_meta( $this->donation_id, 'donor', true );
		}

		/**
		 * The public label of the gateway used to process the donation.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_gateway_label() {
			$gateway = $this->get_gateway_object();
			$label   = $gateway ? $gateway->get_label() : ucfirst( $this->get_gateway() );

			/**
			 * Filter the gateway label.
			 *
			 * @since 1.2.0
			 *
			 * @param string              $label    The label.
			 * @param Charitable_Donation $donation This `Charitable_Donation` instance.
			 */
			return apply_filters( 'charitable_donation_gateway_label', $label, $this );
		}

		/**
		 * Returns the gateway's object helper.
		 *
		 * @since  1.0.0
		 *
		 * @return false|Charitable_Gateway
		 */
		public function get_gateway_object() {
			$object = charitable_get_helper( 'gateways' )->get_gateway_object( $this->get_gateway() );

			if ( is_null( $object ) ) {
				return false;
			}

			return $object;
		}

		/**
		 * The status of this donation.
		 *
		 * @since  1.0.0
		 * @since  1.5.0 Deprecated $label argument. Use Charitable_Donation::get_status_label() instead.
		 *
		 * @param  boolean $label Whether to return the label. If not, returns the key.
		 * @return string
		 */
		public function get_status( $label = false ) {
			$status = $this->donation_data->post_status;

			if ( $label ) {
				charitable_get_deprecated()->deprecated_argument(
					__METHOD__,
					'1.5.0',
					__( 'The $label argument is deprecated. Use Charitable_Donation::get_status_label() instead.', 'charitable' )
				);
				return $this->get_status_label( $status );
			}

			return $status;
		}

		/**
		 * Return the donation status label.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $status Optional. The status to return the label for.
		 * @return string
		 */
		public function get_status_label( $status = '' ) {
			$statuses = charitable_get_valid_donation_statuses();
			$status   = empty( $status ) ? $this->get_status() : $status;

			return array_key_exists( $status, $statuses ) ? $statuses[ $status ] : $status;
		}

		/**
		 * Checks the order status against a passed in status.
		 *
		 * @since  1.3.6
		 *
		 * @param  string|array $status A status or array of statuses to check against.
		 * @return boolean
		 */
		public function has_status( $status ) {
			$donation_status = $this->get_status();
			$has_status      = ( is_array( $status ) && in_array( $donation_status, $status ) ) || $donation_status === $status;

			/**
			 * Filter whether the donation has the status.
			 *
			 * @since 1.3.6
			 *
			 * @param boolean             $has_status Whether the donation has the status.
			 * @param Charitable_Donation $donation   This instance of `Charitable_Donation`.
			 * @param string|array        $status     The status we are checking for.
			 */
			return apply_filters( 'charitable_donation_has_status', $has_status, $this, $status );
		}

		/**
		 * Returns the donation ID.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_donation_id() {
			return $this->donation_id;
		}

		/**
		 * Returns the donation type.
		 *
		 * @since  1.4.5
		 *
		 * @return string
		 */
		public function get_donation_type() {
			return $this->donation_type;
		}

		/**
		 * Returns the customer note attached to the donation.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_notes() {
			return $this->donation_data->post_content;
		}

		/**
		 * Returns the donor ID of the donor.
		 *
		 * @since  1.0.0
		 *
		 * @return int|false
		 */
		public function get_donor_id() {
			$campaign_donation = current( $this->get_campaign_donations() );

			if ( ! is_object( $campaign_donation ) ) {
				return false;
			}

			return $campaign_donation->donor_id;
		}

		/**
		 * Returns the donor who made this donation.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Donor
		 */
		public function get_donor() {
			if ( ! isset( $this->donor ) ) {
				$this->donor = new Charitable_Donor( $this->get_donor_id(), $this->donation_id );
			}

			return $this->donor;
		}

		/**
		 * Returns the donor address.
		 *
		 * @since  1.2.0
		 *
		 * @return string
		 */
		public function get_donor_address() {
			return $this->get_donor()->get_address();
		}

		/**
		 * Return whether the donation has been manually edited.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean
		 */
		public function was_manually_edited() {
			return (bool) get_post_meta( $this->ID, '_donation_manually_edited', true );
		}

		/**
		 * Return 'Yes' or 'No' for whether a donation was made in test mode.
		 *
		 * @since  1.5.0
		 *
		 * @param  boolean $text Whether to return a boolean value or readable text.
		 * @return string|boolean
		 */
		public function get_test_mode( $text = true ) {
			$test_mode = get_post_meta( $this->donation_id, 'test_mode', true );

			if ( $text ) {
				return $test_mode ? __( 'Yes', 'charitable' ) : __( 'No', 'charitable' );
			}

			return abs( $test_mode );
		}

		/**
		 * Check whether contact consent was given.
		 *
		 * @since  1.6.0
		 *
		 * @return string
		 */
		public function get_contact_consent() {
			$consent = get_post_meta( $this->ID, 'contact_consent', true );

			/* The donor hasn't given their consent, or has withdrawn it. */
			if ( ! $this->get_donor()->contact_consent ) {
				return $consent ? __( 'Withdrawn', 'charitable' ) : __( 'Not Given', 'charitable' );
			}

			return $consent ? __( 'Given', 'charitable' ) : __( 'Given Later', 'charitable' );
		}

		/**
		 * Checks whether contact consent was explicitly set for this donation.
		 *
		 * If the donation form did not include a contact_consent field, this will
		 * return false. Otherwise, it will return true (including if the donor
		 * did not give their consent).
		 *
		 * @since  1.6.0
		 *
		 * @return boolean
		 */
		public function contact_consent_explicitly_set() {
			$consent = get_post_meta( $this->ID, 'contact_consent' );

			return ! empty( $consent );
		}

		/**
		 * Return an array of meta relating to the donation.
		 *
		 * @since  1.2.0
		 *
		 * @return mixed[]
		 */
		public function get_donation_meta() {
			$fields = charitable()->donation_fields()->get_meta_fields();
			$meta   = array_map( array( $this, 'parse_donation_meta_field' ), $fields );
			$meta   = array_combine( array_keys( $fields ), $meta );

			/**
			 * Filter the donation meta.
			 *
			 * @since 1.2.0
			 *
			 * @param array               $meta     Set of meta values in an array of arrays, with
			 *                                      each array containing a 'label' and a 'value'.
			 * @param Charitable_Donation $donation This instance of `Charitable_Donation`.
			 */
			return apply_filters( 'charitable_donation_admin_meta', $meta, $this );
		}

		/**
		 * Checks whether the donation is from the current user.
		 *
		 * @since  1.4.0
		 *
		 * @return boolean
		 */
		public function is_from_current_user() {
			/* If the donation key is stored in the session, the user can access this receipt */
			if ( charitable_get_session()->has_donation_key( $this->get_donation_key() ) ) {
				return true;
			}

			if ( ! is_user_logged_in() ) {
				return false;
			}

			/* Retrieve the donor and current logged in user */
			$donor = $this->get_donor();
			$user  = wp_get_current_user();

			/* Make sure they match */
			if ( $donor->ID ) {
				return $donor->ID == $user->ID;
			}

			return $donor->get_email() == $user->user_email;
		}

		/**
		 * Check whether this donation can be refunded in the gateway automatically.
		 *
		 * @since  1.6.0
		 *
		 * @return boolean
		 */
		public function is_refundable_in_gateway() {
			$gateway = $this->get_gateway_object();
			return $gateway && $gateway->is_donation_refundable( $this );
		}

		/**
		 * Return the donation log object for this donation.
		 *
		 * @since  1.5.4
		 *
		 * @return Charitable_Donation_Log
		 */
		public function log() {
			if ( ! isset( $this->log ) ) {
				$this->log = new Charitable_Donation_Log( $this->ID );
			}

			return $this->log;
		}

		/**
		 * Get a donation's log.
		 *
		 * @since  1.0.0
		 * @since  1.3.0 Deprecated $donation_id arg. Method is now expected to be called in an object context.
		 *               i.e. $donation->get_donation_log().
		 *
		 * @return array
		 */
		public function get_donation_log( $donation_id = null ) {
			if ( $donation_id ) {
				charitable_get_deprecated()->deprecated_argument(
					__METHOD__,
					'1.3.0',
					sprintf( __( '$donation_id is no longer required as get_donation_log() is used in object context. Use $donation->log()->get_meta_log() instead.' ) )
				);
			}

			return $this->log()->get_meta_log();
		}

		/**
		 * Update the status of the donation.
		 *
		 * @uses   wp_update_post()
		 *
		 * @since  1.0.0
		 *
		 * @param  string $new_status The status to update the donation to.
		 * @return int The value 0 on failure. The donation ID on success.
		 */
		public function update_status( $new_status ) {
			$statuses = charitable_get_valid_donation_statuses();

			if ( false === charitable_is_valid_donation_status( $new_status ) ) {
				$original_new_status = $new_status;
				$new_status          = array_search( $new_status, $statuses );

				if ( false === $new_status ) {
					charitable_get_deprecated()->doing_it_wrong(
						__METHOD__,
						/* translators: %s: status */
						sprintf( '%s is not a valid donation status.', $original_new_status ),
						'1.0.0'
					);
					return 0;
				}
			}

			$old_status = $this->get_status();

			if ( $old_status == $new_status ) {
				return 0;
			}

			/* This actually updates the post status */
			$this->donation_data->post_status = $new_status;

			$donation_id = wp_update_post( $this->donation_data );

			if ( is_wp_error( $donation_id ) ) {
				return 0;
			}

			$message = sprintf(
				/* translators: %$1s: old status; %$2s: new status. */
				__( 'Donation status updated from %1$s to %2$s.', 'charitable' ),
				isset( $statuses[ $old_status ] ) ? $statuses[ $old_status ] : $old_status,
				isset( $statuses[ $new_status ] ) ? $statuses[ $new_status ] : $new_status
			);

			$this->log()->add( $message );

			/**
			 * Handle specific Charitable status transitions.
			 *
			 * @since 1.5.0
			 *
			 * @param Charitable_Donation $donation This instance of `Charitable_Donation`.
			 */
			do_action( 'charitable_donation_status_from_' . $old_status . '_to_' . $new_status, $this );

			/**
			 * Handle specific Charitable status change.
			 *
			 * @since 1.5.0
			 *
			 * @param Charitable_Donation $donation   This instance of `Charitable_Donation`.
			 * @param string              $old_status The previous donation status.
			 */
			do_action( 'charitable_donation_status_' . $new_status, $this, $old_status );

			/**
			 * Handle all Charitable status changes.
			 *
			 * @since 1.5.0
			 *
			 * @param Charitable_Donation $donation   This instance of `Charitable_Donation`.
			 * @param string              $new_status The new donation status.
			 * @param string              $old_status The previous donation status.
			 */
			do_action( 'charitable_donation_status_changed', $this, $new_status, $old_status );

			return $donation_id;
		}

		/**
		 * Add a message to the donation log.
		 *
		 * @since  1.0.0
		 * @since  1.3.0 First parameter changed from $donation_id to $message, as function is now expected
		 *               to be used in an object context. i.e.: $donation->update_donation_log( 'my message' )
		 * @since  1.5.0 Removed second parameter ($deprecated_message), which was only used for backwards
		 *               compatibility. We now use func_get_arg() instead to get the second passed argument.
		 * @since  1.5.4 Now uses Charitable_Donation_Log::add() and returns a result.
		 *
		 * @param  string $message The message to add to the log.
		 * @return int|bool Meta ID if the key didn't exist, true on successful update,
		 *                  false on failure.
		 */
		public function update_donation_log( $message ) {
			if ( is_int( $message ) ) {
				charitable_get_deprecated()->deprecated_argument(
					__METHOD__,
					'1.3.0',
					sprintf( __( '$donation_id is no longer required as update_donation_log() is used in object context. Use $donation->update_donation_log($message)' ) )
				);

				$message = func_get_arg( 1 );
			}

			return $this->log()->add( $message );
		}

		/**
		 * Return the parent donation, if exists
		 *
		 * @since  1.4.5
		 *
		 * @return int
		 */
		public function get_donation_plan_id() {
			return $this->donation_data->post_parent;
		}

		/**
		 * Return the parent donation, if exists.
		 *
		 * @since  1.4.5
		 *
		 * @return false|Charitable_Donation
		 */
		public function get_donation_plan() {
			if ( ! isset( $this->donation_plan ) ) {
				if ( $this->donation_data->post_parent > 0 ) {
					$this->donation_plan = charitable_get_donation( $this->donation_data->post_parent );
				} else {
					$this->donation_plan = false;
				}
			}

			return $this->donation_plan;
		}

		/**
		 * Save the gateway's transaction ID
		 *
		 * @since  1.4.6
		 *
		 * @param  string $value The transaction ID.
		 * @return bool
		 */
		public function set_gateway_transaction_id( $value ) {
			$key   = '_gateway_transaction_id';
			$value = charitable_sanitize_donation_meta( $value, $key );
			return update_post_meta( $this->donation_id, $key, $value );
		}

		/**
		 * Get the gateway's transaction ID
		 *
		 * @since  1.4.6
		 *
		 * @return mixed
		 */
		public function get_gateway_transaction_id() {
			if ( ! isset( $this->gateway_transaction_id ) ) {
				$this->gateway_transaction_id = get_post_meta( $this->donation_id, '_gateway_transaction_id', true );
			}

			return $this->gateway_transaction_id;
		}

		/**
		 * Return the date that the donation data was erased.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $format The format in which to return the date of erasure.
		 * @return false|string The date of erasure, or false if the data has not been erased.
		 */
		public function get_data_erasure_date( $format = null ) {
			$date = get_post_meta( $this->donation_id, 'data_erased', true );

			if ( ! $date ) {
				return false;
			}

			if ( is_null( $format ) ) {
				$format = get_option( 'date_format' );
			}

			return mysql2date( $format, $date );
		}

		/**
		 * Return an array containing a label and value for a donation meta field.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Donation_Field $field Instance of `Charitable_Donation_Field`.
		 * @return array
		 */
		protected function parse_donation_meta_field( Charitable_Donation_Field $field ) {
			$value = charitable_get_sanitized_donation_field_value( $this->get( $field->field ), $field->field );

			if ( empty( $value ) ) {
				$value = '-';
			}

			return array(
				'label' => $field->label,
				'value' => $value,
			);
		}

		/**
		 * Deprecated Methods
		 */

		/**
		 * Return array of valid donations statuses.
		 *
		 * @deprecated 1.7.0
		 *
		 * @see    charitable_get_valid_donation_statuses
		 *
		 * @since  1.0.0
		 * @since  1.4.0 Deprecated
		 *
		 * @return array
		 */
		public function get_valid_donation_statuses() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_get_valid_donation_statuses'
			);

			return charitable_get_valid_donation_statuses();
		}

		/**
		 * Returns whether the donation status is valid.
		 *
		 * @deprecated 1.7.0
		 *
		 * @see    charitable_is_valid_donation_status
		 *
		 * @since  1.0.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @return boolean
		 */
		public function is_valid_donation_status( $status ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_is_valid_donation_status'
			);

			return charitable_is_valid_donation_status( $status );
		}

		/**
		 * Returns the donation statuses that signify a donation was complete.
		 *
		 * By default, this is just 'charitable-completed'. However, 'charitable-preapproval'
		 * is also counted.
		 *
		 * @deprecated 1.7.0
		 *
		 * @see    charitable_get_approval_statuses
		 *
		 * @since  1.0.0
		 *
		 * @return string[]
		 */
		public function get_approval_statuses() {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_get_approval_statuses'
			);

			return charitable_get_approval_statuses();
		}

		/**
		 * Returns whether the passed status is an confirmed status.
		 *
		 * @deprecated 1.7.0
		 *
		 * @see    charitable_is_approved_status
		 *
		 * @since  1.0.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @return boolean
		 */
		public function is_approved_status( $status ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_is_approved_status'
			);

			return charitable_is_approved_status( $status );
		}

		/**
		 * Sanitize meta values before they are persisted to the database.
		 *
		 * @deprecated 1.7.0
		 *
		 * @see    charitable_sanitize_donation_meta
		 *
		 * @since  1.0.0
		 * @since  1.4.0
		 *
		 * @param  mixed  $value
		 * @param  string $key
		 * @return mixed
		 */
		public function sanitize_meta( $value, $key ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_sanitize_donation_meta()'
			);

			return charitable_sanitize_donation_meta( $value, $key );
		}

		/**
		 * Flush the donations cache for every campaign receiving a donation.
		 *
		 * @deprecated 1.7.0
		 *
		 * @see    charitable_flush_campaigns_donation_cache
		 *
		 * @since  1.0.0
		 * @since  1.4.0 Deprecated.
		 *
		 * @param  int $donation_id
		 * @return void
		 */
		public function flush_campaigns_donation_cache( $donation_id ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'charitable_sanitize_donation_meta()'
			);

			return charitable_flush_campaigns_donation_cache( $donation_id );
		}
	}

endif;
