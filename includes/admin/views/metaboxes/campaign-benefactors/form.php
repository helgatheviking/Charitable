<?php
/**
 * Renders the campaign benefactors form.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.35
 */

$i18n       = charitable()->registry()->get( 'i18n' );
$php_format = $i18n->get_datepicker_format( 'F d, Y' );
$js_format  = $i18n->get_js_datepicker_format( 'MM d, yy' );

$benefactor           = isset( $view_args['benefactor'] ) ? $view_args['benefactor'] : null;
$extension            = isset( $view_args['extension'] ) ? $view_args['extension'] : '';
$is_active_benefactor = is_null( $benefactor ) || $benefactor->is_active();

if ( is_null( $benefactor ) ) {
	$default_args = array(
		'index'                           => '_0',
		'contribution_amount'             => '',
		'contribution_amount_is_per_item' => 0,
		'date_created'                    => date_i18n( $php_format ),
		'date_deactivated'                => 0,
	);

	$args = array_merge( $default_args, $view_args );
} else {
	$args = array(
		'index'                           => $benefactor->campaign_benefactor_id,
		'contribution_amount'             => $benefactor->get_contribution_amount(),
		'contribution_amount_is_per_item' => $benefactor->contribution_amount_is_per_item,
		'date_created'                    => date_i18n( $php_format, strtotime( $benefactor->date_created ) ),
		'date_deactivated'                => '0000-00-00 00:00:00' == $benefactor->date_deactivated ? '' : date_i18n( $php_format, strtotime( $benefactor->date_deactivated ) ),
	);
}

$id_base   = 'campaign_benefactor_' . $args['index'];
$name_base = '_campaign_benefactor[' . $args['index'] . ']';

?>
<div id="<?php echo esc_attr( $id_base ); ?>" class="charitable-metabox-wrap charitable-benefactor-wrap" style="display: none;">
	<?php if ( is_null( $benefactor ) ) : ?>
		<a class="charitable-benefactor-form-cancel" href="#"><?php _e( 'Cancel', 'charitable' ); ?></a>
	<?php endif ?>
	<p><strong><?php _e( 'Contribution Amount', 'charitable' ); ?></strong></p>
	<fieldset class="charitable-benefactor-contribution-amount">
		<input type="text" id="<?php echo esc_attr( $id_base ); ?>_contribution_amount" class="contribution-amount" name="<?php echo esc_attr( $name_base ); ?>[contribution_amount]" value="<?php echo $args['contribution_amount']; ?>" placeholder="<?php _e( 'Enter amount. e.g. 10%, $2', 'charitable' ); ?>" />
		<select id="<?php echo esc_attr( $id_base ); ?>_contribution_amount_is_per_item" class="contribution-type" name="<?php echo esc_attr( $name_base ); ?>[contribution_amount_is_per_item]">
			<option value="1" <?php selected( 1, $args['contribution_amount_is_per_item'] ); ?>><?php _e( 'Apply to every matching item', 'charitable' ); ?></option>
			<option value="0" <?php selected( 0, $args['contribution_amount_is_per_item'] ); ?>><?php _e( 'Apply only once per purchase', 'charitable' ); ?></option>
		</select>
	</fieldset>
	<?php
		do_action( 'charitable_campaign_benefactor_form_extension_fields', $benefactor, $extension, $args['index'] );
	?>
	<div class="charitable-benefactor-date-wrap cf">
		<label for="<?php echo esc_attr( $id_base ); ?>_date_created"><?php _e( 'Starting From:', 'charitable' ); ?>
			<input type="text"
				id="<?php echo esc_attr( $id_base ); ?>_date_created"
				name="<?php echo esc_attr( $name_base ); ?>[date_created]"
				tabindex="3"
				class="charitable-datepicker"
				data-date="<?php echo $args['date_created']; ?>"
				data-format="<?php echo $js_format; ?>"
			/>
		</label>
		<label for="<?php echo esc_attr( $id_base ); ?>_date_deactivated"><?php _e( 'Ending:', 'charitable' ); ?>
			<input type="text"
				id="<?php echo esc_attr( $id_base ); ?>_date_deactivated"
				name="<?php echo esc_attr( $name_base ); ?>[date_deactivated]"
				placeholder="&#8734;"
				tabindex="3"
				class="charitable-datepicker"
				data-date="<?php echo $args['date_deactivated']; ?>"
				data-format="<?php echo $js_format; ?>"
			/>
		</label>
	</div>
</div>
