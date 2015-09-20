<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\Util\Util;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Manager' ) ) {

	final class Manager {
		public static function get_instance( $args, $repeatable = false ) {
			if ( ! isset( $args['type'] ) ) {
				return null;
			}

			$field_type = self::validate_field_type( $args['type'] );
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

		public static function get_field_types() {
			return array(
				'checkbox',
				'radio',
				'multibox',
				'select',
				'multiselect',
				'media',
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

		private static function validate_field_type( $field_type ) {
			$field_types = self::get_field_types();
			if ( ! in_array( $field_type, $field_types ) ) {
				return null;
			}

			if ( $repeatable ) {
				return self::map_repeatable_type( $field_type );
			}

			return $field_type;
		}

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
			);

			if ( isset( $replace_types[ $field_type ] ) ) {
				$field_type = $replace_types[ $field_type ];
			}

			return $field_type;
		}

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

		private static function format_string( $value, $mode = 'input' ) {
			return esc_html( $value );
		}

		private static function format_html( $value, $mode = 'input' ) {
			$formatted = wp_kses_post( $value );

			if ( 'output' === $mode ) {
				$formatted = wpautop( $formatted );
			}

			return $formatted;
		}

		private static function format_url( $value, $mode = 'input' ) {
			$formatted = esc_html( $value );

			if ( 'output' === $mode ) {
				return esc_url( $formatted );
			}

			return esc_url_raw( $formatted );
		}

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

		private static function parse_int( $value ) {
			return intval( $value );
		}

		private static function parse_float( $value ) {
			return floatval( $value );
		}

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
