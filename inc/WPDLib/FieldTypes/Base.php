<?php
/**
 * @package WPDLib
 * @version 0.5.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\FieldTypes\Manager as FieldManager;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Base' ) ) {
	/**
	 * The base class for all field types.
	 *
	 * Every field type that does not have its own class will be instantiated with this class.
	 *
	 * @since 0.5.0
	 */
	class Base {

		/**
		 * @since 0.5.0
		 * @var string Holds the field type.
		 */
		protected $type = '';

		/**
		 * @since 0.5.0
		 * @var array Holds the field type arguments.
		 */
		protected $args = array();

		/**
		 * @since 0.5.0
		 * @var string Stores whether assets for this type have been enqueued yet.
		 */
		private static $enqueued = array();

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
			$this->type = $type;
			$this->args = wp_parse_args( $args, array(
				'id'			=> '',
				'name'			=> '',
				'class'			=> '',
				'placeholder'	=> '',
				'required'		=> false,
				'readonly'		=> false,
				'disabled'		=> false,
			) );
			if ( isset( $this->args['label'] ) ) {
				unset( $this->args['label'] );
			}
			if ( strpos( $this->args['class'], 'wpdlib-input' ) === false ) {
				if ( ! empty( $this->args['class'] ) ) {
					$this->args['class'] .= ' ';
				}
				$this->args['class'] .= 'wpdlib-input';
			}
			if ( strpos( $this->args['class'], 'wpdlib-input-' . $this->type ) === false ) {
				$this->args['class'] .= ' wpdlib-input-' . $this->type;
			}
		}

		/**
		 * Magic set method.
		 *
		 * This function provides direct access to the field type arguments.
		 *
		 * Note that only 'id' and 'name' can be set.
		 * Other arguments are read-only.
		 *
		 * @since 0.5.0
		 * @param string $property name of the property to get
		 * @param mixed $value new value for the property
		 */
		public function __set( $property, $value ) {
			if ( in_array( $property, array( 'id', 'name' ) ) ) {
				$this->args[ $property ] = $value;
			}
		}

		/**
		 * Magic get method.
		 *
		 * This function provides direct access to the field type arguments.
		 *
		 * @since 0.5.0
		 * @param string $property name of the property to get
		 * @return mixed value of the property or null if it does not exist
		 */
		public function __get( $property ) {
			if ( property_exists( $this, $property ) ) {
				return $this->$property;
			} elseif ( isset( $this->args[ $property ] ) ) {
				return $this->args[ $property ];
			}

			return null;
		}

		/**
		 * Magic isset method.
		 *
		 * This function provides direct access to the field type arguments.
		 *
		 * @since 0.5.0
		 * @param string $property name of the property to check
		 * @return bool true if the property exists, otherwise false
		 */
		public function __isset( $property ) {
			if ( property_exists( $this, $property ) ) {
				return true;
			} elseif ( isset( $this->args[ $property ] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param string $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			$args = $this->args;
			$args['value'] = $val;

			$output = '<input type="' . $this->type . '"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

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

			return FieldManager::format( $val, 'string', 'input' );
		}

		/**
		 * Checks whether a value for the field is considered empty.
		 *
		 * This function is needed to check whether a required field has been properly filled.
		 *
		 * @since 0.5.0
		 * @param string $val the current value of the field
		 * @return bool whether the value is considered empty
		 */
		public function is_empty( $val ) {
			return empty( $val );
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
				return FieldManager::format( $val, 'string', 'output' );
			}

			return FieldManager::format( $val, 'string', 'input' );
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
			return array();
		}

		/**
		 * Checks whether the assets of a field type of a specific class have been enqueued yet.
		 *
		 * @since 0.5.0
		 * @param string $class the class name to check for its assets
		 * @return bool whether the assets for the class have been enqueued yet
		 */
		protected static function is_enqueued( $class ) {
			if ( ! in_array( $class, self::$enqueued ) ) {
				self::$enqueued[] = $class;
				return false;
			}
			return true;
		}
	}

}
