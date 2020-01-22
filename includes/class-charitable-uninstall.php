<?php
/**
 * Charitable Uninstall class.
 *
 * The responsibility of this class is to manage the events that need to happen
 * when the plugin is deactivated.
 *
 * @package		Charitable/Charitable_Uninstall
 * @version		1.0.0
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Uninstall' ) ) :

/**
 * Charitable_Uninstall
 *
 * @since   1.0.0
 */
class Charitable_Uninstall {

	/**
	 * Uninstall the plugin.
	 *
	 * @since   1.0.0
	 */
	public function __construct() {
		if ( charitable()->is_deactivation() && charitable_get_option( 'delete_data_on_uninstall' ) ) {

			$this->remove_caps();
			$this->remove_post_data();
			$this->remove_tables();

			do_action( 'charitable_uninstall' );
		}
	}

	/**
	 * Remove plugin-specific roles.
	 *
	 * @since   1.0.0
	 *
	 * @return 	void
	 */
	private function remove_caps() {
		$roles = new Charitable_Roles();
		$roles->remove_caps();
	}

	/**
	 * Remove post objects created by Charitable.
	 *
	 * @since   1.0.0
	 *
	 * @return 	void
	 */
	private function remove_post_data() {
		foreach ( array( 'campaign', 'donation' ) as $post_type ) {
			$posts = get_posts( array(
				'posts_per_page' 	=> -1,
				'post_type'			=> $post_type
			) );

			foreach ( $posts as $post ) {
				wp_delete_post( $post->ID, true );
			}
		}
	}

	/**
	 * Remove the custom tables added by Charitable.
	 *
	 * @since   1.0.0
	 *
	 * @return 	void
	 */
	private function remove_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "charitable_campaign_donations" );
		$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "charitable_donors" );
		$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "charitable_benefactors" );

		delete_option( $wpdb->prefix . 'charitable_campaign_donations_db_version' );
		delete_option( $wpdb->prefix . 'charitable_donors_db_version' );
		delete_option( $wpdb->prefix . 'charitable_benefactors_db_version' );
	}
}

endif;