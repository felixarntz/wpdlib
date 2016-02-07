<?php
/**
 * @package WPDLib
 * @version 0.6.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Url' ) ) {
	/**
	 * Class for a URL field.
	 *
	 * @since 0.5.0
	 */
	class Url extends Base {
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

			return FieldManager::format( $val, 'url', 'input' );
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
			if ( $formatted ) {
				if ( ! is_array( $formatted ) ) {
					$formatted = array();
				}
				$formatted = wp_parse_args( $formatted, array(
					'mode'	=> 'text',
				) );
				switch ( $formatted['mode'] ) {
					case 'link':
						return '<a href="' . esc_url( $val ) . '" target="_blank">' . esc_url( $val ) . '</a>';
					case 'text':
					default:
						return FieldManager::format( $val, 'url', 'output' );
				}
			}

			return FieldManager::format( $val, 'url', 'input' );
		}
	}

}
