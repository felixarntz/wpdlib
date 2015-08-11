<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Util;

use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Util\Error' ) ) {

	final class Error extends WPError {
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
