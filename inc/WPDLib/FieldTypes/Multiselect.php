<?php
/**
 * @package WPDLib
 * @version 0.6.4
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Multiselect' ) ) {
	/**
	 * Class for a multiselect (select with multiple options selectable at once) field.
	 *
	 * @since 0.5.0
	 */
	class Multiselect extends Select {

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
			parent::__construct( $type, $args );
			$this->args['multiple'] = true;
		}
	}

}
