<?php
/**
 * Display section heading in settings area.
 *
 * @author    Eric Daams
 * @package   Charitable/Admin View/Settings
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.6.19
 */

if ( isset( $view_args['description'] ) ) : ?>
	<div class="charitable-description"><?php echo $view_args['description']; ?></div>
<?php else : ?>
<hr <?php echo charitable_get_arbitrary_attributes( $view_args ); ?> />
<?php endif; ?>
