<?php

namespace WPDLib\Components;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Components\Base' ) ) {

	abstract class Base {
		protected $slug = '';
		protected $args = array();

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
			if ( is_a( $component, 'WPDLib\Components\Base' ) ) {
				$children = \WPDLib\Components\Manager::instance()->get_children( get_class( $this ) );
				if ( in_array( get_class( $component ), $children ) ) {
					//TODO: check if component must be globally unique
					$component->validate();
					$component->parent = $this;
					$this->children[ $component->slug ] = $component;
				} else {
					//TODO: throw notice
				}
			} else {
				//TODO: throw notice
			}
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
