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

			return \WPDLib\FieldTypes\Manager::format( $val, 'url', 'input' );
		}

		public function parse( $val, $formatted = false ) {
			if ( $formatted ) {
				return \WPDLib\FieldTypes\Manager::format( $val, 'url', 'output' );
			}

			return \WPDLib\FieldTypes\Manager::format( $val, 'url', 'input' );
		}
	}

}
