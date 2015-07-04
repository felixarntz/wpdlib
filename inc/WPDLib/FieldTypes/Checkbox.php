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

if ( ! class_exists( 'WPDLib\FieldTypes\Checkbox' ) ) {

	class Checkbox extends \WPDLib\FieldTypes\Base {
		public function display( $val, $echo = true ) {
			$args = $this->args;
			if ( $val ) {
				$args['checked'] = true;
			}

			$output = '<input type="' . $this->type . '"' . \WPDLib\FieldTypes\Manager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( $val === null ) {
				return false;
			}

			return \WPDLib\FieldTypes\Manager::format( $val, 'boolean', 'input' );
		}

		public function parse( $val, $formatted = false ) {
			if ( $formatted ) {
				return \WPDLib\FieldTypes\Manager::format( $val, 'boolean', 'output' );
			}

			return \WPDLib\FieldTypes\Manager::format( $val, 'boolean', 'input' );
		}
	}

}
