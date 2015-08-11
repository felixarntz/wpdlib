<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Email' ) ) {

	class Email extends Base {
		public function validate( $val = null ) {
			if ( $val === null ) {
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
