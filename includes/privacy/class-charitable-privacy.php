<?php
/**
 * A class providing privacy tools for Charitable.
 *
 * @package   Charitable/Classes/Charitable_Privacy
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

if ( ! class_exists( 'Charitable_Privacy' ) ) :

	/**
	 * Charitable_Privacy
	 *
	 * @since 1.6.0
	 */
	class Charitable_Privacy {

		/**
		 * User donation fields.
		 *
		 * @since 1.6.0
		 *
		 * @var   Charitable_Donation_Field[]
		 */
		protected $user_donation_fields;

		/**
		 * Fields that should be retained during the Data Retention Period.
		 *
		 * @since 1.6.0
		 *
		 * @var   Charitable_Donation_Field[]
		 */
		protected $data_retention_fields;

		/**
		 * Set up class instance.
		 *
		 * @since  1.6.0
		 */
		public function __construct() {
			if ( ! function_exists( 'wp_privacy_anonymize_data' ) ) {
				return;
			}

			/* Register our data exporter. */
			add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );

			/* Register our data eraser. */
			add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );

			/* Add our privacy policy content. */
			add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		}

		/**
		 * Return the data to be retained for donations within the Data Retention Period.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_data_retention_fields() {
			if ( ! isset( $this->data_retention_fields ) ) {
				$this->data_retention_fields = charitable_get_option( 'data_retention_fields', array_keys( $this->get_user_donation_fields() ) );
			}

			return $this->data_retention_fields;
		}

		/**
		 * Return the Data Retention Period.
		 *
		 * @since  1.6.0
		 *
		 * @return int|string
		 */
		public function get_data_retention_period() {
			return charitable_get_option( 'minimum_data_retention_period', 2 );
		}

		/**
		 * Get the user donation fields.
		 *
		 * @since  1.6.0
		 *
		 * @return Charitable_Donation_Field[]
		 */
		public function get_user_donation_fields() {
			if ( ! isset( $this->user_donation_fields ) ) {
				$this->user_donation_fields = charitable()->donation_fields()->get_data_type_fields( 'user' );
			}

			return $this->user_donation_fields;
		}

		/**
		 * Return all erasable fields for a particular donation.
		 *
		 * @since  1.6.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return Charitable_Donation_Field[]
		 */
		public function get_erasable_fields_for_donation( $donation_id ) {
			if ( ! $this->is_donation_in_data_retention_period( $donation_id ) ) {
				return $this->get_user_donation_fields();
			}

			return $this->get_erasable_fields_in_data_retention_period();
		}

		/**
		 * Checks whether a donation is in the Data Retention Period.
		 *
		 * @since  1.6.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return boolean
		 */
		public function is_donation_in_data_retention_period( $donation_id ) {
			$period = $this->get_data_retention_period();

			if ( '0' == (string) $period ) {
				return false;
			}

			if ( 'endless' == $period ) {
				return true;
			}

			$diff = (int) abs( time() - strtotime( get_post_field( 'post_date_gmt', $donation_id, 'raw' ) ) );

			return floor( $diff / YEAR_IN_SECONDS ) < $period;
		}

		/**
		 * Register the data exporter.
		 *
		 * @since  1.6.0
		 *
		 * @param  array $exporters The list of exporters.
		 * @return array
		 */
		public function register_exporter( $exporters ) {
			return array_merge( $exporters, array(
				array(
					'exporter_friendly_name' => __( 'Charitable Donor Data', 'charitable' ),
					'callback'               => array( $this, 'export_user_data' ),
				),
			) );
		}

		/**
		 * Register the data eraser.
		 *
		 * @since  1.6.0
		 *
		 * @param  array $erasers The list of registered data erasers.
		 * @return array
		 */
		public function register_eraser( $erasers ) {
			return array_merge( $erasers, array(
				array(
					'eraser_friendly_name' => __( 'Charitable Donor Data Eraser', 'charitable' ),
					'callback'             => array( $this, 'erase_user_data' ),
				),
			) );
		}

		/**
		 * Add privacy policy content for Charitable.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function add_privacy_policy_content() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return;
			}

			/**
			 * Filter the Charitable privacy policy content.
			 *
			 * @since 1.6.0
			 *
			 * @param string $content The privacy policy content.
			 */
			$content = wp_kses_post( apply_filters( 'charitable_privacy_policy_content', wpautop( __( '
We collect information about you during the donation process on our website. This information may include, but is not limited to, your name, address, phone number, credit card/payment details and any other details that might be requested from you for the purpose of processing your donations.

Handling this data allows us to:

- Send you important account/donation/service information.
- Respond to your queries, refund requests or complaints.
- Process payments and prevent fraudulent transactions. We do this on the basis of our legitimate organizational interests.
- Set up and administer your account, provide technical and donor support, and to verify your identity.

Additionally, we may also collect the following information:

- Your donor comments if you choose to leave them on our website.
- Cookies which are essential to keep track of your donation history whilst your session is active. This allows you to access your donation receipt without being a registered, logged in user.
- Account email/password to allow access to your account, if you have one.
- If you choose to create an account with us, your name, address, email and phone number, which will be used to populate the donation form for future donations.
', 'charitable' ) ) ) );

			wp_add_privacy_policy_content( 'Charitable', $content );
		}

		/**
		 * Return a user's data.
		 *
		 * We export the following pieces of data:
		 *
		 * 1. Registered donor meta. (*_usermeta)
		 * 2. Donor profile records. (*_charitable_donors table)
		 * 3. Donation donor meta. (*_postmeta)
		 *
		 * @since  1.6.0
		 *
		 * @param  string $email The user's email address.
		 * @param  int    $page  The page of data to retrieve.
		 * @return array
		 */
		public function export_user_data( $email, $page = 1 ) {
			$export_items = array();

			if ( 1 === $page ) {
				/* 1. Get registered donor meta. */
				$user = get_user_by( 'email', $email );

				if ( $user instanceof WP_User ) {
					$export_items[] = $this->get_registered_donor_data( $user );
				}
			}

			/* 2. Get donor profile. */
			$profiles = charitable_get_table( 'donors' )->get_personal_data( $email );

			if ( is_array( $profiles ) && 1 === $page ) {
				$export_items = array_merge(
					$export_items,
					array_map( array( $this, 'get_donor_profile_data' ), $profiles )
				);
			}

			/* If there are no donor profiles, there are no donations; return whatever we have. */
			if ( empty( $profiles ) ) {
				return array(
					'data' => $export_items,
					'done' => true,
				);
			}

			/* 3. Donation donor meta */
			$user_donation_fields = $this->get_user_donation_fields();

			if ( ! empty( $user_donation_fields ) ) {
				$donor_id     = wp_list_pluck( $profiles, 'donor_id' );
				$donations    = charitable_get_table( 'campaign_donations' )->get_distinct_ids( 'donation_id', $donor_id, 'donor_id' );
				$export_items = array_merge(
					$export_items,
					array_map( array( $this, 'get_personal_donation_meta_data' ), $donations )
				);
			}

			return array(
				'data' => $export_items,
				'done' => true,
			);
		}

		/**
		 * Remove a user's personal data.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $email The user's email address.
		 * @param  int    $page  The page of data to retrieve.
		 * @return array
		 */
		public function erase_user_data( $email, $page = 1 ) {
			$this->retained = false;
			$this->removed  = false;

			/* 1. Remove registered donor meta. */
			$user = get_user_by( 'email', $email );

			if ( $user instanceof WP_User ) {
				$this->erase_registered_donor_personal_data( $user );
			}

			/* 2. Remove donor profile data. */
			$profiles = charitable_get_table( 'donors' )->get_personal_data( $email );

			if ( is_array( $profiles ) ) {
				charitable_get_table( 'donors' )->erase_donor_data( wp_list_pluck( $profiles, 'donor_id' ) );

				$this->removed = true;
			}

			/* If there are no donor profiles, there are no donations. */
			if ( empty( $profiles ) ) {
				return array(
					'items_removed'  => $this->removed,
					'items_retained' => $this->retained,
					'messages'       => array(),
					'done'           => true,
				);
			}

			/* 3. Donation donor meta */
			$user_donation_fields = $this->get_user_donation_fields();

			if ( ! empty( $user_donation_fields ) ) {
				$donations = $this->get_donations_from_profiles( $profiles );

				array_walk( $donations, array( $this, 'erase_donation_personal_data' ) );
			}

			return array(
				'items_removed'  => $this->removed,
				'items_retained' => $this->retained,
				'messages'       => array(),
				'done'           => true,
			);
		}

		/**
		 * Return a registered donor's data.
		 *
		 * @since  1.6.0
		 *
		 * @param  WP_User $user An instance of `WP_User`.
		 * @return array
		 */
		public function get_registered_donor_data( $user ) {
			$data    = array();
			$form    = new Charitable_Profile_Form;
			$methods = array(
				'get_user_fields',
				'get_address_fields',
				'get_social_fields',
			);
			$key_map = charitable_get_user_mapped_keys();

			foreach ( $methods as $method ) {
				$fields = call_user_func( array( $form, $method ) );

				if ( ! is_array( $fields ) ) {
					continue;
				}

				foreach ( $fields as $key => $field ) {
					$key = array_key_exists( $key, $key_map ) ? $key_map[ $key ] : $key;

					if ( ! $user->has_prop( $key ) ) {
						continue;
					}

					$data[] = array(
						'name'  => array_key_exists( 'label', $field ) ? $field['label'] : ucfirst( str_replace( '_', ' ', $key ) ),
						'value' => $user->{$key},
					);
				}
			}

			/**
			 * Filter the personal donor meta data for a registered donor.
			 *
			 * @since 1.6.0
			 *
			 * @param array   $data Set of personal donor data.
			 * @param WP_User $user An instance of `WP_User`.
			 */
			$data = apply_filters( 'charitable_privacy_export_personal_registered_donor_data', $data, $user );

			return array(
				'item_id'     => 'user',
				'group_id'    => 'charitable_users',
				'group_label' => __( 'Donor Meta', 'charitable' ),
				'data'        => $data,
			);
		}

		/**
		 * Returns donor profile data for export.
		 *
		 * @since  1.6.0
		 *
		 * @param  object $profile The profile record.
		 * @return array
		 */
		protected function get_donor_profile_data( $profile ) {
			/**
			 * Filter the personal donor record data.
			 *
			 * @since 1.6.0
			 *
			 * @param array  $data    Set of personal donor data.
			 * @param object $profile The profile record.
			 */
			$data = apply_filters( 'charitable_privacy_export_personal_donor_profile_data', array(
				array(
					'name'  => __( 'First Name', 'charitable' ),
					'value' => $profile->first_name,
				),
				array(
					'name'  => __( 'Last Name', 'charitable' ),
					'value' => $profile->last_name,
				),
				array(
					'name'  => __( 'Email', 'charitable' ),
					'value' => $profile->email,
				),
			) );

			return array(
				'item_id'     => 'donor-' . $profile->donor_id,
				'group_id'    => 'charitable_donors',
				'group_label' => __( 'Donor Profiles', 'charitable' ),
				'data'        => $data,
			);
		}

		/**
		 * Return the donor meta stored with a donor's donation records.
		 *
		 * @since  1.6.0
		 *
		 * @param  int $donation_id A donation ID.
		 * @return array
		 */
		protected function get_personal_donation_meta_data( $donation_id ) {
			$meta = get_post_meta( $donation_id, 'donor', true );

			if ( ! is_array( $meta ) ) {
				return array();
			}

			$data = array();

			foreach ( $this->get_user_donation_fields() as $field_id => $field ) {
				if ( ! array_key_exists( $field_id, $meta ) ) {
					continue;
				}

				$data[] = array(
					'name'  => $field->label,
					'value' => $meta[ $field_id ],
				);
			}

			/**
			 * Filter the personal donation meta data for a particular donation.
			 *
			 * @since 1.6.0
			 *
			 * @param array $data        Set of personal donation meta data.
			 * @param array $data        The donor meta array.
			 * @param int   $donation_id The donation ID.
			 */
			$data = apply_filters( 'charitable_privacy_export_personal_donation_meta_data', $data, $meta, $donation_id );

			return array(
				'item_id'     => 'donation-' . $donation_id,
				'group_id'    => 'charitable_donations',
				'group_label' => __( 'Donation Meta', 'charitable' ),
				'data'        => $data,
			);
		}

		/**
		 * Remove personal data for a registered donor.
		 *
		 * @since  1.6.0
		 *
		 * @param  WP_User $user The instance of `WP_User`.
		 * @return void
		 */
		protected function erase_registered_donor_personal_data( WP_User $user ) {
			$form    = new Charitable_Profile_Form;
			$methods = array(
				'get_user_fields',
				'get_address_fields',
				'get_social_fields',
			);
			$key_map = charitable_get_user_mapped_keys();

			foreach ( $methods as $method ) {
				$fields = call_user_func( array( $form, $method ) );

				if ( ! is_array( $fields ) ) {
					continue;
				}

				foreach ( $fields as $key => $field ) {
					$key = array_key_exists( $key, $key_map ) ? $key_map[ $key ] : $key;

					if ( ! $user->has_prop( $key ) ) {
						continue;
					}

					/* Get the data type we will use for erasing this particular data field. */
					$data_type = $this->get_field_data_type( (object) $field );

					/**
					 * Filter the placeholder data that will replace the personal data.
					 *
					 * @since 1.6.0
					 *
					 * @param string $data  The anonymous data.
					 * @param mixed  $value The current value of the field.
					 * @param string $key   The key of the field.
					 * @param array  $field The field definition.
					 */
					$data = apply_filters( 'charitable_privacy_erasure_registered_user_data_prop_value', wp_privacy_anonymize_data( $data_type, $user->{$key} ), $user->{$key}, $key, $field );

					update_user_meta( $user->ID, $key, $data );

					$this->removed = true;
				}
			}
		}

		/**
		 * Remove personal data from a single donation.
		 *
		 * @since  1.6.0
		 *
		 * @param  int $donation_id The donation ID.
		 * @return void
		 */
		protected function erase_donation_personal_data( $donation_id ) {
			$meta = get_post_meta( $donation_id, 'donor', true );

			foreach ( $this->get_erasable_fields_for_donation( $donation_id ) as $field_id => $field ) {
				if ( ! array_key_exists( $field_id, $meta ) ) {
					continue;
				}

				/* Get the data type we will use for erasing this particular data field. */
				$data_type = $this->get_field_data_type( $field );
				$replace   = empty( $meta[ $field_id ] ) ? '' : wp_privacy_anonymize_data( $data_type, $meta[ $field_id ] );

				/**
				 * Filter the placeholder data that will replace the personal data.
				 *
				 * @since 1.6.0
				 *
				 * @param string                    $data  The anonymous data.
				 * @param mixed                     $value The current value of the field.
				 * @param string                    $key   The key of the field.
				 * @param Charitable_Donation_Field $field The field definition.
				 */
				$meta[ $field_id ] = apply_filters( 'charitable_privacy_erasure_donation_donor_data_prop_value', $replace, $meta[ $field_id ], $field_id, $field );
			}

			update_post_meta( $donation_id, 'donor', $meta );
			update_post_meta( $donation_id, 'data_erased', current_time( 'mysql', 0 ) );

			/* Change the post_title since that otherwise has the donor's name. */
			wp_update_post( array(
				'ID'         => $donation_id,
				'post_title' => sprintf(
					/* translators: %s: campaign names */
					__( 'Anonymous &ndash; %s', 'charitable' ),
					charitable_get_donation( $donation_id )->get_campaigns_donated_to()
				),
			) );

			$log = new Charitable_Donation_Log( $donation_id );
			$log->add( __( 'Personal data erased.', 'charitable' ) );
		}

		/**
		 * Checks whether there are any user donation fields that can be erased
		 * for donations within the data retention period.
		 *
		 * @since  1.6.0
		 *
		 * @return boolean
		 */
		protected function has_erasable_fields_within_data_retention_period() {
			return 0 < count( array_unique(
				array_keys( $this->get_user_donation_fields() ),
				$this->get_data_retention_fields()
			) );
		}

		/**
		 * Returns the erasable fields within the Data Retention Period.
		 *
		 * @since  1.6.0
		 *
		 * @return Charitable_Donation_Field[]
		 */
		protected function get_erasable_fields_in_data_retention_period() {
			return array_diff_key(
				$this->get_user_donation_fields(),
				array_flip( $this->get_data_retention_fields() )
			);
		}

		/**
		 * Return the donation IDs for one or more profiles.
		 *
		 * @since  1.6.0
		 *
		 * @param  object[] $profiles The donor profiles.
		 * @return int[]
		 */
		protected function get_donations_from_profiles( $profiles ) {
			$donor_id = wp_list_pluck( $profiles, 'donor_id' );

			return charitable_get_table( 'campaign_donations' )->get_distinct_ids( 'donation_id', $donor_id, 'donor_id' );
		}

		/**
		 * Return the data type for a field.
		 *
		 * @since  1.6.0
		 *
		 * @param  object $field The field attributes. This may be a Charitable_Donation_Field
		 *                       instance, or a simple object cast from an array.
		 * @return string
		 */
		protected function get_field_data_type( $field ) {
			if ( isset( $field->type ) ) {
				$type = $field->type;
			} else {
				$type = is_array( $field->donation_form ) ? $field->donation_form['type'] : $field->admin_form['type'];
			}

			switch ( $type ) {
				case 'select':
				case 'multi-checkbox':
					$data_type = '';
					break;

				case 'textarea':
				case 'editor':
					$data_type = 'longtext';
					break;

				default:
					$data_type = $type;
			}

			/**
			 * Filter the data type to use for a field to be erased.
			 *
			 * @since 1.6.0
			 *
			 * @param string $data_type The data type.
			 * @param object $field     The field attributes. This may be a Charitable_Donation_Field
			 *                          instance, or a simple object cast from an array.
			 */
			return apply_filters( 'charitable_privacy_erasure_field_data_type', $data_type, $field );
		}
	}

endif;
