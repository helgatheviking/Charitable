<?php
/**
 * Displays a table header row.
 *
 * Override this template by copying it to yourtheme/charitable/tables/header-row.php
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

if ( ! array_key_exists( 'data', $view_args ) || ! array_key_exists( 'columns', $view_args ) ) {
    return;
}

$data    = $view_args['data'];
$columns = $view_args['columns'];

?>
<tr>
    <?php foreach ( $columns as $key => $header ) : ?>
    <td class="charitable-table-cell-<?php echo esc_attr( $key ) ?>"><?php echo array_key_exists( $key, $data ) ? $data[ $key ] : '' ?></td>
    <?php endforeach ?>
</tr>
