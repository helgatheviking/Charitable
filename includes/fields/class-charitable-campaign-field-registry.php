<?php
/**
 * Register and retrieve donation fields.
 *
 * @package   Charitable/Classes/Charitable_Campaign_Field_Registry
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

if ( ! class_exists( 'Charitable_Campaign_Field_Registry' ) ) :

	/**
	 * Charitable_Campaign_Field_Registry
	 *
	 * @since 1.6.0
	 */
	class Charitable_Campaign_Field_Registry extends Charitable_Field_Registry implements Charitable_Field_Registry_Interface {

		/**
		 * Admin form fields.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		protected $admin_form_fields;

		/**
		 * Campaign form fields.
		 *
		 * @since 1.6.25
		 *
		 * @var   array
		 */
		protected $campaign_form_fields;

		/**
		 * Instantiate registry.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function __construct() {
			parent::__construct();

			$this->sections = $this->get_core_sections();
		}

		/**
		 * Returns the core sections.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		private function get_core_sections() {
			return apply_filters(
				'charitable_default_campaign_sections',
				array(
					'defaults' => array(
						'admin' => 'campaign-extended-settings',
					),
					'admin'    => array(
						'campaign-donation-options'     => __( 'Donation Options', 'charitable' ),
						'campaign-extended-description' => __( 'Extended Description', 'charitable' ),
						'campaign-creator'              => __( 'Campaign Creator', 'charitable' ),
						'campaign-extended-settings'    => __( 'Extended Settings', 'charitable' ),
					),
				)
			);
		}

		/**
		 * Return the admin form fields.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $section The section of fields to get.
		 * @return array
		 */
		public function get_admin_form_fields( $section = '' ) {
			if ( ! isset( $this->admin_form_fields ) ) {
				$this->admin_form_fields = array_filter( $this->fields, array( new Charitable_Field_Filter( 'admin_form' ), 'is_not_false' ) );
			}

			if ( empty( $section ) ) {
				return $this->admin_form_fields;
			}

			return $this->get_form_fields_in_section( $section, $this->admin_form_fields, 'admin_form' );
		}

		/**
		 * Return the front-end campaign form fields.
		 *
		 * @since  1.6.25
		 *
		 * @param  string $section The section of fields to get within the form.
		 * @return array
		 */
		public function get_campaign_form_fields( $section = '' ) {
			if ( ! isset( $this->campaign_form_fields ) ) {
				$this->campaign_form_fields = array_filter( $this->fields, array( new Charitable_Field_Filter( 'campaign_form' ), 'is_not_false' ) );
			}

			if ( empty( $section ) ) {
				return $this->campaign_form_fields;
			}

			return $this->get_form_fields_in_section( $section, $this->campaign_form_fields, 'campaign_form' );
		}

		/**
		 * Return the email tag fields.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_email_tag_fields() {
			return array_filter( $this->fields, array( new Charitable_Field_Filter( 'email_tag' ), 'is_not_false' ) );
		}

		/**
		 * Return the fields to be included in the export.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_export_fields() {
			return array_filter( $this->fields, array( new Charitable_Field_Filter( 'show_in_export' ), 'is_true' ) );
		}

		/**
		 * Return the sanitized meta keys for a set of fields.
		 *
		 * @since  1.6.0
		 *
		 * @param  array   $fields    The fields to return meta keys for.
		 * @param  boolean $meta_only Whether to only return keys for meta fields. Core fields will be skipped.
		 * @return array
		 */
		public function get_sanitized_keys( $fields, $meta_only = true ) {
			$keys = array();

			foreach ( $fields as $key => $field ) {
				if ( ! $meta_only && 'core' == $field->data_type ) {
					$keys[] = $key;
				} elseif ( 'meta' == $field->data_type ) {
					$keys[] = '_campaign_' . $key;
				}
			}

			return $keys;
		}

		/**
		 * Set the default form section.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $section Section to register.
		 * @param  string $form    Which form we're registering the section in.
		 * @return void
		 */
		public function set_default_section( $section, $form = 'public' ) {
			$this->sections['defaults'][ $form ] = $section;
		}

		/**
		 * Register a field.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Field_Interface $field Instance of `Charitable_Field_Interface`.
		 * @return boolean
		 */
		public function register_field( Charitable_Field_Interface $field ) {
			if ( ! is_a( $field, 'Charitable_Campaign_Field' ) ) {
				return false;
			}

			$field->value_callback         = $this->get_field_value_callback( $field );
			$field->admin_form             = $this->get_field_admin_form( $field );
			$field->campaign_form          = $this->get_field_campaign_form( $field );
			$this->fields[ $field->field ] = $field;

			return true;
		}

		/**
		 * Return a callback for the field.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Campaign_Field $field Instance of `Charitable_Campaign_Field`.
		 * @return false|string|callable            Returns a callable function or false if none is set and
		 *                                          we don't have a default one for the data type.
		 */
		public function get_field_value_callback( $field ) {
			return isset( $field->value_callback ) && is_callable( $field->value_callback ) ? $field->value_callback : false;
		}

		/**
		 * Return a parsed array of settings for the field, or false if it should not appear
		 * in the admin form.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Campaign_Field $field Instance of `Charitable_Campaign_Field`.
		 * @return array|false
		 */
		protected function get_field_admin_form( Charitable_Campaign_Field $field ) {
			$settings = $field->admin_form;

			if ( false === $settings ) {
				return $settings;
			}

			if ( ! array_key_exists( 'section', $settings ) ) {
				$settings['section'] = $this->get_default_section( 'public' );
			}

			return $this->parse_form_settings( $settings, $field );
		}

		/**
		 * Return a parsed array of settings for the field, or false if it should not appear
		 * in the donation form.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Campaign_Field $field Instance of `Charitable_Campaign_Field`.
		 * @return array
		 */
		protected function get_field_campaign_form( Charitable_Campaign_Field $field ) {
			$settings = $field->campaign_form;

			if ( false === $settings ) {
				return $settings;
			}

			/* If the value is true, we use the same args as for the campaign_form setting. */
			if ( true === $settings ) {
				return $field->campaign_form;
			}

			if ( is_array( $field->campaign_form ) ) {
				$settings = array_merge( $field->campaign_form, $settings );
			}

			if ( ! array_key_exists( 'section', $settings ) ) {
				$settings['section'] = $this->get_default_section( 'admin' );
			}

			return $this->parse_form_settings( $settings, $field );
		}

		/**
		 * Return an array containing the keys of the form properties.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		protected function set_forms() {
			return array(
				'admin_form',
				'campaign_form',
			);
		}

		/**
		 * Return the Ambassadors form fields.
		 *
		 * @deprecated 1.9.0
		 *
		 * @since  1.6.0
		 * @since  1.6.25 Deprecated. Use Charitable_Campaign_Field_Registry::get_campaign_form_fields() instead.
		 *
		 * @param  string $section The section of fields to get within the form.
		 * @return array
		 */
		public function get_ambassadors_form_fields( $section = '' ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.6.25',
				'Charitable_Campaign_Field_Registry::get_campaign_form_fields()'
			);

			return $this->get_campaign_form_fields( $section );
		}

		/**
		 * Return a parsed array of settings for the field, or false if it should not appear
		 * in the donation form.
		 *
		 * @deprecated 1.9.0
		 *
		 * @since  1.6.0
		 * @since  1.6.25 Deprecated. Use Charitable_Campaign_Field_Registry::get_campaign_form_fields() instead.
		 *
		 * @param  Charitable_Campaign_Field $field Instance of `Charitable_Campaign_Field`.
		 * @return array
		 */
		protected function get_field_ambadssadors_form( Charitable_Campaign_Field $field ) {
			charitable_get_deprecated()->deprecated_function(
				__METHOD__,
				'1.6.25',
				'Charitable_Campaign_Field_Registry::get_field_campaign_form()'
			);

			return $this->get_field_campaign_form( $field );
		}
	}

endif;
