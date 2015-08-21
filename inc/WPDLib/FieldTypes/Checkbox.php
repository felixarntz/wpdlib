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

if ( ! class_exists( 'WPDLib\FieldTypes\Checkbox' ) ) {

	class Checkbox extends Base {
		public function __construct( $type, $args ) {
			parent::__construct( $type, $args );
			if ( isset( $args['label'] ) ) {
				$this->args['label'] = $args['label'];
			} else {
				$this->args['label'] = __( 'Enable?', 'wpdlib' );
			}
		}

		public function display( $val, $echo = true ) {
			$args = $this->args;
			$label = $args['label'];
			unset( $args['label'] );
			unset( $args['placeholder'] );
			if ( $val ) {
				$args['checked'] = true;
			}

			$output = '<label>';
			$output = '<input type="' . $this->type . '"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';
			$output = esc_html( $label );
			$output = '</label>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( $val === null ) {
				return false;
			}

			return FieldManager::format( $val, 'boolean', 'input' );
		}

		public function is_empty( $val ) {
			return false;
		}

		public function parse( $val, $formatted = false ) {
			if ( $formatted ) {
				return FieldManager::format( $val, 'boolean', 'output' );
			}

			return FieldManager::format( $val, 'boolean', 'input' );
		}
	}

}
