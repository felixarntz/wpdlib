<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Components;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Components\Manager' ) ) {

	final class Manager {
		private static $instance = null;

		public static function instance() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private $hierarchy_toplevel = array();
		private $hierarchy_children = array();

		private function __construct() {

		}

		public function register_hierarchy( $hierarchy, $toplevel = true ) {
			foreach ( $hierarchy as $class => $children ) {
				if ( $toplevel && ! in_array( $class, $this->hierarchy_toplevel ) ) {
					$this->hierarchy_toplevel[] = $class;
				}
				if ( ! isset( $this->hierarchy_children[ $class ] ) ) {
					$this->hierarchy_children[ $class ] = array_keys( $children );
				} else {
					$this->hierarchy_children[ $class ] = array_merge( $this->hierarchy_children[ $class ], array_keys( $children ) );
				}
				$this->register_hierarchy( $children, false );
			}
		}

		public function get_children( $class ) {
			if ( isset( $this->hierarchy_children[ $class ] ) ) {
				return $this->hierarchy_children[ $class ];
			}
			return array();
		}

		public function is_toplevel( $class ) {
			if ( in_array( $class, $this->hierarchy_toplevel ) ) {
				return true;
			}
			return false;
		}
	}

}
