<?php
/**
 * Display notice in settings area.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

$notice_type = isset( $view_args['notice_type'] ) ? $view_args['notice_type'] : 'error';

?>
<div class="notice <?php echo $notice_type; ?>" <?php echo charitable_get_arbitrary_attributes( $view_args ); ?>>
	<p><?php echo $view_args['content']; ?></p>
</div>
