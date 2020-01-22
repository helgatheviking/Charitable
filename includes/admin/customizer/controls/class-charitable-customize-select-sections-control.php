<?php
/**
 * Add Select Sections control.
 *
 * @package   Charitable/Classes/Charitable_Customize_Select_Sections_Control
 * @author    Eric Daams
 * @copyright Copyright (c) 2020, Studio 164a
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     1.6.5
 * @version   1.6.5
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Customize_Select_Sections_Control' ) ) :

	/**
	 * Select Sections customizer control class.
	 *
	 * @since 1.6.5
	 */
	class Charitable_Customize_Select_Sections_Control extends WP_Customize_Control {

		/**
		 * The control type.
		 *
		 * @since 1.6.5
		 *
		 * @var   string
		 */
		public $type = 'select_sections';

		/**
		 * Render the control's content.
		 *
		 * @since  1.6.5
		 *
		 * @return void
		 */
		public function render_content() {
			if ( empty( $this->choices ) ) {
				return;
			}

			$input_id         = '_customize-input-' . $this->id;
			$description_id   = '_customize-description-' . $this->id;
			$describedby_attr = ( ! empty( $this->description ) ) ? ' aria-describedby="' . esc_attr( $description_id ) . '" ' : '';
			$output           = '';

			if ( ! empty( $this->label ) ) {
				$output .= '<label for="' . esc_attr( $input_id ) . '" class="customize-control-title">' . esc_html( $this->label ) . '</label>';
			}

			if ( ! empty( $this->description ) ) {
				$output .= '<span id="' . esc_attr( $description_id ) . '" class="description customize-control-description">' . $this->description . '</span>';
			}

			$output .= '<select id="' . esc_attr( $input_id ) . '" ' . $describedby_attr . ' ' . $this->get_link() . '>';

			foreach ( $this->choices as $section => $option ) {
				if ( ! is_array( $option ) ) {
					$output .= '<option value="' . esc_attr( $section ) . '"' . selected( $this->value(), $section, false ) . '>' . $option . '</option>';
				} else {
					$label   = array_key_exists( 'label', $option ) ? $option['label'] : '';
					$output .= '<optgroup label="' . $label . '">';

					foreach ( $option['options'] as $key => $opt ) {
						$output .= '<option value="' . esc_attr( $key ) . '" ' . selected( $this->value(), $key ) . '>' . $opt . '</option>';
					}

					$output .= '</optgroup>';
				}
			}

			$output .= '</select>';

			echo $output;
		}
	}

endif;
