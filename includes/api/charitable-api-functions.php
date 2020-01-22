<?php
/**
 * REST API functions.
 *
 * @package   Charitable/Functions/API
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST API routes.
 *
 * @since  1.6.0
 *
 * @return void
 */
function charitable_register_api_routes() {
	$route = new Charitable_API_Route_Reports();
	$route->register_routes();
}
