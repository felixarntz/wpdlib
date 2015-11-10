<?php
/**
 * @package WPDLib
 * @version 0.5.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\FieldTypes\Manager as FieldManager;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Datetime' ) ) {
	/**
	 * Class for a datetime field.
	 *
	 * The class is also used as base class for date fields and time fields.
	 *
	 * @since 0.5.0
	 */
	class Datetime extends Base {

		/**
		 * @since 0.5.0
		 * @var array Holds the locale settings related to date formatting.
		 */
		protected static $locale = null;

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
			) );
			parent::__construct( $type, $args );
		}

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param string $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['value'] = $this->parse( $val, true );

			$output = '<input type="text"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		/**
		 * Validates a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return string|WP_Error the validated field value or an error object
		 */
		public function validate( $val = null ) {
			if ( ! $val ) {
				return '';
			}

			if ( 'time' != $this->type ) {
				$val = $this->untranslate( $val );
			}

			$value = $this->validate_min( $val );
			if ( is_wp_error( $value ) ) {
				return $value;
			}

			return $this->validate_max( $val );
		}

		/**
		 * Parses a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return string the correctly parsed value
		 */
		public function parse( $val, $formatted = false ) {
			if ( ! $val ) {
				return '';
			}

			$timestamp = strtotime( $val );

			if ( $formatted ) {
				if ( ! is_array( $formatted ) ) {
					$formatted = array();
				}
				$formatted = wp_parse_args( $formatted, array(
					'format'		=> '',
				) );
				return FieldManager::format( $timestamp, $this->type, 'output', $formatted );
			}
			return FieldManager::format( $timestamp, $this->type, 'input' );
		}

		/**
		 * Enqueues required assets for the field type.
		 *
		 * The function also generates script vars to be applied in `wp_localize_script()`.
		 *
		 * @since 0.5.0
		 * @return array array which can (possibly) contain a 'dependencies' array and a 'script_vars' array
		 */
		public function enqueue_assets() {
			if ( self::is_enqueued( __CLASS__ ) ) {
				return array();
			}

			$assets_url = ComponentManager::get_base_url() . '/assets';
			$version = ComponentManager::get_dependency_info( 'datetimepicker', 'version' );

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

		/**
		 * Validates that a value is not lower than the minimum allowed.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return string|WP_Error the validated field value or an error object
		 */
		protected function validate_min( $val ) {
			$value = FieldManager::format( strtotime( $val ), $this->type, 'input' );

			$timestamp_min = $this->parse_timestamp( $this->args['min'] );
			if ( null === $timestamp_min ) {
				return $value;
			}

			if ( $value < $this->format_timestamp( $timestamp_min ) ) {
				return new WPError( 'invalid_' . $this->type . '_too_small', sprintf( __( 'The date %1$s is invalid. It must not occur earlier than %2$s.', 'wpdlib' ), FieldManager::format( $val, $this->type, 'output' ), FieldManager::format( $timestamp_min, $this->type, 'output' ) ) );
			}

			return $value;
		}

		/**
		 * Validates that a value is not higher than the maximum allowed.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return string|WP_Error the validated field value or an error object
		 */
		protected function validate_max( $val ) {
			$value = FieldManager::format( strtotime( $val ), $this->type, 'input' );

			$timestamp_max = $this->parse_timestamp( $this->args['max'] );
			if ( null === $timestamp_max ) {
				return $value;
			}

			if ( $value > $this->format_timestamp( $timestamp_max ) ) {
				return new WPError( 'invalid_' . $this->type . '_too_big', sprintf( __( 'The date %1$s is invalid. It must not occur later than %2$s.', 'wpdlib' ), FieldManager::format( $val, $this->type, 'output' ), FieldManager::format( $timestamp_max, $this->type, 'output' ) ) );
			}

			return $value;
		}

		/**
		 * Parses date format string into a timestamp.
		 *
		 * @since 0.5.0
		 * @param string $val the value to parse
		 * @return integer|null the timestamp or null if transforming not possible
		 */
		protected function parse_timestamp( $val ) {
			return ! empty( $val ) ? strtotime( $val ) : null;
		}

		/**
		 * Formats a timestamp into a date format string ready for saving to the database.
		 *
		 * @since 0.5.0
		 * @param integer $val the timestamp to format
		 * @return string|null the date format string or null if formatting not possible
		 */
		protected function format_timestamp( $val ) {
			return $val !== null ? FieldManager::format( $val, $this->type, 'input' ) : null;
		}

		/**
		 * Untranslates a date format string.
		 *
		 * WordPress localizes date format strings.
		 * In the actual field controls, the value is displayed in proper localization.
		 * This function tries to untranslate everything using the locale settings.
		 * This is needed so that it can be correctly parsed into a timestamp (since non-English months are not recognized for example).
		 *
		 * @since 0.5.0
		 * @see WPDLib\FieldTypes\Datetime::untranslate_replace()
		 * @param string $val the date format string to untranslate
		 * @return string the date format string in English language
		 */
		protected function untranslate( $val ) {
			self::maybe_init_locale();

			return preg_replace_callback( '/[A-Za-z]+/', array( $this, 'untranslate_replace' ), $val );
		}

		/**
		 * Callback for the `preg_replace_callback()` call in the `untranslate()` method.
		 *
		 * It looks at the localized parts of the date format string and replaces them by their English equivalents.
		 *
		 * @since 0.5.0
		 * @param array $matches the matches from the regular expression
		 * @return string the replacement
		 */
		protected function untranslate_replace( $matches ) {
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
		}

		/**
		 * Sets up the class' static locale object if it has not been setup yet.
		 *
		 * @since 0.5.0
		 */
		protected static function maybe_init_locale() {
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
		}
	}

}
