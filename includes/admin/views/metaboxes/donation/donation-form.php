<?php
/**
 * Renders the donation form meta box for the Donation post type.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin Views/Metaboxes
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.5.0
 * @version   1.5.0
 */

$form   = $view_args['form'];
$fields = $form->get_fields();

unset( $fields['meta_fields'] );

?>
<div class="donation-banner-wrapper">
	<div class="donation-banner">
		<h3 class="donation-number"><?php printf( '%s #%d', __( 'Donation', 'charitable' ), $form->get_donation()->get_number() ); ?></h3>
	</div>
</div>
<div class="charitable-form-fields primary">
	<?php
	$form->view()->render_hidden_fields();
	$form->view()->render_fields( $fields );
	?>
</div><!-- .charitable-form-fields -->
