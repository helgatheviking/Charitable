<?php
/**
 * Public form view.
 *
 * This is responsible for rendering the output of forms.
 *
 * @package   Charitable/Forms/Charitable_Public_Form_View
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.6.37
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Public_Form_View' ) ) :

	/**
	 * Charitable_Public_Form_View.
	 *
	 * @since 1.5.0
	 */
	class Charitable_Public_Form_View implements Charitable_Form_View_Interface {

		/**
		 * The Form we are responsible for rendering.
		 *
		 * @since 1.5.0
		 *
		 * @var   Charitable_Form
		 */
		protected $form;

		/**
		 * Whether the notices have been rendered.
		 *
		 * @since 1.5.2
		 *
		 * @var   boolean
		 */
		protected $rendered_notices;

		/**
		 * Whether the hidden fields have been rendered.
		 *
		 * @since 1.5.2
		 *
		 * @var   boolean
		 */
		protected $rendered_hidden_fields;

		/**
		 * Whether the honeypot has been rendered.
		 *
		 * @since 1.5.2
		 *
		 * @var   boolean
		 */
		protected $rendered_honeypot;

		/**
		 * List of custom field templates.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		protected $custom_field_templates;

		/**
		 * Set up view instance.
		 *
		 * @since  1.5.0
		 *
		 * @param Charitable_Form $form Form object.
		 */
		public function __construct( Charitable_Form $form ) {
			$this->form                   = $form;
			$this->rendered_honeypot      = false;
			$this->rendered_notices       = false;
			$this->rendered_hidden_fields = false;
			$this->custom_field_templates = $this->init_custom_field_templates();
		}

		/**
		 * Return value of a protected class property.
		 *
		 * @since  1.5.2
		 *
		 * @param  string $prop The class property.
		 * @return mixed
		 */
		public function __get( $prop ) {
			if ( ! property_exists( $this, $prop ) ) {
				return null;
			}

			return $this->$prop;
		}

		/**
		 * Register a custom field template.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $field_type The type of field we are registering this template for.
		 * @param  string $class      The template class name.
		 * @param  string $path       The path to the template.
		 * @return void
		 */
		public function register_custom_field_template( $field_type, $class, $path ) {
			$this->custom_field_templates[ $field_type ] = array(
				'class' => $class,
				'path'  => $path,
			);
		}

		/**
		 * Render a form.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function render() {
			$this->render_notices();
			$this->render_honeypot();
			$this->render_hidden_fields();
			$this->render_fields();
		}

		/**
		 * Render notices before the form.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function render_notices() {
			charitable_template_from_session(
				'form-fields/notices.php',
				array(
					'notices' => charitable_get_notices()->get_notices(),
				),
				'notices'
			);

			$this->rendered_notices = true;

			/* Clear all notices. */
			charitable_get_notices()->clear();
		}

		/**
		 * Render the honeypot fields.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function render_honeypot() {
			printf( '<input type="hidden" name="charitable_form_id" value="%1$s" autocomplete="off" /><input type="text" name="%1$s" class="charitable-hidden" value="" autocomplete="off" />', esc_attr( $this->form->get_form_identifier() ) );

			$this->rendered_honeypot = true;
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

			$this->rendered_hidden_fields = true;
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

			$i = 1;

			foreach ( $fields as $key => $field ) {
				$this->render_field(
					$field,
					$key,
					array(
						'index' => $i,
					)
				);

				$i += $this->increment_index( $field, $key, $i );
			}
		}

		/**
		 * Render a hidden field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string|array $value Field value.
		 * @param  string       $key   Field key.
		 * @return void
		 */
		public function render_hidden_field( $value, $key ) {
			$attrs = '';

			if ( is_array( $value ) ) {
				if ( ! array_key_exists( 'value', $value ) ) {
					return;
				}

				$args  = $value;
				$value = $args['value'];

				unset( $args['value'] );

				$attrs = charitable_get_arbitrary_attributes( array( 'attrs' => $args ) );
			}

			printf( '<input type="hidden" name="%s" value="%s" %s />', esc_attr( $key ), esc_attr( $value ), $attrs );
		}

		/**
		 * Render a specific form field.
		 *
		 * @since  1.5.0
		 *
		 * @param  array  $field Field definition.
		 * @param  string $key   Field key.
		 * @param  array  $args  Mixed array of arguments.
		 * @return boolean       False if the field was not rendered. True otherwise.
		 */
		public function render_field( $field, $key, $args = array() ) {
			if ( ! array_key_exists( 'type', $field ) ) {
				return false;
			}

			/* Get our index and namespace, with defaults if they're not provided. */
			$namespace = array_key_exists( 'namespace', $args ) ? $args['namespace'] : null;
			$index     = array_key_exists( 'index', $args ) ? $args['index'] : 0;

			/* Set up some form attributes. */
			$field['key']   = $this->get_field_name( $key, $namespace, $index );
			$field['attrs'] = array_key_exists( 'attrs', $field ) ? $field['attrs'] : array();

			/**
			 * Do something before the field is rendered.
			 *
			 * @since 1.0.0
			 * @since 1.5.4 Added $from_template parameter to avoid backwards compatibility issues.
			 *
			 * @param array           $field         Field definition.
			 * @param string          $key           The field's key.
			 * @param Charitable_Form $form          The Charitable_Form object.
			 * @param int             $index         The current index.
			 * @param null            $namespace     Unused. Namespace for the form field's name attribute.
			 * @param false           $from_template Whether the method was called from a template file.
			 */
			do_action( 'charitable_form_field', $field, $key, $this->form, $index, null, false );

			/* Get the template and make sure it's valid. */
			$template = $this->get_field_template( $field, $index );

			if ( ! $template ) {
				return false;
			}

			$template->set_view_args(
				array(
					'form'    => $this->form,
					'field'   => $field,
					'classes' => $this->get_field_classes( $field, $index ),
				)
			);

			$template->render();

			return true;
		}

		/**
		 * Return the key for a particular field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string      $key       Field key.
		 * @param  string|null $namespace Namespace for the form field's name attribute.
		 * @param  int         $index     The current index.
		 * @return string
		 */
		protected function get_field_name( $key, $namespace = null, $index = 0 ) {
			$name = $key;

			if ( ! is_null( $namespace ) ) {
				$name = $namespace . '[' . $name . ']';
			}

			/**
			 * Filter the name attribute to be used for the field.
			 *
			 * @since 1.0.0
			 *
			 * @param string          $name      The name attribute.
			 * @param string          $key       The field's key.
			 * @param string|null     $namespace Namespace for the field's attribute.
			 * @param Charitable_Form $form      The Charitable_Form object.
			 * @param int             $index     The current index.
			 */
			return apply_filters( 'charitable_form_field_key', $name, $key, $namespace, $this->form, $index );
		}

		/**
		 * Return a field template.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field              Field definition.
		 * @param  int   $index              The current index.
		 * @return Charitable_Template|false Returns a template if the template file exists. If it doesn't returns false.
		 */
		public function get_field_template( $field, $index ) {
			$template = $this->get_custom_template( $field['type'] );

			/**
			 * Filter the template to be used for the form.
			 *
			 * Any callback hooking into this filter should return a `Charitable_Template`
			 * instance. Anything else will be ignored.
			 *
			 * @since 1.0.0
			 *
			 * @param false|Charitable_Template $template False by default.
			 * @param array                     $field    Field definition.
			 * @param Charitable_Form           $form     The Charitable_Form object.
			 * @param int                       $index    The current index.
			 */
			$template = apply_filters( 'charitable_form_field_template', $template, $field, $this->form, $index );

			/* Fall back to default Charitable_Template if no template returned or if template was not object of 'Charitable_Template' class. */
			if ( ! $this->is_valid_template( $template ) ) {
				$template = new Charitable_Template( $this->get_template_name( $field ), false );
			}

			if ( ! $template->template_file_exists() ) {
				return false;
			}

			return $template;
		}

		/**
		 * Return a custom template for a particular field.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $field_type The type of field.
		 * @return false|Charitable_Template False if there is no matching custom template.
		 */
		public function get_custom_template( $field_type ) {
			if ( ! array_key_exists( $field_type, $this->custom_field_templates ) ) {
				return false;
			}

			$class = $this->custom_field_templates[ $field_type ]['class'];
			$path  = $this->custom_field_templates[ $field_type ]['path'];

			/* Final sanity check to make sure the template class exists. */
			if ( ! class_exists( $class ) ) {
				return false;
			}

			return new $class( $path, false );
		}

		/**
		 * Return the template name used for this field.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field Field definition.
		 * @return string
		 */
		public function get_template_name( $field ) {
			if ( $this->use_default_field_template( $field['type'] ) ) {
				$template_name = 'form-fields/default.php';
			} else {
				$template_name = 'form-fields/' . $field['type'] . '.php';
			}

			/**
			 * Filter the template name.
			 *
			 * @since 1.0.0
			 *
			 * @param string $template_name Default template name.
			 */
			return apply_filters( 'charitable_form_field_template_name', $template_name );
		}

		/**
		 * Checks whether a template is valid.
		 *
		 * @since  1.5.0
		 *
		 * @param  mixed $template Template we're checking.
		 * @return boolean
		 */
		public function is_valid_template( $template ) {
			return is_object( $template ) && is_a( $template, 'Charitable_Template' );
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
		 * Return classes that will be applied to the field.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field Field definition.
		 * @param  int   $index Field index.
		 * @return string
		 */
		public function get_field_classes( $field, $index = 0 ) {
			if ( 'hidden' == $field['type'] ) {
				return;
			}

			$classes = $this->get_field_type_classes( $field['type'] );

			if ( array_key_exists( 'class', $field ) ) {
				$classes[] = $field['class'];
			}

			if ( array_key_exists( 'required', $field ) && $field['required'] ) {
				$classes[] = 'required-field';
			}

			if ( array_key_exists( 'fullwidth', $field ) && $field['fullwidth'] ) {
				$classes[] = 'fullwidth';
			} elseif ( $index > 0 ) {
				$classes[] = $index % 2 ? 'odd' : 'even';
			}

			/**
			 * Filter the array of classes before it is returned as a string.
			 *
			 * @since 1.0.0
			 *
			 * @param array $classes List of classes.
			 * @param array $field   Field definition.
			 * @param int   $index   The field index.
			 */
			$classes = apply_filters( 'charitable_form_field_classes', $classes, $field, $index );

			return implode( ' ', $classes );
		}

		/**
		 * Return array of classes based on the field type.
		 *
		 * @since  1.5.0
		 *
		 * @param  string $type Type of field.
		 * @return string[]
		 */
		public function get_field_type_classes( $type ) {
			$classes = array();

			switch ( $type ) {
				case 'paragraph':
					$classes[] = 'charitable-form-content';
					break;

				case 'fieldset':
				case 'donation-amount-wrapper':
					$classes[] = 'charitable-fieldset';
					break;

				default:
					$classes[] = 'charitable-form-field';
					$classes[] = 'charitable-form-field-' . $type;
			}

			return $classes;
		}

		/**
		 * Set how much the index should be incremented by.
		 *
		 * @since  1.5.0
		 *
		 * @param  array    $field The field definition.
		 * @param  string   $key   The key of the current field. May be empty.
		 * @param  int|null $index The current index. May be null.
		 * @return int
		 */
		public function increment_index( $field, $key = '', $index = null ) {
			if ( ! $this->should_increment( $field ) ) {
				return 0;
			}

			/**
			 * Set the default increment.
			 *
			 * @since 1.0.0
			 *
			 * @param int             $increment The default increment amount. Defaults to 1.
			 * @param array           $field     The field definition.
			 * @param string          $key       The key of the current field.
			 * @param Charitable_Form $form      The form we are displaying.
			 * @param int             $index     The current index.
			 */
			return apply_filters( 'charitable_form_field_increment', 1, $field, $key, $this->form, $index );
		}

		/**
		 * Whether the index should be incremented.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $field Field definition.
		 * @return boolean
		 */
		protected function should_increment( $field ) {
			if ( in_array( $field['type'], array( 'hidden', 'paragraph', 'fieldset' ) ) ) {
				return false;
			}

			if ( array_key_exists( 'fullwidth', $field ) && $field['fullwidth'] ) {
				return false;
			}

			return true;
		}

		/**
		 * Set up custom field templates.
		 *
		 * @since  1.5.0
		 *
		 * @return array
		 */
		protected function init_custom_field_templates() {
			/**
			 * Filter the custom field templates.
			 *
			 * @since 1.5.0
			 */
			$templates = apply_filters(
				'charitable_public_form_view_custom_field_templates',
				array(
					'donation-amount-wrapper' => array(
						'class' => 'Charitable_Template',
						'path'  => 'donation-form/donation-amount-wrapper.php',
					),
					'donation-amount'         => array(
						'class' => 'Charitable_Template',
						'path'  => 'donation-form/donation-amount.php',
					),
					'donor-fields'            => array(
						'class' => 'Charitable_Template',
						'path'  => 'donation-form/donor-fields.php',
					),
					'details-fields'          => array(
						'class' => 'Charitable_Template',
						'path'  => 'donation-form/details-fields.php',
					),
					'meta-fields'             => array(
						'class' => 'Charitable_Template',
						'path'  => 'donation-form/meta-fields.php',
					),
					'gateway-fields'          => array(
						'class' => 'Charitable_Template',
						'path'  => 'donation-form/gateway-fields.php',
					),
					'cc-expiration'           => array(
						'class' => 'Charitable_Template',
						'path'  => 'donation-form/cc-expiration.php',
					),
				)
			);

			return array_filter( $templates, array( $this, 'sanitize_custom_field_template' ) );
		}

		/**
		 * Filter custom field templates to make sure they all have a class and path.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $template The registered template.
		 * @return boolean
		 */
		protected function sanitize_custom_field_template( $template ) {
			return is_array( $template ) && array_key_exists( 'path', $template ) && array_key_exists( 'class', $template );
		}
	}

endif;
