<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;
use WPDLib\Util\Util;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Radio' ) ) {

	class Radio extends Base {
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'options'	=> array(),
			) );
			parent::__construct( $type, $args );

			if ( is_array( $this->args['options'] ) && count( $this->args['options'] ) == 1 ) {
				if ( isset( $this->args['options']['posts'] ) || isset( $this->args['options']['terms'] ) || isset( $this->args['options']['users'] ) ) {
					add_action( 'wp_loaded', array( $this, 'parse_options' ) );
				}
			}
		}

		public function display( $val, $echo = true ) {
			$args = array(
				'id'	=> $this->args['id'],
				'class'	=> $this->args['class'],
			);

			$name = $this->get_sanitized_name();

			$single_type = 'radio';
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				$single_type = 'checkbox';
			}

			$output = '<div' . FieldManager::make_html_attributes( $args, false, false ) . '>';

			foreach ( $this->args['options'] as $value => $label ) {
				$output .= $this->display_item( $value, $label, $single_type, $args['id'], $name, $val, false );
			}

			$output .= '</div>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				return $this->validate_multi( $val );
			}

			return $this->validate_single( $val );
		}

		public function is_empty( $val ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				return count( (array) $val ) < 1;
			}
			return empty( $val );
		}

		public function parse( $val, $formatted = false ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				return $this->parse_multi( $val, $formatted );
			}

			return $this->parse_single( $val, $formatted );
		}

		protected function display_item( $value, $label, $single_type, $id, $name, $current = '', $echo = true ) {
			$option_atts = array(
				'id'		=> $id . '-' . $value,
				'name'		=> $name,
				'value'		=> $value,
				'checked'	=> $this->is_value_checked_or_selected( $current, $value ),
				'readonly'	=> $this->args['readonly'],
				'disabled'	=> $this->args['disabled'],
			);

			$additional_output = $additional_class = '';
			if ( is_array( $label ) ) {
				list( $additional_output, $additional_class, $label ) = $this->maybe_generate_additional_item_output( $option_atts['id'], $label, $option_atts['checked'] );
			}

			$output = '<div class="wpdlib-' . $single_type . $additional_class . '">';

			$output .= '<input type="' . $single_type . '"' . FieldManager::make_html_attributes( $option_atts, false, false ) . ' />';

			$output .= $additional_output;

			if ( ! empty( $label ) ) {
				$output .= ' <label for="' . $option_atts['id'] . '">' . esc_html( $label ) . '</label>';
			}

			$output .= '</div>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		protected function maybe_generate_additional_item_output( $id, $data, $checked = false ) {
			$output = $class = $label = '';
			if ( isset( $data['image'] ) || isset( $data['color'] ) ) {
				$output = '<div id="' . $id . '-asset"';

				if ( $checked ) {
					$output .= ' class="checked"';
				}

				if ( isset( $data['image'] ) ) {
					$output .= ' style="background-image:url(\'' . esc_url( $data['image'] ) . '\');"';
				} else {
					$output .= ' style="background-color:#' . ltrim( $data['color'], '#' ) . ';"';
				}

				$output .= '></div>';

				$class .= ' box';
			}

			if ( isset( $data['label'] ) ) {
				$label = $data['label'];
			}

			return array( $output, $class, $label );
		}

		protected function validate_single( $val = null ) {
			if ( ! $val ) {
				if ( count( $this->args['options'] ) > 0 ) {
					return key( $this->args['options'] );
				}
				return '';
			}

			if ( ! isset( $this->args['options'][ $val ] ) ) {
				$key = array_search( $val, $this->args['options'] );
				if ( '' !== $key ) {
					return new WPError( 'invalid_' . $this->type . '_option', sprintf( __( '%s is not a valid option.', 'wpdlib' ), FieldManager::format( $val, 'string', 'output' ) ) );
				} else {
					$val = $key;
				}
			}

			return $val;
		}

		protected function validate_multi( $val = null ) {
			if ( ! $val ) {
				return array();
			}

			if ( ! is_array( $val ) ) {
				if ( $val ) {
					$val = array( $val );
				} else {
					$val = array();
				}
			}

			for ( $i = 0; $i < count( $val ); $i++ ) {
				if ( ! isset( $this->args['options'][ $val[ $i ] ] ) ) {
					$key = array_search( $val[ $i ], $this->args['options'] );
					if ( '' !== $key ) {
						return new WPError( 'invalid_' . $this->type . '_option', sprintf( __( '%s is not a valid option.', 'wpdlib' ), FieldManager::format( $val[ $i ], 'string', 'output' ) ) );
					} else {
						$val[ $i ] = $key;
					}
				}
			}

			return $val;
		}

		protected function parse_single( $val, $formatted = false ) {
			if ( $formatted ) {
				if ( ! is_array( $formatted ) ) {
					$formatted = array();
				}
				$formatted = wp_parse_args( $formatted, array(
					'mode'		=> 'text',
				) );
				return $this->format_item( $val, $formatted );
			}

			return FieldManager::format( $val, 'string', 'input' );
		}

		protected function parse_multi( $val, $formatted = false ) {
			$parsed = array();
			if ( $formatted ) {
				if ( ! is_array( $formatted ) ) {
					$formatted = array();
				}
				$formatted = wp_parse_args( $formatted, array(
					'mode'		=> 'text',
					'list'		=> false,
				) );
				$list_separator = ( 'html' == $formatted['mode'] ) ? ' ' : ', ';
				foreach ( (array) $val as $_val) {
					$_item = $this->format_item( $_val, $formatted );
					if ( false === strpos( $_item, '<img' ) && false === strpos( $_item, '<div' ) ) {
						$list_separator = ', ';
					}
					$parsed[] = $_item;
				}

				if ( $formatted['list'] ) {
					return implode( $list_separator, $parsed );
				}
			} else {
				foreach ( (array) $val as $_val) {
					$parsed[] = FieldManager::format( $_val, 'string', 'input' );
				}
			}

			return $parsed;
		}

		protected function format_item( $val, $formatted = array() ) {
			$skip_formatting = false;

			if ( isset( $this->args['options'][ $val ] ) ) {
				if ( is_array( $this->args['options'][ $val ] ) ) {
					if ( isset( $this->args['options'][ $val ]['label'] ) && ! empty( $this->args['options'][ $val ]['label'] ) ) {
						$val = $this->args['options'][ $val ]['label'];
					} elseif ( isset( $this->args['options'][ $val ]['image'] ) ) {
						if ( 'html' == $formatted['mode'] ) {
							$val = '<img src="' . esc_url( $this->args['options'][ $val ]['image'] ) . '" style="display: inline-block;width:64px;height:auto;">';
							$skip_formatting = true;
						} else {
							$val = esc_url( $this->args['options'][ $val ]['image'] );
						}
					} elseif ( isset( $this->args['options'][ $val ]['color'] ) ) {
						if ( 'html' == $formatted['mode'] ) {
							$val = '<div style="display:inline-block;width:64px;height:48px;background-color:' . $this->args['options'][ $val ]['color'] . ';"></div>';
							$skip_formatting = true;
						} else {
							$val = $this->args['options'][ $val ]['color'];
						}
					}
				} else {
					$val = $this->args['options'][ $val ];
				}
			}

			if ( $skip_formatting ) {
				return $val;
			}

			return FieldManager::format( $val, 'string', 'output' );
		}

		protected function is_value_checked_or_selected( $option, $value ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				if ( ! is_array( $option ) ) {
					$option = array( $option );
				}

				return in_array( $value, $option );
			}

			return $option == $value;
		}

		protected function get_sanitized_name() {
			$name = $this->args['name'];
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				if ( '[]' != substr( $name, -2 ) ) {
					$name .= '[]';
				}
			}
			return $name;
		}

		public function parse_options() {
			if ( isset( $this->args['options']['posts'] ) ) {
				$this->args['options'] = Util::get_posts_options( $this->args['options']['posts'] );
			} elseif ( isset( $this->args['options']['terms'] ) ) {
				$this->args['options'] = Util::get_terms_options( $this->args['options']['terms'] );
			} elseif ( isset( $this->args['options']['user'] ) ) {
				$this->args['options'] = Util::get_users_options( $this->args['options']['users'] );
			}
		}
	}

}
