<?php
/**
 * @package WPDLib
 * @version 0.6.3
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
	/**
	 * Class for a radio group field.
	 *
	 * The class is also used as base class for select fields, multiselect fields and multibox fields.
	 *
	 * @since 0.5.0
	 */
	class Radio extends Base {

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
				'options'	=> array(),
			) );
			parent::__construct( $type, $args );

			if ( is_array( $this->args['options'] ) && 1 === count( $this->args['options'] ) ) {
				if ( isset( $this->args['options']['posts'] ) || isset( $this->args['options']['terms'] ) || isset( $this->args['options']['users'] ) ) {
					add_action( 'wp_loaded', array( $this, 'parse_options' ) );
				}
			}
		}

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param string|array $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
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

		/**
		 * Validates a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return string|array|WP_Error the validated field value or an error object
		 */
		public function validate( $val = null ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				return $this->validate_multi( $val );
			}

			return $this->validate_single( $val );
		}

		/**
		 * Checks whether a value for the field is considered empty.
		 *
		 * This function is needed to check whether a required field has been properly filled.
		 *
		 * @since 0.5.0
		 * @param string|array $val the current value of the field
		 * @return bool whether the value is considered empty
		 */
		public function is_empty( $val ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				return count( (array) $val ) < 1;
			}
			return empty( $val );
		}

		/**
		 * Parses a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return string|array the correctly parsed value
		 */
		public function parse( $val, $formatted = false ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				return $this->parse_multi( $val, $formatted );
			}

			return $this->parse_single( $val, $formatted );
		}

		/**
		 * Displays a single item in the group.
		 *
		 * @since 0.5.0
		 * @param string $value the value of the item
		 * @param string|array $label the label of the item
		 * @param string $id the overall field's ID attribute
		 * @param string $name the overall field's name attribute
		 * @param string|array $current the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the item
		 */
		protected function display_item( $value, $label, $single_type, $id, $name, $current = '', $echo = true ) {
			$option_atts = array(
				'id'		=> $id . '-' . $value,
				'name'		=> $name,
				'value'		=> $value,
				'checked'	=> $this->is_value_checked_or_selected( $current, $value ),
				'readonly'	=> $this->args['readonly'],
				'disabled'	=> $this->args['disabled'],
			);

			$option_atts = array_merge( $option_atts, $this->data_atts );

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

		/**
		 * Generates output for a label in array format.
		 *
		 * Instead of just a default text label, you can also use an image or a color as label.
		 * To do that the label must be provided as an array which can have the following keys:
		 * - image (the URL to an image to use for the label)
		 * - color (a hex color string to use for the label)
		 * - label (the text to display)
		 *
		 * @since 0.5.0
		 * @param string|array $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
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

		/**
		 * Validates a value for the field.
		 *
		 * This function is used for radio groups / selects.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return string|WP_Error the validated field value or an error object
		 */
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

		/**
		 * Validates a value for the field.
		 *
		 * This function is used for multiboxes (checkbox groups) / multiselects.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return array|WP_Error the validated field value or an error object
		 */
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

		/**
		 * Parses a value for the field.
		 *
		 * This function is used for radio groups / selects.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return string the correctly parsed value
		 */
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

		/**
		 * Parses a value for the field.
		 *
		 * This function is used for multiboxes (checkbox groups) / multiselects.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return string the correctly parsed value
		 */
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

		/**
		 * Formats a single item for output.
		 *
		 * The argument passed as $val is the value of a specific field option.
		 *
		 * The 'mode' key in the $args array specifies in which format it should be returned:
		 * - html (if option has an image, it will be printed inside an IMG tag; if it has a color, it will be printed as a div with that color as background)
		 * - text (if option has an image, it will be printed as the plain URL; if it has a color, it will be printed as a hex color string)
		 *
		 * @since 0.5.0
		 * @param string|array $val the field option value to format
		 * @param array $args arguments on how to format
		 * @return string the correctly parsed value
		 */
		protected function format_item( $val, $args = array() ) {
			$skip_formatting = false;

			if ( isset( $this->args['options'][ $val ] ) ) {
				if ( is_array( $this->args['options'][ $val ] ) ) {
					if ( isset( $this->args['options'][ $val ]['label'] ) && ! empty( $this->args['options'][ $val ]['label'] ) ) {
						$val = $this->args['options'][ $val ]['label'];
					} elseif ( isset( $this->args['options'][ $val ]['image'] ) ) {
						if ( 'html' == $args['mode'] ) {
							$val = '<img src="' . esc_url( $this->args['options'][ $val ]['image'] ) . '" style="display: inline-block;width:64px;height:auto;">';
							$skip_formatting = true;
						} else {
							$val = esc_url( $this->args['options'][ $val ]['image'] );
						}
					} elseif ( isset( $this->args['options'][ $val ]['color'] ) ) {
						if ( 'html' == $args['mode'] ) {
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

		/**
		 * Returns whether a specific choice should be checked / selected.
		 *
		 * @since 0.5.0
		 * @param string|array $val the field option value
		 * @param string $choice the current choice to determine the checked / selected status for
		 * @return bool whether the choice should be marked as checked / selected
		 */
		protected function is_value_checked_or_selected( $val, $choice ) {
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				if ( ! is_array( $val ) ) {
					$val = array( $val );
				}

				return in_array( $choice, $val );
			}

			return $val == $choice;
		}

		/**
		 * Returns the sanitized name attribute for the field.
		 *
		 * This function is used to append parentheses to the name if the field is a multibox or multiselect.
		 *
		 * @since 0.5.0
		 * @return string the sanitized field name attribute
		 */
		protected function get_sanitized_name() {
			$name = $this->args['name'];
			if ( isset( $this->args['multiple'] ) && $this->args['multiple'] ) {
				if ( '[]' != substr( $name, -2 ) ) {
					$name .= '[]';
				}
			}
			return $name;
		}

		/**
		 * Transforms options of a specific type into actual options.
		 *
		 * Instead of specifying an array of options in the `$value => $label` format,
		 * you may also specify an array that only contains one of the following key value pairs:
		 * - `'posts' => $post_type` (will be transformed into a dropdown of posts of the post type $post_type)
		 * - `'terms' => $taxonomy` (will be transformed into a dropdown of terms of the taxonomy $taxonomy)
		 * - `'users' => $user_role (will be transformed into a dropdown of users of the role $user_role)
		 *
		 * Note that instead of providing a value of the above, you could also simply specify a boolean true
		 * to get a dropdown of ALL posts / terms / users respectively.
		 *
		 * @since 0.5.0
		 */
		public function parse_options() {
			if ( isset( $this->args['options']['posts'] ) ) {
				$this->args['options'] = Util::get_posts_options( $this->args['options']['posts'] );
			} elseif ( isset( $this->args['options']['terms'] ) ) {
				$this->args['options'] = Util::get_terms_options( $this->args['options']['terms'] );
			} elseif ( isset( $this->args['options']['users'] ) ) {
				$this->args['options'] = Util::get_users_options( $this->args['options']['users'] );
			}
		}
	}

}
