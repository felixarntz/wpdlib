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

if ( ! class_exists( 'WPDLib\FieldTypes\Textarea' ) ) {

	class Textarea extends Base {
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'rows'	=> 5,
			) );
			parent::__construct( $type, $args );
		}

		public function display( $val, $echo = true ) {
			$output = '<textarea' . FieldManager::make_html_attributes( $this->args, false, false ) . '>';
			$output .= esc_textarea( $val );
			$output .= '</textarea>';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}
	}

}
