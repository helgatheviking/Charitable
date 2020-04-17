<?php
/**
 * Display the date filters above the campaigns table.
 *
 * @author  Studio 164a
 * @package Charitable/Admin View/Campaigns Page
 * @since   1.6.36
 * @version 1.6.36
 */

$filters = $_GET;

unset(
	$filters['post_type'],
	$filters['paged'],
	$filters['ids']
);

?>
<a href="#charitable-campaigns-filter-modal" class="charitable-filter-button button dashicons-before dashicons-filter trigger-modal hide-if-no-js" data-trigger-modal><?php _e( 'Filter', 'charitable' ); ?></a>

<?php if ( count( $filters ) ) : ?>
	<a href="<?php echo esc_url_raw( add_query_arg( array( 'post_type' => Charitable::CAMPAIGN_POST_TYPE ), admin_url( 'edit.php' ) ) ); ?>" class="charitable-campaigns-clear button dashicons-before dashicons-clear"><?php _e( 'Clear Filters', 'charitable' ); ?></a>
<?php endif ?>
