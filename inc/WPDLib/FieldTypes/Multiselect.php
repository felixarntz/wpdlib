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

if ( ! class_exists( 'WPDLib\FieldTypes\Multiselect' ) ) {

	class Multiselect extends \WPDLib\FieldTypes\Select {
		public function __construct( $type, $args ) {
			parent::__construct( $type, $args );
			$this->args['multiple'] = true;
		}
	}

}
