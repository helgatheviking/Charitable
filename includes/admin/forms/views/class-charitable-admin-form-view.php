<?php
/**
 * Admin form view.
 *
 * This is responsible for rendering the output of forms in the WordPress dashboard.
 *
 * @package   Charitable/Forms/Charitable_Admin_Form_View
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Admin_Form_View' ) ) :

	/**
	 * Charitable_Admin_Form_View.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Admin_Form_View implements Charitable_Form_View_Interface {

		/**
		 * The Form we are responsible for rendering.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Form
		 */
		protected $form;

		/**
		 * Current WP_Post object.
		 *
		 * @since 1.5.0
		 *
		 * @var   WP_Post
		 */
		protected $post;

		/**
		 * Custom keys for the current post.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		protected $post_custom_keys;

		/**
		 * Current tabindex.
		 *
		 * @since 1.5.0
		 *
		 * @var   int
		 */
		protected $tabindex;

		/**
		 * Set up view instance.
		 *
		 * @since  1.5.0
		 *
		 * @param Charitable_Form $form Form object.
		 */
		public function __construct( Charitable_Form $form ) {
			$this->form     = $form;
			$this->tabindex = 0;
		}

		/**
		 * Render a form.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function render() {
			$this->render_hidden_fields();
			$this->render_fields();
		}

		/**
		 * Render a form's hidden fields.
		 *
		 * @since  1.5.0
		 *
		 * @return boolean True if any fields were rendered. False otherwise.
		 */
		public function render_hidden_fields() {
			$fields = $this->form->get_hidden_fields();

			if ( ! is_array( $fields ) || empty( $fields ) ) {
				return false;
			}

			array_walk( $fields, array( $this, 'render_hidden_field' ) );

			return true;
		}

		/**
		 * Render notices before the form.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function render_notices() {
		}

		/**
		 * Render all of a form's fields.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $fields Optional. A set of fields to display. If not set,
		 *                       we will look for fields in the form's `get_fields()`
		 *                       method (if it has one).
		 * @return boolean       True if any fields were rendered. False otherwsie.
		 */
		public function render_fields( $fields = array() ) {
			if ( empty( $fields ) ) {
				$fields = $this->form->get_fields();

				/* If still missing fields, return false and throw an error. */
				if ( empty( $fields ) ) {
					charitable_get_deprecated()->doing_it_wrong(
						__METHOD__,
						__( 'There are no fields to render.', 'charitable' ),
						'1.5.0'
					);
					return false;
				}
			}

			array_walk( $fields, array( $this, 'render_field' ) );
		}

		/**
		 * Render a hidden field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $value Field value.
		 * @param  string $key   Field key.
		 * @return void
		 */
		public function render_hidden_field( $value, $key ) {
			printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $key ), esc_attr( $value ) );
		}

		/**
		 * Render a specific form field.
		 *
		 * @since  1.5.0
		 *
		 * @param  array  $field Field definition.
		 * @param  string $key   Field key.
		 * @param  array  $args  Unused. Mixed array of arguments.
		 * @return boolean False if the field was not rendered. True otherwise.
		 */
		public function render_field( $field, $key, $args = array() ) {
			$field['form_view']     = $this;
			$field['view']          = $this->get_field_view( $field );
			$field['type']          = $this->get_field_type( $field );
			$field['key']           = $this->get_field_key( $field, $key );
			$field['id']            = $this->get_field_id( $field );
			$field['wrapper_id']    = 'charitable-' . $field['id'] . '-wrap';
			$field['wrapper_class'] = $this->get_field_class( $field );
			$field['tabindex']      = array_key_exists( 'tabindex', $field ) ? (int) $field['tabindex'] : $this->tabindex;

			if ( 'checkbox' == $field['type'] ) {
				$field['checked'] = $this->is_field_checked( $field );
			} else {
				$field['value'] = $this->get_field_value( $field );
			}

			/* Increment the tabindex. */
			$this->tabindex = $field['tabindex'] + 1;

			return charitable_admin_view( $field['view'], $field );
		}

		/**
		 * Checks whether a field has all required arguments.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $view_args The view args.
		 * @return boolean
		 */
		public function field_has_required_args( $view_args ) {
			$required = array( 'view', 'key', 'id', 'wrapper_id', 'type' );
			$missing  = count( array_diff( $required, array_keys( $view_args ) ) );

			if ( $missing > 0 ) {
				return false;
			}

			$type = $view_args['type'];

			if ( 'fieldset' == $type ) {
				return array_key_exists( 'fields', $view_args ) && is_array( $view_args['fields'] ) && count( $view_args['fields'] );
			}

			if ( $this->field_needs_options( $type ) ) {
				return array_key_exists( 'options', $view_args );
			}

			if ( 'checkbox' == $type && ! array_key_exists( 'checked', $view_args ) ) {
				return false;
			}

			return array_key_exists( 'value', $view_args );
		}

		/**
		 * Return the field view based on a field 'type'.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		protected function get_field_view( $field ) {
			if ( array_key_exists( 'view', $field ) ) {
				return $field['view'];
			}

			if ( $this->use_default_field_template( $field['type'] ) ) {
				return 'metaboxes/field-types/default';
			}

			switch ( $field['type'] ) {
				case 'charitable-fieldset':
					return 'metaboxes/field-types/fieldset';

				default:
					return 'metaboxes/field-types/' . $field['type'];
			}
		}

		/**
		 * Return the field type.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		protected function get_field_type( $field ) {
			if ( array_key_exists( 'type', $field ) ) {
				return $field['type'];
			}

			return str_replace( 'metaboxes/field-types/', '', $field['view'] );
		}

		/**
		 * Return the field wrapper class.
		 *
		 * @since  1.5.7
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		protected function get_field_class( $field ) {
			$classes = array(
				'charitable-metabox-wrap',
				'charitable-' . $this->get_field_type( $field ) . '-wrap',
			);

			if ( array_key_exists( 'wrapper_class', $field ) ) {
				$classes = array_merge( $classes, $field['wrapper_class'] );
			}

			return implode( ' ', $classes );

		}

		/**
		 * Return the key for a particular field.
		 *
		 * @since  1.5.0
		 *
		 * @param  array  $field   Field definition.
		 * @param  string $default The key of the form field. This is the fallback option.
		 * @return string|void
		 */
		protected function get_field_key( $field, $default ) {
			foreach ( array( 'key', 'meta_key' ) as $key ) {
				if ( array_key_exists( $key, $field ) ) {
					return $field[ $key ];
				}
			}

			return $default;
		}

		/**
		 * Return the ID for a particular field.
		 *
		 * @since  1.5.3
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		protected function get_field_id( $field ) {
			if ( array_key_exists( 'id', $field ) ) {
				return $field['id'];
			}

			return str_replace( '_', '-', trim( preg_replace( '![^a-z0-9]+!i', '-', $field['key'] ), '-' ) );
		}

		/**
		 * Return the current value of a particular field.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		protected function get_field_value( $field ) {
			if ( array_key_exists( 'value', $field ) ) {
				return $field['value'];
			}

			return $this->get_current_value( $field );
		}

		/**
		 * Check whether the field is checked.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field Field definition.
		 * @return boolean
		 */
		protected function is_field_checked( $field ) {
			if ( array_key_exists( 'checked', $field ) ) {
				return $field['checked'];
			}

			$value = $this->get_current_value( $field );

			if ( array_key_exists( 'value', $field ) ) {
				return $field['value'] == $value;
			}

			return false != $value;
		}

		/**
		 * Fetches the current value for a field, ignoring the 'value' setting of the field.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field Field definition.
		 * @return mixed
		 */
		protected function get_current_value( $field ) {
			global $post;

			if ( empty( $field['key'] ) ) {
				return;
			}

			$default = array_key_exists( 'default', $field ) ? $field['default'] : '';
			$value   = get_post_meta( $post->ID, $field['key'], true );

			return $value ? $value : $default;
		}

		/**
		 * Return the custom keys for the current post.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		protected function get_post_custom_keys() {
			if ( ! is_a( $this->post, 'WP_Post' ) ) {
				return array();
			}

			if ( ! isset( $this->post_custom_keys ) ) {
				$this->post_custom_keys = get_post_custom_keys( $this->post->ID );

				if ( ! is_array( $this->post_custom_keys ) ) {
					$this->post_custom_keys = array();
				}
			}

			return $this->post_custom_keys;
		}

		/**
		 * Whether the given field type can use the default field template.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $field_type Type of field.
		 * @return boolean
		 */
		protected function use_default_field_template( $field_type ) {
			/**
			 * Filter the list of field types that use the default template.
			 *
			 * @since 1.0.0
			 *
			 * @param string[] $types Field types.
			 */
			$default_field_types = apply_filters(
				'charitable_default_template_field_types',
				array(
					'text',
					'email',
					'password',
					'date',
				)
			);

			return in_array( $field_type, $default_field_types );
		}

		/**
		 * Whether a field needs an array of options to be set.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $field_type The type of field.
		 * @return boolean
		 */
		protected function field_needs_options( $field_type ) {
			return in_array( $field_type, array( 'select', 'multi-checkbox', 'radio' ) );
		}
	}

endif;
