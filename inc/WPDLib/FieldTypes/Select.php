<?php
/**
 * @package WPDLib
 * @version 0.6.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Select' ) ) {
	/**
	 * Class for a select field.
	 *
	 * @since 0.5.0
	 */
	class Select extends Radio {

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param string|array $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['name'] = $this->get_sanitized_name();

			$args = array_merge( $args, $this->data_atts );

			if ( ! empty( $args['placeholder'] ) ) {
				$data_placeholder = array(
					'id'	=> '',
					'text'	=> $args['placeholder'],
				);
				if ( isset( $args['data-placeholder'] ) ) {
					$data_placeholder = array_merge_recursive( json_decode( $args['data-placeholder'], true ), $data_placeholder );
				}
				$args['data-placeholder'] = json_encode( $data_placeholder );
			}
			unset( $args['placeholder'] );
			unset( $args['options'] );

			$output = '<select' . FieldManager::make_html_attributes( $args, false, false ) . '>';
			if ( ! empty( $this->args['placeholder'] ) ) {
				$output .= '<option value=""' . ( empty( $val ) ? ' selected="selected"' : '' ) . '>' . esc_html( $this->args['placeholder'] ) . '</option>';
			}
			foreach ( $this->args['options'] as $value => $label ) {
				$output .= $this->display_item( $value, $label, 'select', $args['id'], $args['name'], $val, false );
			}
			$output .= '</select>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
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
			if ( self::is_enqueued( __CLASS__ ) ) {
				return array();
			}

			$assets_dir = ComponentManager::get_base_dir() . '/assets';
			$assets_url = ComponentManager::get_base_url() . '/assets';
			$version = ComponentManager::get_dependency_info( 'select2', 'version' );

			wp_enqueue_style( 'select2', $assets_url . '/vendor/select2/dist/css/select2.min.css', array(), $version );
			wp_enqueue_script( 'select2', $assets_url . '/vendor/select2/dist/js/select2.min.js', array( 'jquery' ), $version, true );

			$dependencies = array( 'select2' );

			$locale = str_replace( '_', '-', get_locale() );
			$language = substr( $locale, 0, 2 );

			if ( file_exists( $assets_dir . '/vendor/select2/dist/js/i18n/' . $locale . '.js' ) ) {
				wp_enqueue_script( 'select2-locale', $assets_url . '/vendor/select2/dist/js/i18n/' . $locale . '.js', array( 'select2' ), $version, true );
				$dependencies[] = 'select2-locale';
			} elseif( file_exists( $assets_dir . '/vendor/select2/dist/js/i18n/' . $language . '.js' ) ) {
				wp_enqueue_script( 'select2-locale', $assets_url . '/vendor/select2/dist/js/i18n/' . $language . '.js', array( 'select2' ), $version, true );
				$dependencies[] = 'select2-locale';
			}

			return array(
				'dependencies'	=> $dependencies,
			);
		}

		/**
		 * Displays a single item in the select.
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
				'value'		=> $value,
				'selected'	=> $this->is_value_checked_or_selected( $current, $value ),
			);

			if ( is_array( $label ) ) {
				if ( isset( $label['image'] ) ) {
					$option_atts['data-image'] = esc_url( $label['image'] );
				} elseif ( isset( $label['color'] ) ) {
					$option_atts['data-color'] = ltrim( $label['color'], '#' );
				}
				if ( isset( $label['label'] ) ) {
					$label = $label['label'];
				} else {
					$label = '';
				}
			}

			$output = '<option' . FieldManager::make_html_attributes( $option_atts, false, false ) . '>' . esc_html( $label ) . '</option>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}
	}

}
