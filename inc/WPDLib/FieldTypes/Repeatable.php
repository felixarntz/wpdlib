<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Repeatable' ) ) {

	class Repeatable extends Base {
		protected $fields = array();

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
			$output .= '<th></th>';
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

		public function validate( $val = null ) {
			if ( $val === null || ! is_array( $val ) ) {
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

		public function is_empty( $val ) {
			return count( (array) $val ) < 1;
		}

		public function parse( $val, $formatted = false ) {
			$parsed = array();
			foreach ( $val as $key => $values ) {
				$parsed[ $key ] = array();
				foreach ( $this->args['repeatable']['fields'] as $slug => $args ) {
					if ( ! isset( $this->fields[ $slug ] ) ) {
						continue;
					}

					if ( isset( $values[ $slug ] ) ) {
						$parsed[ $key ][ $slug ] = $this->fields[ $slug ]->parse( $values[ $slug ], $formatted );
					} else {
						$parsed[ $key ][ $slug ] = $this->fields[ $slug ]->validate();
					}
				}
			}
			return $parsed;
		}

		public function enqueue_assets() {
			$dependencies = array();
			$script_vars = array();

			foreach ( $this->fields as $field_slug => $field ) {
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

			if ( ! isset( $script_vars['repeatable_field_templates'] ) ) {
				$script_vars['repeatable_field_templates'] = array();
			}
			$script_vars['repeatable_field_templates'][ $this->args['id'] ] = $this->display_item( '{{' . 'KEY' . '}}', array(), false );

			return array(
				'dependencies'	=> $dependencies,
				'script_vars'	=> $script_vars,
			);
		}

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

			$output .= '<td>';
			$output .= '<a' . FieldManager::make_html_attributes( $button_args, false, false ) . '>' . __( 'Remove', 'wpdlib' ) . '</a>';
			$output .= '</td>';

			$output .= '</tr>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}
	}

}
