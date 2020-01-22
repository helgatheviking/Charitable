<?php
/**
 * Renders a single panel's content in the Campaign Settings meta box.
 *
 * @author    Studio 164a
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @since     1.6.0
 * @version   1.6.0
 */

if ( ! array_key_exists( 'fields', $view_args ) || empty( $view_args['fields'] ) ) {
	return;
}

$form = new Charitable_Admin_Form();
$form->set_fields( $view_args['fields'] );
$form->view()->render_fields();
