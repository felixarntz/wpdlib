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

if ( ! class_exists( 'WPDLib\FieldTypes\Base' ) ) {

	class Base {
		protected $type = '';
		protected $args = array();

		private static $enqueued = array();

		public function __construct( $type, $args ) {
			$this->type = $type;
			$this->args = wp_parse_args( $args, array(
				'id'		=> '',
				'name'		=> '',
				'class'		=> '',
				'required'	=> false,
				'readonly'	=> false,
				'disabled'	=> false,
			) );
			if ( strpos( $this->args['class'], 'wpdlib-input-' . $this->type ) === false ) {
				if ( ! empty( $this->args['class'] ) ) {
					$this->args['class'] .= ' ';
				}
				$this->args['class'] .= 'wpdlib-input-' . $this->type;
			}
			if ( strpos( $this->args['class'], 'wpdlib-input' ) === false ) {
				$this->args['class'] .= ' wpdlib-input';
			}
		}

		public function __get( $property ) {
			if ( method_exists( $this, $method = 'get_' . $property ) ) {
				return $this->$method();
			} elseif ( property_exists( $this, $property ) ) {
				return $this->$property;
			} elseif ( isset( $this->args[ $property ] ) ) {
				return $this->args[ $property ];
			}

			return null;
		}

		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['value'] = $val;

			$output = '<input type="' . $this->type . '"' . \WPDLib\FieldTypes\Manager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( $val === null ) {
				return '';
			}

			return \WPDLib\FieldTypes\Manager::format( $val, 'string', 'input' );
		}

		public function parse( $val, $formatted = false ) {
			if ( $formatted ) {
				return \WPDLib\FieldTypes\Manager::format( $val, 'string', 'output' );
			}

			return \WPDLib\FieldTypes\Manager::format( $val, 'string', 'input' );
		}

		public function enqueue_assets() {
			return array();
		}

		protected static function is_enqueued( $class ) {
			if ( ! in_array( $class, self::$enqueued ) ) {
				self::$enqueued[] = $class;
				return false;
			}
			return true;
		}
	}

}
