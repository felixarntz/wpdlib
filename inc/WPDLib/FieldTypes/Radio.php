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
				$option_atts = array(
					'id'		=> $args['id'] . '-' . $value,
					'name'		=> $name,
					'value'		=> $value,
					'checked'	=> $this->is_value_checked_or_selected( $val, $value ),
					'readonly'	=> $this->args['readonly'],
					'disabled'	=> $this->args['disabled'],
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

				$output .= '<div class="wpdlib-' . $single_type . $additional_class . '">';

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
			} else {
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
					if ( ! is_array( $formatted ) ) {
						$formatted = array();
					}
					$formatted = wp_parse_args( $formatted, array(
						'mode'		=> 'text',
						'list'		=> false,
					) );
					$list_separator = ( 'html' == $formatted['mode'] ) ? ' ' : ', ';
					foreach ( (array) $val as $v ) {
						$skip_string_formatting = false;
						if ( isset( $this->args['options'][ $v ] ) ) {
							if ( is_array( $this->args['options'][ $v ] ) ) {
								if ( isset( $this->args['options'][ $v ]['label'] ) && ! empty( $this->args['options'][ $v ]['label'] ) ) {
									$v = $this->args['options'][ $v ]['label'];
									$list_separator = ', ';
								} elseif ( isset( $this->args['options'][ $v ]['image'] ) ) {
									if ( 'html' == $formatted['mode'] ) {
										$v = '<img src="' . esc_url( $this->args['options'][ $v ]['image'] ) . '" style="display: inline-block;width:64px;height:auto;">';
										$skip_string_formatting = true;
									} else {
										$v = esc_url( $this->args['options'][ $v ]['image'] );
									}
								} elseif ( isset( $this->args['options'][ $v ]['color'] ) ) {
									if ( 'html' == $formatted['mode'] ) {
										$v = '<div style="display:inline-block;width:64px;height:48px;background-color:' . $this->args['options'][ $v ]['color'] . ';"></div>';
										$skip_string_formatting = true;
									} else {
										$v = $this->args['options'][ $v ]['color'];
									}
								} else {
									$v = '';
								}
							} else {
								$v = $this->args['options'][ $v ];
								$list_separator = ', ';
							}
						}
						if ( $skip_string_formatting ) {
							$parsed[] = $v;
						} else {
							$parsed[] = FieldManager::format( $v, 'string', 'output' );
						}
					}

					if ( $formatted['list'] ) {
						return implode( $list_separator, $parsed );
					}
				} else {
					foreach ( (array) $val as $v ) {
						$parsed[] = FieldManager::format( $v, 'string', 'input' );
					}
				}

				return $parsed;
			} else {
				if ( $formatted ) {
					if ( ! is_array( $formatted ) ) {
						$formatted = array();
					}
					$formatted = wp_parse_args( $formatted, array(
						'mode'		=> 'text',
					) );
					$skip_string_formatting = false;
					if ( isset( $this->args['options'][ $val ] ) ) {
						if ( is_array( $this->args['options'][ $val ] ) ) {
							if ( isset( $this->args['options'][ $val ]['label'] ) && ! empty( $this->args['options'][ $val ]['label'] ) ) {
								$val = $this->args['options'][ $val ]['label'];
							} elseif ( isset( $this->args['options'][ $val ]['image'] ) ) {
								if ( 'html' == $formatted['mode'] ) {
									$val = '<img src="' . esc_url( $this->args['options'][ $val ]['image'] ) . '" style="display: inline-block;width:64px;height:auto;">';
									$skip_string_formatting = true;
								} else {
									$val = esc_url( $this->args['options'][ $val ]['image'] );
								}
							} elseif ( isset( $this->args['options'][ $val ]['color'] ) ) {
								if ( 'html' == $formatted['mode'] ) {
									$val = '<div style="display:inline-block;width:64px;height:48px;background-color:' . $this->args['options'][ $val ]['color'] . ';"></div>';
									$skip_string_formatting = true;
								} else {
									$val = $this->args['options'][ $val ]['color'];
								}
							}
						} else {
							$val = $this->args['options'][ $val ];
						}
					}
					if ( $skip_string_formatting ) {
						return $val;
					}
					return FieldManager::format( $val, 'string', 'output' );
				}

				return FieldManager::format( $val, 'string', 'input' );
			}
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
