<?php
/**
 * Charitable_Campaign_Field model.
 *
 * @package   Charitable/Classes/Charitable_Campaign_Field
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.25
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Campaign_Field' ) ) :

	/**
	 * Charitable_Campaign_Field
	 *
	 * @since 1.6.0
	 *
	 * @property string         $field
	 * @property string         $label
	 * @property string         $data_type
	 * @property false|callable $value_callback
	 * @property boolean|array  $admin_form
	 * @property boolean|array  $campaign_form
	 * @property boolean        $show_in_export
	 * @property boolean|array  $email_tag
	 */
	class Charitable_Campaign_Field extends Charitable_Field implements Charitable_Field_Interface {

		/**
		 * Field identifier.
		 *
		 * @since 1.6.0
		 *
		 * @var   string
		 */
		protected $field;

		/**
		 * Field arguments.
		 *
		 * @since 1.6.0
		 *
		 * @var   array $args  {
		 *     Array of field arguments.
		 *
		 *     @type string         $label           The label to use in the export, meta, Ambassadors campaign form (unless overridden),
		 *                                           admin form (unless overriden) and email tag (unless overridden).
		 *                                           If no `label` is provided in the campaign_form args, this label will be used in
		 *                                           the Ambassadors campaign form. If no `label` is provided in the admin_form args, this
		 *                                           label will also be used in the admin form. Unless a `description` is set in the
		 *                                           `email_tag` args, this label will also be used there as the tag description.
		 *     @type string         $data_type       How the data should be saved. This may be set to 'meta' or 'core', through 'core'
		 *                                           is designed stricly for core Charitable use.
		 *     @type false|callable $value_callback  A callback function to retrieve the value of the field for a campaign.
		 *                                           The callback function receives up to two arguments: a `Charitable_Campaign` object
		 *                                           and the field key.
		 *                                           Note that this should only be set to false if either of the following is true:
		 *                                           `Charitable_Campaign` has a getter named `get_$key` where `$key` is the field of
		 *                                           this key; or $key is a member variable of `WP_Post`.
		 *     @type boolean|array  $admin_form   {
		 *         Sets whether the field should be shown in the admin form. To prevent the field being available
		 *         in the form (not even as a hidden input), set to false. For control over how the field should be
		 *         shown in the form, an array can be passed with any of these keys:
		 *
		 *         @type string         $label          The field label. This will override the `label` set above.
		 *         @type string         $type           The type of field. Options include (but may not be limited to):
		 *                                              text, email, password, date, datepicker, checkbox, multi-checkbox, select,
		 *                                              radio, file, fieldset, editor (uses WP Editor), textarea, number, picture,
		 *                                              url and hidden.
		 *                                              This will default to text.
		 *         @type boolean        $required       Whether this is a required field.
		 *         @type array          $options        Provide a set of options. This is required when `type` is select, radio or
		 *                                              multi-checkbox. These should be provided in a simple value=>label array,
		 *                                              where the label is what people see when they select an option, and value
		 *                                              is what gets stored in the database.
		 *         @type mixed          $default        The default value for this field.
		 *         @type boolean        $fullwidth      Whether to show the field as a full-width field.
		 *         @type array          $attrs          Arbitrary set of form field attributes. These should be provided in a simple
		 *                                              key=>value array, which will be parsed as key="value" attributes in the field.
		 *         @type string         $section        The section or panel that the field should be included in.
		 *         @type int            $priority       Set the position of the field within the form. This overrides `show_after`
		 *                                              and `show_before`. If `priority`, `show_after` and `show_before` are not set,
		 *                                              the field will be shown after the most recently registered form field.
		 *         @type string         $show_after     Specify another field that this field should be shown after.
		 *                                              If `priority` is set, this field is ignored. Note that if multiple fields
		 *                                              are set to show after the same field, it may not appear immediately after the
		 *                                              other field. Use in combination with `show_before` or use `priority` instead
		 *                                              for fine-grained control.
		 *         @type string         $show_before    Specify another field that this field should be shown before.
		 *                                              If `priority` is set, this field is ignored. Note that if multiple fields
		 *                                              are set to show before the same field, it may not appear immediately before
		 *                                              the other field. Use in combination with `show_after` or use `priority`
		 *                                              instead for fine-grained control.
		 *         @type false|callable $value_callback A callback function to retrieve the value of the field for a campaign.
		 *                                              This will override the `value_callback` setting for the field.
		 *     }
		 *     @type boolean|array  $campaign_form   {
		 *         Sets whether the field should be shown in the Ambassadors campaign form. To prevent the field being available
		 *         in the form (not even as a hidden input), set to false. If set to true, the form field will inherit arguments
		 *         from the `admin_form` (if provided), or use default arguments. For control over how the field should be
		 *         shown in the form, an array can be passed with the same keys as described for `admin_form` above.
		 *     }
		 *     @type boolean        $show_in_export  Whether the field should be shown in campaign exports.
		 *     @type boolean|array  $email_tag       {
		 *         Automatically create an email tag for this field. Set to false to prevent the field being available as an
		 *         email tag. For control over the email tag options, an array can be passed with the following keys:
		 *
		 *         @type string $description The description shown for the email tag. If no description is set, the `label`
		 *                                   will be used.
		 *         @type string $tag         The email tag. If this is not provided, the field key will be used.
		 *         @type string $preview     A value to use in email previews for this field.
		 *     }
		 * }
		 */
		protected $args;

		/**
		 * Return the default arguments for this field type.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		protected function get_defaults() {
			return array(
				'label'          => '',
				'data_type'      => 'meta',
				'value_callback' => false,
				'campaign_form'  => true,
				'admin_form'     => true,
				'show_in_export' => true,
				'email_tag'      => true,
			);
		}

		/**
		 * Sanitize the argument.
		 *
		 * @since  1.6.0
		 *
		 * @param  string $key   The argument's key.
		 * @param  mixed  $value The argument's value.
		 * @return mixed The argument value after being registered.
		 */
		protected function sanitize_arg( $key, $value ) {
			$value = parent::sanitize_arg( $key, $value );

			if ( in_array( $key, array( 'show_in_export' ) ) ) {
				return (bool) $value;
			}

			return $value;
		}

		/**
		 * Sanitize the campaign_form setting.
		 *
		 * @since  1.6.0
		 *
		 * @param  mixed $value The argument setting.
		 * @return boolean|array
		 */
		protected function sanitize_campaign_form( $value ) {
			return $this->sanitize_form_arg(
				$value,
				array(
					'type'      => 'text',
					'required'  => false,
					'fullwidth' => false,
					'default'   => '',
					'attrs'     => array(),
				)
			);
		}

		/**
		 * Sanitize the admin_form setting.
		 *
		 * @since  1.6.0
		 *
		 * @param  mixed $value The argument setting.
		 * @return boolean|array
		 */
		public function sanitize_admin_form( $value ) {
			return $this->sanitize_form_arg(
				$value,
				array(
					'type'      => 'text',
					'required'  => false,
					'fullwidth' => false,
					'default'   => '',
					'attrs'     => array(),
					'section'   => charitable()->campaign_fields()->get_default_section( 'admin' ),
				)
			);
		}
	}

endif;
