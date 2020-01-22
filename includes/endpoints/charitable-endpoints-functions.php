<?php
/**
 * Charitable Endpoint Functions.
 *
 * @package   Charitable/Functions/Endpoints
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @version   1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a new endpoint.
 *
 * @since  1.5.0
 *
 * @param  Charitable_Endpoint $endpoint The endpoint object to be registered.
 * @return void
 */
function charitable_register_endpoint( Charitable_Endpoint $endpoint ) {
	return charitable()->endpoints()->register( $endpoint );
}

/**
 * Return the URL for a given page.
 *
 * Example usage:
 *
 * - charitable_get_permalink( 'campaign_donation_page' );
 * - charitable_get_permalink( 'login_page' );
 * - charitable_get_permalink( 'registration_page' );
 * - charitable_get_permalink( 'profile_page' );
 * - charitable_get_permalink( 'donation_receipt_page' );
 * - charitable_get_permalink( 'donation_cancellation_page' );
 *
 * @since  1.0.0
 *
 * @param  string $page The endpoint id.
 * @param  array  $args Optional array of arguments.
 * @return string|false String if page is found. False if none found.
 */
function charitable_get_permalink( $page, $args = array() ) {
	return charitable()->endpoints()->get_page_url( $page, $args );
}

/**
 * Checks whether we are currently looking at the given page.
 *
 * Example usage:
 *
 * - charitable_is_page( 'campaign_donation_page' );
 * - charitable_is_page( 'login_page' );
 * - charitable_is_page( 'registration_page' );
 * - charitable_is_page( 'profile_page' );
 * - charitable_is_page( 'donation_receipt_page' );
 * - charitable_is_page( 'donation_cancellation_page' );
 *
 * @since  1.0.0
 *
 * @param  string $page The endpoint id.
 * @param  array  $args Optional array of arguments.
 * @return boolean
 */
function charitable_is_page( $page, $args = array() ) {
	return charitable()->endpoints()->is_page( $page, $args );
}

/**
 * Checks whether the current request is for a single campaign.
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_campaign_page() {
	return is_singular() && Charitable::CAMPAIGN_POST_TYPE == get_post_type();
}

/**
 * Returns the URL for the campaign donation page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'campaign_donation' ).
 * It will produce the same results.
 *
 * We keep both for backwards compatibility (pre 1.5).
 *
 * @uses   Charitable_Endpoints::get_page_url()
 *
 * @since  1.0.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_campaign_donation_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'campaign_donation', $args );
}

/**
 * Checks whether the current request is for a campaign donation page.
 *
 * This is functionally equivalent to using charitable_is_page( 'campaign_donation' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * By default, this will return true when viewing a campaign with the `donate`
 * query var set, or when the donation form is shown on the campaign page or
 * in a modal.
 *
 * Pass `'strict' => true` in `$args` to only return true when the `donate`
 * query var is set.
 *
 * @uses   Charitable_Endpoints::is_page()
 *
 * @since  1.0.0
 *
 * @param  boolean $ret  Unused argument.
 * @param  array   $args Mixed args.
 * @return boolean
 */
function charitable_is_campaign_donation_page( $ret = null, $args = array() ) {
	return charitable()->endpoints()->is_page( 'campaign_donation', $args );
}

/**
 * Returns the URL for the campaign donation page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'donation_receipt' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_donation_receipt_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'donation_receipt', $args );
}

/**
 * Checks whether the current request is for the donation receipt page.
 *
 * This is used when you call charitable_is_page( 'donation_receipt_page' ).
 * In general, you should use charitable_is_page() instead since it will
 * take into account any filtering by plugins/themes.
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_donation_receipt_page() {
	return charitable()->endpoints()->is_page( 'donation_receipt' );
}

/**
 * Returns the URL for the campaign donation page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'donation_processing' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.2.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_donation_processing_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'donation_processing', $args );
}

/**
 * Checks whether the current request is for the donation receipt page.
 *
 * This is functionally equivalent to using charitable_is_page( 'donation_processing' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_donation_processing_page() {
	return charitable()->endpoints()->is_page( 'donation_processing' );
}

/**
 * Returns the URL for the donation cancellation page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'donation_cancellation' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.4.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_donation_cancel_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'donation_cancellation', $args );
}

/**
 * Checks whether the current request is for the donation cancellation page.
 *
 * This is functionally equivalent to using charitable_is_page( 'donation_cancellation' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.4.0
 *
 * @return boolean
 */
function charitable_is_donation_cancel_page() {
	return charitable()->endpoints()->is_page( 'donation_cancellation' );
}

/**
 * Returns the URL for the campaign donation page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'campaign_widget' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.2.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_campaign_widget_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'campaign_widget', $args );
}

/**
 * Checks whether the current request is for the donation receipt page.
 *
 * This is functionally equivalent to using charitable_is_page( 'campaign_widget' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_campaign_widget_page() {
	return charitable()->endpoints()->is_page( 'campaign_widget' );
}

/**
 * Returns the URL for the forgot password page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'forgot_password' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.4.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_forgot_password_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'forgot_password', $args );
}

/**
 * Checks whether the current request is for the forgot password page.
 *
 * This is functionally equivalent to using charitable_is_page( 'forgot_password' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.4.0
 *
 * @return boolean
 */
function charitable_is_forgot_password_page() {
	return charitable()->endpoints()->is_page( 'forgot_password' );
}

/**
 * Returns the URL for the reset password page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'reset_password' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.4.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_reset_password_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'reset_password' );
}

/**
 * Checks whether the current request is for the reset password page.
 *
 * This is functionally equivalent to using charitable_is_page( 'reset_password' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.4.0
 *
 * @return boolean
 */
function charitable_is_reset_password_page() {
	return charitable()->endpoints()->is_page( 'reset_password' );
}

/**
 * Returns the URL for the login page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'login' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_login_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'login' );
}

/**
 * Checks whether the current request is for the login page.
 *
 * This is functionally equivalent to using charitable_is_page( 'login' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_login_page() {
	return charitable()->endpoints()->is_page( 'login' );
}

/**
 * Returns the URL for the registration page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'registration' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_registration_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'registration' );
}

/**
 * Checks whether the current request is for the registration page.
 *
 * This is functionally equivalent to using charitable_is_page( 'registration' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_registration_page() {
	return charitable()->endpoints()->is_page( 'registration' );
}

/**
 * Returns the URL for the profile page.
 *
 * This is functionally equivalent to using charitable_get_permalink( 'profile' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @param  string $url  Deprecated argument.
 * @param  array  $args Mixed arguments.
 * @return string
 */
function charitable_get_profile_page_permalink( $url = null, $args = array() ) {
	return charitable()->endpoints()->get_page_url( 'profile' );
}

/**
 * Checks whether the current request is for the profile page.
 *
 * This is functionally equivalent to using charitable_is_page( 'profile' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_profile_page() {
	return charitable()->endpoints()->is_page( 'profile' );
}

/**
 * Checks whether the current request is for an email preview.
 *
 * This is functionally equivalent to using charitable_is_page( 'email_preview' ).
 * It will produce the same results. We keep both for backwards compatibility (pre 1.5).
 *
 * @since  1.0.0
 *
 * @return boolean
 */
function charitable_is_email_preview() {
	return charitable()->endpoints()->is_page( 'email_preview' );
}
