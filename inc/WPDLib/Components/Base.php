<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Components;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\Util\Util;
use WPDLib\Util\Error as UtilError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Components\Base' ) ) {

	abstract class Base {
		protected $slug = '';
		protected $args = array();

		protected $scope = '';
		protected $parents = array();
		protected $children = array();

		protected $validated = false;
		protected $valid_slug = null;
		protected $validate_filter = '';

		public function __construct( $slug, $args ) {
			$this->slug = $slug;
			$this->args = (array) $args;
		}

		public function __set( $property, $value ) {
			if ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			} elseif ( isset( $this->args[ $property ] ) ) {
				$this->args[ $property ] = $value;
			}
		}

		public function __get( $property ) {
			if ( property_exists( $this, $property ) ) {
				return $this->$property;
			} elseif ( isset( $this->args[ $property ] ) ) {
				return $this->args[ $property ];
			}

			return null;
		}

		public function __isset( $property ) {
			if ( property_exists( $this, $property ) ) {
				return true;
			} elseif ( isset( $this->args[ $property ] ) ) {
				return true;
			}

			return false;
		}

		public function add( $component ) {
			if ( ComponentManager::is_too_late() ) {
				return new UtilError( 'too_late_component', sprintf( __( 'Components must not be added later than the %s hook.', 'wpdlib' ), '<code>init</code>' ), '', ComponentManager::get_scope() );
			}

			if ( ! is_a( $component, 'WPDLib\Components\Base' ) ) {
				return new UtilError( 'no_component', __( 'The object is not a component.', 'wpdlib' ), '', ComponentManager::get_scope() );
			}

			$component_class = get_class( $component );

			$children = ComponentManager::get_children( get_class( $this ) );
			if ( ! in_array( $component_class, $children ) ) {
				return new UtilError( 'no_valid_child_component', sprintf( __( 'The component %1$s of class %2$s is not a valid child for the component %3$s.', 'wpdlib' ), $component->slug, get_class( $component ), $this->slug ), '', ComponentManager::get_scope() );
			}

			$status = $component->validate( $this );
			if ( is_wp_error( $status ) ) {
				return $status;
			}

			if ( ! $component->is_valid_slug() ) {
				return new UtilError( 'no_valid_slug_component', sprintf( __( 'A component of class %1$s with slug %2$s already exists.', 'wpdlib' ), get_class( $component ), $component->slug ), '', ComponentManager::get_scope() );
			}

			if ( ! isset( $this->children[ $component_class ] ) ) {
				$this->children[ $component_class ] = array();
			}

			$this->children[ $component_class ] = Util::object_array_insert( $this->children[ $component_class ], $component, 'slug', 'position' );

			return $component;
		}

		public function get_path() {
			$path = array();

			$parents = $this->parents;
			while ( count( $parents ) > 0 ) {
				$parent_slug = key( $parents );
				$path[] = $parent_slug;
				$parents = $parents[ $parent_slug ]->parents;
			}

			return implode( '.', array_reverse( $path ) );
		}

		public function get_children( $class = '' ) {
			$children = array();

			if ( $class && '*' !== $class ) {
				if ( isset( $this->children[ $class ] ) ) {
					$children = $this->children[ $class ];
				}
			} else {
				$children = Util::object_array_merge( $this->children, 'slug', 'position' );
			}

			return $children;
		}

		public function get_parent( $index = 0, $depth = 1 ) {
			$current = $this;
			for ( $i = 0; $i < $depth; $i++ ) {
				$parents = array_values( $current->parents );
				if ( isset( $parents[ $index ] ) ) {
					$current = $parents[ $index ];
				} else {
					return null;
				}
			}
			return $current;
		}

		public function validate( $parent = null ) {
			if ( $parent !== null ) {
				if ( count( $this->parents ) > 0 && ! $this->supports_multiparents() ) {
					return new UtilError( 'no_multiparent_component', sprintf( __( 'The component %1$s of class %2$s already has a parent assigned and is not a multiparent component.', 'wpdlib' ), $this->slug, get_class( $this ) ), '', ComponentManager::get_scope() );
				}
				$this->parents[ $parent->slug ] = $parent;
			}
			if ( ! $this->validated ) {
				$defaults = $this->get_defaults();
				foreach ( $defaults as $key => $default ) {
					if ( ! isset( $this->args[ $key ] ) ) {
						$this->args[ $key ] = $default;
					}
				}
				$this->scope = ComponentManager::get_scope();

				if ( ! empty( $this->validate_filter ) ) {
					$this->args = apply_filters( $this->validate_filter, $this->args, $this );
				}

				$this->validated = true;
				return true;
			}
			return false;
		}

		public function is_valid_slug() {
			if ( $this->valid_slug === null ) {
				$globalnames = $this->supports_globalslug();
				if ( $globalnames !== true ) {
					if ( $globalnames !== false ) {
						$found = false;
						$parent = $this->get_parent();
						if ( $parent !== null ) {
							$found = true;
							while ( get_class( $parent ) != $globalnames ) {
								$parent = $parent->get_parent();
								if ( $parent === null ) {
									$found = false;
									break;
								}
							}
						}
						if ( $found ) {
							$this->valid_slug = ! ComponentManager::exists( $this->slug, get_class( $this ), $parent->slug );
						} else {
							$this->valid_slug = ! ComponentManager::exists( $this->slug, get_class( $this ) );
						}
					} else {
						$this->valid_slug = ! ComponentManager::exists( $this->slug, get_class( $this ) );
					}
				} else {
					ComponentManager::exists( $this->slug, get_class( $this ) ); // just use the function to add the component
					$this->valid_slug = true;
				}
			}
			return $this->valid_slug;
		}

		protected abstract function get_defaults();

		protected abstract function supports_multiparents();

		protected abstract function supports_globalslug();
	}

}
