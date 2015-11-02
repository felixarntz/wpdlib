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

		protected $added = false;
		protected $menu_slug = '';
		protected $first_submenu_label = false;

		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpdlib_menu_validated';
		}

		public function is_already_added( $screen_slug ) {
			global $admin_page_hooks;

			if ( $this->added ) {
				return $this->added;
			}

			$menu_slug = $this->check_for_admin_page( $this->slug, $admin_page_hooks );
			if ( false === $menu_slug ) {
				if ( isset( $admin_page_hooks[ $screen_slug ] ) ) {
					$menu_slug = $screen_slug;
				}
			}

			if ( false !== $menu_slug ) {
				$this->added = true;
				$this->menu_slug = $menu_slug;
				$this->first_submenu_label = true;
			}

			return $this->added;
		}

		public function create() {
			foreach ( $this->get_children() as $menu_item ) {
				if ( is_callable( array( $menu_item, 'add_to_menu' ) ) ) {
					if ( empty( $this->slug ) ) {
						$this->add_non_menu_page( $menu_item );
					} elseif ( ! $this->is_already_added( $menu_item->slug ) ) {
						$this->add_menu_page( $menu_item );
					} else {
						$this->add_submenu_page( $menu_item );
					}
				}
			}
		}

		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
				if ( null !== $this->args['position'] ) {
					$this->args['position'] = floatval( $this->args['position'] );
				}

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

		protected function check_for_admin_page( $slug, $admin_page_hooks ) {
			if ( isset( $admin_page_hooks[ $slug ] ) ) {
				// check for the exact menu slug
				return $slug;
			}

			if ( false !== ( $key = array_search( $slug, $admin_page_hooks ) ) && false === strstr( $key, 'separator' ) ) {
				// check for the sanitized menu title
				return $key;
			}

			if ( false !== ( $base = $this->check_for_admin_page_func( $slug ) ) ) {
				// check for submenu page function
				return $base;
			}

			if ( false !== ( $base = $this->check_for_post_type_menu( $slug, $admin_page_hooks ) ) ) {
				// check for post type menu
				return $base;
			}

			return false;
		}

		protected function check_for_admin_page_func( $slug ) {
			if ( function_exists( 'add_' . $slug . '_page' ) ) {
				switch ( $slug ) {
					case 'dashboard':
						return 'index.php';
					case 'posts':
						return 'edit.php';
					case 'media':
						return 'upload.php';
					case 'links':
						return 'link-manager.php';
					case 'pages':
						return 'edit.php?post_type=page';
					case 'comments':
						return 'edit-comments.php';
					case 'theme':
						return 'themes.php';
					case 'plugins':
						return 'plugins.php';
					case 'users':
						if ( current_user_can( 'edit_users' ) ) {
							return 'users.php';
						} else {
							return 'profile.php';
						}
					case 'management':
						return 'tools.php';
					case 'options':
						return 'options-general.php';
					default:
				}
			}

			return false;
		}

		protected function check_for_post_type_menu( $slug, $admin_page_hooks ) {
			if ( isset( $admin_page_hooks[ 'edit.php?post_type=' . $slug ] ) ) {
				return 'edit.php?post_type=' . $slug;
			}

			$special_post_types = array(
				'post'			=> 'edit.php',
				'attachment'	=> 'upload.php',
				'link'			=> 'link-manager.php',
			);

			if ( isset( $special_post_types[ $slug ] ) ) {
				return $special_post_types[ $slug ];
			}

			return false;
		}

		protected function add_menu_page( $menu_item ) {
			$status = false;
			if ( is_callable( array( $menu_item, 'add_to_menu' ) ) ) {
				$status = $menu_item->add_to_menu( array(
					'mode'			=> 'menu',
					'menu_label'	=> $this->args['label'],
					'menu_icon'		=> $this->args['icon'],
					'menu_position'	=> $this->args['position'],
				) );
			}

			if ( $status ) {
				$this->added = true;
				$this->menu_slug = $menu_item->slug;
				if ( is_callable( array( $menu_item, 'get_menu_slug' ) ) ) {
					$this->menu_slug = $menu_item->get_menu_slug();
				}
				$this->first_submenu_label = $status;

				$this->maybe_adjust_first_submenu_label();
			}

			return $status;
		}

		protected function add_submenu_page( $menu_item ) {
			$status = false;
			if ( is_callable( array( $menu_item, 'add_to_menu' ) ) ) {
				$status = $menu_item->add_to_menu( array(
					'mode'			=> 'submenu',
					'menu_slug'		=> $this->menu_slug,
				) );
			}

			if ( $status ) {
				$this->maybe_adjust_first_submenu_label();
			}

			return $status;
		}

		protected function add_non_menu_page( $menu_item ) {
			$status = false;
			if ( is_callable( array( $menu_item, 'add_to_menu' ) ) ) {
				$status = $menu_item->add_to_menu( array(
					'mode'			=> 'submenu',
					'menu_slug'		=> null,
				) );
			}

			return $status;
		}

		protected function maybe_adjust_first_submenu_label() {
			global $submenu;

			if ( true !== $this->first_submenu_label ) {
				if ( isset( $submenu[ $this->menu_slug ] ) ) {
					$submenu[ $this->menu_slug ][0][0] = $this->first_submenu_label;
					$this->first_submenu_label = true;
				}
			}
		}
	}
}
