<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Util;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Util\Error' ) ) {

	final class Error extends \WP_Error {
		private $scope = '';

		public function __construct( $code = '', $message = '', $data = '', $scope = '' ) {
			parent::__construct( $code, $message, $data );
			$this->scope = $scope;
		}

		public function get_scope() {
			return $this->scope;
		}
	}

}
