<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Select' ) ) {

	class Select extends Radio {
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['name'] = $this->get_sanitized_name();
			if ( ! empty( $args['placeholder'] ) ) {
				$args['data-placeholder'] = json_encode( array( 'id' => '', 'text' => $args['placeholder'] ) );
			}
			unset( $args['placeholder'] );
			unset( $args['options'] );

			$output = '<select' . FieldManager::make_html_attributes( $args, false, false ) . '>';
			if ( ! empty( $this->args['placeholder'] ) ) {
				$output .= '<option value=""' . ( empty( $val ) ? ' selected="selected"' : '' ) . '>' . esc_html( $this->args['placeholder'] ) . '</option>';
			}
			foreach ( $this->args['options'] as $value => $label ) {
				$output .= $this->display_item( $value, $label, $val, false );
			}
			$output .= '</select>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

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

		protected function display_item( $value, $label, $current = '', $echo = true ) {
			$option_atts = array(
				'value'		=> $value,
				'selected'	=> $this->is_value_checked_or_selected( $value, $current ),
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
