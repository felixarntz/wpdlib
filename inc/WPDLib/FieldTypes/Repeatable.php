<?php
/**
 * @package WPDLib
 * @version 0.6.2
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Repeatable' ) ) {
	/**
	 * Class for a repeatable field.
	 *
	 * A repeatable field is a special field type that actually bundles multiple fields together.
	 * These fields are display in a row as one group, and you can define multiple of such groups.
	 *
	 * Each bundled field must have a unique slug so that they can be referenced.
	 *
	 * The value of a repeatable field is always an array that contains arrays itself.
	 * Each inner array represents a group and contains the field slugs alongside with their values.
	 *
	 * Internally, a repeatable field object contains instances of all field type objects that are part of it.
	 * It uses each field object's methods to display, validate and parse the field's values.
	 *
	 * @since 0.5.0
	 */
	class Repeatable extends Base {

		/**
		 * @since 0.5.0
		 * @var array Holds the field type objects that belong to the repeatable.
		 */
		protected $fields = array();

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
				'repeatable'	=> array(),
			) );
			if ( ! is_array( $args['repeatable'] ) ) {
				$args['repeatable'] = array();
			}
			if ( ! isset( $args['repeatable']['limit'] ) ) {
				$args['repeatable']['limit'] = 0;
			} else {
				$args['repeatable']['limit'] = absint( $args['repeatable']['limit'] );
			}
			if ( ! isset( $args['repeatable']['fields'] ) || ! is_array( $args['repeatable']['fields'] ) ) {
				$args['repeatable']['fields'] = array();
			}
			parent::__construct( $type, $args );

			foreach ( $this->args['repeatable']['fields'] as $field_slug => $field_args ) {
				$field = FieldManager::get_instance( $field_args );
				if ( $field !== null ) {
					$this->fields[ $field_slug ] = $field;
				}
			}
		}

		/**
		 * Displays the input control for the field.
		 *
		 * The individual fields are rendered inside a table.
		 * Each row represents a group and each column represents a field.
		 *
		 * The function will also render button controls to add / remove groups.
		 *
		 * @since 0.5.0
		 * @param string $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			if ( ! is_array( $val ) ) {
				$val = array();
			}

			$args = array(
				'id'			=> $this->args['id'],
				'class'			=> $this->args['class'],
				'data-limit'	=> $this->args['repeatable']['limit'],
			);

			$button_args = array(
				'class'			=> 'wpdlib-new-repeatable-button button',
				'href'			=> '#',
			);
			if ( $this->args['repeatable']['limit'] > 0 && count( $val ) == $this->args['repeatable']['limit'] ) {
				$button_args['style'] = 'display:none;';
			}

			$output = '<div' . FieldManager::make_html_attributes( $args, false, false ) . '>';
			$output .= '<p><a' . FieldManager::make_html_attributes( $button_args, false, false ) . '>' . __( 'Add new', 'wpdlib' ) . '</a></p>';
			$output .= '<table class="wpdlib-repeatable-table"' . ( count( $val ) < 1 ? ' style="display:none;"' : '' ) . '>';
			$output .= '<tr>';
			$output .= '<th class="wpdlib-repeatable-number">#</th>';
			foreach ( $this->args['repeatable']['fields'] as $slug => $args ) {
				$output .= '<th class="wpdlib-repeatable-' . $this->args['id'] . '-' . $slug . '">' . ( isset( $args['title'] ) ? $args['title'] : '' ) . '</th>';
			}
			$output .= '<th class="wpdlib-repeatable-remove"></th>';
			$output .= '</tr>';
			foreach ( $val as $key => $values ) {
				$output .= $this->display_item( $key, $values, false );
			}
			$output .= '</table>';
			$output .= '</div>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		/**
		 * Validates a value for the field.
		 *
		 * The function calls the validation functions of each field for each group and composes the value from those.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return array the validated field value
		 */
		public function validate( $val = null ) {
			if ( ! $val || ! is_array( $val ) ) {
				return array();
			}

			if ( $this->args['repeatable']['limit'] > 0 && count( $val ) > $this->args['repeatable']['limit'] ) {
				$val = array_slice( $val, 0, $this->args['repeatable']['limit'] );
			}

			foreach ( $val as $key => &$values ) {
				foreach ( $this->args['repeatable']['fields'] as $slug => $args ) {
					if ( ! isset( $this->fields[ $slug ] ) ) {
						continue;
					}

					if ( isset( $values[ $slug ] ) ) {
						$values[ $slug ] = $this->fields[ $slug ]->validate( $values[ $slug ] );
						if ( is_wp_error( $values[ $slug ] ) ) {
							$values[ $slug ] = $this->fields[ $slug ]->validate();
						}
					} else {
						$values[ $slug ] = $this->fields[ $slug ]->validate();
					}
				}
			}

			return $val;
		}

		/**
		 * Checks whether a value for the field is considered empty.
		 *
		 * This function is needed to check whether a required field has been properly filled.
		 *
		 * @since 0.5.0
		 * @param array $val the current value of the field
		 * @return bool whether the value is considered empty
		 */
		public function is_empty( $val ) {
			return count( (array) $val ) < 1;
		}

		/**
		 * Parses a value for the field.
		 *
		 * If you specify an array for the $formatted parameter and that array contains a 'mode' key with 'template' as value,
		 * you should specify an additional key 'template' which holds the template as an HTML string.
		 * This template will be printed for every group currently in the repeatable.
		 * You can use field slugs wrapped in percent signs as template tags (for example '%background_color%' if the repeatable contains a field with the slug 'background color').
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return array the correctly parsed value
		 */
		public function parse( $val, $formatted = false ) {
			$parsed = array();
			$items_formatted = false;
			if ( $formatted ) {
				$items_formatted = array( 'mode' => 'text', 'list' => true );
			}
			foreach ( $val as $key => $values ) {
				$parsed[ $key ] = array();
				foreach ( $this->args['repeatable']['fields'] as $slug => $args ) {
					if ( ! isset( $this->fields[ $slug ] ) ) {
						continue;
					}

					if ( isset( $values[ $slug ] ) ) {
						$parsed[ $key ][ $slug ] = $this->fields[ $slug ]->parse( $values[ $slug ], $items_formatted );
					} else {
						$parsed[ $key ][ $slug ] = $this->fields[ $slug ]->parse( $this->fields[ $slug ]->validate(), $items_formatted );
					}
				}
			}
			if ( $formatted ) {
				if ( is_array( $formatted ) ) {
					$formatted = wp_parse_args( $formatted, array(
						'mode'			=> 'array',
						'template'		=> '',
						'before'		=> '',
						'after'			=> '',
					) );

					if ( 'template' === $formatted['mode'] ) {
						return $this->parse_template( $parsed, $formatted['template'], $formatted['before'], $formatted['after'] );
					}
				}
			}
			return $parsed;
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
			list( $dependencies, $script_vars ) = FieldManager::get_dependencies_and_script_vars( $this->fields );

			if ( ! isset( $script_vars['repeatable_field_templates'] ) ) {
				$script_vars['repeatable_field_templates'] = array();
			}
			$script_vars['repeatable_field_templates'][ $this->args['id'] ] = $this->display_item( '{{' . 'KEY' . '}}', array(), false );

			return array(
				'dependencies'	=> $dependencies,
				'script_vars'	=> $script_vars,
			);
		}

		/**
		 * Displays a single group of fields.
		 *
		 * The function is also used to generate a dynamic template which is used in Javascript when appending new groups.
		 *
		 * @since 0.5.0
		 * @param integer $key the index of the group
		 * @param array $values the values of the group as `$field_slug => $value`
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the group
		 */
		protected function display_item( $key, $values = array(), $echo = true ) {
			$output = '<tr class="wpdlib-repeatable-row">';

			$output .= '<td class="wpdlib-repeatable-number">';
			if ( '{{' . 'KEY' . '}}' === $key ) {
				$output .= '<span>' . sprintf( __( '%s.', 'wpdlib' ), '{{' . 'KEY_PLUSONE' . '}}' ) . '</span>';
			} else {
				$key = absint( $key );
				$output .= '<span>' . sprintf( __( '%s.', 'wpdlib' ), $key + 1 ) . '</span>';
			}
			$output .= '</td>';

			foreach ( $this->args['repeatable']['fields'] as $slug => $args ) {
				if ( ! isset( $this->fields[ $slug ] ) ) {
					continue;
				}

				$val = isset( $values[ $slug ] ) ? $values[ $slug ] : $this->fields[ $slug ]->validate();

				$this->fields[ $slug ]->id = $this->args['id'] . '-' . $key . '-' . $slug;
				$this->fields[ $slug ]->name = $this->args['name'] . '[' . $key . '][' . $slug . ']';

				$output .= '<td class="wpdlib-repeatable-col wpdlib-repeatable-' . $this->args['id'] . '-' . $slug . '">';
				$output .= $this->fields[ $slug ]->display( $val, false );
				$output .= '</td>';
			}

			$button_args = array(
				'class'			=> 'wpdlib-remove-repeatable-button',
				'href'			=> '#',
				'data-number'	=> $key,
			);

			$output .= '<td class="wpdlib-repeatable-remove">';
			$output .= '<a' . FieldManager::make_html_attributes( $button_args, false, false ) . '>' . __( 'Remove', 'wpdlib' ) . '</a>';
			$output .= '</td>';

			$output .= '</tr>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		/**
		 * Parses a template for repeatable values.
		 *
		 * This function is used by the `parse()` method (with mode 'template').
		 *
		 * @since 0.5.0
		 * @param array $val the current value of the field
		 * @param string $template the template to parse
		 * @param string $before content to print before the template (optional, default is an empty string)
		 * @param string $after content to print after the template (optional, default is an empty string)
		 * @return string the parsed template containing the repeatable values
		 */
		protected function parse_template( $val, $template, $before = '', $after = '' ) {
			$output = '';

			if ( ! $this->is_empty( $val ) ) {
				$output .= $before;
				foreach ( $val as $key => $values ) {
					$search = array();
					$replace = array();
					foreach ( $this->args['repeatable']['fields'] as $slug => $args ) {
						$search[] = '%' . $slug . '%';
						$replace[] = isset( $values[ $slug ] ) ? $values[ $slug ] : '';
					}
					$output .= str_replace( $search, $replace, $template );
				}
				$output .= $after;
			}

			return $output;
		}
	}

}
