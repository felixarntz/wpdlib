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

if ( ! class_exists( 'WPDLib\FieldTypes\Color' ) ) {

	class Color extends \WPDLib\FieldTypes\Base {
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

		public function validate( $val = null ) {
			if ( $val === null ) {
				return '#000000';
			}

			if ( ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i', $val ) ) {
				return new \WP_Error( 'invalid_color_hex', sprintf( __( '%s is not a valid hexadecimal color.', 'wpdlib' ), \WPDLib\FieldTypes\Manager::format( $val, 'string', 'output' ) ) );
			}

			return strtolower( $val );
		}
	}

}
