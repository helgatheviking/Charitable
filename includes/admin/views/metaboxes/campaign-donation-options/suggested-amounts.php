<?php
/**
 * Renders the suggested donation amounts field inside the donation options metabox for the Campaign post type.
 *
 * @author    Studio 164a
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @since     1.0.0
 * @version   1.6.0
 */

global $post;

/**
 * Filter the fields to be included in the suggested amounts array.
 *
 * @since 1.0.0
 *
 * @param array $fields A set of fields including a column header and placeholder value.
 */
$fields = apply_filters( 'charitable_campaign_donation_suggested_amounts_fields', array(
	'amount'      => array(
		'column_header' => __( 'Amount', 'charitable' ),
		'placeholder'   => __( 'Amount', 'charitable' ),
	),
	'description' => array(
		'column_header' => __( 'Description (optional)', 'charitable' ),
		'placeholder'   => __( 'Optional Description', 'charitable' ),
	),
) );

$title               = isset( $view_args['label'] ) ? $view_args['label'] : '';
$tooltip             = isset( $view_args['tooltip'] ) ? '<span class="tooltip"> '. $view_args['tooltip'] . '</span>' : '';
$description         = isset( $view_args['description'] ) ? '<span class="charitable-helper">' . $view_args['description'] . '</span>' : '';
$suggested_donations = charitable_get_campaign( $post->ID )->get_suggested_donations();

/* Add a default empty row to the end. We will use this as our clone model. */
$default = array_fill_keys( array_keys( $fields ), '' );

array_push( $suggested_donations, $default );

?>
<div id="charitable-campaign-suggested-donations-metabox-wrap" class="charitable-metabox-wrap">
	<table id="charitable-campaign-suggested-donations" class="widefat charitable-campaign-suggested-donations">
		<thead>
			<tr class="table-header">
				<th colspan="<?php echo count( $fields ) + 2; ?>"><label for="campaign_suggested_donations"><?php echo $title; ?></label></th>
			</tr>
			<tr>
				<?php $i = 1; ?>
				<?php foreach ( $fields as $key => $field ) : ?>
					<th <?php echo $i == 1 ? 'colspan="2"' : ''; ?> class="<?php echo $key; ?>-col"><?php echo $field['column_header']; ?></th>
					<?php $i++; ?>
				<?php endforeach ?>
				<th class="remove-col"></th>
			</tr>
		</thead>
		<tbody>
			<tr class="no-suggested-amounts <?php echo ( count( $suggested_donations ) > 1 ) ? 'hidden' : ''; ?>">
				<td colspan="<?php echo count( $fields ) + 2; ?>"><?php _e( 'No suggested amounts have been created yet.', 'charitable' ); ?></td>
			</tr>
		<?php
		foreach ( $suggested_donations as $i => $donation ) :
			?>
			<tr data-index="<?php echo $i; ?>" class="<?php echo ( $donation === end( $suggested_donations ) ) ? 'to-copy hidden' : 'default'; ?>">
				<td class="reorder-col"><span class="charitable-icon charitable-icon-donations-grab handle"></span></td>
				<?php
				foreach ( $fields as $key => $field ) :

					if ( is_array( $donation ) && isset( $donation[ $key ] ) ) {
						$value = $donation[ $key ];
					} elseif ( 'amount' == $key ) {
						$value = $donation;
					} else {
						$value = '';
					}

					if ( 'amount' == $key && strlen( $value ) ) {
						$value = charitable_format_money( $value, false, true );
					}
				?>
					<td class="<?php echo $key; ?>-col"><input
						type="text"
						class="campaign_suggested_donations"
						name="_campaign_suggested_donations[<?php echo $i; ?>][<?php echo $key; ?>]"
						value="<?php echo esc_attr( $value ); ?>"
						placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" />
					</td>
				<?php endforeach ?>
				<td class="remove-col"><span class="dashicons-before dashicons-dismiss charitable-delete-row"></span></td>
			</tr>
			<?php endforeach ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="<?php echo count( $fields ) + 2; ?>"><a class="button" href="#" data-charitable-add-row="suggested-amount"><?php _e( '+ Add a Suggested Amount', 'charitable' ); ?></a></td>
			</tr>
		</tfoot>
	</table>
</div>
