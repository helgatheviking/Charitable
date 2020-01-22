<?php
/**
 * Register and retrieve donation fields.
 *
 * @package   Charitable/Classes/Charitable_Donation_Field_Registry
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donation_Field_Registry' ) ) :

	/**
	 * Charitable_Donation_Field_Registry
	 *
	 * @since 1.5.0
	 */
	class Charitable_Donation_Field_Registry extends Charitable_Field_Registry implements Charitable_Field_Registry_Interface {

		/**
		 * Admin form fields.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		protected $admin_form_fields;

		/**
		 * Public donation form fields.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		protected $donation_form_fields;

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
			/**
			 * Filter the default donation sections.
			 *
			 * @since 1.6.0
			 *
			 * @param array $sections The full array of sections for all forms, including defaults.
			 */
			return apply_filters(
				'charitable_default_donation_sections',
				array(
					'defaults' => array(
						'public' => 'user',
						'admin'  => 'user',
					),
					'public'   => array(
						'user' => __( 'Your Details', 'charitable' ),
					),
					'admin'    => array(
						'user' => '',
						'meta' => '',
					),
				)
			);
		}

		/**
		 * Return the donation form fields.
		 *
		 * @since  1.5.0
		 * @since  1.6.0 Added $section parameter.
		 *
		 * @param  string $section Optional. If set, will only return the fields within this section.
		 * @return array
		 */
		public function get_donation_form_fields( $section = '' ) {
			if ( ! isset( $this->donation_form_fields ) ) {
				$this->donation_form_fields = array_filter( $this->fields, array( new Charitable_Field_Filter( 'donation_form' ), 'is_not_false' ) );
			}

			if ( empty( $section ) ) {
				return $this->donation_form_fields;
			}

			$fields = array();

			foreach ( $this->donation_form_fields as $key => $field ) {
				if ( $section != $field->donation_form['section'] ) {
					continue;
				}

				$fields[ $key ] = $field;
			}

			return $fields;
		}

		/**
		 * Return the donation form fields.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $section Optional. If set, this will only return fields within this section.
		 * @return array
		 */
		public function get_admin_form_fields( $section = '' ) {
			if ( ! isset( $this->admin_form_fields ) ) {
				$this->admin_form_fields = array_filter( $this->fields, array( new Charitable_Field_Filter( 'admin_form' ), 'is_not_false' ) );
			}

			if ( empty( $section ) ) {
				return $this->admin_form_fields;
			}

			$fields = array();

			foreach ( $this->admin_form_fields as $key => $field ) {
				if ( $section != $field->admin_form['section'] ) {
					continue;
				}

				$fields[ $key ] = $field;
			}

			return $fields;
		}

		/**
		 * Return the donation form fields.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_email_tag_fields() {
			return array_filter( $this->fields, array( new Charitable_Field_Filter( 'email_tag' ), 'is_not_false' ) );
		}

		/**
		 * Return the fields to be included in the export.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_export_fields() {
			return array_filter( $this->fields, array( new Charitable_Field_Filter( 'show_in_export' ), 'is_true' ) );
		}

		/**
		 * Return the fields to be included in the donation meta.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		public function get_meta_fields() {
			return array_filter( $this->fields, array( new Charitable_Field_Filter( 'show_in_meta' ), 'is_true' ) );
		}

		/**
		 * Return all fields for a particular data type.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $data_type The data type.
		 * @return Charitable_Donation_Field[]
		 */
		public function get_data_type_fields( $data_type ) {
			return array_filter( $this->fields, array( new Charitable_Field_Filter( 'data_type', $data_type ), 'is_equal_to' ) );
		}

		/**
		 * Set the default form section.
		 *
		 * @since  1.5.0
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
		 * @since  1.5.0
		 *
		 * @param  Charitable_Field_Interface $field Instance of `Charitable_Field_Interface`.
		 * @return boolean
		 */
		public function register_field( Charitable_Field_Interface $field ) {
			if ( ! is_a( $field, 'Charitable_Donation_Field' ) ) {
				return false;
			}

			$field->value_callback         = $this->get_field_value_callback( $field );
			$field->donation_form          = $this->get_field_donation_form( $field );
			$field->admin_form             = $this->get_field_admin_form( $field );
			$this->fields[ $field->field ] = $field;

			return true;
		}

		/**
		 * Return a callback for the field.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Donation_Field $field Instance of `Charitable_Donation_Field`.
		 * @return false|string|callable            Returns a callable function or false if none is set and
		 *                                          we don't have a default one for the data type.
		 */
		public function get_field_value_callback( $field ) {
			if ( isset( $field->value_callback ) ) {
				return $field->value_callback;
			}

			switch ( $field->data_type ) {
				case 'user':
					return 'charitable_get_donor_meta_value';

				case 'meta':
					return 'charitable_get_donation_meta_value';

				default:
					return false;
			}
		}

		/**
		 * Return a parsed array of settings for the field, or false if it should not appear
		 * in the donation form.
		 *
		 * @since  1.5.0
		 *
		 * @param  Charitable_Donation_Field $field Instance of `Charitable_Donation_Field`.
		 * @return array|false
		 */
		protected function get_field_donation_form( Charitable_Donation_Field $field ) {
			$settings = $field->donation_form;

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
		 * @since  1.5.0
		 *
		 * @param  Charitable_Donation_Field $field Instance of `Charitable_Donation_Field`.
		 * @return array
		 */
		protected function get_field_admin_form( Charitable_Donation_Field $field ) {
			$settings = $field->admin_form;

			if ( false === $settings ) {
				return $settings;
			}

			/* If the value is true, we use the same args as for the donation_form setting. */
			if ( true === $settings ) {
				return $field->donation_form;
			}

			if ( is_array( $field->donation_form ) ) {
				$settings = array_merge( $field->donation_form, $settings );
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
				'donation_form',
				'admin_form',
			);
		}
	}

endif;
