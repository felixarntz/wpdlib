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

if ( ! class_exists( 'WPDLib\Components\Base' ) ) {

	abstract class Base {
		protected $slug = '';
		protected $args = array();

		protected $scope = '';
		protected $parents = array();
		protected $children = array();

		protected $validated = false;
		protected $valid_slug = null;

		public function __construct( $slug, $args ) {
			$this->slug = $slug;
			$this->args = $args;
		}

		public function __set( $property, $value ) {
			if ( method_exists( $this, $method = 'set_' . $property ) ) {
				$this->$method( $value );
			} elseif ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			} elseif ( isset( $this->args[ $property ] ) ) {
				$this->args[ $property ] = $value;
			}
		}

		public function __get( $property ) {
			if ( method_exists( $this, $method = 'get_' . $property ) ) {
				return $this->$method();
			} elseif ( property_exists( $this, $property ) ) {
				return $this->$property;
			} elseif ( isset( $this->args[ $property ] ) ) {
				return $this->args[ $property ];
			}

			return null;
		}

		public function add( $component ) {
			if ( \WPDLib\Components\Manager::is_too_late() ) {
				return new \WPDLib\Util\Error( 'too_late_component', sprintf( __( 'Components must not be added later than the %s hook.', 'wpdlib' ), '<code>init</code>' ), '', \WPDLib\Components\Manager::get_scope() );
			}

			if ( ! is_a( $component, 'WPDLib\Components\Base' ) ) {
				return new \WPDLib\Util\Error( 'no_component', __( 'The object is not a component.', 'wpdlib' ), '', \WPDLib\Components\Manager::get_scope() );
			}

			$children = \WPDLib\Components\Manager::get_children( get_class( $this ) );
			if ( ! in_array( get_class( $component ), $children ) ) {
				return new \WPDLib\Util\Error( 'no_valid_child_component', sprintf( __( 'The component %1$s of class %2$s is not a valid child for the component %3$s.', 'wpdlib' ), $component->slug, get_class( $component ), $this->slug ), '', \WPDLib\Components\Manager::get_scope() );
			}

			$status = $component->validate( $this );
			if ( is_wp_error( $status ) ) {
				return $status;
			}

			if ( ! $component->is_valid_slug() ) {
				return new \WPDLib\Util\Error( 'no_valid_slug_component', sprintf( __( 'A component of class %1$s with slug %2$s already exists.', 'wpdlib' ), get_class( $component ), $component->slug ), '', \WPDLib\Components\Manager::get_scope() );
			}

			$this->children[ $component->slug ] = $component;
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

		public function get_parent( $index = 0 ) {
			$parents = array_values( $this->parents );
			if ( isset( $parents[ $index ] ) ) {
				return $parents[ $index ];
			}
			return null;
		}

		public function validate( $parent = null ) {
			if ( $parent !== null ) {
				if ( count( $this->parents ) > 0 && ! $this->supports_multiparents() ) {
					return new \WPDLib\Util\Error( 'no_multiparent_component', sprintf( __( 'The component %1$s of class %2$s already has a parent assigned and is not a multiparent component.', 'wpdlib' ), $this->slug, get_class( $this ) ), '', \WPDLib\Components\Manager::get_scope() );
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
				$this->scope = \WPDLib\Components\Manager::get_scope();
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
							$this->valid_slug = ! \WPDLib\Components\Manager::exists( $this->slug, get_class( $this ), $parent->slug );
						} else {
							$this->valid_slug = ! \WPDLib\Components\Manager::exists( $this->slug, get_class( $this ) );
						}
					} else {
						$this->valid_slug = ! \WPDLib\Components\Manager::exists( $this->slug, get_class( $this ) );
					}
				} else {
					\WPDLib\Components\Manager::exists( $this->slug, get_class( $this ) ); // just use the function to add the component
					$this->valid_slug = true;
				}
			}
			return $this->valid_slug;
		}

		protected function supports_multiparents() {
			return false;
		}

		protected function supports_globalslug() {
			if ( \WPDLib\Components\Manager::is_toplevel( get_class( $this ) ) ) {
				return true;
			}
			return false;
		}

		protected abstract function get_defaults();
	}

}
