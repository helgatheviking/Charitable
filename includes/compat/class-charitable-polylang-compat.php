<?php
/**
 * A class to resolve compatibility issues with Polylang.
 *
 * @package   Charitable/Classes/Charitable_Polylang_Compat
 * @author    Eric Daams
 * @copyright Copyright (c) 2019, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.42
 * @version   1.6.42
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Polylang_Compat' ) ) :

	/**
	 * Charitable_Polylang_Compat
	 *
	 * @since 1.6.42
	 */
	class Charitable_Polylang_Compat {

		/**
		 * Endpoints that can have Polylang variants.
		 *
		 * @since 1.6.42
		 *
		 * @var   array
		 */
		private $endpoints;

		/**
		 * Create class object.
		 *
		 * @since 1.6.42
		 */
		public function __construct() {
			/* Load Donation & Campaign field translations late */
			add_action( 'wp', array( $this, 'load_late_translations' ) );

			/* Handle Polylang translation of user dashboard menu */
			add_action( 'wp_update_nav_menu', array( $this, 'flush_menu_object_cache' ) );
			add_action( 'wp_update_nav_menu_item', array( $this, 'flush_menu_object_cache' ) );
			add_filter( 'transient_charitable_user_dashboard_objects', array( $this, 'get_user_dashboard_objects' ) );
			add_filter( 'pre_set_transient_charitable_user_dashboard_objects', array( $this, 'set_user_dashboard_objects' ) );

			/* Profile Page */
			add_filter( 'charitable_permalink_profile_page', array( $this, 'get_profile_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_profile_page', array( $this, 'is_profile_page' ), 10, 2 );

			/* Login Page */
			add_filter( 'charitable_permalink_login_page', array( $this, 'get_login_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_login_page', array( $this, 'is_login_page' ), 10, 2 );

			/* Registration Page */
			add_filter( 'charitable_permalink_registration_page', array( $this, 'get_registration_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_registration_page', array( $this, 'is_registration_page' ), 10, 2 );

			/* Donation Receipt Page */
			add_filter( 'charitable_permalink_donation_receipt_page', array( $this, 'get_donation_receipt_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_donation_receipt_page', array( $this, 'is_donation_receipt_page' ), 10, 2 );

			/* Terms & Conditions and Privacy Policy */
			add_filter( 'charitable_option_terms_conditions_page', array( $this, 'get_polylang_page_id' ) );
			add_filter( 'charitable_option_privacy_policy_page', array( $this, 'get_polylang_page_id' ) );

			/**
			 * Do something with this class instance.
			 *
			 * @since 1.6.42
			 *
			 * @param Charitable_Polylang_Compat $helper Polylang compatibility class instance.
			 */
			do_action( 'charitable_polylang_compat', $this );
		}

		/**
		 * When Polylang picks up the language from the content, it translates
		 * content late, on the 'wp' hook. This is after Charitable's donation
		 * and campaign fields are loaded, which results in their labels, options
		 * and help text not being translated.
		 *
		 * To overcome this, with pick up the fields on the 'wp' hook and loop
		 * over them all to ensure Polylang translates them.
		 *
		 * @see https://github.com/polylang/polylang/issues/507#issuecomment-634640027
		 *
		 * @since  1.6.42
		 *
		 * @return void
		 */
		public function load_late_translations() {
			$options = get_option( 'polylang' );

			/* We only have to do this if the language is picked up from the content. */
			if ( 0 !== $options['force_lang'] ) {
				return;
			}

			$field_apis = [
				[
					'fields' => charitable()->donation_fields(),
					'forms'  => [ 'donation_form', 'admin_form' ],
				],
				[
					'fields' => charitable()->campaign_fields(),
					'forms'  => [ 'campaign_form', 'admin_form' ],
				]
			];

			$translateable_form_fields = [ 'label', 'placeholder' ];

			foreach ( $field_apis as $api ) {
				$fields = $api['fields'];

				foreach ( $fields->get_fields() as $field ) {

					/* Update the field label. */
					$field->label = pll__( $field->label );

					foreach ( $api['forms'] as $form ) {
						$form_settings = $field->$form;

						if ( ! is_array( $form_settings ) ) {
							continue;
						}

						/* Translate form label and placeholder. */
						foreach ( $translateable_form_fields as $form_field ) {
							if ( array_key_exists( $form_field, $form_settings ) ) {
								$field->set( $form, $form_field, pll__( $form_settings[ $form_field ] ) );
							}
						}

						/* Translate options */
						if ( array_key_exists( 'options', $form_settings ) && is_array( $form_settings['options'] ) ) {
							$options = $form_settings['options'];

							foreach ( $options as $key => $value ) {
								$options[ $key ] = pll__( $value );
							}

							if ( in_array( $field->field, [ 'country', 'state' ] ) ) {
								asort( $options );
							}

							$field->set( $form, 'options', $options );
						}
					}
				}
			}
		}

		/**
		 * Returns the user dashboard menus, including any Polylang
		 * translation versions.
		 *
		 * @since  1.6.42
		 *
		 * @return array
		 */
		public function get_user_dashboard_menus() {
			$dashboard_menus = array();

			/* Get all nav menu locations. */
			$locations = get_nav_menu_locations();

			foreach ( $locations as $location => $assigned_menu ) {
				if ( 'charitable-dashboard' === substr( $location, 0, 20 ) ) {
					$dashboard_menus[] = wp_get_nav_menu_object( $assigned_menu );
				}
			}

			return $dashboard_menus;
		}

		/**
		 * Flush the object cache when a menu assigned to the user dashboard
		 * location (in any language) is edited.
		 *
		 * @since  1.6.42
		 *
		 * @param  int $menu_id The menu id.
		 * @return void
		 */
		public function flush_menu_object_cache( $menu_id ) {
			$dashboard_menus = $this->get_user_dashboard_menus();

			if ( empty( $dashboard_menus ) ) {
				delete_transient( 'charitable_user_dashboard_objects' );
			}

			if ( in_array( $menu_id, wp_list_pluck( $dashboard_menus, 'term_id' ) ) ) {
				delete_transient( 'charitable_user_dashboard_objects' );
			}
		}

		/**
		 * Get the user dashboard objects for the current language.
		 *
		 * @since  1.6.42
		 *
		 * @return array
		 */
		public function get_user_dashboard_objects( $objects ) {
			$language = pll_current_language();

			if ( false === $objects ) {
				return false;
			}

			if ( ! array_key_exists( $language, $objects ) ) {
				return false;
			}

			return $objects[ $language ];
		}

		/**
		 * When saving the user dashboard objects, include any objects in Polylang
		 * locations of the user dashboard menu.
		 *
		 * @since  1.6.42
		 *
		 * @return array
		 */
		public function set_user_dashboard_objects( $menu_items ) {
			$language = pll_current_language();

			/* Temporarily switch off our filter. */
			remove_filter( 'transient_charitable_user_dashboard_objects', array( $this, 'get_user_dashboard_objects' ) );

			$all_menu_items = get_transient( 'charitable_user_dashboard_objects' );

			if ( ! is_array( $all_menu_items ) ) {
				$all_menu_items = array();
			}

			if ( ! array_key_exists( $language, $all_menu_items ) ) {
				$all_menu_items[ $language ] = $menu_items;
			}

			/* Temporarily switch off our filter. */
			add_filter( 'transient_charitable_user_dashboard_objects', array( $this, 'get_user_dashboard_objects' ) );

			return $all_menu_items;
		}

		/**
		 * Get the current language version of the specified endpoint,
		 * or return the default.
		 *
		 * @since  1.6.42
		 *
		 * @param  string $endpoint_id The endpoint ID.
		 * @param  string $default     The endpoint's URL.
		 * @param  array  $args        Mixed set of arguments.
		 * @return string
		 */
		public function get_polylang_page_url( $page_option, $default, $args ) {
			if ( empty( $default ) ) {
				return $default;
			}

			/* Prevent Polylang override. */
			if ( array_key_exists( 'polylang_override', $args ) && ! $args['polylang_override'] ) {
				return $default;
			}

			$page_id = charitable_get_option( $page_option, false );

			if ( in_array( $page_id, array( 'wp', 'auto' ) ) ) {
				return $default;
			}

			return get_permalink( pll_get_post( $page_id ) );
		}

		/**
		 * Checks whether this is the specified page.
		 *
		 * @since  1.6.42
		 *
		 * @param  string  $page_option The option key used to record the page ID.
		 * @param  boolean $is_page     Whether we are currently on the page.
		 * @param  array   $args        Mixed arguments.
		 * @return string
		 */
		public function is_polylang_page( $page_option, $is_page, $args ) {
			/* We've already determined it's the current page. */
			if ( $is_page ) {
				return $is_page;
			}

			/* Prevent Polylang override. */
			if ( array_key_exists( 'polylang_override', $args ) && ! $args['polylang_override'] ) {
				return $is_page;
			}

			global $post, $wp_query;

			if ( is_null( $post ) || ! $wp_query->is_main_query() ) {
				return $is_page;
			}

			$page_id = charitable_get_option( $page_option, false );

			if ( in_array( $page_id, array( 'wp', 'auto' ) ) ) {
				return $is_page;
			}

			$pll_page = pll_get_post( $page_id );

			return $pll_page && $pll_page === $post->ID;
		}

		/**
		 * Get the profile page URL.
		 *
		 * @since  1.6.42
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_profile_page_url( $default, $args ) {
			return $this->get_polylang_page_url( 'profile_page', $default, $args );
		}

		/**
		 * Check whether we are currently on the profile page.
		 *
		 * @since  1.6.42
		 *
		 * @param  boolean $is_page Whether we are currently on the profile page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_profile_page( $is_page, $args ) {
			return $this->is_polylang_page( 'profile_page', $is_page, $args );
		}

		/**
		 * Get the login page URL.
		 *
		 * @since  1.6.42
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_login_page_url( $default, $args ) {
			return $this->get_polylang_page_url( 'login_page', $default, $args );
		}

		/**
		 * Check whether we are currently on the login page.
		 *
		 * @since  1.6.42
		 *
		 * @param  boolean $is_page Whether we are currently on the login page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_login_page( $is_page, $args ) {
			return $this->is_polylang_page( 'login_page', $is_page, $args );
		}

		/**
		 * Get the registration page URL.
		 *
		 * @since  1.6.42
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_registration_page_url( $default, $args ) {
			return $this->get_polylang_page_url( 'registration_page', $default, $args );
		}

		/**
		 * Check whether we are currently on the registration page.
		 *
		 * @since  1.6.42
		 *
		 * @param  boolean $is_page Whether we are currently on the registration page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_registration_page( $is_page, $args ) {
			return $this->is_polylang_page( 'registration_page', $is_page, $args );
		}

		/**
		 * Get the donation receipt page URL.
		 *
		 * @since  1.6.42
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_donation_receipt_page_url( $default, $args ) {
			$pll_url = $this->get_polylang_page_url( 'donation_receipt_page', $default, $args );

			if ( $pll_url !== $default ) {
				$donation_id = isset( $args['donation_id'] ) ? $args['donation_id'] : get_the_ID();
				$pll_url     = esc_url_raw( add_query_arg( array( 'donation_id' => $donation_id ), $pll_url ) );
			}

			return $pll_url;
		}

		/**
		 * Check whether we are currently on the donation receipt page.
		 *
		 * @since  1.6.42
		 *
		 * @param  boolean $is_page Whether we are currently on the donation_receipt page.
		 * @param  array   $args    Mixed arguments.
		 * @return boolean
		 */
		public function is_donation_receipt_page( $is_page, $args ) {
			return $this->is_polylang_page( 'donation_receipt_page', $is_page, $args );
		}

		/**
		 * Get the page ID of a specific page, returning the Polylang
		 * translation if applicable.
		 *
		 * @since  1.6.42
		 *
		 * @param  int|string $page_id The set page id.
		 * @return int|string
		 */
		public function get_polylang_page_id( $page_id ) {
			if ( empty( $page_id ) || 0 == $page_id ) {
				return $page_id;
			}

			return pll_get_post( $page_id );
		}
	}

endif;
