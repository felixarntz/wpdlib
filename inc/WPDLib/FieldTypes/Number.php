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

if ( ! class_exists( 'WPDLib\FieldTypes\Number' ) ) {

	class Number extends \WPDLib\FieldTypes\Base {
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'min'	=> '',
				'max'	=> '',
				'step'	=> 1,
			) );
			parent::__construct( $type, $args );
		}

		public function validate( $val = null ) {
			$format = 'float';
			if ( is_int( $this->args['step'] ) ) {
				$format = 'int';
			}

			if ( $val === null ) {
				return $format == 'int' ? 0 : 0.0;
			}

			$val = \WPDLib\FieldTypes\Manager::format( $val, $format, 'input' );

			if ( ! empty( $this->args['step'] ) && $val % \WPDLib\FieldTypes\Manager::format( $this->args['step'], $format, 'input' ) != 0 ) {
				return new \WP_Error( 'invalid_number_step', sprintf( __( 'The number %1$s is invalid since it is not divisible by %2$s.', 'wpdlib' ), \WPDLib\FieldTypes\Manager::format( $val, $format, 'output' ), \WPDLib\FieldTypes\Manager::format( $this->args['step'], $format, 'output' ) ) );
			}

			if ( ! empty( $this->args['min'] ) && $val < \WPDLib\FieldTypes\Manager::format( $this->args['min'], $format, 'input' ) ) {
				return new \WP_Error( 'invalid_number_too_small', sprintf( __( 'The number %1$s is invalid. It must be greater than or equal to %2$s.', 'wpdlib' ), \WPDLib\FieldTypes\Manager::format( $val, $format, 'output' ), \WPDLib\FieldTypes\Manager::format( $this->args['min'], $format, 'output' ) ) );
			}

			if ( ! empty( $this->args['max'] ) && $val > \WPDLib\FieldTypes\Manager::format( $this->args['max'], $format, 'input' ) ) {
				return new \WP_Error( 'invalid_number_too_big', sprintf( __( 'The number %1$s is invalid. It must be lower than or equal to %2$s.', 'wpdlib' ), \WPDLib\FieldTypes\Manager::format( $val, $format, 'output' ), \WPDLib\FieldTypes\Manager::format( $this->args['max'], $format, 'output' ) ) );
			}

			return $val;
		}

		public function parse( $val, $formatted = false ) {
			$format = 'float';
			if ( is_int( $this->args['step'] ) ) {
				$format = 'int';
			}

			if ( $formatted ) {
				return \WPDLib\FieldTypes\Manager::format( $val, $format, 'output' );
			}

			return \WPDLib\FieldTypes\Manager::format( $val, $format, 'input' );
		}
	}

}
