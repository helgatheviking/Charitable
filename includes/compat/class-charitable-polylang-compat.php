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
		 * Create class object.
		 *
		 * @since 1.6.42
		 */
		public function __construct() {
			add_action( 'wp', array( $this, 'load_late_translations' ) );

			/* Get Polylang translation of endpoints. */
			add_filter( 'charitable_permalink_profile_page', array( $this, 'get_profile_page_url' ), 10, 2 );
			add_filter( 'charitable_is_page_profile_page', array( $this, 'is_profile_page' ), 10, 2 );
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
		 * Get the profile page URL.
		 *
		 * @since  1.6.42
		 *
		 * @param  string $default The endpoint's URL.
		 * @param  array  $args    Mixed set of arguments.
		 * @return string
		 */
		public function get_profile_page_url( $default, $args ) {
			if ( empty( $default ) ) {
				return $default;
			}

			/* Prevent Polylang override. */
			if ( array_key_exists( 'polylang_override', $args ) && ! $args['polylang_override'] ) {
				return $default;
			}

			$pll_page = pll_get_post( charitable_get_option( 'profile_page', false ) );

			return get_permalink( $pll_page );
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
				return false;
			}

			$page = pll_get_post( charitable_get_option( 'profile_page', false ) );

			return $page && $page === $post->ID;
		}
	}

endif;
