<?php
/**
 * Charitable Core Admin Functions
 *
 * General core functions available only within the admin area.
 *
 * @package 	Charitable/Functions/Admin
 * @version     1.0.0
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load a view from the admin/views folder.
 *
 * If the view is not found, an Exception will be thrown.
 *
 * Example usage: charitable_admin_view('metaboxes/campaign-title');
 *
 * @since  1.0.0
 *
 * @param  string $view      The view to display.
 * @param  array  $view_args Optional. Arguments to pass through to the view itself.
 * @return boolean True if the view exists and was rendered. False otherwise.
 */
function charitable_admin_view( $view, $view_args = array() ) {
	$base_path = array_key_exists( 'base_path', $view_args ) ? $view_args['base_path'] : charitable()->get_path( 'admin' ) . 'views/';

	/**
	 * Filter the path to the view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path      The default path.
	 * @param string $view      The view.
	 * @param array  $view_args View args.
	 */
	$filename = apply_filters( 'charitable_admin_view_path', $base_path . $view . '.php', $view, $view_args );

	if ( ! is_readable( $filename ) ) {
		charitable_get_deprecated()->doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: %s: Filename of passed view */
				__( 'Passed view (%s) not found or is not readable.', 'charitable' ),
				$filename
			),
			'1.0.0'
		);

		return false;
	}

	ob_start();

	include( $filename );

	ob_end_flush();

	return true;
}

/**
 * Returns the Charitable_Settings helper.
 *
 * @since  1.0.0
 *
 * @return Charitable_Settings
 */
function charitable_get_admin_settings() {
	return Charitable_Settings::get_instance();
}

/**
 * Returns the Charitable_Admin_Notices helper.
 *
 * @since  1.4.6
 *
 * @return Charitable_Admin_Notices
 */
function charitable_get_admin_notices() {
	return charitable()->registry()->get( 'admin_notices' );
}

/**
 * Returns whether we are currently viewing the Charitable settings area.
 *
 * @since  1.2.0
 *
 * @param  string $tab Optional. If passed, the function will also check that we are on the given tab.
 * @return boolean
 */
function charitable_is_settings_view( $tab = '' ) {
	if ( ! empty( $_POST ) ) {
		$is_settings = array_key_exists( 'option_page', $_POST ) && 'charitable_settings' === $_POST['option_page'];

		if ( ! $is_settings || empty( $tab ) ) {
			return $is_settings;
		}

		return array_key_exists( 'charitable_settings', $_POST ) && array_key_exists( $tab, $_POST['charitable_settings'] );
	}

	$is_settings = isset( $_GET['page'] ) && 'charitable-settings' == $_GET['page'];

	if ( ! $is_settings || empty( $tab ) ) {
		return $is_settings;
	}

	/* The general tab can be loaded when tab is not set. */
	if ( 'general' == $tab ) {
		return ! isset( $_GET['tab'] ) || 'general' == $_GET['tab'];
	}

	return isset( $_GET['tab'] ) && $tab == $_GET['tab'];
}

/**
 * Print out the settings fields for a particular settings section.
 *
 * This is based on WordPress' do_settings_fields but allows the possibility
 * of leaving out a field lable/title, for fullwidth fields.
 *
 * @see    do_settings_fields
 *
 * @since  1.0.0
 *
 * @global $wp_settings_fields Storage array of settings fields and their pages/sections
 *
 * @param  string  $page       Slug title of the admin page who's settings fields you want to show.
 * @param  string  $section    Slug title of the settings section who's fields you want to show.
 * @return string
 */
function charitable_do_settings_fields( $page, $section ) {
	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {
		$class = '';

		if ( ! empty( $field['args']['class'] ) ) {
			$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
		}

		echo "<tr{$class}>";

		if ( ! empty( $field['args']['label_for'] ) ) {
			echo '<th scope="row"><label for="' . esc_attr( $field['args']['label_for'] ) . '">' . $field['title'] . '</label></th>';
			echo '<td>';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td>';
		} elseif ( ! empty( $field['title'] ) ) {
			echo '<th scope="row">' . $field['title'] . '</th>';
			echo '<td>';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td>';
		} else {
			echo '<td colspan="2" class="charitable-fullwidth">';
			call_user_func( $field['callback'], $field['args'] );
			echo '</td>';
		}

		echo '</tr>';
	}
}

/**
 * Add new tab to the Charitable settings area.
 *
 * @since  1.3.0
 *
 * @param  string[] $tabs
 * @param  string $key
 * @param  string $name
 * @param  mixed[] $args
 * @return string[]
 */
function charitable_add_settings_tab( $tabs, $key, $name, $args = array() ) {
	$defaults = array(
		'index' => 3,
	);

	$args   = wp_parse_args( $args, $defaults );
	$keys   = array_keys( $tabs );
	$values = array_values( $tabs );

	array_splice( $keys, $args['index'], 0, $key );
	array_splice( $values, $args['index'], 0, $name );

	return array_combine( $keys, $values );
}

/**
 * Return the donation actions class.
 *
 * @since  1.5.0
 *
 * @return Charitable_Donation_Admin_Actions
 */
function charitable_get_donation_actions() {
    return Charitable_Admin::get_instance()->get_donation_actions();
}
