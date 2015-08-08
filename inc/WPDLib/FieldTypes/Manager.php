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

if ( ! class_exists( 'WPDLib\FieldTypes\Manager' ) ) {

	final class Manager {
		public static function get_instance( $args, $repeatable = false ) {
			if ( ! isset( $args['type'] ) ) {
				return null;
			}
			$field_type = $args['type'];

			$field_types = self::get_field_types();
			if ( ! in_array( $field_type, $field_types ) ) {
				return null;
			}

			if ( $repeatable ) {
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
			}

			$field_keys = array(
				'id',
				'name',
				'class',
				'placeholder',
				'required',
				'readonly',
				'disabled',
				'options',
				'min',
				'max',
				'step',
				'mime_types',
				'repeatable',
			);

			$field_args = array_intersect_key( $args, array_flip( $field_keys ) );

			if ( class_exists( 'WPDLib\FieldTypes\\' . ucfirst( $field_type ) ) ) {
				$class_name = '\WPDLib\FieldTypes\\' . ucfirst( $field_type );
				return new $class_name( $field_type, $field_args );
			}
			return new \WPDLib\FieldTypes\Base( $field_type, $field_args );
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
			$assets_url = \WPDLib\Components\Manager::get_base_url() . '/assets';
			$version = \WPDLib\Components\Manager::get_info( 'version' );

			$dependencies = array( 'jquery' );
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

			wp_enqueue_style( 'wpdlib-fields', $assets_url . '/fields.min.css', array(), $version );

			wp_enqueue_script( 'wpdlib-fields', $assets_url . '/fields.min.js', $dependencies, $version, true );

			if ( count( $script_vars ) > 0 ) {
				wp_localize_script( 'wpdlib-fields', '_wpdlib_data', $script_vars );
			}
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
			$mode = $mode == 'output' ? 'output' : 'input';

			$formatted = $value;

			switch ( $type ) {
				case 'string':
					$formatted = esc_html( $value );
					break;
				case 'html':
					$formatted = wp_kses_post( $value );
					if ( $mode == 'output' ) {
						$formatted = wpautop( $formatted );
					}
					break;
				case 'url':
					$formatted = esc_html( $value );
					if ( $mode == 'output' ) {
						$formatted = esc_url( $formatted );
					} else {
						$formatted = esc_url_raw( $formatted );
					}
					break;
				case 'boolean':
				case 'bool':
					if ( is_int( $value ) ) {
						if ( $value > 0 ) {
							$formatted = true;
						} else {
							$formatted = false;
						}
					} elseif ( is_string( $value ) ) {
						if ( ! empty( $value ) ) {
							if ( strtolower( $value ) == 'false' ) {
								$formatted = false;
							} else {
								$formatted = true;
							}
						} else {
							$formatted = false;
						}
					} else {
						$formatted = (bool) $value;
					}
					if ( $mode == 'output' ) {
						if ( $formatted ) {
							$formatted = 'true';
						} else {
							$formatted = 'false';
						}
					}
					break;
				case 'integer':
				case 'int':
					$positive_only = isset( $args['positive_only'] ) ? (bool) $args['positive_only'] : false;
					if ( $positive_only ) {
						$formatted = absint( $value );
					} else {
						$formatted = intval( $value );
					}
					if ( $mode == 'output' ) {
						$formatted = number_format_i18n( floatval( $formatted ), 0 );
					}
					break;
				case 'float':
				case 'double':
					$positive_only = isset( $args['positive_only'] ) ? (bool) $args['positive_only'] : false;
					$formatted = floatval( $value );
					if ( $positive_only ) {
						$formatted = abs( $formatted );
					}
					if ( $mode == 'output' ) {
						$decimals = isset( $args['decimals'] ) ? absint( $args['decimals'] ) : 2;
						$formatted = number_format_i18n( $formatted, $decimals );
					} else {
						$decimals = isset( $args['decimals'] ) ? absint( $args['decimals'] ) : false;
						if ( $decimals !== false ) {
							$formatted = number_format( $formatted, $decimals );
						}
					}
					break;
				case 'date':
				case 'time':
				case 'datetime':
					$timestamp = $value;
					if ( ! is_int( $timestamp ) ) {
						$timestamp = mysql2date( 'U', $timestamp );
					}
					$format = isset( $args['format'] ) ? $args['format'] : '';
					if ( empty( $format ) ) {
						if ( $mode == 'output' ) {
							if ( $type == 'date' ) {
								$format = get_option( 'date_format' );
							} elseif ( $type == 'time' ) {
								$format = get_option( 'time_format' );
							} else {
								$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
							}
						} else {
							if ( $type == 'date' ) {
								$format = 'Ymd';
							} elseif ( $type == 'time' ) {
								$format = 'His';
							} else {
								$format = 'YmdHis';
							}
						}
					}
					$formatted = date_i18n( $format, $timestamp );
					break;
				case 'byte':
					$formatted = floatval( $value );
					if ( $mode == 'output' ) {
						$units = array( 'B', 'kB', 'MB', 'GB', 'TB' );
						$decimals = isset( $args['decimals'] ) ? absint( $args['decimals'] ) : 2;
						$base_unit = isset( $args['base_unit'] ) && in_array( $args['base_unit'], $units ) ? $args['base_unit'] : 'B';
						if ( $base_unit != 'B' ) {
							$formatted *= pow( 1024, array_search( $base_unit, $units ) );
						}
						for ( $i = count( $units ) - 1; $i >= 0; $i-- ) {
							if ( $formatted > pow( 1024, $i ) ) {
								$formatted = number_format_i18n( $formatted / pow( 1024, $i ), $decimals ) . ' ' . $units[ $i ];
								break;
							} elseif ( $i == 0 ) {
								$formatted = number_format_i18n( $formatted, $decimals ) . ' B';
							}
						}
					} else {
						$decimals = isset( $args['decimals'] ) ? absint( $args['decimals'] ) : false;
						if ( $decimals !== false ) {
							$formatted = number_format( $formatted, $decimals );
						}
					}
					break;
				default:
			}

			return $formatted;
		}

		private static function sort_html_attributes( $a, $b ) {
			if ( $a != $b ) {
				$priorities = array( 'id', 'name', 'class' );
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
				} elseif ( strpos( $a, 'data-' ) === 0 && strpos( $b, 'data-' ) === 0 ) {
					return 0;
				}

				$priorities = array_merge( $priorities, array( 'rel', 'type', 'value', 'href' ) );
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
			}

			return 0;
		}
	}

}
