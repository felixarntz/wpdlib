<?php
/**
 * @package WPDLib
 * @version 0.6.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\Util\Util;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Manager' ) ) {
	/**
	 * This class manages all field types and provides some utility functions for fields.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	final class Manager {

		/**
		 * Status whether the class has been initialized.
		 *
		 * @since 0.6.0
		 * @var boolean
		 */
		private static $initialized = false;

		/**
		 * Initialization function.
		 *
		 * This has to be called by every plugin using WPDLib.
		 *
		 * @since 0.6.0
		 */
		public static function init() {
			if ( self::$initialized ) {
				return;
			}

			add_action( 'wp_ajax_get-attachment-by-url', array( __CLASS__, 'ajax_get_attachment_by_url' ), 15 );

			self::$initialized = true;
		}

		/**
		 * Returns a new field type instance.
		 *
		 * @since 0.5.0
		 * @param array $args the field arguments
		 * @param bool $repeatable whether the field will be part of a repeatable field (default is false)
		 * @return WPDLib\FieldTypes\Base|null the field type instance or null if the field type is invalid or missing
		 */
		public static function get_instance( $args, $repeatable = false ) {
			if ( ! isset( $args['type'] ) ) {
				return null;
			}

			$field_type = self::validate_field_type( $args['type'], $repeatable );
			if ( null === $field_type ) {
				return null;
			}

			$field_args = self::validate_field_args( $args );

			if ( class_exists( 'WPDLib\FieldTypes\\' . ucfirst( $field_type ) ) ) {
				$class_name = '\WPDLib\FieldTypes\\' . ucfirst( $field_type );
				return new $class_name( $field_type, $field_args );
			}

			return new Base( $field_type, $field_args );
		}

		/**
		 * Returns a list of available field types.
		 *
		 * @since 0.5.0
		 * @return array an array of available field types
		 */
		public static function get_field_types() {
			return array(
				'checkbox',
				'radio',
				'multibox',
				'select',
				'multiselect',
				'media',
				'map',
				'textarea',
				'wysiwyg',
				'datetime',
				'date',
				'time',
				'color',
				'range',
				'number',
				'url',
				'email',
				'tel',
				'text',
				'repeatable',
			);
		}

		/**
		 * Enqueues assets for one or more fields.
		 *
		 * This function should be used from all places in the WordPress admin that contain field types from WPDLib.
		 * All fields active on the screen should be passed to the function.
		 *
		 * @since 0.5.0
		 * @param array $fields the field type objects to enqueue assets for
		 */
		public static function enqueue_assets( $fields = array() ) {
			$assets_url = ComponentManager::get_base_url() . '/assets';
			$version = ComponentManager::get_info( 'version' );

			list( $dependencies, $script_vars ) = self::get_dependencies_and_script_vars( $fields );
			if ( ! in_array( 'jquery', $dependencies ) ) {
				$dependencies[] = 'jquery';
			}

			wp_enqueue_style( 'wpdlib-fields', $assets_url . '/fields.min.css', array(), $version );

			wp_enqueue_script( 'wpdlib-fields', $assets_url . '/fields.min.js', $dependencies, $version, true );

			wp_localize_script( 'wpdlib-fields', '_wpdlib_data', $script_vars );
		}

		/**
		 * Returns an array of Javascript dependencies and script variables for several field types.
		 *
		 * All dependencies must be loaded, otherwise the main WPDLib script will not be loaded.
		 * The field type classes should handle that automatically though.
		 *
		 * The script vars are put into a JSON object '_wpdlib_data' by `wp_localize_script()`.
		 *
		 * @since 0.5.0
		 * @param array $fields the field type objects to get dependencies and script vars for
		 * @return array an array containing a dependencies array and a script vars array
		 */
		public static function get_dependencies_and_script_vars( $fields = array() ) {
			$dependencies = array();
			$script_vars = array();

			foreach ( $fields as $field ) {
				$asset_data = $field->enqueue_assets();
				if ( isset( $asset_data['dependencies'] ) ) {
					foreach ( $asset_data['dependencies'] as $dependency ) {
						$dependencies[] = $dependency;
					}
				}
				if ( isset( $asset_data['script_vars'] ) ) {
					foreach ( $asset_data['script_vars'] as $key => $value ) {
						if ( isset( $script_vars[ $key ] ) && is_array( $script_vars[ $key ] ) && is_array( $value ) ) {
							$script_vars[ $key ] = array_merge( $script_vars[ $key ], $value );
						} else {
							$script_vars[ $key ] = $value;
						}
					}
				}
			}

			$dependencies = array_unique( $dependencies );

			return array( $dependencies, $script_vars );
		}

		/**
		 * Transforms an array of HTML attributes into an attributes string.
		 *
		 * The attributes are printed in a nicely-sorted format.
		 *
		 * @since 0.5.0
		 * @see WPDLib\FieldTypes\Manager::sort_html_attributes()
		 * @param array $atts array of arguments and their values
		 * @param bool $html5 whether to output arguments in html5 syntax (default is true)
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML attributes string
		 */
		public static function make_html_attributes( $atts, $html5 = true, $echo = true ) {
			$output = '';

			$bool_atts = array_filter( $atts, 'is_bool' );

			$atts = array_diff_key( $atts, $bool_atts );
			uksort( $atts, array( __CLASS__, 'sort_html_attributes' ) );

			foreach ( $atts as $key => $value ) {
				if ( is_array( $value ) ) {
					continue;
				}
				if ( is_string( $value ) && empty( $value ) ) {
					continue;
				}
				$output .= ' ' . $key . '="' . esc_attr( $value ) . '"';
			}

			foreach ( $bool_atts as $key => $active ) {
				if ( $active ) {
					if ( $html5 ) {
						$output .= ' ' . $key;
					} else {
						$output .= ' ' . $key . '="' . esc_attr( $key ) . '"';
					}
				}
			}

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		/**
		 * Formats a value depending on certain criteria.
		 *
		 * Valid types are:
		 * - string
		 * - html
		 * - url
		 * - boolean / bool
		 * - integer / int
		 * - float / double
		 * - datetime
		 * - date
		 * - time
		 * - byte
		 *
		 * @since 0.5.0
		 * @param mixed $value the value to format
		 * @param string $type the type of the value to format
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @param array $args additional formatting args (optional, they depend on the $type)
		 * @return mixed the formatted value
		 */
		public static function format( $value, $type, $mode = 'input', $args = array() ) {
			$mode = 'output' === $mode ? 'output' : 'input';

			$formatted = $value;

			switch ( $type ) {
				case 'string':
					return self::format_string( $value, $mode );
				case 'html':
					return self::format_html( $value, $mode );
				case 'url':
					return self::format_url( $value, $mode );
				case 'boolean':
				case 'bool':
					return self::format_bool( $value, $mode );
				case 'integer':
				case 'int':
					return self::format_int( $value, $mode, $args );
				case 'float':
				case 'double':
					return self::format_float( $value, $mode, $args );
				case 'date':
				case 'time':
				case 'datetime':
					return self::format_datetime( $value, $mode, $type, $args );
				case 'byte':
					return self::format_byte( $value, $mode, $args );
				default:
			}

			return $formatted;
		}

		/**
		 * AJAX handler to retrieve an attachment by URL.
		 *
		 * WordPress does not have an AJAX function like this, so WPDLib adds it.
		 * This is required for the media field type to work properly.
		 *
		 * @since 0.6.0
		 */
		public static function ajax_get_attachment_by_url() {
			if ( ! isset( $_REQUEST['url'] ) ) {
				wp_send_json_error();
			}

			$id = attachment_url_to_postid( $_REQUEST['url'] );
			if ( ! $id ) {
				wp_send_json_error();
			}

			$_REQUEST['id'] = $id;

			wp_ajax_get_attachment();
			die();
		}

		/**
		 * Validates a field type.
		 *
		 * If the field type should be part of a repeatable, some additional checks must be made.
		 *
		 * @since 0.5.0
		 * @param string $field_type the field type to validate
		 * @param bool $repeatable whether the field will be part of a repeatable field (default is false)
		 * @return string|null the validated field type or null if the field type is invalid
		 */
		private static function validate_field_type( $field_type, $repeatable = false ) {
			$field_types = self::get_field_types();
			if ( ! in_array( $field_type, $field_types ) ) {
				return null;
			}

			if ( $repeatable ) {
				return self::map_repeatable_type( $field_type );
			}

			return $field_type;
		}

		/**
		 * Checks a field type for inclusion in a repeatable field.
		 *
		 * There are a few field types which are not allowed in a repeatable fields.
		 * Some others are automatically replaced by different field types with a similar data structure.
		 *
		 * @since 0.5.0
		 * @param string $field_type the field type to validate
		 * @return string|null the validated field type or null if the field type is invalid
		 */
		private static function map_repeatable_type( $field_type ) {
			$non_repeatable_types = array(
				'wysiwyg',
				'repeatable',
			);

			if ( in_array( $field_type, $non_repeatable_types ) ) {
				return null;
			}

			$replace_types = array(
				'radio'		=> 'select',
				'multibox'	=> 'multiselect',
				'textarea'	=> 'text',
			);

			if ( isset( $replace_types[ $field_type ] ) ) {
				$field_type = $replace_types[ $field_type ];
			}

			return $field_type;
		}

		/**
		 * Validates arguments for a field type.
		 *
		 * The function checks a whitelist of arguments.
		 * The only other thing it allows are data attributes.
		 *
		 * @since 0.5.0
		 * @param array $args the field type arguments
		 * @return array the validated field type arguments
		 */
		private static function validate_field_args( $args ) {
			$field_keys = array(
				'id',
				'name',
				'class',
				'placeholder',
				'required',
				'readonly',
				'disabled',
				'options',
				'label',
				'min',
				'max',
				'step',
				'store',
				'mime_types',
				'repeatable',
			);

			$data_args = array();
			foreach ( $args as $key => $value ) {
				if ( strpos( $key, 'data-' ) === 0 ) {
					$data_args[ $key ] = $value;
				}
			}

			return array_merge( $data_args, array_intersect_key( $args, array_flip( $field_keys ) ) );
		}

		/**
		 * Callback function to sort HTML attributes.
		 *
		 * Attributes have the following order:
		 * - id
		 * - name
		 * - class
		 * - any data attributes
		 * - rel
		 * - type
		 * - value
		 * - href
		 * - any other attributes
		 *
		 * Boolean attributes are not handled by this function.
		 * They are automatically appended as the last attributes.
		 *
		 * @since 0.5.0
		 * @see WPDLib\FieldTypes\Manager::sort_html_data_attributes()
		 * @see WPDLib\FieldTypes\Manager::sort_html_priority_attributes()
		 * @param string $a the first attribute to compare
		 * @param string $b the second attribute to compare
		 * @return integer -1 if $a < $b, 1 if $a > $b, otherwise 0
		 */
		private static function sort_html_attributes( $a, $b ) {
			if ( $a == $b ) {
				return 0;
			}

			$priorities = array( 'id', 'name', 'class' );

			if ( strpos( $a, 'data-' ) === 0 || strpos( $b, 'data-' ) === 0 ) {
				return self::sort_html_data_attributes( $a, $b, $priorities );
			}

			$priorities = array_merge( $priorities, array( 'rel', 'type', 'value', 'href' ) );

			return self::sort_html_priority_attributes( $a, $b, $priorities );
		}

		/**
		 * Sorts HTML data attributes.
		 *
		 * Attributes within the $priorities should show before the data attributes.
		 * All other attributes should appear after them.
		 *
		 * @since 0.5.0
		 * @param string $a the first attribute to compare
		 * @param string $b the second attribute to compare
		 * @param array $priorities array of high-priority attributes
		 * @return integer -1 if $a < $b, 1 if $a > $b, otherwise 0
		 */
		private static function sort_html_data_attributes( $a, $b, $priorities = array() ) {
			if ( strpos( $a, 'data-' ) === 0 && strpos( $b, 'data-' ) !== 0 ) {
				if ( in_array( $b, $priorities ) ) {
					return 1;
				}
				return -1;
			} elseif ( strpos( $a, 'data-' ) !== 0 && strpos( $b, 'data-' ) === 0 ) {
				if ( in_array( $a, $priorities ) ) {
					return -1;
				}
				return 1;
			}

			return 0;
		}

		/**
		 * Sorts HTML attributes by priority.
		 *
		 * Attributes within the $priorities should show before the other attributes (in that particular order).
		 *
		 * @since 0.5.0
		 * @param string $a the first attribute to compare
		 * @param string $b the second attribute to compare
		 * @param array $priorities array of high-priority attributes
		 * @return integer -1 if $a < $b, 1 if $a > $b, otherwise 0
		 */
		private static function sort_html_priority_attributes( $a, $b, $priorities = array() ) {
			if ( in_array( $a, $priorities ) && ! in_array( $b, $priorities ) ) {
				return -1;
			} elseif ( ! in_array( $a, $priorities ) && in_array( $b, $priorities ) ) {
				return 1;
			} elseif ( in_array( $a, $priorities ) && in_array( $b, $priorities ) ) {
				$key_a = array_search( $a, $priorities );
				$key_b = array_search( $b, $priorities );
				if ( $key_a < $key_b ) {
					return -1;
				} elseif ( $key_a > $key_b ) {
					return 1;
				}
			}

			return 0;
		}

		/**
		 * Formats a string.
		 *
		 * @since 0.5.0
		 * @param string $value the value to format
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @return string the formatted value
		 */
		private static function format_string( $value, $mode = 'input' ) {
			return esc_html( $value );
		}

		/**
		 * Formats a HTML string.
		 *
		 * @since 0.5.0
		 * @param string $value the value to format
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @return string the formatted value
		 */
		private static function format_html( $value, $mode = 'input' ) {
			$formatted = wp_kses_post( $value );

			if ( 'output' === $mode ) {
				$formatted = wpautop( $formatted );
			}

			return $formatted;
		}

		/**
		 * Formats a URL.
		 *
		 * @since 0.5.0
		 * @param string $value the value to format
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @return string the formatted value
		 */
		private static function format_url( $value, $mode = 'input' ) {
			$formatted = esc_html( $value );

			if ( 'output' === $mode ) {
				return esc_url( $formatted );
			}

			return esc_url_raw( $formatted );
		}

		/**
		 * Formats a boolean.
		 *
		 * @since 0.5.0
		 * @param bool $value the value to format
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @return bool|string the formatted value (if $mode == 'output', the bool is formatted as a string)
		 */
		private static function format_bool( $value, $mode = 'input' ) {
			$formatted = self::parse_bool( $value );

			if ( 'output' === $mode ) {
				if ( $formatted ) {
					$formatted = 'true';
				} else {
					$formatted = 'false';
				}
			}

			return $formatted;
		}

		/**
		 * Formats an integer.
		 *
		 * Possible $args:
		 * - positive_only (bool)
		 *
		 * @since 0.5.0
		 * @param integer $value the value to format
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @param array $args additional formatting args (optional)
		 * @return integer|string the formatted value (if $mode == 'output', the integer is formatted as a string)
		 */
		private static function format_int( $value, $mode = 'input', $args = array() ) {
			$positive_only = isset( $args['positive_only'] ) ? (bool) $args['positive_only'] : false;

			$formatted = self::parse_int( $value );
			if ( $positive_only ) {
				$formatted = abs( $formatted );
			}

			if ( 'output' === $mode ) {
				$formatted = number_format_i18n( floatval( $formatted ), 0 );
			}

			return $formatted;
		}

		/**
		 * Formats a float.
		 *
		 * Possible $args:
		 * - positive_only (bool)
		 * - decimals (integer)
		 *
		 * @since 0.5.0
		 * @param float $value the value to format
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @param array $args additional formatting args (optional)
		 * @return float|string the formatted value (if $mode == 'output', the float is formatted as a string)
		 */
		private static function format_float( $value, $mode = 'input', $args = array() ) {
			$positive_only = isset( $args['positive_only'] ) ? (bool) $args['positive_only'] : false;

			$formatted = self::parse_float( $value );
			if ( $positive_only ) {
				$formatted = abs( $formatted );
			}

			if ( 'output' === $mode ) {
				$decimals = isset( $args['decimals'] ) ? absint( $args['decimals'] ) : 2;
				$formatted = number_format_i18n( $formatted, $decimals );
			} else {
				$decimals = isset( $args['decimals'] ) ? absint( $args['decimals'] ) : false;
				if ( $decimals !== false ) {
					$formatted = number_format( $formatted, $decimals );
				}
			}

			return $formatted;
		}

		/**
		 * Formats a date.
		 *
		 * Possible $args:
		 * - format (string)
		 *
		 * The default format is selected depending on the $type parameter.
		 *
		 * @since 0.5.0
		 * @see WPDLib\FieldTypes\Manager::get_default_datetime_format()
		 * @param int|string $value the value to format
		 * @param string $type the formating type; either 'datetime' (default), 'date' or 'time'
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @param array $args additional formatting args (optional)
		 * @return string the formatted value
		 */
		private static function format_datetime( $value, $mode = 'input', $type = 'datetime', $args = array() ) {
			$timestamp = $value;
			if ( ! is_int( $timestamp ) ) {
				$timestamp = mysql2date( 'U', $timestamp );
			}

			$format = isset( $args['format'] ) ? $args['format'] : '';
			if ( empty( $format ) ) {
				$format = self::get_default_datetime_format( $type, $mode );
			}

			return date_i18n( $format, $timestamp );
		}

		/**
		 * Formats a numeric value as a byte value.
		 *
		 * Possible $args:
		 * - decimals (integer)
		 * - base_unit (string, either 'B', 'kB', 'MB', 'GB' or 'TB')
		 *
		 * @since 0.5.0
		 * @see WPDLib\Util\Util::format_unit()
		 * @param integer|float $value the value to format
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @param array $args additional formatting args (optional)
		 * @return float|string the formatted value (if $mode == 'output', the float is formatted as a string)
		 */
		private static function format_byte( $value, $mode = 'input', $args = array() ) {
			if ( 'output' === $mode ) {
				$units = array( 'B', 'kB', 'MB', 'GB', 'TB' );
				$decimals = isset( $args['decimals'] ) ? absint( $args['decimals'] ) : 2;
				$base_unit = isset( $args['base_unit'] ) && in_array( $args['base_unit'], $units ) ? $args['base_unit'] : 'B';

				return Util::format_unit( $value, $units, 1024, $base_unit, $decimals );
			}

			$formatted = self::parse_float( $value );

			$decimals = isset( $args['decimals'] ) ? absint( $args['decimals'] ) : false;
			if ( $decimals !== false ) {
				$formatted = number_format( $formatted, $decimals );
			}

			return $formatted;
		}

		/**
		 * Parses a value into a boolean.
		 *
		 * @since 0.5.0
		 * @param mixed $value the value to parse
		 * @return bool the parsed value
		 */
		private static function parse_bool( $value ) {
			if ( is_int( $value ) ) {
				if ( $value > 0 ) {
					return true;
				}
				return false;
			} elseif ( is_string( $value ) ) {
				if ( ! empty( $value ) ) {
					if ( strtolower( $value ) == 'false' ) {
						return false;
					}

					return true;
				}
				return false;
			}

			return (bool) $value;
		}

		/**
		 * Parses a value into an integer.
		 *
		 * @since 0.5.0
		 * @param mixed $value the value to parse
		 * @return integer the parsed value
		 */
		private static function parse_int( $value ) {
			return intval( $value );
		}

		/**
		 * Parses a value into a float.
		 *
		 * @since 0.5.0
		 * @param mixed $value the value to parse
		 * @return float the parsed value
		 */
		private static function parse_float( $value ) {
			return floatval( $value );
		}

		/**
		 * Returns the default date / time format.
		 *
		 * @since 0.5.0
		 * @param string $type the formating type; either 'datetime', 'date' or 'time'
		 * @param string $mode the formatting mode; either 'input' (default) or 'output'
		 * @return string date format string
		 */
		private static function get_default_datetime_format( $type, $mode = 'input' ) {
			if ( 'output' === $mode ) {
				if ( $type == 'date' ) {
					return get_option( 'date_format' );
				} elseif ( $type == 'time' ) {
					return get_option( 'time_format' );
				} else {
					return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				}
			}

			if ( $type == 'date' ) {
				return 'Ymd';
			} elseif ( $type == 'time' ) {
				return 'His';
			} else {
				return 'YmdHis';
			}
		}
	}

}
