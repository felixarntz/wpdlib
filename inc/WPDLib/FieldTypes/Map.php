<?php
/**
 * WPDLib\FieldTypes\Map class
 *
 * @package WPDLib
 * @subpackage FieldTypes
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.6.0
 */

namespace WPDLib\FieldTypes;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\FieldTypes\Manager as FieldManager;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Map' ) ) {
	/**
	 * Class for a map picker field.
	 *
	 * This implementation can either store an address (default) or coords (lat and lng).
	 *
	 * @since 0.6.0
	 */
	class Map extends Base {

		/**
		 * Class constructor.
		 *
		 * For an overview of the supported arguments, please read the Field Types Reference.
		 *
		 * @since 0.6.0
		 * @param string $type the field type
		 * @param array $args array of field type arguments
		 */
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'store' => 'address',
			) );

			if ( 'coords' !== $args['store'] ) {
				$args['store'] = 'address';
			} elseif ( ! isset( $args['placeholder'] ) ) {
				$args['placeholder'] = __( 'Enter coords in the format &quot;latitude|longitude&quot;', 'wpdlib' );
			}

			parent::__construct( $type, $args );
		}

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.6.0
		 * @param string $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			global $wp_locale;

			$args = $this->args;
			$args['value'] = $val;

			if ( 'coords' === $args['store'] ) {
				$args['value'] = $this->parse( $val, true );
			}

			$args = array_merge( $args, $this->data_atts );

			$args['data-store'] = $args['store'];
			$args['data-decimal_separator'] = $wp_locale->number_format['decimal_point'];

			unset( $args['store'] );

			$output = '<input type="text"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		/**
		 * Validates a value for the field.
		 *
		 * @since 0.6.0
		 * @param mixed $val the current value of the field
		 * @return string|WP_Error the validated field value or an error object
		 */
		public function validate( $val = null ) {
			global $wp_locale;

			if ( ! $val ) {
				return '';
			}

			$orig_val = $val;

			if ( 'coords' === $this->args['store'] ) {
				$val = explode( '|', $val );
				$val = array_filter( array_map( 'trim', $val ) );

				if ( 2 !== count( $val ) ) {
					return new WPError( 'invalid_map_coords_format', sprintf( __( 'The string %s is not in valid geo coordinates format. It must be specified in the format &quot;latitude|longitude&quot;.', 'wpdlib' ), $orig_val ) );
				}

				for ( $i = 0; $i < 2; $i++ ) {
					$val[ $i ] = floatval( str_replace( $wp_locale->number_format['decimal_point'], '.', $val[ $i ] ) );
				}

				if ( -90.0 > $val[0] || 90.0 < $val[0] ) {
					return new WPError( 'invalid_map_coords_latitude', sprintf( __( 'The latitude %1$s is invalid. It must be between %2$s and %3$s.', 'wpdlib' ), FieldManager::format( $val[0], 'float', 'output', array( 'decimals' => 10 ) ), FieldManager::format( -90.0, 'float', 'output' ), FieldManager::format( 90.0, 'float', 'output' ) ) );
				}

				if ( -180.0 > $val[0] || 180.0 < $val[0] ) {
					return new WPError( 'invalid_map_coords_longitude', sprintf( __( 'The longitude %1$s is invalid. It must be between %2$s and %3$s.', 'wpdlib' ), FieldManager::format( $val[0], 'float', 'output', array( 'decimals' => 10 ) ), FieldManager::format( -180.0, 'float', 'output' ), FieldManager::format( 180.0, 'float', 'output' ) ) );
				}

				return (string) $val[0] . '|' . (string) $val[1];
			}

			return FieldManager::format( $val, 'string', 'input' );
		}

		/**
		 * Checks whether a value for the field is considered empty.
		 *
		 * This function is needed to check whether a required field has been properly filled.
		 *
		 * @since 0.6.0
		 * @param string $val the current value of the field
		 * @return bool whether the value is considered empty
		 */
		public function is_empty( $val ) {
			if ( 'coords' === $this->args['store'] ) {
				if ( ! empty( $val ) ) {
					$val = explode( '|' );
					$val = array_filter( array_map( 'trim', $val ) );
					return 2 !== count( $val );
				}
				return true;
			}
			return empty( $val );
		}

		/**
		 * Parses a value for the field.
		 *
		 * @since 0.6.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return string the correctly parsed value
		 */
		public function parse( $val, $formatted = false ) {
			if ( 'coords' === $this->args['store'] ) {
				$val = explode( '|', $val );
				if ( 2 !== count( $val ) ) {
					return '';
				}
				$mode = 'input';
				if ( $formatted ) {
					$mode = 'output';
				}
				for ( $i = 0; $i < 2; $i++ ) {
					$val[ $i ] = FieldManager::format( $val[ $i ], 'float', $mode, array( 'decimals' => 10 ) );
				}
				return $val[0] . '|' . $val[1];
			}

			if ( $formatted ) {
				return FieldManager::format( $val, 'string', 'output' );
			}
			return FieldManager::format( $val, 'string', 'input' );
		}

		/**
		 * Enqueues required assets for the field type.
		 *
		 * The function also generates script vars to be applied in `wp_localize_script()`.
		 *
		 * @since 0.6.0
		 * @return array array which can (possibly) contain a 'dependencies' array and a 'script_vars' array
		 */
		public function enqueue_assets() {
			if ( self::is_enqueued( __CLASS__ ) ) {
				return array();
			}

			$assets_url = ComponentManager::get_base_url() . '/assets';
			$version = ComponentManager::get_dependency_info( 'wp-map-picker', 'version' );

			$gmaps_url = 'https://maps.google.com/maps/api/js';
			$gmaps_args = array(
				'language' => str_replace( '_', '-', get_locale() ),
			);
			if ( ( $api_key = self::get_api_key() ) ) {
				$gmaps_args['key'] = $api_key;
			}
			$gmaps_url = add_query_arg( $gmaps_args, $gmaps_url );

			wp_enqueue_script( 'google-maps', $gmaps_url, array(), false, true );

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_style( 'wp-map-picker', $assets_url . '/vendor/wp-map-picker/wp-map-picker' . $min . '.css', array(), $version );
			wp_enqueue_script( 'wp-map-picker', $assets_url . '/vendor/wp-map-picker/wp-map-picker' . $min . '.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-autocomplete', 'google-maps' ), $version, true );

			return array(
				'dependencies' => array( 'wp-map-picker' ),
			);
		}

		/**
		 * Returns the API key for Google Maps.
		 *
		 * This function contains an empty string by default, but the filter in
		 * the function can be used to add an API key to append to all maps requests.
		 *
		 * @since 0.6.0
		 * @return string Google Maps API Key or an empty string
		 */
		public static function get_api_key() {
			return apply_filters( 'wpdlib_google_maps_api_key', '', get_current_blog_id() );
		}
	}

}
