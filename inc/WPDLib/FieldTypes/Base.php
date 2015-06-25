<?php

namespace WPDLib\FieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Base' ) ) {

	class Base {
		private $type = '';

		public function __construct( $type ) {
			$this->type = $type;
		}

		public abstract function display( $args, $echo = true ) {
			$args = wp_parse_args( $args, array(
				'id'		=> '',
				'name'		=> '',
				'value'		=> '',
			) );

			extract( $args );

			if ( ! empty( $id ) ) {
				$id = ' id="' . $id . '"';
			}

			if ( ! empty( $name ) ) {
				$name = ' name="' . $name . '"';
			}

			if ( ! empty( $value ) ) {
				$value = ' value="' . esc_attr( $value ) . '"';
			}

			$output = '<input type="' . $this->type . '"' . $id . $name . $value . '>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( $val === null ) {
				$val = '';
			}
			return wp_kses_post( $val );
		}

		public function parse( $val, $formatted = false ) {
			return $val;
		}

		public function enqueue_assets() {

		}
	}

}
