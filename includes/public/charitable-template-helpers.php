<?php
/**
 * Charitable Template Helpers.
 *
 * Functions used to assist with rendering templates.
 *
 * @package   Charitable/Functions/Templates
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.34
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a template.
 *
 * @since  1.0.0
 * @since  1.5.2 Added $classname parameter.
 *
 * @param  string|string[] $template_name A single template name or an ordered array of template.
 * @param  mixed[]         $args          Optional array of arguments to pass to the view.
 * @param  string          $classname     Template class name. Allows for extensions to use charitable_template().
 * @return Charitable_Template
 */
function charitable_template( $template_name, array $args = array(), $classname = 'Charitable_Template' ) {
	$classname = apply_filters( 'charitable_template_class_name', $classname, $template_name, $args );

	if ( ! class_exists( $classname ) ) {
		$classname = 'Charitable_Template';
	}

	$class = class_exists( $classname ) ? $classname : 'Charitable_Template';
	if ( empty( $args ) ) {
		$template = new $classname( $template_name );
	} else {
		$template = new $classname( $template_name, false );
		$template->set_view_args( $args );
		$template->render();
	}

	return $template;
}

/**
 * When a session-reliant template is displayed without a session ID being
 * set, wrap the output in a wrapper that tells our client-side session
 * handler to fetch content via AJAX.
 *
 * @since  1.5.0
 *
 * @param  string|string[] $template_name A single template name or an ordered array of template.
 * @param  mixed[]         $args          Array of arguments to pass to the view.
 * @param  string          $template_key  A key representing the template, which will be passed
 *                                        as one of the arguments in the AJAX request.
 * @param  array           $wrapper_args  A mixed set of arguments that need to be passed along
 *                                        in the AJAX request.
 * @param  string          $classname     Template class name. Allows for extensions to use charitable_template().
 * @return void Content is echoed.
 */
function charitable_template_from_session( $template_name, array $args, $template_key, $wrapper_args = array(), $classname = 'Charitable_Template' ) {
	ob_start();

	charitable_template( $template_name, $args, $classname );

	echo charitable_template_from_session_content( $template_key, $wrapper_args, ob_get_clean() );
}

/**
 * Returns a piece of content or an empty string, wrapped in the correct markup
 * for on-demand session content retrieval.
 *
 * @since  1.5.0
 *
 * @param  string $template_key  A key representing the template, which will be passed
 *                               as one of the arguments in the AJAX request.
 * @param  array  $wrapper_args  A mixed set of arguments that need to be passed along
 *                               in the AJAX request.
 * @param  string $default       The default content to be displayed if no content is
 *                               returned from the AJAX request, or the user does not
 *                               have Javascript enabled.
 * @return string
 */
function charitable_template_from_session_content( $template_key, $wrapper_args = array(), $default = '' ) {
	/* When we have a session ID, we just print the default. */
	if ( charitable_get_session()->has_session_id() ) {
		return $default;
	}

	$content  = '<div class="charitable-session-content" data-template="' . esc_attr( $template_key ) . '" data-args="' . esc_attr( json_encode( $wrapper_args ) ) . '" style="display: none;">' . $default . '</div>';
	$content .= '<noscript>' . $default . '</noscript>';

	return $content;
}

/**
 * Return the template path if the template exists. Otherwise, return default.
 *
 * @since  1.0.0
 *
 * @param  string|string[] $template The template to load.
 * @param  string          $default  The default to load if the template file doesn't exist.
 * @return string The template path if the template exists. Otherwise, return default.
 */
function charitable_get_template_path( $template, $default = '' ) {
	$t    = new Charitable_Template( $template, false );
	$path = $t->locate_template();

	if ( ! file_exists( $path ) ) {
		$path = $default;
	}

	return $path;
}

/**
 * Insert a particular template into an array of templates.
 *
 * @since  1.5.0
 *
 * @param  string $template  The template to be inserted.
 * @param  array  $templates The default set of templates.
 * @param  int    $index     The position at which to insert the template.
 * @return array
 */
function charitable_splice_template( $template, $templates, $index = 1 ) {
	if ( empty( $template ) ) {
		return $templates;
	}

	array_splice( $templates, $index, 0, $template );

	return $templates;
}

/**
 * Simple CSS compression.
 *
 * Removes all comments, removes spaces after colons and strips out all the whitespace.
 *
 * Based on http://manas.tungare.name/software/css-compression-in-php/
 *
 * @since  1.2.0
 *
 * @param  string $css The block of CSS to be compressed.
 * @return string The compressed CSS
 */
function charitable_compress_css( $css ) {
	/* 1. Remove comments */
	$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

	/* 2. Remove space after colons */
	$css = str_replace( ': ', ':', $css );

	/* 3. Remove whitespace */
	$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

	return $css;
}

/**
 * Provides arguments passed to campaigns within the loop.
 *
 * @since  1.2.3
 *
 * @param  mixed[] $view_args Optional. If called by the shortcode, this will contain the arguments passed to the shortcode.
 * @return mixed[]
 */
function charitable_campaign_loop_args( $view_args = array() ) {
	$defaults = array(
		'button' => 'donate',
	);

	$args = wp_parse_args( $view_args, $defaults );

	return apply_filters( 'charitable_campaign_loop_args', $args );
}

/**
 * Provides arguments passed to campaigns within the loop.
 *
 * @since  1.5.7
 *
 * @param  mixed[] $view_args Optional. If called by the shortcode, this will contain the arguments passed to the shortcode.
 * @return mixed[]
 */
function charitable_campaign_loop_class( $view_args = array() ) {
	$classes = array( 'campaign-loop' );

	if ( $view_args['columns'] > 1 ) {
		$classes = array_merge( $classes, array( 'campaign-grid campaign-grid-' . $view_args['columns'] ) );

		if ( $view_args['masonry'] ) {
			$classes[] = 'masonry';
		}
	} else {
		$classes[] = 'campaign-list';
	}

	/**
	 * Filter the classes to be applied to the campaign loop.
	 *
	 * @since 1.5.7
	 *
	 * @param array $classes   Array of classes.
	 * @param array $view_args View args.
	 */
	$classes = apply_filters( 'charitable_campaign_loop_classes', $classes, $view_args );

	return implode( ' ', $classes );
}

/**
 * Processes arbitrary form attributes into HTML-safe key/value pairs
 *
 * @since  1.3.0
 *
 * @param  array $field Array defining the form field attributes.
 * @return string The formatted HTML-safe attributes.
 */
function charitable_get_arbitrary_attributes( $field ) {
	if ( ! isset( $field['attrs'] ) ) {
		$field['attrs'] = array();
	}

	/* Add backwards compatibility support for placeholder, min, max, step, pattern, rows and required. */
	foreach ( array( 'placeholder', 'min', 'max', 'step', 'pattern', 'rows', 'required' ) as $attr ) {

		/* Skip if the attribute is not defined in the field, or is explicitly set in the 'attrs' property. */
		if ( ! array_key_exists( $attr, $field ) || array_key_exists( $attr, $field['attrs'] ) ) {
			continue;
		}

		if ( 'required' == $attr ) {
			/* Skip required attribute if it's not true. */
			if ( ! $field['required'] ) {
				continue;
			}

			$field['attrs'][ $attr ] = 'required';
		} else {
			$field['attrs'][ $attr ] = $field[ $attr ];
		}
	}

	$output = '';

	foreach ( $field['attrs'] as $key => $value ) {
		$escaped_value = esc_attr( $value );
		$output       .= " $key=\"$escaped_value\" ";
	}

	return apply_filters( 'charitable_arbitrary_field_attributes', $output );
}

/**
 * Checks whether we are currently in the main loop on a singular page.
 *
 * This should be used in any functions run on the_content hook, to prevent
 * Charitable's filters touching other the_content instances outside the main
 * loop.
 *
 * @since  1.4.11
 *
 * @return boolean
 */
function charitable_is_main_loop() {
	return is_single() && in_the_loop() && is_main_query();
}

/**
 * Returns the current URL.
 *
 * @see    https://gist.github.com/leereamsnyder/fac3b9ccb6b99ab14f36
 *
 * @since  1.0.0
 *
 * @global WP $wp
 * @return string
 */
function charitable_get_current_url() {
	global $wp;

	return trailingslashit(
		add_query_arg(
			array_key_exists( 'QUERY_STRING', $_SERVER ) ? $_SERVER['QUERY_STRING'] : '',
			'',
			home_url( $wp->request )
		)
	);
}

/**
 * Returns the URL to which the user should be redirected after signing on or registering an account.
 *
 * @since  1.0.0
 *
 * @return string
 */
function charitable_get_login_redirect_url() {
	if ( isset( $_REQUEST['redirect_to'] ) ) {
		$redirect = $_REQUEST['redirect_to'];
	} elseif ( charitable_get_permalink( 'profile_page' ) ) {
		$redirect = charitable_get_permalink( 'profile_page' );
	} else {
		$redirect = home_url();
	}

	return apply_filters( 'charitable_signon_redirect_url', $redirect );
}

/**
 * Displays a table.
 *
 * @since  1.5.0
 *
 * @param  array $columns The set of table columns.
 * @param  array $data    The data to display in the table.
 * @param  array $args    Optional set of extra arguments.
 * @return void
 */
function charitable_table_template( array $columns, array $data, $args = array() ) {
	$table = new Charitable_Table_Helper( $columns, $data, $args );
	$table->render();
}

/**
 * Return the email verification link.
 *
 * @since  1.5.0
 * @since  1.6.32 Added $force_send argument.
 *
 * @param  WP_User      $user         An instance of `WP_User`.
 * @param  string|false $redirect_url The URL to redirect to after verification.
 * @param  boolean      $force_send   Whether the link should include an argument to force
 *                                    resending the email, even if it was sent recently.
 * @return string
 */
function charitable_get_email_verification_link( WP_User $user, $redirect_url = false, $force_send = false ) {
	return add_query_arg(
		array(
			'charitable_action' => 'verify_email',
			'user'              => $user->ID,
			'redirect_url'      => $redirect_url,
			'force_send'        => (int) $force_send,
		)
	);
}

/**
 * Get the button classes for a Charitable button.
 *
 * @since  1.6.29
 *
 * @param  string $button Which button we're showing.
 * @return string
 */
function charitable_get_button_class( $button ) {
	$classes = [ 'button', 'charitable-button' ];

	switch ( $button ) {
		case 'donate':
			$classes[] = 'donate-button';
			$classes[] = 'button-primary';
			break;

		default:
			$classes[] = $button . '-button';
	}

	/**
	 * Filter the button classes.
	 *
	 * @since 1.6.29
	 *
	 * @param array  $classes The button classes.
	 * @param string $button  The specific button we're showing.
	 */
	$classes = apply_filters( 'charitable_button_class', $classes, $button );

	return implode( ' ', $classes );
}
