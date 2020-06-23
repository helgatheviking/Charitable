<?php
/**
 * Charitable Utility Functions.
 *
 * Utility functions.
 *
 * @package   Charitable/Functions/Utility
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders an array by a particular key.
 *
 * @since  1.5.0
 *
 * @param  strign $key The key to sort by.
 * @param  array  $a   First element.
 * @param  array  $b   Element to compare against.
 * @return int
 */
function charitable_element_key_sort( $key, $a, $b ) {
	foreach ( array( $a, $b ) as $item ) {
		if ( ! array_key_exists( $key, $item ) ) {
			error_log( sprintf( '%s missing from element: ' . json_encode( $item ), $key ) );
		}
	}

	if ( $a[ $key ] == $b[ $key ] ) {
		return 0;
	}

	return $a[ $key ] < $b[ $key ] ? -1 : 1;
}

/**
 * Orders an array by the priority key.
 *
 * @since  1.0.0
 *
 * @param  array $a First element.
 * @param  array $b Element to compare against.
 * @return int
 */
function charitable_priority_sort( $a, $b ) {
	return charitable_element_key_sort( 'priority', $a, $b );
}

/**
 * Orders an array by the time key.
 *
 * @since  1.5.0
 *
 * @param  array $a First element.
 * @param  array $b Element to compare against.
 * @return int
 */
function charitable_timestamp_sort( $a, $b ) {
	return charitable_element_key_sort( 'time', $a, $b );
}

/**
 * Checks whether function is disabled.
 *
 * Full credit to Pippin Williamson and the EDD team.
 *
 * @since  1.0.0
 *
 * @param  string $function Name of the function.
 * @return boolean Whether or not function is disabled.
 */
function charitable_is_func_disabled( $function ) {
	$disabled = explode( ',', ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
}

/**
 * Verify a nonce. This also just ensures that the nonce is set.
 *
 * @since  1.0.0
 *
 * @param  string $nonce        The nonce name.
 * @param  string $action       The nonce action.
 * @param  array  $request_args Request arguments. If not set, will populate with $_GET.
 * @return boolean
 */
function charitable_verify_nonce( $nonce, $action, $request_args = array() ) {
	if ( empty( $request_args ) ) {
		$request_args = $_GET;
	}

	return isset( $request_args[ $nonce ] ) && wp_verify_nonce( $request_args[ $nonce ], $action );
}

/**
 * Retrieve the timezone id.
 *
 * Credit: Pippin Williamson & the rest of the EDD team.
 *
 * @since  1.0.0
 *
 * @return string
 */
function charitable_get_timezone_id() {
	$timezone = get_option( 'timezone_string' );

	/* If site timezone string exists, return it */
	if ( $timezone ) {
		return $timezone;
	}

	$utc_offset = 3600 * get_option( 'gmt_offset', 0 );

	/* Get UTC offset, if it isn't set return UTC */
	if ( ! $utc_offset ) {
		return 'UTC';
	}

	/* Attempt to guess the timezone string from the UTC offset */
	$timezone = timezone_name_from_abbr( '', $utc_offset );

	/* Last try, guess timezone string manually */
	if ( false === $timezone ) {

		$is_dst = date( 'I' );

		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
					return $city['timezone_id'];
				}
			}
		}
	}

	/* If we still haven't figured out the timezone, fall back to UTC */
	return 'UTC';
}

/**
 * Given an array and a separate array of keys, returns a new array that only contains the
 * elements in the original array with the specified keys.
 *
 * @since  1.5.0
 *
 * @param  array $original_array The original array we need to pull a subset from.
 * @param  array $subset_keys    The keys to use for our subset.
 * @return array
 */
function charitable_array_subset( array $original_array, $subset_keys ) {
	return array_intersect_key( $original_array, array_flip( $subset_keys ) );
}

/**
 * Ensure a number is a positive integer.
 *
 * @since  1.0.0
 *
 * @param  mixed $i Number received.
 * @return int|false
 */
function charitable_validate_absint( $i ) {
	return filter_var( $i, FILTER_VALIDATE_INT, array( 'min_range' => 1 ) );
}

/**
 * Sanitize any checkbox value.
 *
 * @since  1.5.0
 *
 * @param  mixed $value Value set for checkbox, or false.
 * @return boolean
 */
function charitable_sanitize_checkbox( $value = false ) {
	return intval( true == $value || 'on' == $value );
}

/**
 * Format an array of strings as a sentence part.
 *
 * If there is one item in the string, this will just return that item.
 * If there are two items, it will return a string like this: "x and y".
 * If there are three or more items, it will return a string like this: "x, y and z".
 *
 * @since  1.3.0
 *
 * @param  string[] $list The list.
 * @return string
 */
function charitable_list_to_sentence_part( $list ) {
	$list = array_values( $list );

	if ( 1 == count( $list ) ) {
		return $list[0];
	}

	if ( 2 == count( $list ) ) {
		return sprintf(
			/* translators: %1$s: first list item; %2$s: second list item. */
			_x( '%1$s and %2$s', 'x and y', 'charitable' ),
			$list[0],
			$list[1]
		);
	}

	$last = array_pop( $list );

	return sprintf(
		/* translators: %1$s: all list items except last, comma-separated; %2$s: second list item. */
		_x( '%1$s and %2$s', 'x and y', 'charitable' ),
		implode( ', ', $list ),
		$last
	);
}

/**
 * Sanitizes a date passed in the format of January 29, 2009.
 *
 * We use WP_Locale to parse the month that the user has set.
 *
 * @global WP_Locale $wp_locale
 *
 * @since  1.4.10
 *
 * @param  string $date          The date to be sanitized.
 * @param  string $return_format The date format to return. Default is U (timestamp).
 * @return string|false
 */
function charitable_sanitize_date( $date, $return_format = 'U' ) {
	global $wp_locale;

	if ( empty( $date ) || ! $date ) {
		return false;
	}

	list( $month, $day, $year ) = explode( ' ', $date );

	$day   = trim( $day, ',' );
	$month = 1 + array_search( $month, array_values( $wp_locale->month ) );
	$time  = mktime( 0, 0, 0, $month, $day, $year );

	if ( 'U' == $return_format ) {
		return $time;
	}

	return date( $return_format, $time );
}

/**
 * Return a string containing the correct number & type of placeholders.
 *
 * @since  1.5.0
 *
 * @param  int    $count       The number of placeholders to add.
 * @param  string $placeholder Type of placeholder to insert.
 * @return string
 */
function charitable_get_query_placeholders( $count = 1, $placeholder = '%s' ) {
	$placeholders = array_fill( 0, $count, $placeholder );
	return implode( ', ', $placeholders );
}

/**
 * Return a list of pages in id=>title format for use in a select dropdown.
 *
 * @see    get_pages
 *
 * @since  1.6.0
 *
 * @param  array $args Optional arguments to be passed to get_pages.
 * @return array
 */
function charitable_get_pages_options( $args = array() ) {
	$pages = get_pages( $args );

	if ( ! $pages ) {
		return array();
	}

	return array_combine( wp_list_pluck( $pages, 'ID' ), wp_list_pluck( $pages, 'post_title' ) );
}

/**
 * Checks whether this is localhost.
 *
 * This is not fullproof. It uses a safelist of IP addresses.
 *
 * @since  1.6.14
 *
 * @return boolean
 */
function charitable_is_localhost() {
	/**
	 * Filter list of localhost IP addresses.
	 *
	 * @since 1.6.14
	 *
	 * @param array $ip_addresses The list of IP addresses.
	 */
	$safelist = apply_filters(
		'charitable_localhost_ips',
		array(
			'127.0.0.1',
			'::1',
		)
	);

	return in_array( $_SERVER['REMOTE_ADDR'], $safelist );
}
