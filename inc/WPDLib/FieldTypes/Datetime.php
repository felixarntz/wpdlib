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

if ( ! class_exists( 'WPDLib\FieldTypes\Datetime' ) ) {

	class Datetime extends \WPDLib\FieldTypes\Base {
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'min'	=> '',
				'max'	=> '',
			) );
			parent::__construct( $type, $args );
		}

		public function validate( $val = null ) {
			if ( $val === null ) {
				return \WPDLib\FieldTypes\Manager::format( current_time( 'timestamp' ), $this->type, 'input' );
			}

			$timestamp = strtotime( $val );
			$timestamp_min = ! empty( $this->args['min'] ) ? strtotime( $this->args['min'] ) : null;
			$timestamp_max = ! empty( $this->args['max'] ) ? strtotime( $this->args['max'] ) : null;

			$value = \WPDLib\FieldTypes\Manager::format( $timestamp, $this->type, 'input' );
			$value_min = $timestamp_min !== null ? \WPDLib\FieldTypes\Manager::format( $timestamp_min, $this->type, 'input' ) : null;
			$value_max = $timestamp_max !== null ? \WPDLib\FieldTypes\Manager::format( $timestamp_max, $this->type, 'input' ) : null;

			if ( $value_min !== null && $value < $value_min ) {
				return new \WP_Error( 'invalid_' . $this->type . '_too_small', sprintf( __( 'The date %1$s is invalid. It must not occur earlier than %2$s.', 'wpdlib' ), \WPDLib\FieldTypes\Manager::format( $val, $this->type, 'output' ), \WPDLib\FieldTypes\Manager::format( $timestamp_min, $this->type, 'output' ) ) );
			}

			if ( $value_max !== null && $value > $value_max ) {
				return new \WP_Error( 'invalid_' . $this->type . '_too_big', sprintf( __( 'The date %1$s is invalid. It must not occur later than %2$s.', 'wpdlib' ), \WPDLib\FieldTypes\Manager::format( $val, $this->type, 'output' ), \WPDLib\FieldTypes\Manager::format( $timestamp_max, $this->type, 'output' ) ) );
			}

			return $value;
		}

		public function parse( $val, $formatted = false ) {
			$timestamp = strtotime( $val );

			if ( $formatted ) {
				return \WPDLib\FieldTypes\Manager::format( $timestamp, $this->type, 'output' );
			}
			return \WPDLib\FieldTypes\Manager::format( $timestamp, $this->type, 'input' );
		}

		public function enqueue_assets() {
			if ( self::is_enqueued( __CLASS__ ) ) {
				return array();
			}

			$assets_url = \WPDLib\Components\Manager::get_base_url() . '/assets';
			$version = \WPDLib\Components\Manager::get_dependency_info( 'datetimepicker', 'version' );

			wp_enqueue_style( 'datetimepicker', $assets_url . '/vendor/datetimepicker/jquery.datetimepicker.css', array(), $version );
			wp_enqueue_script( 'datetimepicker', $assets_url . '/vendor/datetimepicker/jquery.datetimepicker.js', array( 'jquery' ), $version, true );

			return array(
				'dependencies'	=> array( 'datetimepicker' ),
				'script_vars'	=> array(
					'language'		=> substr( get_locale(), 0, 2 ),
					'date_format'	=> get_option( 'date_format' ),
					'time_format'	=> get_option( 'time_format' ),
					'start_of_week'	=> get_option( 'start_of_week' ),
				),
			);
		}
	}

}
