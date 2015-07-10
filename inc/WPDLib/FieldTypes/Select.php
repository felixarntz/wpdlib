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

if ( ! class_exists( 'WPDLib\FieldTypes\Select' ) ) {

	class Select extends \WPDLib\FieldTypes\Radio {
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['name'] = $this->get_sanitized_name();
			unset( $args['placeholder'] );
			unset( $args['options'] );

			$output = '<select' . \WPDLib\FieldTypes\Manager::make_html_attributes( $args, false, false ) . '>';
			if ( ! empty( $this->args['placeholder'] ) ) {
				$output .= '<option value=""' . ( empty( $val ) ? ' selected="selected"' : '' ) . '>' . esc_html( $this->args['placeholder'] ) . '</option>';
			}
			foreach ( $this->args['options'] as $value => $label ) {
				$option_atts = array(
					'value'		=> $value,
					'selected'	=> $this->is_value_checked_or_selected( $value, $val ),
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
				$output .= '<option' . \WPDLib\FieldTypes\Manager::make_html_attributes( $option_atts, false, false ) . '>' . esc_html( $label ) . '</option>';
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

			$assets_dir = \WPDLib\Components\Manager::get_base_dir() . '/assets';
			$assets_url = \WPDLib\Components\Manager::get_base_url() . '/assets';
			$version = \WPDLib\Components\Manager::get_dependency_info( 'select2', 'version' );

			wp_enqueue_style( 'select2', $assets_url . '/vendor/select2/select2.css', array(), $version );
			wp_enqueue_script( 'select2', $assets_url . '/vendor/select2/select2.min.js', array( 'jquery' ), $version, true );

			$dependencies = array( 'select2' );

			$locale = str_replace( '_', '-', get_locale() );
			$language = substr( $locale, 0, 2 );

			if ( file_exists( $assets_dir . '/vendor/select2/select2_locale_' . $locale . '.js' ) ) {
				wp_enqueue_script( 'select2-locale', $assets_url . '/vendor/select2/select2_locale_' . $locale . '.js', array( 'select2' ), $version, true );
				$dependencies[] = 'select2-locale';
			} elseif( file_exists( $assets_dir . '/vendor/select2/select2_locale_' . $language . '.js' ) ) {
				wp_enqueue_script( 'select2-locale', $assets_url . '/vendor/select2/select2_locale_' . $language . '.js', array( 'select2' ), $version, true );
				$dependencies[] = 'select2-locale';
			}

			return array(
				'dependencies'	=> $dependencies,
			);
		}
	}

}
