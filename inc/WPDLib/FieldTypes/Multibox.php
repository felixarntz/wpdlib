<?php
/**
 * @package WPDLib
 * @version 0.6.3
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Multibox' ) ) {
	/**
	 * Class for a multibox (checkbox group) field.
	 *
	 * @since 0.5.0
	 */
	class Multibox extends Radio {

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
