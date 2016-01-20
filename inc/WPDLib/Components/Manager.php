<?php
/**
 * @package WPDLib
 * @version 0.6.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Components;

use WPDLib\Util\Error as UtilError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Components\Manager' ) ) {
	/**
	 * This class contains and manages all components.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	final class Manager {

		/**
		 * @since 0.5.0
		 * @var string Holds the base path to wpdlib.
		 */
		private static $base_dir = '';

		/**
		 * @since 0.5.0
		 * @var string Holds the base URL to wpdlib.
		 */
		private static $base_url = '';

		/**
		 * @since 0.5.0
		 * @var array Holds the class names of all components that are toplevel components.
		 */
		private static $hierarchy_toplevel = array();

		/**
		 * @since 0.5.0
		 * @var array Holds an array of valid child component class names for each component class.
		 */
		private static $hierarchy_children = array();

		/**
		 * @since 0.5.0
		 * @var array Holds an array of all the registered scopes.
		 */
		private static $scopes = array();

		/**
		 * @since 0.5.0
		 * @var string Holds the current scope (an empty string is the default scope).
		 */
		private static $current_scope = '';

		/**
		 * @since 0.5.0
		 * @var array Holds all toplevel components. Since components are nested within each other, this is the access point to all the added components.
		 */
		private static $components = array();

		/**
		 * @since 0.5.0
		 * @var array Holds utility data to improve performance of finding a component.
		 */
		private static $component_finder = array();

		/**
		 * Registers a component hierarchy.
		 *
		 * A hierarchy is a nested array of component class names as keys and an array of their child component classes as values.
		 * The hierarchy determines which components can be parent/child of which other component(s).
		 *
		 * The function iterates through the hierarchy array recursively.
		 *
		 * @since 0.5.0
		 * @param array $hierarchy the hierarchy array to register
		 * @param bool $toplevel whether the current level is the toplevel (used internally for the recursion, default is true) - NEVER change this parameter!
		 */
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

		/**
		 * Returns the valid class names a child component of a specific class must have one of.
		 *
		 * @since 0.5.0
		 * @param string $class the class name to get the child class names for
		 * @return array the array of class names (or an empty array if the component cannot have any children)
		 */
		public static function get_children( $class ) {
			if ( isset( self::$hierarchy_children[ $class ] ) ) {
				return self::$hierarchy_children[ $class ];
			}
			return array();
		}

		/**
		 * Checks whether a component class is toplevel.
		 *
		 * @since 0.5.0
		 * @param string $class the class to check
		 * @return bool true if the component class is toplevel, otherwise false
		 */
		public static function is_toplevel( $class ) {
			if ( in_array( $class, self::$hierarchy_toplevel ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Sets the current scope.
		 *
		 * This function should be called by every individual plugin or theme before registering components.
		 * However in most cases it does not need to be called directly since the Definitely plugins handle it.
		 *
		 * Each plugin and theme should provide a unique slug though.
		 *
		 * @since 0.5.0
		 * @param string $scope the scope to set
		 */
		public static function set_scope( $scope ) {
			if ( ! empty( $scope ) && ! in_array( $scope, self::$scopes ) ) {
				self::$scopes[] = $scope;
			}
			self::$current_scope = $scope;
		}

		/**
		 * Returns the current scope.
		 *
		 * @since 0.5.0
		 * @return string the current scope
		 */
		public static function get_scope() {
			return self::$current_scope;
		}

		/**
		 * Adds a toplevel component.
		 *
		 * This method basically works like the `add()` method in each component.
		 * It is needed to add toplevel components since they obviously cannot be added from another component.
		 *
		 * The function also validates the component.
		 *
		 * The function checks multiple things and only adds the component if all requirements are met.
		 * It returns the added component or an error object.
		 *
		 * @since 0.5.0
		 * @see WPDLib\Components\Base::add()
		 * @param WPDLib\Components\Base the component to add
		 * @return WPDLib\Components\Base|WPDLib\Util\Error the added component or an error object
		 */
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

		/**
		 * Gets one or more components.
		 *
		 * This is the main method to use when you need to get specific components.
		 *
		 * The function accepts a component path as first parameter.
		 * A component path is a string of hierarchically ordered component slugs, each separated by dots.
		 * Instead of specific slugs it is also possible to specify asterisks to get multiple components with the same parents or type.
		 *
		 * The second parameter accepts a class path.
		 * It works just like a component path, but takes component class names.
		 * It is optional, but should be used if you only need children of one class where the parent can have multiple classes as children.
		 *
		 * If you set the third parameter to true, the function will only return a single component, not an array of components.
		 *
		 * @since 0.5.0
		 * @see WPDLib\Components\Manager::get_components_results()
		 * @see WPDLib\Components\Manager::get_components_recursive()
		 * @param string $component_path the component path specifying which component(s) you want to retrieve
		 * @param string $class_path the class path specifying class restrictions (optional, default is an empty string)
		 * @param bool $single whether to return just a single component instead of an array (default is false)
		 * @return array|WPDLib\Components\Base returns an array of components or (if $single is true) a single component
		 */
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

			$results = self::get_components_results( $component_path, $toplevel_components, $class_path_children );

			if ( $single ) {
				if ( count( $results ) > 0 ) {
					return $results[0];
				}
				return null;
			}

			return $results;
		}

		/**
		 * Checks whether a component with a specific slug and class already exists.
		 *
		 * If you specify the third parameter appropriately, the search will be faster.
		 *
		 * @since 0.5.0
		 * @param string $slug the component slug
		 * @param string $class the component's class name
		 * @param string $check_slug the component's parent slug (optional)
		 * @return bool whether the component exists
		 */
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

		/**
		 * Checks whether it is too late to add new components.
		 *
		 * Components must not be added later than the WordPress 'init' action.
		 *
		 * @since 0.5.0
		 * @return whether it is too late to add components
		 */
		public static function is_too_late() {
			return did_action( 'init' ) > 0 && ! doing_action( 'init' );
		}

		/**
		 * Returns information about WPDLib.
		 *
		 * It will read from composer.json and return an array of information or a single field (if you specify the first parameter).
		 *
		 * @since 0.5.0
		 * @param string $field the field to get information for (optional, default is an empty string)
		 * @return array|string either all information in an array or a single field as string
		 */
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

		/**
		 * Returns information about a WPDLib dependency.
		 *
		 * It will read from the dependency's bower.json and return an array of information or a single field (if you specify the second parameter).
		 *
		 * @since 0.5.0
		 * @param string $dependency the dependency slug (this is the folder name, for example 'select2')
		 * @param string $field the field to get information for (optional, default is an empty string)
		 * @return array|string either all information in an array or a single field as string
		 */
		public static function get_dependency_info( $dependency, $field = '' ) {
			$bower_file = self::get_base_dir() . '/assets/vendor/' . $dependency . '/.bower.json';
			if ( ! file_exists( $bower_file ) ) {
				$bower_file = self::get_base_dir() . '/assets/vendor/' . $dependency . '/bower.json';
			}
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

		/**
		 * Returns the base path to WPDLib.
		 *
		 * @since 0.5.0
		 * @return string path to WPDLib
		 */
		public static function get_base_dir() {
			if ( empty( self::$base_dir ) ) {
				self::determine_base();
			}
			return self::$base_dir;
		}

		/**
		 * Returns the base URL to WPDLib.
		 *
		 * @since 0.5.0
		 * @return string URL to WPDLib
		 */
		public static function get_base_url() {
			if ( empty( self::$base_url ) ) {
				self::determine_base();
			}
			return self::$base_url;
		}

		/**
		 * Loads the textdomain of WPDLib.
		 *
		 * @since 0.5.0
		 * @return bool whether the textdomain was successfully loaded
		 */
		public static function load_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'wpdlib' );
			return load_textdomain( 'wpdlib', self::get_base_dir() . '/languages/wpdlib-' . $locale . '.mo' );
		}

		/**
		 * Creates menus for all menu components.
		 *
		 * Since the menu component is part of WPDLib, the library handles these components itself by running this function.
		 *
		 * @since 0.5.0
		 */
		public static function create_menu() {
			$menus = self::get( '*', 'WPDLib\Components\Menu' );
			foreach ( $menus as $menu ) {
				$menu->create();
			}
		}

		/**
		 * Gets one or more components.
		 *
		 * This is a helper function that collaborates with `get_component_results()`.
		 *
		 * @since 0.5.0
		 * @see WPDLib\Components\Manager::get_components_results()
		 * @param string $component_path the component path specifying which component(s) you want to retrieve
		 * @param WPDLib\Components\Base $curr the current component to iterate through
		 * @param string $class_path the class path specifying class restrictions (optional, default is an empty string)
		 * @return array returns an array of components
		 */
		private static function get_components_recursive( $component_path, $curr, $class_path = '' ) {
			$component_path = explode( '.', $component_path, 2 );
			$class_path = explode( '.', $class_path, 2 );

			$current_class_path = isset( $class_path[0] ) ? $class_path[0] : '*';

			$current_children = $curr->get_children( $current_class_path );

			$class_path_children = isset( $class_path[1] ) ? $class_path[1] : '';

			return self::get_components_results( $component_path, $current_children, $class_path_children );
		}

		/**
		 * Gets one or more components.
		 *
		 * This is a helper function that collaborates with `get_component_recursive()`.
		 *
		 * @since 0.5.0
		 * @see WPDLib\Components\Manager::get_components_recursive()
		 * @param string $component_path the component path specifying which component(s) you want to retrieve
		 * @param array $current_children the current component's children to iterate through
		 * @param string $class_path_children the class path part specifying class restrictions
		 * @return array returns an array of components
		 */
		private static function get_components_results( $component_path, $current_children, $class_path_children ) {
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

		/**
		 * Merges a component into another.
		 *
		 * The two components must be of the same class!
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $a the original component
		 * @param WPDLib\Components\Base $b the component to merge into the original component
		 * @return WPDLib\Components\Base the first component with the second component merged into it
		 */
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

		/**
		 * Determines the base path and base URL to WPDLib.
		 *
		 * @since 0.5.0
		 */
		private static function determine_base() {
			self::$base_dir = str_replace( '/inc/WPDLib/Components', '', wp_normalize_path( dirname( __FILE__ ) ) );
			self::$base_url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, self::$base_dir );
		}
	}

	// automatically load the WPDLib textdomain
	Manager::load_textdomain();

	// automatically create menu from all menu components
	add_action( 'admin_menu', array( 'WPDLib\Components\Manager', 'create_menu' ), 40 );

}
