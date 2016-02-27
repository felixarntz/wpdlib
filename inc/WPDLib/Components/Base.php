<?php
/**
 * @package WPDLib
 * @version 0.6.2
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
	/**
	 * The base class for all components.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	abstract class Base {

		/**
		 * @since 0.5.0
		 * @var string Holds the slug of the component.
		 */
		protected $slug = '';

		/**
		 * @since 0.5.0
		 * @var array Holds the properties of the component.
		 */
		protected $args = array();

		/**
		 * @since 0.5.0
		 * @var string Holds the scope the component belongs to.
		 */
		protected $scope = '';

		/**
		 * @since 0.5.0
		 * @var array Holds the component's parent components (in most cases it will be just one).
		 */
		protected $parents = array();

		/**
		 * @since 0.5.0
		 * @var array Holds the component's child components, separated by class name.
		 */
		protected $children = array();

		/**
		 * @since 0.5.0
		 * @var bool Stores whether the component has been validated yet.
		 */
		protected $validated = false;

		/**
		 * @since 0.5.0
		 * @var bool|null Stores whether the component slug is valid (if it has already been validated).
		 */
		protected $valid_slug = null;

		/**
		 * @since 0.5.0
		 * @var string Holds the name of the filter that should be executed once the component has been validated.
		 */
		protected $validate_filter = '';

		/**
		 * Class constructor.
		 *
		 * @since 0.5.0
		 * @param string $slug the field slug
		 * @param array $args array of field properties
		 */
		public function __construct( $slug, $args ) {
			$this->slug = $slug;
			$this->args = (array) $args;
		}

		/**
		 * Magic set method.
		 *
		 * This function provides direct access to the component properties.
		 *
		 * @since 0.5.0
		 * @param string $property name of the property to get
		 * @param mixed $value new value for the property
		 */
		public function __set( $property, $value ) {
			if ( ComponentManager::is_too_late() ) {
				return;
			}

			if ( in_array( $property, array( 'scope', 'args', 'children', 'parents', 'validate_filter' ) ) ) {
				$this->$property = $value;
			} elseif ( isset( $this->args[ $property ] ) ) {
				$this->args[ $property ] = $value;
			}
		}

		/**
		 * Magic get method.
		 *
		 * This function provides direct access to the component properties.
		 *
		 * @since 0.5.0
		 * @param string $property name of the property to get
		 * @return mixed value of the property or null if it does not exist
		 */
		public function __get( $property ) {
			if ( property_exists( $this, $property ) ) {
				return $this->$property;
			} elseif ( isset( $this->args[ $property ] ) ) {
				return $this->args[ $property ];
			}

			return null;
		}

		/**
		 * Magic isset method.
		 *
		 * This function provides direct access to the component properties.
		 *
		 * @since 0.5.0
		 * @param string $property name of the property to check
		 * @return bool true if the property exists, otherwise false
		 */
		public function __isset( $property ) {
			if ( property_exists( $this, $property ) ) {
				return true;
			} elseif ( isset( $this->args[ $property ] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Adds a component as a child to the component.
		 *
		 * The function also validates the component.
		 *
		 * The function checks multiple things and only adds the component if all requirements are met.
		 * It returns the added component or an error object.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base the component to add as a child
		 * @return WPDLib\Components\Base|WPDLib\Util\Error either the added component or an error object
		 */
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

		/**
		 * Returns the slug path to the component.
		 *
		 * This path consists of the slugs that lead to this component, each separated by a dot.
		 *
		 * @since 0.5.0
		 * @return string the slug path to the component
		 */
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

		/**
		 * Returns the class path to the component.
		 *
		 * This path consists of the class names that lead to this component, each separated by a dot.
		 *
		 * @since 0.6.1
		 * @return string the class path to the component
		 */
		public function get_class_path() {
			$path = array();

			$parents = $this->parents;
			while ( count( $parents ) > 0 ) {
				$parent_slug = key( $parents );
				$path[] = get_class( $parents[ $parent_slug ] );
				$parents = $parents[ $parent_slug ]->parents;
			}

			return implode( '.', array_reverse( $path ) );
		}

		/**
		 * Returns the child components of the component.
		 *
		 * If a class is specified, only children of that class are returned.
		 *
		 * @since 0.5.0
		 * @param string $class the class the children should have (default is an empty string for no restrictions)
		 * @return array the array of child components, or an empty array if nothing found
		 */
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

		/**
		 * Returns the parent of the component.
		 *
		 * If a component has multiple parents, the $index parameter can be used to get a specific parent (by default it will return the first one).
		 * The $depth parameter can be used to get another ancestor, for example a grandparent component ($depth would need to be 2 in this case).
		 *
		 * @since 0.5.0
		 * @param integer $index the index of the parent to get (default is 0)
		 * @param integer $depth the generation depth to get (default is 1)
		 * @return WPDLib\Components\Base|null the parent component, or null if nothing found
		 */
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

		/**
		 * Validates the component.
		 *
		 * This method should be overwritten in the component class itself.
		 * However it must call the original method from within.
		 *
		 * It will throw an error when trying to add an additional parent to a component that does not support multiple parents.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $parent the parent component of the component
		 * @return bool|WPDLib\Util\Error an error object if an error occurred during validation, true if it was validated, false if it did not need to be validated
		 */
		public function validate( $parent = null ) {
			if ( null !== $parent ) {
				if ( count( $this->parents ) > 0 && ! $this->supports_multiparents() ) {
					return new UtilError( 'no_multiparent_component', sprintf( __( 'The component %1$s of class %2$s already has a parent assigned and is not a multiparent component.', 'wpdlib' ), $this->slug, get_class( $this ) ), '', ComponentManager::get_scope() );
				}
				$this->parents[ $parent->slug ] = $parent;
			}
			if ( ! $this->validated ) {
				if ( empty( $this->slug ) ) {
					return new UtilError( 'empty_slug_component', __( 'A component with an empty slug is not allowed.', 'wpdlib' ), '', ComponentManager::get_scope() );
				}
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

		/**
		 * Checks if the slug of the component is valid.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $parent the parent component of the component
		 * @return bool whether the component slug is valid
		 */
		public function is_valid_slug( $parent = null ) {
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

		/**
		 * Returns the keys of the arguments array and their default values.
		 *
		 * This abstract method must be implemented in the actual component.
		 *
		 * @since 0.5.0
		 * @return array
		 */
		protected abstract function get_defaults();

		/**
		 * Returns whether this component supports multiple parents.
		 *
		 * This abstract method must be implemented in the actual component.
		 *
		 * @since 0.5.0
		 * @return bool
		 */
		protected abstract function supports_multiparents();

		/**
		 * Returns whether this component supports global slugs.
		 *
		 * This abstract method must be implemented in the actual component.
		 *
		 * If the component does not support global slugs, the function must either return false for the slug to be globally unique
		 * or the class name of a parent component to ensure the slug is unique within that parent's scope.
		 *
		 * @since 0.5.0
		 * @return bool|string
		 */
		protected abstract function supports_globalslug();
	}

}
