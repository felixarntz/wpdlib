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

if ( ! class_exists( 'WPDLib\FieldTypes\Radio' ) ) {

	class Radio extends Base {
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'options'	=> array(),
			) );
			parent::__construct( $type, $args );
		}

		public function display( $val, $echo = true ) {
			$args = array(
				'id'	=> $this->args['id'],
				'class'	=> $this->args['class'],
			);

			$name = $this->get_sanitized_name();

			$single_type = 'wpdlib-radio';
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				$single_type = 'wpdlib-checkbox';
			}

			$output = '<div' . FieldManager::make_html_attributes( $args, false, false ) . '>';

			foreach ( $this->args['options'] as $value => $label ) {
				$option_atts = array(
					'id'		=> $args['id'] . '-' . $value,
					'name'		=> $name,
					'value'		=> $value,
					'checked'	=> $this->is_value_checked_or_selected( $value, $val ),
					'readonly'	=> $args['readonly'],
					'disabled'	=> $args['disabled'],
				);

				$additional_output = $additional_class = '';
				if ( is_array( $label ) ) {
					if ( isset( $label['image'] ) || isset( $label['color'] ) ) {
						$additional_output = '<div id="' . $option_atts['id'] . '-asset"';

						if ( $option_atts['checked'] ) {
							$additional_output .= ' class="checked"';
						}

						if ( isset( $label['image'] ) ) {
							$additional_output .= ' style="background-image:url(\'' . esc_url( $label['image'] ) . '\');"';
						} else {
							$additional_output .= ' style="background-color:#' . ltrim( $label['color'], '#' ) . ';"';
						}

						$additional_output .= '></div>';

						$additional_class .= ' box';
					}
					if ( isset( $label['label'] ) ) {
						$label = $label['label'];
					} else {
						$label = '';
					}
				}

				$output .= '<div class="' . $single_type . $additional_class . '">';

				$output .= '<input type="' . $single_type . '"' . FieldManager::make_html_attributes( $option_atts, false, false ) . ' />';

				$output .= $additional_output;

				if ( ! empty( $label ) ) {
					$output .= ' <label for="' . $option_atts['id'] . '">' . esc_html( $label ) . '</label>';
				}

				$output .= '</div>';
			}

			$output .= '</div>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				if ( $val === null ) {
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
			} else {
				if ( $val === null ) {
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
		}

		public function is_empty( $val ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				return count( (array) $val ) < 1;
			}
			return empty( $val );
		}

		public function parse( $val, $formatted = false ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				$parsed = array();
				if ( $formatted ) {
					foreach ( $val as $v ) {
						if ( isset( $this->args['options'][ $v ] ) ) {
							if ( is_array( $this->args['options'][ $v ] ) ) {
								if ( isset( $this->args['options'][ $v ]['label'] ) && ! empty( $this->args['options'][ $v ]['label'] ) ) {
									$v = $this->args['options'][ $v ]['label'];
								} elseif ( isset( $this->args['options'][ $v ]['image'] ) ) {
									$v = esc_url( $this->args['options'][ $v ]['image'] );
								} elseif ( isset( $this->args['options'][ $v ]['color'] ) ) {
									$v = $this->args['options'][ $v ]['color'];
								} else {
									$v = '';
								}
							} else {
								$v = $this->args['options'][ $v ];
							}
						}
						$parsed[] = FieldManager::format( $v, 'string', 'output' );
					}
				} else {
					foreach ( $val as $v ) {
						$parsed[] = FieldManager::format( $v, 'string', 'input' );
					}
				}

				return $parsed;
			} else {
				if ( $formatted ) {
					if ( isset( $this->args['options'][ $val ] ) ) {
						if ( is_array( $this->args['options'][ $val ] ) ) {
							if ( isset( $this->args['options'][ $val ]['label'] ) && ! empty( $this->args['options'][ $val ]['label'] ) ) {
								$val = $this->args['options'][ $val ]['label'];
							} elseif ( isset( $this->args['options'][ $val ]['image'] ) ) {
								$val = esc_url( $this->args['options'][ $val ]['image'] );
							} elseif ( isset( $this->args['options'][ $val ]['color'] ) ) {
								$val = $this->args['options'][ $val ]['color'];
							}
						} else {
							$val = $this->args['options'][ $val ];
						}
					}
					return FieldManager::format( $val, 'string', 'output' );
				}

				return FieldManager::format( $val, 'string', 'input' );
			}
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
	}

}
