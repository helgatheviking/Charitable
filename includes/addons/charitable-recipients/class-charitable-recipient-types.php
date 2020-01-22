<?php
/**
 * Singleton class that stores registered recipient types.
 *
 * @package     Charitable/Classes/Charitable_Recipient_Types
 * @version     1.0.0
 * @author      Eric Daams
 * @copyright   Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Recipient_Types' ) ) :

	/**
	 * Charitable_Recipient_Types
	 *
	 * @since   1.0.0
	 */
	class Charitable_Recipient_Types {

		/**
		 * @var     Charitable_Recipient_Types
		 */
		private static $instance = null;

		/**
		 * @var     array
		 */
		private $types = array();

		/**
		 * Create class object.
		 *
		 * @since   1.0.0
		 */
		private function __construct() {}

		/**
		 * Returns the single instance of this class.
		 *
		 * @since   1.0.0
		 *
		 * @return  Charitable_Recipient_Types
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Registers a new recipient type.
		 *
		 * @since   1.0.0
		 *
		 * @return  Charitable_Recipient_Types
		 */
		public function register( $recipient_type, $args = array() ) {
			$defaults = array(
				'label' => '',
				'description' => '',
				'admin_label' => '',
				'admin_description' => '',
				'searchable' => false,
				'search_placeholder' => '',
				'options' => array(),
			);

			$args = wp_parse_args( $args, $defaults );

			$this->types[ $recipient_type ] = $args;
		}

		/**
		 * Returns all registered recipient types.
		 *
		 * @since   1.0.0
		 *
		 * @return  array
		 */
		public function get_types() {
			return $this->types;
		}
	}

endif;
