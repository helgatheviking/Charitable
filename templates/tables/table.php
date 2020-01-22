<?php
/**
 * Displays the table wrapper and includes the rest the table.
 *
 * Override this template by copying it to yourtheme/charitable/tables/table.php
 *
 * @author  Studio 164a
 * @package Charitable/Templates/Tables
 * @since   1.5.0
 * @version 1.5.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! array_key_exists( 'helper', $view_args ) ) {
    return;
}

?>
<table <?php echo $view_args['helper']->table_attributes() ?>>
    <thead>
        <?php charitable_template( 'tables/header.php', $view_args ) ?>
    </thead>
    <tbody>
        <?php array_walk( $view_args['helper']->data, array( $view_args['helper'], 'render_row' ) ) ?>
    </tbody>
</table>
