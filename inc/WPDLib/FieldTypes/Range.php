<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Range' ) ) {

	class Range extends Number {
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
