<?php
/**
 * Renders the campaign benefactors form.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

$benefactor = $view_args['benefactor'];

if ( $benefactor->is_active() ) {
	$summary = $benefactor;
} elseif ( $benefactor->is_expired() ) {
	$summary = sprintf( '<span>%s</span>%s', __( 'Expired', 'charitable' ), $benefactor );
} else {
	$summary = sprintf( '<span>%s</span>%s', __( 'Inactive', 'charitable' ), $benefactor );
}

?>
<div class="charitable-benefactor-summary">
	<span class="summary"><?php echo $summary; ?></span>
	<span class="alignright">
		<a href="#" data-charitable-toggle="campaign_benefactor_<?php echo esc_attr( $benefactor->campaign_benefactor_id ); ?>" data-charitable-toggle-text="<?php esc_attr_e( 'Close', 'charitable' ); ?>"><?php _e( 'Edit', 'charitable' ); ?></a>&nbsp;&nbsp;&nbsp;
		<a href="#" data-campaign-benefactor-delete="<?php echo esc_attr( $benefactor->campaign_benefactor_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'charitable-deactivate-benefactor' ) ); ?>"><?php _e( 'Delete', 'charitable' ); ?></a>
	</span>
</div>
