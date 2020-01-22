<?php
/**
 * A helper class designed to output the content of a table.
 *
 * @package   Charitable/Classes/Charitable_Table_Helper
 * @version   1.5.0
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Table_Helper' ) ) :

	/**
	 * Charitable_Table_Helper
	 *
	 * @since 1.5.0
	 */
	class Charitable_Table_Helper {

		/**
		 * The table columns.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		public $columns;

		/**
		 * The table data.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		public $data;

		/**
		 * Other table args.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		public $args;

		/**
		 * Table attribute tags.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		public $table_attributes = array( 'class', 'id' );

		/**
		 * The current row.
		 *
		 * @since 1.5.0
		 *
		 * @var   array
		 */
		public $current_row;

		/**
		 * The current row number.
		 *
		 * @since 1.5.0
		 *
		 * @var   int
		 */
		public $current_row_idx;

		/**
		 * Create class object.
		 *
		 * @since 1.5.0
		 *
		 * @param array $columns A set of columns.
	 	 * @param array $data    Data.
	 	 * @param array $args    Optional set of extra arguments.
		 */
		public function __construct( array $columns, array $data, $args = array() ) {
			$this->columns = $columns;
			$this->data    = $data;
			$this->args    = $args;
		}

		/**
		 * Display the table.
		 *
		 * @since  1.5.0
		 *
		 * @return void
		 */
		public function render() {
			if ( empty( $this->columns ) ) {
				return;
			}

			charitable_template( 'tables/table.php', array( 'helper' => $this ) );
		}

		/**
		 * Render a table row.
		 *
		 * @since  1.5.0
		 *
		 * @param  array $rowdata The data to be displayed on this row.
		 * @return void
		 */
		public function render_row( $row_data ) {
			charitable_template( 'tables/row.php', array( 'data' => $row_data, 'columns' => $this->columns ) );
		}

		/**
		 * Return a string containing the table attributes.
		 *
		 * @since  1.5.0
		 *
		 * @return string
		 */
		public function table_attributes() {
			$attrs = '';

			foreach ( $this->table_attributes as $attr ) {
				if ( ! array_key_exists( $attr, $this->args ) ) {
					continue;
				}

				$attrs .= sprintf( ' %s="%s"', $attr, esc_attr( $this->args[ $attr ] ) );
			}

			return ltrim( $attrs );
		}
	}

endif;
