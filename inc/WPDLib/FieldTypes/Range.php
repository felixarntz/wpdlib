<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Range' ) ) {

	class Range extends \WPDLib\FieldTypes\Number {
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['value'] = $val;

			$text_args = array(
				'id'	=> $args['id'] . '-' . $this->type . '-viewer',
				'class'	=> 'wpdlib-input-' . $this->type . '-viewer',
				'value'	=> $args['value'],
			);

			$output = '<input type="text"' . \WPDLib\FieldTypes\Manager::make_html_attributes( $text_args, false, false ) . ' />';
			$output .= '<input type="' . $this->type . '"' . \WPDLib\FieldTypes\Manager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}
	}

}
