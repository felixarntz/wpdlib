<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Url' ) ) {

	class Url extends Base {
		public function validate( $val = null ) {
			if ( ! $val ) {
				return '';
			}

			return FieldManager::format( $val, 'url', 'input' );
		}

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
