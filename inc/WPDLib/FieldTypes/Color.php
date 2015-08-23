<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Color' ) ) {

	class Color extends Base {
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['maxlength'] = 7;
			$args['value'] = $val;

			/*$text_args = array(
				'id'	=> $args['id'] . '-' . $this->type . '-viewer',
				'class'	=> 'wpdlib-input-' . $this->type . '-viewer',
				'value'	=> $args['value'],
			);

			$output = '<input type="text"' . FieldManager::make_html_attributes( $text_args, false, false ) . ' />';
			$output .= '<input type="' . $this->type . '"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';*/

			$output = '<input type="text"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

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
				return new WPError( 'invalid_color_hex', sprintf( __( '%s is not a valid hexadecimal color.', 'wpdlib' ), FieldManager::format( $val, 'string', 'output' ) ) );
			}

			return strtolower( $val );
		}

		public function enqueue_assets() {
			if ( self::is_enqueued( __CLASS__ ) ) {
				return array();
			}

			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			return array(
				'dependencies'	=> array( 'wp-color-picker' ),
			);
		}
	}

}
