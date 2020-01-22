<?php
/**
 * Charitable template part
 *
 * @version		1.0.0
 * @package		Charitable/Classes/Charitable_Template_Part
 * @author 		Eric Daams
 * @copyright 	Copyright (c) 2020, Studio 164a
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Template_Part' ) ) :

/**
 * Charitable_Template_Part
 *
 * @since   1.0.0
 */
class Charitable_Template_Part {

	/**
	 * @var 	string 			The template's slug.
	 */
	private $slug;

	/**
	 * @var 	string 			An optional name to be appended to the slug.
	 */
	private $name;

	/**
	 * Class constructor.
	 *
	 * @since   1.0.0
	 *
	 * @param 	string $slug
	 * @param 	string $name 	Optional name.
	 * @return 	void
	 */
	public function __construct($slug, $name = "" ) {
		$this->slug = $slug;
		$this->name = $name;

		new Charitable_Template( $this->get_template_names(), true, false );
	}

	/**
	 * Returns the array of template names.
	 *
	 * @since   1.0.0
	 *
	 * @return 	array
	 */
	private function get_template_names() {
		$names = array(
			$this->slug . '.php'
		);

		/**
		 * If a name is set, add the slug-name.php combination to the start of the $names array
		 */
		if ( strlen( $this->name ) ) {
			array_unshift( $names, $this->slug . '-' . $this->name . '.php' );
		}

		return $names;
	}
}

endif;