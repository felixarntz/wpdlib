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

if ( ! class_exists( 'WPDLib\FieldTypes\Wysiwyg' ) ) {

	class Wysiwyg extends Textarea {
		public function display( $val, $echo = true ) {
			$wp_editor_settings = array(
				'wpautop'		=> true,
				'media_buttons'	=> false,
				'textarea_name'	=> $this->args['name'],
				'textarea_rows'	=> absint( $this->args['rows'],
				'editor_class'	=> $this->args['class'],
				'tinymce'		=> array( 'plugins' => 'wordpress' ),
			);

			ob_start();
			wp_editor( $val, $this->args['id'], $wp_editor_settings );
			$output = ob_get_clean();

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( $val === null ) {
				return '';
			}

			return FieldManager::format( $value, 'html', 'input' );;
		}

		public function parse( $val, $formatted = false ) {
			if ( $formatted ) {
				return FieldManager::format( $val, 'html', 'output' );
			}

			return FieldManager::format( $val, 'html', 'input' );
		}
	}

}
