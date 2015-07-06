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
		private static $base_dir = '';
		private static $base_url = '';

		private static $hierarchy_toplevel = array();
		private static $hierarchy_children = array();

		private static $scopes = array();
		private static $current_scope = '';

		private static $components = array();

		public static function register_hierarchy( $hierarchy, $toplevel = true ) {
			foreach ( $hierarchy as $class => $children ) {
				if ( $toplevel && ! in_array( $class, self::$hierarchy_toplevel ) ) {
					self::$hierarchy_toplevel[] = $class;
				}
				if ( ! isset( self::$hierarchy_children[ $class ] ) ) {
					self::$hierarchy_children[ $class ] = array_keys( $children );
				} else {
					self::$hierarchy_children[ $class ] = array_merge( self::$hierarchy_children[ $class ], array_keys( $children ) );
				}
				self::register_hierarchy( $children, false );
			}
		}

		public static function get_children( $class ) {
			if ( isset( self::$hierarchy_children[ $class ] ) ) {
				return self::$hierarchy_children[ $class ];
			}
			return array();
		}

		public static function is_toplevel( $class ) {
			if ( in_array( $class, self::$hierarchy_toplevel ) ) {
				return true;
			}
			return false;
		}

		public static function set_scope( $scope ) {
			if ( ! in_array( $scope, self::$scopes ) ) {
				self::$scopes[] = $scope;
			}
			self::$current_scope = $scope;
		}

		public static function get_scope() {
			return self::$current_scope;
		}

		public static function add( $component ) {
			if ( ! self::is_too_late() ) {
				if ( is_a( $component, 'WPDLib\Components\Base' ) ) {
					$component_class = get_class( $component );
					if ( self::is_toplevel( $component_class ) ) {
						if ( ! isset( self::$components[ $component_class ] ) ) {
							self::$components[ $component_class ] = array();
						}
						if ( ! isset( self::$components[ $component_class ][ $component->slug ] ) ) {
							$component->validate();
							$component->scope = self::$current_scope;
							self::$components[ $component_class ][ $component->slug ] = $component;
							return $component;
						}
						return self::$components[ $component_class ][ $component->slug ];
					}
					return new \WPDLib\Util\Error( 'no_toplevel_component', sprintf( __( 'The component %1$s of class %2$s is not a valid toplevel component.', 'wpdlib' ), $component->slug, $component_class ), '', self::$current_scope );
				}
				return new \WPDLib\Util\Error( 'no_component', __( 'The object is not a component.', 'wpdlib' ), '', self::$current_scope );
			}
			return new \WPDLib\Util\Error( 'too_late_component', sprintf( __( 'Components must not be added later than the %s hook.', 'wpdlib' ), '<code>init</code>' ), '', self::$current_scope );
		}

		public static function get( $component_path, $start_class = '' ) {
			$component_path = explode( '.', $component_path, 2 );
			if ( ! empty( $start_class ) ) {
				if ( isset( self::$components[ $class ] ) && isset( self::$components[ $class ][ $component_path[0] ] ) ) {
					$current = self::$components[ $class ][ $component_path[0] ];
					if ( isset( $component_path[1] ) ) {
						$current = self::_get( $component_path[1], $current->children );
					}
					if ( $current !== null ) {
						return $current;
					}
				}
			} else {
				foreach ( self::$components as $class => $components ) {
					if ( isset( $components[ $component_path[0] ] ) ) {
						$current = $components[ $component_path[0] ];
						if ( isset( $component_path[1] ) ) {
							$current = self::_get( $component_path[1], $current->children );
						}
						if ( $current !== null ) {
							return $current;
						}
					}
				}
			}
			return null;
		}

		public static function is_too_late() {
			return did_action( 'init' ) > 0 && ! doing_action( 'init' );
		}

		public static function get_info( $field = '' ) {
			$composer_file = self::get_base_dir() . '/composer.json';
			if ( ! file_exists( $composer_file ) ) {
				if ( ! empty( $field ) ) {
					return false;
				}
				return array();
			}
			$info = json_decode( file_get_contents( $composer_file ), true );
			if ( ! empty( $field ) ) {
				if ( isset( $info[ $field ] ) ) {
					return $info[ $field ];
				}
				return false;
			}
			return $info;
		}

		public static function get_dependency_info( $dependency, $field = '' ) {
			$bower_file = self::get_base_dir() . '/assets/vendor/' . $dependency . '/bower.json';
			if ( ! file_exists( $bower_file ) ) {
				if ( ! empty( $field ) ) {
					return false;
				}
				return array();
			}
			$info = json_decode( file_get_contents( $bower_file ), true );
			if ( ! empty( $field ) ) {
				if ( isset( $info[ $field ] ) ) {
					return $info[ $field ];
				}
				return false;
			}
			return $info;
		}

		public static function get_base_dir() {
			if ( empty( self::$base_dir ) ) {
				self::_determine_base();
			}
			return self::$base_dir;
		}

		public static function get_base_url() {
			if ( empty( self::$base_url ) ) {
				self::_determine_base();
			}
			return self::$base_url;
		}

		private static function _get( $component_path, $current_children ) {
			$component_path = explode( '.', $component_path, 2 );
			if ( isset( $current_children[ $component_path[0] ] ) ) {
				$current = $current_children[ $component_path[0] ];
				if ( isset( $component_path[1] ) ) {
					return self::_get( $component_path[1], $current->children );
				}
				return $current;
			}
			return null;
		}

		private static function _determine_base() {
			self::$base_dir = str_replace( '/inc/WPDLib/Components', '', dirname( __FILE__ ) );
			self::$base_url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, self::$base_dir );
		}
	}

}
