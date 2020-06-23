<?php
/**
 * Class used to provide information about the current request.
 *
 * @package   Charitable/Classes/Charitable_Request
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.0.0
 * @version   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Request' ) ) :

	/**
	 * Charitable_Request.
	 *
	 * @since 1.0.0
	 * @final
	 */
	final class Charitable_Request {

		/**
		 * The single instance of this class.
		 *
		 * @var Charitable_Request|null
		 */
		private static $instance = null;

		/**
		 * Campaign object.
		 *
		 * @var Charitable_Campaign|false
		 */
		private $campaign;

		/**
		 * Campaign ID.
		 *
		 * @var int
		 */
		private $campaign_id;

		/**
		 * Donation object.
		 *
		 * @var Charitable_Donation
		 */
		private $donation;

		/**
		 * Set up the class.
		 *
		 * Note that the only way to instantiate an object is with the on_start method,
		 * which can only be called during the start phase. In other words, don't try
		 * to instantiate this object.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			/**
			 * Set the current campaign on the_post hook.
			 */
			add_action( 'the_post', array( $this, 'set_current_campaign' ) );

			/**
			 * Add any supported donation parameters to the session.
			 */
			add_action( 'charitable_is_donate_page', array( $this, 'add_donation_params_to_session' ) );
		}

		/**
		 * Returns and/or create the single instance of this class.
		 *
		 * @since  1.2.0
		 *
		 * @return Charitable_Request
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * When the_post is set, sets the current campaign to the current post if it is a campaign.
		 *
		 * @since  1.0.0
		 *
		 * @param  WP_Post $post The Post object.
		 * @return void
		 */
		public function set_current_campaign( $post ) {
			if ( 'campaign' == $post->post_type ) {
				$this->campaign = new Charitable_Campaign( $post );
			} else {
				unset( $this->campaign, $this->campaign_id );
			}
		}

		/**
		 * Returns the current campaign. If there is no current campaign, return false.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Campaign|false Campaign object if we're viewing a campaign within a loop. False otherwise.
		 */
		public function get_current_campaign() {
			if ( ! isset( $this->campaign ) ) {
				if ( $this->get_current_campaign_id() > 0 ) {
					$this->campaign = new Charitable_Campaign( $this->get_current_campaign_id() );
				} else {
					$this->campaign = false;
				}
			}

			return $this->campaign;
		}

		/**
		 * Returns the current campaign ID. If there is no current campaign, return 0.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_current_campaign_id() {
			if ( isset( $this->campaign ) && $this->campaign ) {
				$this->campaign_id = $this->campaign->ID;
			} else {
				$this->campaign_id = 0;

				if ( get_post_type() == Charitable::CAMPAIGN_POST_TYPE ) {
					$this->campaign_id = get_the_ID();
				} elseif ( get_query_var( 'donate', false ) ) {
					$session_donation = charitable_get_session()->get( 'donation' );

					if ( false !== $session_donation ) {
						$this->campaign_id = $session_donation->get( 'campaign_id' );
					}
				}
			}//end if

			if ( ! $this->campaign_id ) {
				$this->campaign_id = $this->get_campaign_id_from_submission();
			}

			return $this->campaign_id;
		}

		/**
		 * Returns the campaign ID from a form submission.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_campaign_id_from_submission() {
			if ( ! isset( $_POST['campaign_id'] ) ) {
				return 0;
			}

			$campaign_id = absint( $_POST['campaign_id'] );

			if ( Charitable::CAMPAIGN_POST_TYPE !== get_post_type( $campaign_id ) ) {
				return 0;
			}

			return $campaign_id;
		}

		/**
		 * Returns the current donation object. If there is no current donation, return false.
		 *
		 * @since  1.0.0
		 *
		 * @return Charitable_Donation|false
		 */
		public function get_current_donation() {
			if ( ! isset( $this->donation ) ) {
				$donation_id    = $this->get_current_donation_id();
				$this->donation = $donation_id ? charitable_get_donation( $donation_id ) : false;
			}

			return $this->donation;
		}

		/**
		 * Returns the current donation ID. If there is no current donation, return 0.
		 *
		 * @since  1.0.0
		 *
		 * @return int
		 */
		public function get_current_donation_id() {
			$donation_id = get_query_var( 'donation_id', 0 );

			if ( ! $donation_id && isset( $_GET['donation_id'] ) ) {
				$donation_id = $_GET['donation_id'];
			}

			return $donation_id;
		}

		/**
		 * If set, add supported donation parameters to the session.
		 *
		 * @since  1.6.25
		 *
		 * @param  int $campaign_id The campaign receiving the donation.
		 * @return void
		 */
		public function add_donation_params_to_session( $campaign_id ) {
			if ( array_key_exists( 'amount', $_REQUEST ) ) {
				$period = array_key_exists( 'period', $_REQUEST ) ? $_REQUEST['period'] : 'once';

				charitable_get_session()->add_donation( $campaign_id, $_REQUEST['amount'], $period );
			}
		}
	}

endif;
