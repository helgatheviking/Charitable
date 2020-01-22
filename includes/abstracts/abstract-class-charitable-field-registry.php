<?php
/**
 * Base Charitable_Field_Registry model.
 *
 * @package   Charitable/Classes/Charitable_Field_Registry
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

if ( ! class_exists( 'Charitable_Field_Registry' ) ) :

	/**
	 * Charitable_Field
	 *
	 * @since 1.6.0
	 */
	abstract class Charitable_Field_Registry implements Charitable_Field_Registry_Interface {

		/**
		 * Registered fields.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		protected $fields;

		/**
		 * Form sections.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		protected $sections;

		/**
		 * The keys of the form properties.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		protected $forms;

		/**
		 * Create class object.
		 *
		 * @since 1.6.0
		 */
		public function __construct() {
			$this->fields   = array();
			$this->sections = array();
			$this->forms    = $this->set_forms();

			add_action( 'init', array( $this, 'order_fields' ), 9999 );
		}

		/**
		 * Register a new section.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $form    The form that the section is registered to.
		 * @param  string $section The key of the section.
		 * @param  string $label   Optional. The section label.
		 * @return void
		 */
		public function register_section( $form, $section, $label = '' ) {
			$this->sections[ $form ][ $section ] = $label;
		}

		/**
		 * Return the sections in a particular form.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $form The form to retrieve sections for.
		 * @return array
		 */
		public function get_sections( $form ) {
			return array_key_exists( $form, $this->sections ) ? $this->sections[ $form ] : array();
		}

		/**
		 * Return the default section for a particular form.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $form The form to retrieve sections for.
		 * @return string
		 */
		public function get_default_section( $form ) {
			$sections = $this->get_sections( $form );

			if ( empty( $sections ) ) {
				return '';
			}

			/* If we're missing a registered default section, we'll use the first one in the list. */
			$fallback = key( $sections );
			$defaults = array_key_exists( 'defaults', $this->sections ) ? $this->sections['defaults'] : false;

			if ( ! $defaults ) {
				return $fallback;
			}

			return array_key_exists( $form, $defaults ) ? $defaults[ $form ] : $fallback;
		}

		/**
		 * Return all the fields.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_fields() {
			return $this->fields;
		}

		/**
		 * Return a single field.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $field_key The field's key.
		 * @return Charitable_Field|false Instance of `Charitable_Field` if the field
		 *                                is registered. False otherwise.
		 */
		public function get_field( $field_key ) {
			return array_key_exists( $field_key, $this->fields ) ? $this->fields[ $field_key ] : false;
		}

		/**
		 * Order fields within each form.
		 *
		 * @since  1.6.0
		 *
		 * @return void
		 */
		public function order_fields() {
			foreach ( $this->forms as $key ) {
				$fields = array_filter( $this->fields, array( new Charitable_Field_Filter( $key ), 'is_not_false' ) );

				new Charitable_Field_Sorter( $fields, $this, $key );
			}
		}

		/**
		 * Parse form settings.
		 *
		 * @since  1.6.0
		 *
		 * @param  array            $settings An array of form settings.
		 * @param  Charitable_Field $field    Instance of `Charitable_Field`.
		 * @return array
		 */
		protected function parse_form_settings( array $settings, Charitable_Field $field ) {
			$settings['data_type'] = $field->data_type;

			/* Make sure a label is set. */
			if ( ! array_key_exists( 'label', $settings ) ) {
				$settings['label'] = $field->label;
			}

			/* Make sure that options are set for fields that need it. */
			if ( $this->field_needs_options( $settings['type'] ) ) {
				$has_options         = array_key_exists( 'options', $settings ) && is_array( $settings['options'] );
				$settings['options'] = $has_options ? $settings['options'] : array();
			}

			if ( $this->checkbox_field_needs_value( $settings ) ) {
				$settings['value'] = 1;
			}

			return $settings;
		}

		/**
		 * Whether a field needs an array of options to be set.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $field_type The type of field.
		 * @return boolean
		 */
		protected function field_needs_options( $field_type ) {
			return in_array( $field_type, array( 'select', 'multi-checkbox', 'radio' ) );
		}

		/**
		 * Returns true if this is a checkbox field and it needs a value.
		 *
		 * @since  1.6.0
		 *
		 * @param  array $settings Fields settings.
		 *
		 * @return boolean
		 */
		protected function checkbox_field_needs_value( $settings ) {
			return 'checkbox' == $settings['type'] && ! array_key_exists( 'value', $settings );
		}

		/**
		 * Return all of the passed form fields that are included in a particular section.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $section The section of fields.
		 * @param  array  $fields  The full set of form fields.
		 * @param  string $setting The field setting that defines the form field arguments.
		 * @return array
		 */
		protected function get_form_fields_in_section( $section, $fields, $setting ) {
			$section_fields = array();

			foreach ( $fields as $key => $field ) {
				$settings = $field->$setting;

				if ( $section != $settings['section'] ) {
					continue;
				}

				$section_fields[ $key ] = $field;
			}

			return $section_fields;
		}

		/**
		 * Return an array containing the keys of the form properties.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		abstract protected function set_forms();
	}

endif;
