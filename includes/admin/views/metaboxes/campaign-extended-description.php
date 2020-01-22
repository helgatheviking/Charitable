<?php
/**
 * Renders the extended description field for the Campaign post type.
 *
 * @author    Studio 164a
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @since     1.0.0
 * @version   1.0.0
 */

global $post;

$textarea_name      = 'content';
$textarea_rows      = apply_filters( 'charitable_extended_description_rows', 40 );
$textarea_tab_index = isset( $view_args['tab_index'] ) ? $view_args['tab_index'] : 0;

wp_editor( $post->post_content, 'charitable-extended-description', array(
	'textarea_name' => 'post_content',
	'textarea_rows' => $textarea_rows,
	'tabindex'      => $textarea_tab_index,
) );
