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

			$output = '<input type="text"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( ! $val ) {
				return '';
			}

			if ( ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i', $val ) ) {
				return new WPError( 'invalid_color_hex', sprintf( __( '%s is not a valid hexadecimal color.', 'wpdlib' ), FieldManager::format( $val, 'string', 'output' ) ) );
			}

			return strtolower( $val );
		}

		public function parse( $val, $formatted = false ) {
			if ( ! $val ) {
				return '';
			}

			if ( $formatted ) {
				if ( ! is_array( $formatted ) ) {
					$formatted = array();
				}
				$formatted = wp_parse_args( array(
					'mode'		=> 'text',
				) );
				switch ( $formatted['mode'] ) {
					case 'color':
						return '<div style="display:inline-block;width:64px;height:48px;background-color:' . $val . ';"></div>';
					case 'text':
					default:
						return FieldManager::format( $val, 'string', 'output' );
				}
			}

			return FieldManager::format( $val, 'string', 'input' );
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
