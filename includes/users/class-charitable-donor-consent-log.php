<?php
/**
 * Responsible for getting and updating a donor's consent log.
 *
 * @package   Charitable/Classes/Charitable_Donor_Consent_Log
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.0
 * @version   1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Donor_Consent_Log' ) ) :

	/**
	 * Charitable_Donor_Consent_Log
	 *
	 * @since 1.6.0
	 */
	class Charitable_Donor_Consent_Log {

		/**
		 * The donor ID.
		 *
		 * @since 1.6.0
		 *
		 * @var   int
		 */
		private $donor_id;

		/**
		 * The log.
		 *
		 * @since 1.6.0
		 *
		 * @var   array
		 */
		private $log;

		/**
		 * Create class object.
		 *
		 * @since 1.6.0
		 *
		 * @param int $donor_id The donor ID.
		 */
		public function __construct( $donor_id ) {
			$this->donor_id = $donor_id;
		}

		/**
		 * Add a meta log.
		 *
		 * @since  1.6.0
		 *
		 * @param  boolean $consent_given     Whether the donor gave their consent.
		 * @param  string  $consent_statement The statement that the donor agreed to.
		 * @return int|boolean Meta ID if the key didn't exist, true on successful update,
		 *                     false on failure or if the log has not changed.
		 */
		public function add( $consent_given, $consent_statement ) {
			$log               = $this->get_log();
			$last_log          = end( $log );
			$consent_changed   = $last_log['consent_given'] != $consent_given;
			$statement_changed = $last_log['statement'] != $consent_statement;

			/* Neither the consent nor the statement has changed. */
			if ( ! $consent_changed && ! $statement_changed ) {
				return false;
			}

			array_push(
				$log,
				array(
					'time'          => time(),
					'consent_given' => $consent_given,
					'statement'     => $consent_statement,
				)
			);

			$ret = update_metadata( 'donor', $this->donor_id, 'consent_log', $log );

			/* Clear the meta_log */
			unset(
				$this->log
			);

			/**
			 * Do something when contact consent is changed.
			 *
			 * @since 1.6.5
			 *
			 * @param boolean $consent_changed   Whether the actual consent has changed.
			 * @param boolean $statement_changed Whether the consent statement changed.
			 * @param int     $donor_id          The donor ID.
			 */
			do_action( 'charitable_donor_contact_consent_changed', $consent_changed, $statement_changed, $this->donor_id );

			return $ret;
		}

		/**
		 * Return the raw meta log.
		 *
		 * This is stored as donor meta with the `consent_log` meta key.
		 *
		 * @since  1.6.0
		 *
		 * @return array
		 */
		public function get_log() {
			if ( ! isset( $this->log ) ) {
				$this->log = get_metadata( 'donor', $this->donor_id, 'consent_log', true );

				if ( ! is_array( $this->log ) ) {
					$this->log = array();
				}
			}

			return $this->log;
		}

		/**
		 * Return the most recent consent log entry.
		 *
		 * @since  1.6.5
		 *
		 * @return array
		 */
		public function get_last_log() {
			$log = $this->get_log();

			return end( $log );
		}
	}

endif;
