<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Number' ) ) {

	class Number extends Base {
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

			if ( ! $val ) {
				if ( 'int' == $format ) {
					if ( $this->args['min'] > 0 ) {
						return absint( $this->args['min'] );
					}
					return 0;
				} else {
					if ( $this->args['min'] > 0 ) {
						return floatval( $this->args['min'] );
					}
					return 0.0;
				}
			}

			$val = FieldManager::format( $val, $format, 'input' );

			if ( ! empty( $this->args['step'] ) && $val % FieldManager::format( $this->args['step'], $format, 'input' ) != 0 ) {
				return new WPError( 'invalid_number_step', sprintf( __( 'The number %1$s is invalid since it is not divisible by %2$s.', 'wpdlib' ), FieldManager::format( $val, $format, 'output' ), FieldManager::format( $this->args['step'], $format, 'output' ) ) );
			}

			if ( ! empty( $this->args['min'] ) && $val < FieldManager::format( $this->args['min'], $format, 'input' ) ) {
				return new WPError( 'invalid_number_too_small', sprintf( __( 'The number %1$s is invalid. It must be greater than or equal to %2$s.', 'wpdlib' ), FieldManager::format( $val, $format, 'output' ), FieldManager::format( $this->args['min'], $format, 'output' ) ) );
			}

			if ( ! empty( $this->args['max'] ) && $val > FieldManager::format( $this->args['max'], $format, 'input' ) ) {
				return new WPError( 'invalid_number_too_big', sprintf( __( 'The number %1$s is invalid. It must be lower than or equal to %2$s.', 'wpdlib' ), FieldManager::format( $val, $format, 'output' ), FieldManager::format( $this->args['max'], $format, 'output' ) ) );
			}

			return $val;
		}

		public function parse( $val, $formatted = false ) {
			$format = 'float';
			if ( is_int( $this->args['step'] ) ) {
				$format = 'int';
			}

			if ( $formatted ) {
				return FieldManager::format( $val, $format, 'output' );
			}

			return FieldManager::format( $val, $format, 'input' );
		}
	}

}
