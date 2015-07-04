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

if ( ! class_exists( 'WPDLib\FieldTypes\Url' ) ) {

	class Url extends \WPDLib\FieldTypes\Base {
		public function validate( $val = null ) {
			if ( $val === null ) {
				return '';
			}

			$sanitized_url = sanitize_url( $val );
			if ( $sanitized_url != $val ) {
				return new \WP_Error( 'invalid_url', sprintf( __( '%s is not a valid URL.', 'wpdlib' ), \WPDLib\FieldTypes\Manager::format( $val, 'string', 'output' ) ) );
			}

			return \WPDLib\FieldTypes\Manager::format( $sanitized_url, 'string', 'output' );
		}
	}

}
