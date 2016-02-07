<?php
/**
 * @package WPDLib
 * @version 0.6.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;
use WPDLib\Util\Util;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Number' ) ) {
	/**
	 * Class for a number field.
	 *
	 * The class is also used as base class for range fields.
	 *
	 * @since 0.5.0
	 */
	class Number extends Base {

		/**
		 * Class constructor.
		 *
		 * For an overview of the supported arguments, please read the Field Types Reference.
		 *
		 * @since 0.5.0
		 * @param string $type the field type
		 * @param array $args array of field type arguments
		 */
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'min'	=> '',
				'max'	=> '',
				'step'	=> 1,
			) );
			parent::__construct( $type, $args );
		}

		/**
		 * Validates a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return integer|float|WP_Error the validated field value or an error object
		 */
		public function validate( $val = null ) {
			$format = 'float';
			$zero = 0.0;
			if ( is_int( $this->args['step'] ) ) {
				$format = 'int';
				$zero = 0;
			}

			if ( ! $val ) {
				if ( 'int' === $format ) {
					if ( $this->args['min'] > 0 ) {
						return intval( $this->args['min'] );
					}
					return 0;
				} else {
					if ( $this->args['min'] > 0 ) {
						return floatval( $this->args['min'] );
					}
					return 0.0;
				}
			}

			if ( 'int' === $format ) {
				$val = intval( $val );
			} else {
				$val = floatval( $val );
			}

			if ( ! empty( $this->args['step'] ) && ! Util::is_rest_zero( $val, $this->args['step'] ) ) {
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

		/**
		 * Parses a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return integer|float|string the correctly parsed value (string if $formatted is true)
		 */
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
