<?php
/**
 * A class that is responsible for taking a set of fields and sorting them by priority.
 *
 * @package   Charitable/Classes/Charitable_Field_Sorter
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.27
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Field_Sorter' ) ) :

	/**
	 * Charitable_Field_Sorter
	 *
	 * @since 1.6.0
	 */
	class Charitable_Field_Sorter {

		/**
		 * The fields to be sorted.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		private $fields;

		/**
		 * Registry.
		 *
		 * @since 1.6.0
		 *
		 * @var   Charitable_Field_Registry
		 */
		private $registry;

		/**
		 * The field property we are sorting by.
		 *
		 * @since 1.6.0
		 *
		 * @var   string
		 */
		private $property;

		/**
		 * Create class object.
		 *
		 * @since 1.6.0
		 *
		 * @param  array                     $fields   The fields to be sorted.
		 * @param  Charitable_Field_Registry $registry The registry that the fields are included in.
		 * @param  string                    $property The field property we are sorting by.
		 */
		public function __construct( $fields, Charitable_Field_Registry $registry, $property ) {
			$this->property = $property;
			$this->registry = $registry;
			$this->fields   = $fields;

			array_walk( $fields, array( $this, 'set_field_priority' ) );
		}

		/**
		 * Set a field's priority.
		 *
		 * @since  1.6.0
		 *
		 * @param  Charitable_Field $field The field to set the priority for.
		 * @return void
		 */
		public function set_field_priority( $field ) {
			$settings = $field->{$this->property};

			/* The field was defined with a priority, so we will use that as default. */
			$default  = array_key_exists( 'priority', $settings ) ? $settings['priority'] : false;
			$priority = $this->calculate_field_priority( $settings, $default );

			$field->set( $this->property, 'priority', $priority );
		}

		/**
		 * Determine a field's priority based on its `show_after` and `show_before` settings.
		 *
		 * @since  1.6.0
		 * @since  1.6.27 Added $default parameter for priority.
		 *
		 * @param  array     $settings The field settings.
		 * @param  int|false $default  The default priority.
		 * @return int
		 */
		private function calculate_field_priority( $settings, $default ) {
			$after  = $this->get_referenced_field( 'show_after', $settings );
			$before = $this->get_referenced_field( 'show_before', $settings );

			/* If the field was set to show after a certain field and before another field. */
			if ( $after && $before ) {
				return ( $after->{$this->property}['priority'] + $before->{$this->property}['priority'] ) / 2;
			}

			if ( $after ) {
				return $after->{$this->property}['priority'] + 0.5;
			}

			if ( $before ) {
				return $before->{$this->property}['priority'] - 0.5;
			}

			/* Neither show_after or show_before was set, so return the default if one was set. */
			if ( $default ) {
				return $default;
			}

			/* Otherwise, put it 2 after the most recently registered field. */
			foreach ( array_reverse( $this->fields ) as $field ) {
				if ( array_key_exists( 'priority', $field->{$this->property} ) ) {
					return $field->{$this->property}['priority'] + 2;
				}
			}

			/* Still no luck? Set priority to 2. */
			return 2;
		}

		/**
		 * Return the field referenced by a `show_after` or `show_before` setting.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $setting  The setting used for the reference.
		 * @param  array  $settings The field settings.
		 * @return array|false False if the field does not exist or the setting was not set.
		 */
		private function get_referenced_field( $setting, $settings ) {
			if ( ! array_key_exists( $setting, $settings ) ) {
				return false;
			}

			return array_key_exists( $settings[ $setting ], $this->fields ) ? $this->fields[ $settings[ $setting ] ] : false;
		}
	}

endif;
