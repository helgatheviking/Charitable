<?php
/**
 * Base Charitable_Field model.
 *
 * @package   Charitable/Classes/Charitable_Field
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

if ( ! class_exists( 'Charitable_Field' ) ) :

	/**
	 * Charitable_Field
	 *
	 * @since 1.5.0
	 *
	 * @property string         $field
	 * @property string         $label
	 * @property string         $data_type
	 * @property false|callable $value_callback
	 * @property boolean|array  $admin_form
	 * @property boolean        $show_in_export
	 * @property boolean|array  $email_tag
	 */
	abstract class Charitable_Field implements Charitable_Field_Interface {

		/**
		 * Field identifier.
		 *
		 * @since 1.5.0
		 *
		 * @var   string
		 */
		protected $field;

		/**
		 * Field arguments.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		protected $args;

		/**
		 * Raw field arguments. These are the arguments that were passed when instantiating the field.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		protected $raw_args;

		/**
		 * Create class object.
		 *
		 * @since 1.5.0
		 *
		 * @param string $field The field key.
		 * @param array  $args  Mixed arguments.
		 */
		public function __construct( $field, array $args = array() ) {
			$this->field    = $field;
			$this->raw_args = $args;
			$this->args     = $this->parse_args( $args );
		}

		/**
		 * Set a field argument.
		 *
		 * Unlike __set(), this also supports setting a setting within an argument.
		 * For example, it allows you to modify the `label` of `donation_form`.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $key     The field's key.
		 * @param  string $setting The individual setting within an array-like argument.
		 *                         If not set, the entire argument is changed.
		 * @param  mixed  $value   The field's value.
		 * @return Charitable_Field
		 */
		public function set( $key, $setting, $value ) {
			if ( empty( $setting ) ) {
				return $this->__set( $key, $value );
			}

			$arg = $this->args[ $key ];

			if ( ! is_array( $arg ) ) {
				charitable_get_deprecated()->doing_it_wrong(
					__METHOD__,
					/* translators: %s: argument key */
					sprintf( _x( 'Attempting to set an argument setting for a non-array argument. Argument: %s', 'argument key', 'charitable' ), $key ),
					'1.6.0'
				);

				return $this;
			}

			$arg[ $setting ]    = $value;
			$this->args[ $key ] = $this->sanitize_arg( $key, $arg );

			return $this;
		}

		/**
		 * Set a specific argument.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key   The field's key.
		 * @param  mixed  $value The field's value.
		 * @return Charitable_Field
		 */
		public function __set( $key, $value ) {
			$this->args[ $key ] = $this->sanitize_arg( $key, $value );
			return $this;
		}

		/**
		 * Get a particular argument value.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key The field's key.
		 * @return mixed
		 */
		public function __get( $key ) {
			return 'field' == $key ? $this->field : $this->args[ $key ];
		}

		/**
		 * Checks whether a particular argument is set.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $key The field's key.
		 * @return boolean
		 */
		public function __isset( $key ) {
			return 'field' == $key || array_key_exists( $key, $this->args );
		}

		/**
		 * Return the default arguments for this field type.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		protected function get_defaults() {
			return array();
		}

		/**
		 * Parse the passed arguments against a set of defaults and sanitize them.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $args Mixed set of field arguments.
		 * @return array       Parsed arguments.
		 */
		protected function parse_args( $args ) {
			$args      = array_merge( $this->get_defaults(), $args );
			$keys      = array_keys( $args );
			$sanitized = array_map( array( $this, 'sanitize_arg' ), $keys, $args );

			return array_combine( $keys, $sanitized );
		}

		/**
		 * Sanitize the argument.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $key   The argument's key.
		 * @param  mixed  $value The argument's value.
		 * @return mixed  The argument value after being registered.
		 */
		protected function sanitize_arg( $key, $value ) {
			$method = 'sanitize_' . $key;
			if ( method_exists( $this, $method ) ) {
				return $this->$method( $value );
			}

			return $value;
		}

		/**
		 * Sanitize a form argument.
		 *
		 * @since  1.6.0
		 *
		 * @param  mixed $value    The argument setting.
		 * @param  array $defaults Default form args.
		 * @return boolean|array
		 */
		protected function sanitize_form_arg( $value, $defaults = array() ) {
			if ( ! $value ) {
				return false;
			}

			if ( ! is_array( $value ) ) {
				return $defaults;
			}

			return array_merge( $defaults, $value );
		}

		/**
		 * Sanitize the email tag.
		 *
		 * @since  1.6.0
		 *
		 * @param  mixed $value The argument setting.
		 * @return false|array
		 */
		public function sanitize_email_tag( $value ) {
			if ( false === $value ) {
				return $value;
			}

			$label    = array_key_exists( 'label', $this->raw_args ) ? $this->raw_args['label'] : '';
			$defaults = array(
				'description' => $label,
				'preview'     => $label,
				'tag'         => $this->field,
			);

			if ( ! is_array( $value ) ) {
				return $defaults;
			}

			return array_merge( $defaults, $value );
		}
	}

endif;
