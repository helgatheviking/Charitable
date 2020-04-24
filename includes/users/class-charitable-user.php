<?php
/**
 * Contains the class that models users in Charitable.
 *
 * There are several different user roles in Charitable, and one user
 * may be more than one. People who make donations get the "donor" role;
 * people who create campaigns (via Charitable Ambassadors) get
 * the "campaign_creator" role; people who create fundraisers for campaigns
 * get the "fundraiser" role.
 *
 * @package   Charitable/Classes/Charitable_User
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.37
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_User' ) ) :

	/**
	 * Charitable_User
	 *
	 * @since 1.0.0
	 *
	 * @property string $nickname
	 * @property string $description
	 * @property string $user_description
	 * @property string $first_name
	 * @property string $user_firstname
	 * @property string $last_name
	 * @property string $user_lastname
	 * @property string $user_login
	 * @property string $user_pass
	 * @property string $user_nicename
	 * @property string $user_email
	 * @property string $user_url
	 * @property string $user_registered
	 * @property string $user_activation_key
	 * @property string $user_status
	 * @property int    $user_level
	 * @property string $display_name
	 * @property string $spam
	 * @property string $deleted
	 * @property string $locale
	 */
	class Charitable_User extends WP_User {

		/**
		 * The donor ID.
		 *
		 * NOTE: This is not the same as the user ID.
		 *
		 * @since 1.0.0
		 *
		 * @var   int
		 */
		protected $donor_id;

		/**
		 * A mapping of user keys.
		 *
		 * @since 1.4.0
		 *
		 * @var   string[]
		 */
		protected $mapped_keys;

		/**
		 * Core keys.
		 *
		 * @since  1.4.0
		 *
		 * @var   string[]
		 */
		protected $core_keys;

		/**
		 * Create class object.
		 *
		 * @since  1.0.0
		 *
		 * @param  int|string|stdClass|WP_User $id      User's ID, a WP_User object, or a user object from the DB.
		 * @param  string                      $name    Optional. User's username.
		 * @param  int                         $blog_id Optional Blog ID, defaults to current blog.
		 * @return void
		 */
		public function __construct( $id = 0, $name = '', $blog_id = '' ) {
			parent::__construct( $id, $name, $blog_id );
		}

		/**
		 * Create object using a donor ID.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $donor_id The donor ID.
		 * @return Charitable_user
		 */
		public static function init_with_donor( $donor_id ) {
			$user_id = charitable_get_table( 'donors' )->get_user_id( $donor_id );
			$user    = charitable_get_user( $user_id );
			$user->set_donor_id( $donor_id );
			return $user;
		}

		/**
		 * Magic getter method. Looks for the specified key in the mapped keys before using WP_User's __get method.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $key The key to retrieve.
		 * @return mixed
		 */
		public function __get( $key ) {
			return parent::__get( $this->map_key( $key ) );
		}

		/**
		 * Display the donor name when printing the object.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function __toString() {
			return $this->get_name();
		}

		/**
		 * Determine whether a property or meta key is set
		 *
		 * Consults the users and usermeta tables.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key Property.
		 * @return boolean
		 */
		public function has_prop( $key ) {
			return parent::has_prop( $this->map_key( $key ) );
		}

		/**
		 * Returns whether the user is logged in.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_logged_in() {
			return 0 !== $this->ID;
		}

		/**
		 * Set the donor ID of this user.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $donor_id The Donor ID.
		 * @return void
		 */
		public function set_donor_id( $donor_id ) {
			$this->donor_id = $donor_id;
		}

		/**
		 * Return the donor ID of this user.
		 *
		 * @since  1.0.0
		 *
		 * @return int|false $donor_id
		 */
		public function get_donor_id() {
			if ( isset( $this->donor_id ) || ! is_null( $this->get_donor() ) ) {
				return $this->donor_id;
			}

			return false;
		}

		/**
		 * Return the donor record.
		 *
		 * @since  1.0.0
		 *
		 * @return Object|null Object if a donor record could be matched. null if user is logged out or no donor record found.
		 */
		public function get_donor() {
			if ( ! $this->is_logged_in() && ! isset( $this->donor_id ) ) {
				return null;
			}

			if ( isset( $this->donor_id ) ) {

				$donor = wp_cache_get( $this->donor_id, 'donors' );

				if ( is_object( $donor ) ) {
					return $donor;
				}

				$donor = charitable_get_table( 'donors' )->get( $this->donor_id );

			} else {

				$donor = charitable_get_table( 'donors' )->get_by( 'email', $this->get( 'user_email' ) );

				if ( ! is_object( $donor ) ) {
					return null;
				}

				$this->donor_id = $donor->donor_id;

			}//end if

			wp_cache_add( $this->donor_id, $donor, 'donors' );

			return $donor;
		}

		/**
		 * Returns whether the user has ever made a donation.
		 *
		 * @since  1.0.0
		 *
		 * @return boolean
		 */
		public function is_donor() {
			return ! is_null( $this->get_donor() );
		}

		/**
		 * Returns whether the user has verified their email address.
		 *
		 * @since  1.5.0
		 *
		 * @return int If verified, return 1. Otherwise return 0.
		 */
		public function is_verified() {
			return absint( get_user_meta( $this->ID, '_charitable_user_verified', true ) );
		}

		/**
		 * Mark a user as verified.
		 *
		 * @since  1.5.0
		 *
		 * @param  boolean $verified Whether the user is verified.
		 * @return int|boolean Meta ID if the key didn't exist, true on successful update, false on failure.
		 */
		public function mark_as_verified( $verified ) {
			$verified = update_user_meta( $this->ID, '_charitable_user_verified', $verified );

			if ( ! $verified ) {
				return $verified;
			}

			/* Delete the time that the verification email was sent. */
			delete_user_meta( $this->ID, '_charitable_email_verification_email_send_time' );

			/* Check for a notice about having sent a verification email and remove it. */
			charitable_get_notices()->clear_notice( "/We have sent you an email to confirm your email address. Haven\'t received the email\?/" );

			/* Check for an existing donor account. */
			$donor_id = $this->get_donor_id();

			if ( $donor_id ) {
				$donor = new Charitable_Donor( $donor_id );
				$donor->set_user_id( $this->ID );
			}

			/**
			 * Do something when a user is verified.
			 *
			 * @since 1.6.23
			 *
			 * @param Charitable_User $user The user object.
			 */
			do_action( 'charitable_user_verified', $this );

			return $verified;
		}

		/**
		 * Returns the email address of the donor.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_email() {
			if ( $this->get_donor() ) {
				$email = $this->get_donor()->email;
			} else {
				$email = $this->get( 'user_email' );
			}

			/**
			 * Filter the user email address.
			 *
			 * @since 1.0.0
			 *
			 * @param string          $email The email address.
			 * @param Charitable_User $user  This instance of `Charitable_User`.
			 */
			return apply_filters( 'charitable_user_email', $email, $this );
		}

		/**
		 * Returns the display name of the user.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_name() {
			if ( $this->is_donor() ) {
				$name = rtrim( sprintf( '%s %s', $this->get_donor()->first_name, $this->get_donor()->last_name ) );
			} else {
				$name = $this->display_name;
			}

			if ( ! $name ) {
				$name = '';
			}

			return apply_filters( 'charitable_user_name', $name, $this );
		}

		/**
		 * Returns the first name of the user.
		 *
		 * @since  1.1.0
		 *
		 * @return string
		 */
		public function get_first_name() {
			if ( $this->is_donor() && $this->get_donor()->first_name ) {
				$name = $this->get_donor()->first_name;
			} else {
				$name = $this->first_name;
			}

			return apply_filters( 'charitable_user_first_name', $name, $this );
		}

		/**
		 * Returns the last name of the user.
		 *
		 * @since  1.1.0
		 *
		 * @return string
		 */
		public function get_last_name() {
			if ( $this->is_donor() && $this->get_donor()->last_name ) {
				$name = $this->get_donor()->last_name;
			} else {
				$name = $this->last_name;
			}
			return apply_filters( 'charitable_user_last_name', $name, $this );
		}

		/**
		 * Returns the user's location.
		 *
		 * @since  1.0.0
		 *
		 * @return string
		 */
		public function get_location() {
			$city     = $this->get( 'donor_city' );
			$state    = $this->get( 'donor_state' );
			$country  = $this->get( 'donor_country' );
			$location = '';

			if ( strlen( $city ) || strlen( $state ) ) {
				$region = strlen( $city ) ? $city : $state;

				if ( strlen( $country ) ) {
					$location = sprintf( '%s, %s', $region, $country );
				} else {
					$location = $region;
				}
			} elseif ( strlen( $country ) ) {
				$location = $country;
			}

			return apply_filters( 'charitable_user_location', $location, $this );
		}

		/**
		 * Return an array of fields used for the address.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_address_fields() {
			return apply_filters(
				'charitable_user_address_fields',
				array(
					'donor_address',
					'donor_address_2',
					'donor_city',
					'donor_state',
					'donor_postcode',
					'donor_country',
				)
			);
		}

		/**
		 * Returns printable address of donor.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $donation_id Optional. If set, will return the address provided for the specific donation. Otherwise, returns the current address for the user.
		 * @return string
		 */
		public function get_address( $donation_id = '' ) {
			$address_fields = false;

			if ( $donation_id ) {

				$address_fields = get_post_meta( $donation_id, 'donor', true );

			}

			/* If the address fields were not set by the check above, get them from the user meta. */
			if ( ! is_array( $address_fields ) ) {

				$address_fields = array(
					'first_name' => $this->get( 'first_name' ),
					'last_name'  => $this->get( 'last_name' ),
					'company'    => $this->get( 'donor_company' ),
					'address'    => $this->get( 'donor_address' ),
					'address_2'  => $this->get( 'donor_address_2' ),
					'city'       => $this->get( 'donor_city' ),
					'state'      => $this->get( 'donor_state' ),
					'postcode'   => $this->get( 'donor_postcode' ),
					'country'    => $this->get( 'donor_country' ),
				);

			}

			$address_fields = apply_filters( 'charitable_user_address_fields', $address_fields, $this, $donation_id );

			return charitable_get_location_helper()->get_formatted_address( $address_fields );
		}

		/**
		 * Return all donations made by donor.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $distinct_donations If true, will only count unique donations.
		 * @return object[]
		 */
		public function get_donations( $distinct_donations = false ) {
			return charitable_get_table( 'campaign_donations' )->get_donations_by_donor( $this->get_donor_id(), $distinct_donations );
		}

		/**
		 * Return the number of donations made by the donor.
		 *
		 * @since  1.0.0
		 *
		 * @param  boolean $distinct_donations If true, will only count unique donations.
		 * @return int
		 */
		public function count_donations( $distinct_donations = false ) {
			return charitable_get_table( 'campaign_donations' )->count_donations_by_donor( $this->get_donor_id(), $distinct_donations );
		}

		/**
		 * Return the number of campaigns that the donor has supported.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function count_campaigns_supported() {
			return charitable_get_table( 'campaign_donations' )->count_campaigns_supported_by_donor( $this->get_donor_id() );
		}

		/**
		 * Return the total amount donated by the donor.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $campaign_id Optional. If set, returns total donated to this particular campaign.
		 * @return float
		 */
		public function get_total_donated( $campaign_id = false ) {
			$amount = wp_cache_get( $this->get_donor_id(), 'charitable_donor_total_donation_amount_' . $campaign_id );

			if ( false === $amount ) {

				$args = apply_filters(
					'charitable_user_total_donated_query_args',
					array(
						'output'          => 'raw',
						'donor_id'        => $this->get_donor_id(),
						'distinct_donors' => true,
						'fields'          => 'amount',
						'campaign'        => (int) $campaign_id,
					),
					$this
				);

				$query = new Charitable_Donor_Query( $args );

				$amount = $query->current()->amount;

				wp_cache_set( $this->get_donor_id(), $amount, 'charitable_donor_total_donation_amount_' . $campaign_id );
			}

			return (float) $amount;
		}

		/**
		 * Returns the user's avatar as a fully formatted <img> tag.
		 *
		 * By default, this will return the gravatar, but it can
		 * be extended to add support for locally hosted avatars.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $size The length and width of the avatar.
		 * @return string
		 */
		public function get_avatar( $size = 100 ) {
			/**If you use this filter, return an attachment ID. */
			$avatar_attachment_id = apply_filters( 'charitable_user_avatar', false, $this );

			/* If we don't have an attachment ID, display the gravatar. */
			if ( ! $avatar_attachment_id ) {
				return get_avatar( $this->ID, $size );
			}

			$img_size = apply_filters( 'charitable_user_avatar_media_size', array( $size, $size ), $size );

			$attachment_src = wp_get_attachment_image_src( $avatar_attachment_id, $img_size );

			/* No image for the given attachment ID? Fall back to the gravatar. */
			if ( ! $attachment_src ) {
				return get_avatar( $this->ID, $size );
			}

			return apply_filters( 'charitable_user_avatar_custom', sprintf( '<img src="%s" alt="%s" class="avatar photo" width="%s" height="%s" />',
				$attachment_src[0],
				esc_attr( $this->display_name ),
				$attachment_src[1],
				$attachment_src[2]
			), $avatar_attachment_id, $size, $this );
		}

		/**
		 * Return the src of the avatar.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $size The length and the width of the avatar.
		 * @return string
		 */
		public function get_avatar_src( $size = 100 ) {
			/* If this returns something, we don't need to deal with the gravatar. */
			$avatar = apply_filters( 'charitable_user_avatar_src', false, $this, $size );

			if ( false === $avatar ) {

				/* The gravatars are returned as fully formatted img tags, so we need to pull out the src. */
				$gravatar = get_avatar( $this->ID, $size );

				preg_match( "@src='([^']+)'@" , $gravatar, $matches );

				$avatar = array_pop( $matches );
			}

			return $avatar;
		}

		/**
		 * Return the campaigns created by the user.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Optional. Any arguments accepted by WP_Query.
		 * @return WP_Query
		 */
		public function get_campaigns( $args = array() ) {
			$defaults = array(
				'author' => $this->ID,
			);

			$args = wp_parse_args( $args, $defaults );

			return Charitable_Campaigns::query( $args );
		}

		/**
		 * Checks whether the user has any current campaigns (i.e. non-expired).
		 *
		 * @since  1.0.0
		 *
		 * @param  array $args Query arguments.
		 * @return WP_Query
		 */
		public function get_current_campaigns( $args = array() ) {
			$defaults = array(
				'author' => $this->ID,
				'meta_query'    => array(
					'relation'      => 'OR',
					array(
						'key'       => '_campaign_end_date',
						'value'     => date( 'Y-m-d H:i:s' ),
						'compare'   => '>=',
						'type'      => 'datetime',
					),
					array(
						'key'       => '_campaign_end_date',
						'value'     => '0',
					)
				),
			);

			$args = wp_parse_args( $args, $defaults );

			return Charitable_Campaigns::query( $args );
		}

		/**
		 * Returns all current campaigns by the user.
		 *
		 * @since  1.0.0
		 *
		 * @return WP_Query
		 */
		public function has_current_campaigns() {
			return $this->get_current_campaigns()->found_posts;
		}

		/**
		 * Returns the user's donation and campaign creation activity.
		 *
		 * @see     WP_Query
		 * @since  1.0.0
		 *
		 * @param  array $args Optional. Any arguments accepted by WP_Query.
		 * @return WP_Query
		 */
		public function get_activity( $args = array() ) {
			$defaults = array(
				'author'      => $this->ID,
				'post_status' => array( 'charitable-completed', 'charitable-preapproved', 'publish' ),
				'post_type'   => array( 'donation', 'campaign' ),
				'order'       => 'DESC',
				'orderby'     => 'date',
			);

			$args = wp_parse_args( $args, $defaults );

			$args = apply_filters( 'charitable_user_activity_args', $args, $this );

			return new WP_Query( $args );
		}

		/**
		 * Add a new donor. This may also create a user account for them.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $submitted Values submitted by the user.
		 * @return int   $donor_id  Donor ID.
		 */
		public function add_donor( $submitted = array() ) {
			$email = false;

			if ( isset( $submitted['email'] ) ) {
				$email = $submitted['email'];
			}

			if ( ! $email && $this->is_logged_in() ) {
				$email = $this->user_email;
			}

			$donor_values = array(
				'user_id'    => $this->ID,
				'email'      => $email,
				'first_name' => array_key_exists( 'first_name', $submitted ) ? $submitted['first_name'] : $this->first_name,
				'last_name'  => array_key_exists( 'last_name', $submitted ) ? $submitted['last_name'] : $this->last_name,
			);

			if ( ! $email ) {
				/* If email-less donations are OK, remove the email from the donor values. */
				if ( charitable_permit_donor_without_email() ) {
					unset( $donor_values['email'] );
				} else {
					charitable_get_deprecated()->doing_it_wrong(
						__METHOD__,
						__( 'Unable to add donor. Email not set for logged out user.', 'charitable' ),
						'1.0.0'
					);

					return 0;
				}
			}

			/**
			 * Filter the donor values.
			 *
			 * @since 1.0.0
			 *
			 * @param array           $donor_values The donor values.
			 * @param Charitable_User $user         This instance of `Charitable_User`.
			 * @param array           $submitted    Submitted values.
			 */
			$donor_values = apply_filters( 'charitable_donor_values', $donor_values, $this, $submitted );

			$donor_id = charitable_get_table( 'donors' )->insert( $donor_values );

			/**
			 * If the user is logged in, add the "Donor" role to their account.
			 */
			if ( $this->is_logged_in() ) {
				$this->add_role( 'donor' );
			}

			do_action( 'charitable_after_insert_donor', $donor_id, $this );

			return $donor_id;
		}

		/**
		 * Insert a new donor with submitted values.
		 *
		 * @since  1.4.0
		 *
		 * @param  array $submitted The submitted values.
		 * @param  array $keys The keys of fields that are to be updated.
		 * @return int
		 */
		public static function create_profile( $submitted = array(), $keys = array() ) {
			$user = new Charitable_User();
			$user_id = $user->update_profile( $submitted, $keys );
			return new Charitable_User( $user_id );
		}

		/**
		 * Update the user's details with submitted values.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $submitted The submitted values.
		 * @param  array $keys The keys of fields that are to be updated.
		 * @return int
		 */
		public function update_profile( $submitted = array(), $keys = array() ) {
			if ( empty( $submitted ) ) {
				$submitted = $_POST;
			}

			if ( empty( $keys ) ) {
				$keys = array_keys( $submitted );
			}

			$user_id = $this->update_core_user( $submitted );

			/* If there were problems with creating the user, stop here. */
			if ( ! $user_id ) {
				return $user_id;
			}

			/* Create or update the user's donor record. */
			$this->update_donor_record( $submitted, $keys );

			$this->update_user_meta( $submitted, $keys );

			return $user_id;
		}

		/**
		 * Create or update a donor record for the current user.
		 *
		 * @since  1.6.2
		 *
		 * @param  array $submitted The submitted values.
		 * @param  array $keys      The keys of fields that are to be updated.
		 * @return int The donor ID, or 0 if none was created or edited.
		 */
		public function update_donor_record( $submitted, $keys ) {
			$donor_fields = array_intersect( $keys, charitable_get_donor_keys() );

			if ( array_key_exists( 'user_email', $submitted ) ) {
				$donor_fields[]     = 'email';
				$submitted['email'] = $submitted['user_email'];
			}

			if ( empty( $donor_fields ) ) {
				return 0;
			}

			$donor_data = array(
				'user_id' => $this->ID,
			);

			foreach ( $donor_fields as $field ) {
				if ( array_key_exists( $field, $submitted ) ) {
					$value = $submitted[ $field ];
				} elseif ( 'contact_consent' == $field ) {
					$value = 0;
				} else {
					$value = '';
				}

				$donor_data[ $field ] = $value;
			}

			if ( array_key_exists( 'donor_id', $donor_data ) ) {
				$donor_id = $donor_data['donor_id'];
			} else {
				$donor_id = $this->get_donor_id();
			}

			/* Clear out the cache */
			wp_cache_delete( $donor_id, 'donors' );

			if ( $donor_id ) {
				charitable_get_table( 'donors' )->update( $donor_id, $donor_data );

				return $donor_id;
			}

			return charitable_get_table( 'donors' )->insert( $donor_data );
		}

		/**
		 * Save core fields of the user (i.e. the wp_users data)
		 *
		 * @uses   wp_insert_user
		 *
		 * @since  1.0.0
		 *
		 * @param  array $submitted Values submitted by the user.
		 * @return int User ID
		 */
		public function update_core_user( $submitted ) {
			$core_fields = array_intersect( array_keys( $submitted ), $this->get_core_keys() );

			if ( empty( $core_fields ) ) {
				return 0;
			}

			$values = array();

			/* If we're updating an active user, set the ID */
			if ( 0 !== $this->ID ) {
				$values['ID'] = $this->ID;
			}

			foreach ( $core_fields as $field ) {
				$values[ $field ] = $submitted[ $field ];
			}

			/* Set the user's display name based on their name. */
			$display_name = $this->sanitize_display_name( $values );

			if ( $display_name ) {
				$values['display_name'] = $display_name;
			}

			/* Insert the user */
			if ( 0 == $this->ID ) {

				if ( ! isset( $values['user_pass'] ) || strlen( $values['user_pass'] ) == 0 ) {
					charitable_get_notices()->add_error( '<strong>ERROR:</strong> Password field is required.' );
					return false;
				}

				if ( ! isset( $values['user_login'] ) ) {
					$values['user_login'] = $values['user_email'];
				}

				/**
				 * `wp_insert_user` calls `sanitize_user` internally - make the
				 * same call here so `$values['user_login']` matches what is
				 * eventually saved to the database
				 */
				$values['user_login'] = sanitize_user( $values['user_login'], true );

				$user_id = wp_insert_user( $values );

				if ( is_wp_error( $user_id ) ) {
					charitable_get_notices()->add_errors_from_wp_error( $user_id );
					return false;
				}

				$this->init( self::get_data_by( 'id', $user_id ) );

				$signon = Charitable_User::signon( $values['user_login'], $values['user_pass'] );

				if ( is_wp_error( $signon ) ) {
					charitable_get_notices()->add_errors_from_wp_error( $signon );
					return false;
				}

				/**
				 * Do something after a user has been registered.
				 *
				 * @since 1.0.0
				 *
				 * @param int   $user_id The new user's ID.
				 * @param array $values  Values submitted to register user.
				 */
				do_action( 'charitable_after_insert_user', $user_id, $values );

			} else {
				$values['ID'] = $this->ID;
				$user_id      = wp_update_user( $values );
			}//end if

			/* If there was an error when inserting or updating the user, lodge the error. */
			if ( is_wp_error( $user_id ) ) {
				charitable_get_notices()->add_errors_from_wp_error( $user_id );
				return false;
			}

			/**
			 * Do something after a user's account has been updated or created.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $user_id The user's ID.
			 * @param array $values  Values submitted to save user.
			 */
			do_action( 'charitable_after_save_user', $user_id, $values );

			return $user_id;
		}

		/**
		 * Save the user's meta fields.
		 *
		 * @since  1.0.0
		 *
		 * @param  array $submitted The submitted values.
		 * @param  array $keys      The keys of fields that are to be updated.
		 * @return int Number of fields updated.
		 */
		public function update_user_meta( $submitted, $keys ) {
			/* Exclude the core keys */
			$meta_fields = array_diff( $keys, $this->get_core_keys() );
			$updated     = 0;

			foreach ( $meta_fields as $field ) {

				if ( isset( $submitted[ $field ] ) ) {
					$meta_key   = $this->map_key( $field );
					$meta_value = sanitize_meta( $meta_key, $submitted[ $field ], 'user' );

					update_user_meta( $this->ID, $meta_key, $meta_value );

					$updated++;
				}
			}

			return $updated;
		}

		/**
		 * Log a user is with their username and password.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $username The user's username.
		 * @param  string $password User password.
		 * @return WP_User|WP_Error|false WP_User on login, WP_Error on failure. False if feature is disabled.
		 */
		public static function signon( $username, $password ) {
			if ( ! apply_filters( 'charitable_auto_login_after_registration', true, $username ) ) {
				return false;
			}

			if ( is_user_logged_in() ) {
				return false;
			}

			$creds = array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => true,
			);

			return wp_signon( $creds, false );
		}

		/**
		 * Return a display name for the user.
		 *
		 * @since  1.5.2
		 *
		 * @param  array $values Submitted values.
		 * @return string|false String if we received a display_name
		 *                      or had a first name or last name.
		 */
		public function sanitize_display_name( $values ) {
			if ( array_key_exists( 'display_name', $values ) ) {
				return $values['display_name'];
			}

			$first_name = array_key_exists( 'first_name', $values ) ? $values['first_name'] : $this->first_name;
			$last_name  = array_key_exists( 'last_name', $values ) ? $values['last_name'] : $this->last_name;
			$display    = trim( sprintf( '%s %s', $first_name, $last_name ) );

			return strlen( $display ) ? $display : false;
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
		 * Given a key, returns the mapped key or itself if it is not mapped.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key The key to map.
		 * @return string
		 */
		public function map_key( $key ) {
			$mapped_keys = $this->get_mapped_keys();

			return array_key_exists( $key, $mapped_keys ) ? $mapped_keys[ $key ] : $key;
		}

		/**
		 * Return the array of core keys.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function get_core_keys() {
			if ( ! isset( $this->core_keys ) ) {
				$this->core_keys = charitable_get_user_core_keys();
			}

			return $this->core_keys;
		}
	}

endif;
