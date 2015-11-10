<?php
/**
 * @package WPDLib
 * @version 0.5.1
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Util;

use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Util\Error' ) ) {
	/**
	 * A custom error class that extends WP_Error.
	 *
	 * It also contains a scope where the error occurred.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	final class Error extends WPError {

		/**
		 * @since 0.5.0
		 * @var string Holds the error scope.
		 */
		private $scope = '';

		/**
		 * Class constructor.
		 *
		 * @since 0.5.0
		 * @param string $code the error code
		 * @param string $message the error message
		 * @param string $data additional data for the error
		 * @param string $scope the error scope
		 */
		public function __construct( $code = '', $message = '', $data = '', $scope = '' ) {
			parent::__construct( $code, $message, $data );
			$this->scope = $scope;
		}

		/**
		 * Returns the error scope.
		 *
		 * @since 0.5.0
		 * @return string the error scope
		 */
		public function get_scope() {
			return $this->scope;
		}
	}

}
