<?php
/**
 * Functions to improve compatibility with Polylang.
 *
 * @package   Charitable/Functions/Compatibility
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.41
 * @version   1.6.41
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
 * @since  1.6.41
 *
 * @return void
 */
function charitable_compat_polylang_late_translations() {
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

add_action( 'wp', 'charitable_compat_polylang_late_translations' );
