<?php
/**
 * @package WPDLib
 * @version 0.5.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Range' ) ) {
	/**
	 * Class for a number field.
	 *
	 * @since 0.5.0
	 */
	class Range extends Number {

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param integer|float $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['value'] = $val;

			$text_args = array(
				'id'	=> $args['id'] . '-' . $this->type . '-viewer',
				'class'	=> 'wpdlib-input-' . $this->type . '-viewer',
				'value'	=> $args['value'],
				'min'	=> $args['min'],
				'max'	=> $args['max'],
				'step'	=> $args['step'],
			);

			$output = '<input type="number"' . FieldManager::make_html_attributes( $text_args, false, false ) . ' />';
			$output .= '<input type="' . $this->type . '"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}
	}

}
