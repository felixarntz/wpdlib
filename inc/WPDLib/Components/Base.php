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
		protected $parent = null;
		protected $children = array();

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
			if ( ! \WPDLib\Components\Manager::is_too_late() ) {
				if ( is_a( $component, 'WPDLib\Components\Base' ) ) {
					$children = \WPDLib\Components\Manager::get_children( get_class( $this ) );
					if ( in_array( get_class( $component ), $children ) ) {
						//TODO: check if component must be globally unique
						if ( ! isset( $this->children[ $component->slug ] ) ) {
							$component->validate();
							$component->parent = $this;
							$component->scope = \WPDLib\Components\Manager::get_scope();
							$this->children[ $component->slug ] = $component;
							return $component;
						}
						return $this->children[ $component->slug ];
					}
					return new \WPDLib\Util\Error( 'no_valid_child_component', sprintf( __( 'The component %1$s of class %2$s is not a valid child for the component %3$s.', 'wpdlib' ), $component->slug, get_class( $component ), $this->slug ), '', \WPDLib\Components\Manager::get_scope() );
				}
				return new \WPDLib\Util\Error( 'no_component', __( 'The object is not a component.', 'wpdlib' ), '', \WPDLib\Components\Manager::get_scope() );
			}
			return new \WPDLib\Util\Error( 'too_late_component', sprintf( __( 'Components must not be added later than the %s hook.', 'wpdlib' ), '<code>init</code>' ), '', \WPDLib\Components\Manager::get_scope() );
		}

		public function validate() {
			$defaults = $this->get_defaults();
			foreach ( $defaults as $key => $default ) {
				if ( ! isset( $this->args[ $key ] ) ) {
					$this->args[ $key ] = $default;
				}
			}
		}

		protected abstract function get_defaults();
	}

}
