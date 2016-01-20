<?php
/**
 * @package WPDLib
 * @version 0.6.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Email' ) ) {
	/**
	 * Class for an email field.
	 *
	 * @since 0.5.0
	 */
	class Email extends Base {

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

			$input = sanitize_email( $val );
			$val = is_email( $input );
			if ( ! $val ) {
				return new WPError( 'invalid_email', sprintf( __( '%s is not a valid email address.', 'wpdlib' ), \WPDLib\FieldTypes\Manager::format( $input, 'string', 'output' ) ) );
			}

			return $val;
		}
	}

}
