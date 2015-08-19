<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Components;

use WPDLib\Util\Error as UtilError;

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

		private static $component_finder = array();

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
			if ( ! empty( $scope ) && ! in_array( $scope, self::$scopes ) ) {
				self::$scopes[] = $scope;
			}
			self::$current_scope = $scope;
		}

		public static function get_scope() {
			return self::$current_scope;
		}

		public static function add( $component ) {
			if ( self::is_too_late() ) {
				return new UtilError( 'too_late_component', sprintf( __( 'Components must not be added later than the %s hook.', 'wpdlib' ), '<code>init</code>' ), '', self::$current_scope );
			}

			if ( ! is_a( $component, 'WPDLib\Components\Base' ) ) {
				return new UtilError( 'no_component', __( 'The object is not a component.', 'wpdlib' ), '', self::$current_scope );
			}

			$component_class = get_class( $component );
			if ( ! self::is_toplevel( $component_class ) ) {
				return new UtilError( 'no_toplevel_component', sprintf( __( 'The component %1$s of class %2$s is not a valid toplevel component.', 'wpdlib' ), $component->slug, $component_class ), '', self::$current_scope );
			}

			$status = $component->validate();
			if ( is_wp_error( $status ) ) {
				return $status;
			}

			if ( ! $component->is_valid_slug() ) {
				return new UtilError( 'no_valid_slug_component', sprintf( __( 'A component of class %1$s with slug %2$s already exists.', 'wpdlib' ), $component_class, $component->slug ), '', self::$current_scope );
			}

			if ( ! isset( self::$components[ $component_class ] ) ) {
				self::$components[ $component_class ] = array();
			}

			// for toplevel components, if a component of the same slug already exists, merge their properties then return the original
			if ( isset( self::$components[ $component_class ][ $component->slug ] ) ) {
				return self::merge_components( self::$components[ $component_class ][ $component->slug ], $component );
			}

			self::$components[ $component_class ][ $component->slug ] = $component;
			return $component;
		}

		public static function get( $component_path, $class_path = '', $single = false ) {
			$component_path = explode( '.', $component_path, 2 );
			$class_path = explode( '.', $class_path, 2 );

			$toplevel_components = array();
			if ( isset( $class_path[0] ) && ! empty( $class_path[0] ) && '*' !== $class_path[0] ) {
				if ( isset( self::$components[ $class_path[0] ] ) ) {
					$toplevel_components = self::$components[ $class_path[0] ];
				}
			} else {
				foreach ( self::$components as $class => $components ) {
					$toplevel_components = array_merge( $toplevel_components, $components );
				}
			}

			$class_path_children = isset( $class_path[1] ) ? $class_path[1] : '';

			$results = array();

			if ( '*' == $component_path[0] ) {
				if ( isset( $component_path[1] ) ) {
					foreach ( $toplevel_components as $current ) {
						$current = self::get_components_recursive( $component_path[1], $current, $class_path_children );
						$results = array_merge( $results, $current );
					}
				} else {
					$results = array_merge( $results, array_values( $toplevel_components ) );
				}
			} elseif ( isset( $toplevel_components[ $component_path[0] ] ) ) {
				$current = $toplevel_components[ $component_path[0] ];
				if ( isset( $component_path[1] ) ) {
					$current = self::get_components_recursive( $component_path[1], $current, $class_path_children );
					$results = array_merge( $results, $current );
				} else {
					$results[] = $current;
				}
			}

			if ( $single ) {
				if ( count( $results ) > 0 ) {
					return $results[0];
				}
				return null;
			}

			return $results;
		}

		public static function exists( $slug, $class, $check_slug = '' ) {
			if ( ! isset( self::$component_finder[ $class ] ) ) {
				self::$component_finder[ $class ] = array();
			}

			if ( ! empty( $check_slug ) ) {
				if ( ! isset( self::$component_finder[ $class ][ $check_slug ] ) ) {
					self::$component_finder[ $class ][ $check_slug ] = array();
				}
				if ( ! in_array( $slug, self::$component_finder[ $class ][ $check_slug ] ) ) {
					self::$component_finder[ $class ][ $check_slug ][] = $slug;
					return false;
				}
				return true;
			}

			if ( ! in_array( $slug, self::$component_finder[ $class ] ) ) {
				self::$component_finder[ $class ][] = $slug;
				return false;
			}
			return true;
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
				self::determine_base();
			}
			return self::$base_dir;
		}

		public static function get_base_url() {
			if ( empty( self::$base_url ) ) {
				self::determine_base();
			}
			return self::$base_url;
		}

		public static function load_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'wpdlib' );
			return load_textdomain( 'wpdlib', self::get_base_dir() . '/languages/wpdlib-' . $locale . '.mo' );
		}

		public static function create_menu() {
			$menus = self::get( '*', 'WPDLib\Components\Menu' );
			foreach ( $menus as $menu ) {
				$menu->create();
			}
		}

		private static function get_components_recursive( $component_path, $curr, $class_path = '' ) {
			$component_path = explode( '.', $component_path, 2 );
			$class_path = explode( '.', $class_path, 2 );

			$current_class_path = isset( $class_path[0] ) ? $class_path[0] : '*';

			$current_children = $curr->get_children( $current_class_path );

			$class_path_children = isset( $class_path[1] ) ? $class_path[1] : '';

			$results = array();

			if ( '*' == $component_path[0] ) {
				if ( isset( $component_path[1] ) ) {
					foreach ( $current_children as $current ) {
						$current = self::get_components_recursive( $component_path[1], $current, $class_path_children );
						$results = array_merge( $results, $current );
					}
				} else {
					$results = array_values( $current_children );
				}
			} elseif ( isset( $current_children[ $component_path[0] ] ) ) {
				$current = $current_children[ $component_path[0] ];
				if ( isset( $component_path[1] ) ) {
					$current = self::get_components_recursive( $component_path[1], $current, $class_path_children );
					$results = array_merge( $results, $current );
				} else {
					$results[] = $current;
				}
			}

			return $results;
		}

		private static function merge_components( $a, $b ) {
			$args = $a->args;
			$parents = $a->parents;
			$children = $a->children;

			$_args = $b->args;
			$_parents = $b->parents;
			$_children = $b->children;

			$a->args = array_merge( $args, $_args );
			$a->parents = array_merge( $parents, $_parents );
			$a->children = array_merge_recursive( $children, $_children );

			return $a;
		}

		private static function determine_base() {
			self::$base_dir = str_replace( '/inc/WPDLib/Components', '', wp_normalize_path( dirname( __FILE__ ) ) );
			self::$base_url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, self::$base_dir );
		}
	}

	Manager::load_textdomain();
	add_action( 'admin_menu', array( 'WPDLib\Components\Manager', 'create_menu' ), 40 );

}
