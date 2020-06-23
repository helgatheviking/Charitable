<?php
/**
 * Charitable Events
 *
 * @package   Charitable/Classes/Charitable_Cron
 * @version   1.1.0
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Cron' ) ) :

	/**
	 * Charitable_Cron
	 *
	 * @since 1.1.0
	 */
	class Charitable_Cron {

		/**
		 * The single instance of this class.
		 *
		 * @since 1.1.0
		 *
		 * @var   Charitable_Cron|null
		 */
		private static $instance = null;

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Cron
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Create class object.
		 *
		 * @since  1.1.0
		 */
		private function __construct() {
			add_action( 'charitable_daily_scheduled_events', array( $this, 'check_expired_campaigns' ) );
		}

		/**
		 * Schedule Charitable event hooks.
		 *
		 * @since  1.1.0
		 *
		 * @return boolean
		 */
		public static function schedule_events() {
			$ret = false;

			if ( ! wp_next_scheduled( 'charitable_daily_scheduled_events' ) ) {
				$ret = wp_schedule_event( time(), 'daily', 'charitable_daily_scheduled_events' );
			}

			return false !== $ret;
		}

		/**
		 * Check for expired campaigns.
		 *
		 * @since  1.1.0
		 *
		 * @return void
		 */
		public function check_expired_campaigns() {
			$yesterday = date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

			$args = array(
				'fields' => 'ids',
				'post_type' => Charitable::CAMPAIGN_POST_TYPE,
				'posts_per_page' => -1,
				'post_status' => 'publish',
				'meta_query' => array(
					array(
						'key'       => '_campaign_end_date',
						'value'     => array( $yesterday, date( 'Y-m-d H:i:s' ) ),
						'compare'   => 'BETWEEN',
						'type'      => 'datetime'
					)
				)
			);

			$campaigns = get_posts( $args );

			if ( empty( $campaigns ) ) {
				return;
			}

			foreach ( $campaigns as $campaign_id ) {
				do_action( 'charitable_campaign_end', $campaign_id );
			}
		}
	}

endif;
