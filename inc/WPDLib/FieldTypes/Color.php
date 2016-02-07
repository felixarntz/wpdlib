<?php
/**
 * @package WPDLib
 * @version 0.6.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Color' ) ) {
	/**
	 * Class for a color picker field.
	 *
	 * @since 0.5.0
	 */
	class Color extends Base {

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param string $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['maxlength'] = 7;
			$args['value'] = $val;
			$args = array_merge( $args, $this->data_atts );

			$output = '<input type="text"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		/**
		 * Validates a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return string|WP_Error the validated field value or an error object
		 */
		public function validate( $val = null ) {
			if ( ! $val ) {
				return '';
			}

			if ( ! preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i', $val ) ) {
				return new WPError( 'invalid_color_hex', sprintf( __( '%s is not a valid hexadecimal color.', 'wpdlib' ), FieldManager::format( $val, 'string', 'output' ) ) );
			}

			return strtolower( $val );
		}

		/**
		 * Parses a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return string the correctly parsed value
		 */
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
					case 'color-text':
						return '<div style="display:inline-block;padding:5px 10px;background-color:' . $val . ';">' . FieldManager::format( $val, 'string', 'output' ) . '</div>';
					case 'text':
					default:
						return FieldManager::format( $val, 'string', 'output' );
				}
			}

			return FieldManager::format( $val, 'string', 'input' );
		}

		/**
		 * Enqueues required assets for the field type.
		 *
		 * The function also generates script vars to be applied in `wp_localize_script()`.
		 *
		 * @since 0.5.0
		 * @return array array which can (possibly) contain a 'dependencies' array and a 'script_vars' array
		 */
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
