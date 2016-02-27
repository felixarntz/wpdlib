<?php
/**
 * @package WPDLib
 * @version 0.6.2
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Checkbox' ) ) {
	/**
	 * Class for a checkbox field.
	 *
	 * @since 0.5.0
	 */
	class Checkbox extends Base {

		/**
		 * Class constructor.
		 *
		 * For an overview of the supported arguments, please read the Field Types Reference.
		 *
		 * @since 0.5.0
		 * @param string $type the field type
		 * @param array $args array of field type arguments
		 */
		public function __construct( $type, $args ) {
			parent::__construct( $type, $args );
			if ( isset( $args['label'] ) ) {
				$this->args['label'] = $args['label'];
			} else {
				$this->args['label'] = __( 'Enable?', 'wpdlib' );
			}
		}

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param bool $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$label = $args['label'];
			unset( $args['label'] );
			unset( $args['placeholder'] );
			if ( $val ) {
				$args['checked'] = true;
			}
			$args = array_merge( $args, $this->data_atts );

			$output = '<label>';
			$output .= '<input type="' . $this->type . '"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';
			$output .= esc_html( $label );
			$output .= '</label>';

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
		 * @return bool the validated field value
		 */
		public function validate( $val = null ) {
			if ( ! $val ) {
				return false;
			}

			return FieldManager::format( $val, 'boolean', 'input' );
		}

		/**
		 * Checks whether a value for the field is considered empty.
		 *
		 * This function is needed to check whether a required field has been properly filled.
		 *
		 * @since 0.5.0
		 * @param bool $val the current value of the field
		 * @return bool whether the value is considered empty
		 */
		public function is_empty( $val ) {
			// a checkbox is never considered 'empty'
			return false;
		}

		/**
		 * Parses a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return bool|string the correctly parsed value (a string if $formatted is true)
		 */
		public function parse( $val, $formatted = false ) {
			if ( $formatted ) {
				if ( ! is_array( $formatted ) ) {
					$formatted = array();
				}
				$formatted = wp_parse_args( array(
					'mode'		=> 'text',
				) );
				switch ( $formatted['mode'] ) {
					case 'tick':
						if ( $val ) {
							return '&#10004;';
						}
						return '';
					case 'text':
					default:
						return FieldManager::format( $val, 'boolean', 'output' );
				}
			}

			return FieldManager::format( $val, 'boolean', 'input' );
		}
	}

}
