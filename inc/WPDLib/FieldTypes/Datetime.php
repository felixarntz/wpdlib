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
		protected static $locale = null;

		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'min'	=> '',
				'max'	=> '',
			) );
			parent::__construct( $type, $args );
		}

		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['value'] = $this->parse( $val, true );

			$output = '<input type="text"' . \WPDLib\FieldTypes\Manager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( $val === null ) {
				return \WPDLib\FieldTypes\Manager::format( current_time( 'timestamp' ), $this->type, 'input' );
			}

			if ( 'time' != $this->type ) {
				$val = $this->untranslate( $val );
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

		protected function untranslate( $val ) {
			if ( self::$locale === null ) {
				global $wp_locale;

				self::$locale = array(
					'weekday'			=> array(
						'Sunday'			=> $wp_locale->weekday[0],
						'Monday'			=> $wp_locale->weekday[1],
						'Tuesday'			=> $wp_locale->weekday[2],
						'Wednesday'			=> $wp_locale->weekday[3],
						'Thursday'			=> $wp_locale->weekday[4],
						'Friday'			=> $wp_locale->weekday[5],
						'Saturday'			=> $wp_locale->weekday[6],
					),
					'weekday_initial'	=> $wp_locale->weekday_initial,
					'weekday_abbrev'	=> $wp_locale->weekday_abbrev,
					'month'				=> array(
						'January'			=> $wp_locale->month['01'],
						'February'			=> $wp_locale->month['02'],
						'March'				=> $wp_locale->month['03'],
						'April'				=> $wp_locale->month['04'],
						'May'				=> $wp_locale->month['05'],
						'June'				=> $wp_locale->month['06'],
						'July'				=> $wp_locale->month['07'],
						'August'			=> $wp_locale->month['08'],
						'September'			=> $wp_locale->month['09'],
						'October'			=> $wp_locale->month['10'],
						'November'			=> $wp_locale->month['11'],
						'December'			=> $wp_locale->month['12'],
					),
					'month_abbrev'		=> $wp_locale->month_abbrev,
				);
			}

			$val = preg_replace_callback( '/[A-Za-z]+/', function( $matches ) {
				$term = $matches[0];

				if ( $key = array_search( $term, self::$locale['weekday_initial'] ) ) {
					if ( $key = array_search( $key, self::$locale['weekday'] ) ) {
						return $key;
					}
				}

				if ( $key = array_search( $term, self::$locale['weekday_abbrev'] ) ) {
					if ( $key = array_search( $key, self::$locale['weekday'] ) ) {
						return $key;
					}
				}

				if ( $key = array_search( $term, self::$locale['weekday'] ) ) {
					return $key;
				}

				if ( $key = array_search( $term, self::$locale['month_abbrev'] ) ) {
					if ( $key = array_search( $key, self::$locale['month'] ) ) {
						return $key;
					}
				}

				if ( $key = array_search( $term, self::$locale['month'] ) ) {
					return $key;
				}

				return $term;
			}, $val );

			return $val;
		}
	}

}
