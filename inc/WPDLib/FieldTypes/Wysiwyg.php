<?php
/**
 * WPDLib\FieldTypes\Wysiwyg class
 *
 * @package WPDLib
 * @subpackage FieldTypes
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Wysiwyg' ) ) {
	/**
	 * Class for a WYSIWYG field.
	 *
	 * This field uses the default WordPress editor as field control.
	 *
	 * @since 0.5.0
	 */
	class Wysiwyg extends Textarea {

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param string $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			$wp_editor_settings = array(
				'textarea_name'  => $this->args['name'],
				'textarea_rows'  => $this->args['rows'],
				'editor_class'   => $this->args['class'],
				'default_editor' => user_can_richedit() ? 'tinymce' : 'html',
				'wpautop'        => true,
				'media_buttons'  => true,
				'quicktags'      => array(
					'buttons'        => 'strong,em,u,link,block,del,ins,img,ul,ol,li,code,close',
				),
				'tinymce'        => array(
					'toolbar1'       => 'bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,spellchecker,wp_adv',
				),
			);

			ob_start();
			wp_editor( $val, $this->args['id'], $wp_editor_settings );
			$output = ob_get_clean();

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
		 * @return string the validated field value
		 */
		public function validate( $val = null ) {
			if ( ! $val ) {
				return '';
			}

			return FieldManager::format( $val, 'html', 'input' );;
		}

		/**
		 * Parses a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return string the correctly parsed value
		 */
		public function parse( $val, $formatted = false ) {
			if ( $formatted ) {
				$parsed = FieldManager::format( $val, 'html', 'input' );
				if ( ! is_array( $formatted ) ) {
					$formatted = array();
				}
				$formatted = wp_parse_args( $formatted, array(
					'wpautop'   => true,
					'shortcode' => false,
				) );

				if ( $formatted['wpautop'] ) {
					$parsed = wpautop( $parsed );
				}

				if ( $formatted['shortcode'] ) {
					$parsed = do_shortcode( $parsed );
				}

				return $parsed;
			}

			return FieldManager::format( $val, 'html', 'input' );
		}
	}

}
