<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Components;

use WPDLib\Components\Base as Base;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Components\Menu' ) ) {

	class Menu extends Base {

		public function is_already_added( $screen_slug ) {
			global $admin_page_hooks;

			if ( null !== $this->args['added'] ) {
				return $this->args['added'];
			}

			$this->args['added'] = false;

			if ( empty( $this->slug ) ) {
				return $this->args['added'];
			}

			if ( isset( $admin_page_hooks[ $this->slug ] ) ) {
				// check for the exact menu slug
				$this->args['added'] = true;
				$this->args['subslug'] = $this->slug;
				$this->args['sublabel'] = true;
			} elseif ( ( $key = array_search( $this->slug, $admin_page_hooks ) ) !== false && strstr( $key, 'separator' ) === false ) {
				// check for the sanitized menu title
				$this->args['added'] = true;
				$this->args['subslug'] = $key;
				$this->args['sublabel'] = true;
			} elseif ( ! in_array( $this->slug, array( 'menu', 'submenu' ) ) && function_exists( 'add_' . $this->slug . '_page' ) ) {
				// check for submenu page function
				$this->args['added'] = true;
				$this->args['subslug'] = 'add_' . $this->slug . '_page';
				$this->args['sublabel'] = true;
			} elseif ( isset( $admin_page_hooks[ 'edit.php?post_type=' . $this->slug ] ) ) {
				// check if it is a post type menu
				$this->args['added'] = true;
				$this->args['subslug'] = 'edit.php?post_type=' . $this->slug;
				$this->args['sublabel'] = true;
			} elseif ( 'post' == $this->slug ) {
				// special case: post type 'post'
				$this->args['added'] = true;
				$this->args['subslug'] = 'edit.php';
				$this->args['sublabel'] = true;
			} elseif ( isset( $admin_page_hooks[ $screen_slug ] ) ) {
				$this->args['added'] = true;
				$this->args['subslug'] = false;
				$this->args['sublabel'] = true;
			}

			return $this->args['added'];
		}

		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
				$this->args['added'] = null;
				$this->args['subslug'] = $this->slug;
				$this->args['sublabel'] = false;

				//TODO add special validation for icon
			}

			return $status;
		}
		protected function get_defaults() {
			$defaults = array(
				'label'			=> __( 'Menu label', 'wpdlib' ),
				'icon'			=> '',
				'position'		=> null,
			);

			return apply_filters( 'wpdlib_menu_defaults', $defaults );
		}

		protected function supports_multiparents() {
			return false;
		}

		protected function supports_globalslug() {
			return true;
		}
	}
}
