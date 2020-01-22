<?php
/**
 * Donor model.
 *
 * @package     Charitable/Classes/Charitable_Donor
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donor' ) ) :

	/**
	 * Charitable_Donor
	 *
	 * @since    1.0.0
	 *
	 * @property int    $donor_id
	 * @property string $first_name
	 * @property string $last_name
	 * @property string $email
	 * @property string $date_joined
	 * @property string $data_erased
	 * @property int    $contact_consent
	 */
	class Charitable_Donor {

		/**
		 * The donor ID.
		 *
		 * @since 1.0.0
		 *
		 * @var   int
		 */
		protected $donor_id;

		/**
		 * The donor data from charitable_donors table.
		 *
		 * @since 1.0.0
		 *
		 * @var   Object
		 */
		protected $data;

		/**
		 * The donation ID.
		 *
		 * @since 1.0.0
		 *
		 * @var   int
		 */
		protected $donation_id;

		/**
		 * User object.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_User
		 */
		protected $user;

		/**
		 * Donation object.
		 *
		 * @since 1.0.0
		 *
		 * @var   Charitable_Donation|null
		 */
		protected $donation = null;

		/**
		 * The donation object for the most recently made donation.
		 *
		 * @since 1.5.7
		 *
		 * @var   Charitable_Donor
		 */
		protected $last_donation;

		/**
		 * Donor meta.
		 *
		 * @since 1.4.0
		 *
		 * @var   mixed[]
		 */
		protected $donor_meta;

		/**
		 * A mapping of user keys.
		 *
		 * @since 1.4.0
		 *
		 * @var   string[]
		 */
		protected $mapped_keys;

		/**
		 * Create class object.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $donor_id    Donor ID.
		 * @param  int $donation_id Donation ID. Passed if this object is created through a donation.
		 */
		public function __construct( $donor_id, $donation_id = false ) {
			$this->donor_id    = $donor_id;
			$this->data        = charitable_get_table( 'donors' )->get( $donor_id );
			$this->donation_id = $donation_id;
		}

		/**
		 * Magic getter method. Looks for the specified key in as a property before using Charitable_User's __get method.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key Key to search for.
		 * @return mixed
		 */
		public function __get( $key ) {
			if ( isset( $this->$key ) ) {
				return $this->$key;
			}

			if ( isset( $this->data->$key ) ) {
				return $this->data->$key;
			}

			return $this->get_user()->$key;
		}

		/**
		 * Display the donor name when echoing object.
		 *
		 * @since  1.4.0
		 *
		 * @return string
		 */
		public function __toString() {
			return $this->get_name();
		}

		/**
		 * A thin wrapper around the Charitable_User::get() method.
		 *
		 * @since  1.2.4
		 *
		 * @param  string $key The user property key.
		 * @return mixed
		 */
		public function get( $key ) {
			return $this->get_user()->get( $key );
		}

		/**
		 * Return the Charitable_User object for this donor.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_User
		 */
		public function get_user() {
			if ( ! isset( $this->user ) ) {
				$this->user = Charitable_User::init_with_donor( $this->donor_id );
			}

			return $this->user;
		}

		/**
		 * Return the Charitable_Donation object associated with this object.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Donation|false
		 */
		public function get_donation() {
			if ( ! isset( $this->donation ) ) {
				$this->donation = $this->donation_id ? charitable_get_donation( $this->donation_id ) : false;
			}

			return $this->donation;
		}

		/**
		 * Return the Charitable_Donation object associated with this object.
		 *
		 * @since  1.3.5
		 *
		 * @return object[]
		 */
		public function get_donations() {
			return $this->get_user()->get_donations();
		}

		/**
		 * Attach a user ID to the donor record.
		 *
		 * @since  1.5.0
		 *
		 * @param  int $user_id The user ID for the donor record.
		 * @return boolean
		 */
		public function set_user_id( $user_id ) {
			return charitable_get_table( 'donors' )->update( $this->donor_id, array( 'user_id' => $user_id ), 'donor_id' );
		}

		/**
		 * Return the donor meta stored for the particular donation.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key Optional key passed to return a particular meta field.
		 * @return array|mixed|false
		 */
		public function get_donor_meta( $key = '' ) {
			if ( ! $this->get_donation() ) {
				return $this->get_donor_meta_from_profile( $key );
			}

			if ( ! isset( $this->donor_meta ) ) {
				$this->donor_meta = get_post_meta( $this->donation_id, 'donor', true );
			}

			return $this->get_donor_meta_from_set_meta( $key );
		}

		/**
		 * Return a donor's meta details from their profile, or their most recent donation.
		 *
		 * @since  1.5.7
		 *
		 * @param  string $key Optional key passed to return a particular meta field.
		 * @return array|mixed|false
		 */
		protected function get_donor_meta_from_profile( $key ) {
			if ( isset( $this->data->$key ) ) {
				return $this->data->$key;
			}

			/* If the donor has a profile, return the values from that. */
			if ( $this->get_user()->ID ) {
				return $this->get_user()->get( $key );
			}

			/**
			 * If we still don't have any data about them, return the
			 * data from the last donation.
			 */
			$last_donation = $this->get_last_donation();

			if ( is_null( $last_donation ) ) {
				return false;
			}

			if ( ! isset( $this->donor_meta ) ) {
				$this->donor_meta = $last_donation->get_donor_data();
			}

			return $this->get_donor_meta_from_set_meta( $key );
		}

		/**
		 * Return a specific key or the entire array of data from a set of donor data.
		 *
		 * @since  1.5.7
		 *
		 * @param  string $key Optional key passed to return a particular meta field.
		 * @return array|mixed
		 */
		protected function get_donor_meta_from_set_meta( $key ) {
			if ( empty( $key ) ) {
				return $this->donor_meta;
			}

			if ( isset( $this->donor_meta[ $key ] ) ) {
				return $this->donor_meta[ $key ];
			}

			$mapped_keys = $this->get_mapped_keys();

			if ( ! in_array( $key, $mapped_keys ) ) {
				return '';
			}

			$key = array_search( $key, $mapped_keys );

			if ( isset( $this->donor_meta[ $key ] ) ) {
				return $this->donor_meta[ $key ];
			}
		}

		/**
		 * Return the most recently made donation.
		 *
		 * @since  1.5.7
		 *
		 * @return Charitable_Donation|null Null if no donation was found. A `Charitable_Donation` instance otherwise.
		 */
		public function get_last_donation() {
			if ( ! isset( $this->last_donation ) ) {
				$donation = new Charitable_Donations_Query(
					array(
						'number'   => 1,
						'donor_id' => $this->donor_id,
					)
				);

				$this->last_donation = $donation->count() ? $donation->current() : null;
			}

			return $this->last_donation;
		}

		/**
		 * Return the donor's name stored for the particular donation.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_name() {
			$name = trim( sprintf( '%s %s', $this->get_donor_meta( 'first_name' ), $this->get_donor_meta( 'last_name' ) ) );

			/**
			 * Filter the donor name.
			 *
			 * @since 1.0.0
			 *
			 * @param string           $name  The donor's name.
			 * @param Charitable_Donor $donor This instance of `Charitable_Donor`.
			 */
			return apply_filters( 'charitable_donor_name', $name, $this );
		}

		/**
		 * Return the donor's email address.
		 *
		 * @since  1.2.4
		 *
		 * @return string
		 */
		public function get_email() {
			return $this->get_donor_meta( 'email' );
		}

		/**
		 * Checks whether the donor has a valid email address.
		 *
		 * @since  1.6.0
		 *
		 * @return boolean
		 */
		public function has_valid_email() {
			return charitable_is_valid_email_address( $this->get_donor_meta( 'email' ) );
		}

		/**
		 * Return the donor's address.
		 *
		 * @since  1.2.4
		 *
		 * @return string
		 */
		public function get_address() {
			return $this->get_user()->get_address( $this->donation_id );
		}

		/**
		 * Return the donor avatar.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $size The side length to use for the avatar. The avatar is returned
		 *                   as a square image, so this is used for both height and width.
		 * @return string
		 */
		public function get_avatar( $size = 100 ) {
			/**
			 * Filter the donor avatar.
			 *
			 * @since 1.2.0
			 *
			 * @param string           $avatar The avatar HTML code.
			 * @param Charitable_Donor $donor  This instance of `Charitable_Donor`.
			 */
			return apply_filters( 'charitable_donor_avatar', $this->get_user()->get_avatar( $size ), $this );
		}

		/**
		 * Return the donor location.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_location() {
			if ( ! $this->get_donor_meta() ) {
				return $this->get_user()->get_location();
			}

			$meta    = $this->get_donor_meta();
			$city    = isset( $meta['city'] ) ? $meta['city'] : '';
			$state   = isset( $meta['state'] ) ? $meta['state'] : '';
			$country = isset( $meta['country'] ) ? $meta['country'] : '';
			$region  = strlen( $city ) ? $city : $state;

			if ( strlen( $country ) ) {
				if ( strlen( $region ) ) {
					$location = sprintf( '%s, %s', $region, $country );
				} else {
					$location = $country;
				}
			} else {
				$location = $region;
			}

			return apply_filters( 'charitable_donor_location', $location, $this );
		}

		/**
		 * Return the donation amount.
		 *
		 * If a donation ID was passed to the object constructor, this will return
		 * the total donated with this particular donation. Otherwise, this will
		 * return the total amount ever donated by the donor.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id Optional. If set, returns total donated to this particular campaign.
		 * @return decimal
		 */
		public function get_amount( $campaign_id = false ) {
			if ( $this->get_donation() ) {
				return $this->get_donation_amount( $campaign_id );
			}

			return $this->get_user()->get_total_donated( $campaign_id );
		}

		/**
		 * Return the amount of the donation.
		 *
		 * @since  1.2.0
		 *
		 * @param  int $campaign_id Optional. If set, returns the amount donated to the campaign.
		 * @return decimal
		 */
		public function get_donation_amount( $campaign_id = '' ) {
			return apply_filters( 'charitable_donor_donation_amount', charitable_get_table( 'campaign_donations' )->get_donation_amount( $this->donation_id, $campaign_id ), $this, $campaign_id );
		}

		/**
		 * Return the array of mapped keys, where the key is mapped to a meta_key in the user meta table.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_mapped_keys() {
			if ( ! isset( $this->mapped_keys ) ) {
				$this->mapped_keys = charitable_get_user_mapped_keys();
			}

			return $this->mapped_keys;
		}

		/**
		 * Return a value from the donor meta.
		 *
		 * @deprecated 1.7.0
		 *
		 * @since  1.2.4
		 * @since  1.4.0 Deprecated
		 *
		 * @param  string $key The particular field to get the value for.
		 * @return mixed
		 */
		public function get_value( $key ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.4.0',
				'Charitable_Donor::get_donor_meta()'
			);
			return $this->get_donor_meta( $key );
		}
	}

endif;
