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

			if ( isset( $admin_page_hooks[ $this->slug ] ) ) {
				// check for the exact menu slug
				$this->added = true;
				$this->menu_slug = $this->slug;
			} elseif ( ( $key = array_search( $this->slug, $admin_page_hooks ) ) !== false && strstr( $key, 'separator' ) === false ) {
				// check for the sanitized menu title
				$this->added = true;
				$this->menu_slug = $key;
			} elseif ( ! in_array( $this->slug, array( 'menu', 'submenu', 'object', 'utility' ) ) && function_exists( 'add_' . $this->slug . '_page' ) ) {
				// check for submenu page function
				$this->added = true;
				switch ( $this->slug ) {
					case 'posts':
						$this->menu_slug = 'edit.php';
						break;
					case 'media':
						$this->menu_slug = 'upload.php';
						break;
					case 'links':
						$this->menu_slug = 'link-manager.php';
						break;
					case 'pages':
						$this->menu_slug = 'edit.php?post_type=page';
						break;
					case 'comments':
						$this->menu_slug = 'edit-comments.php';
						break;
					case 'management':
						$this->menu_slug = 'tools.php';
						break;
					case 'theme':
						$this->menu_slug = 'themes.php';
						break;
					case 'plugins':
						$this->menu_slug = 'plugins.php';
						break;
					case 'users':
						if ( current_user_can( 'edit_users' ) ) {
							$this->menu_slug = 'users.php';
						} else {
							$this->menu_slug = 'profile.php';
						}
						break;
					case 'dashboard':
						$this->menu_slug = 'index.php';
						break;
					case 'options':
					default:
						$this->menu_slug = 'options-general.php';
				}
				$this->menu_slug = 'add_' . $this->slug . '_page';
			} elseif ( isset( $admin_page_hooks[ 'edit.php?post_type=' . $this->slug ] ) ) {
				// check if it is a post type menu
				$this->added = true;
				$this->menu_slug = 'edit.php?post_type=' . $this->slug;
			} elseif ( 'link' == $this->slug ) {
				$this->added = true;
				$this->menu_slug = 'link-manager.php';
			} elseif ( 'attachment' == $this->slug ) {
				$this->added = true;
				$this->menu_slug = 'upload.php';
			} elseif ( 'post' == $this->slug ) {
				// special case: post type 'post'
				$this->added = true;
				$this->menu_slug = 'edit.php';
			} elseif ( isset( $admin_page_hooks[ $screen_slug ] ) ) {
				$this->added = true;
				$this->menu_slug = $screen_slug;
			}

			if ( $this->added ) {
				$this->first_submenu_label = true;
			}

			return $this->added;
		}

		public function create() {
			foreach ( $this->get_children() as $menu_item ) {
				if ( is_callable( array( $menu_item, 'add_to_menu' ) ) ) {
					if ( empty( $this->slug ) ) {
						$status = $menu_item->add_to_menu( array(
							'mode'			=> 'submenu',
							'menu_slug'		=> null,
						) );
					} elseif ( ! $this->is_already_added( $menu_item->slug ) ) {
						$status = $menu_item->add_to_menu( array(
							'mode'			=> 'menu',
							'menu_label'	=> $this->args['label'],
							'menu_icon'		=> $this->args['icon'],
							'menu_priority'	=> $this->args['priority'],
						) );
						if ( $status ) {
							$this->added = true;
							$this->menu_slug = $menu_item->slug;
							if ( is_callable( array( $menu_item, 'get_menu_slug' ) ) ) {
								$this->menu_slug = $menu_item->get_menu_slug();
							}
							$this->first_submenu_label = $status;
						}
					} else {
						$status = $menu_item->add_to_menu( array(
							'mode'			=> 'submenu',
							'menu_slug'		=> $this->menu_slug,
						) );
						if ( $status ) {
							if ( true !== $this->first_submenu_label ) {
								global $submenu;

								if ( isset( $submenu[ $this->menu_slug ] ) ) {
									$submenu[ $this->menu_slug ][0][0] = $this->first_submenu_label;
									$this->first_submenu_label = true;
								}
							}
						}
					}
				}
			}
		}

		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
				if ( isset( $this->args['position'] ) ) {
					if ( null === $this->args['priority'] ) {
						$this->args['priority'] = $this->args['position'];
					}
					unset( $this->args['position'] );
				}

				if ( null !== $this->args['priority'] ) {
					$this->args['priority'] = floatval( $this->args['priority'] );
				}

				//TODO add special validation for icon
			}

			return $status;
		}
		protected function get_defaults() {
			$defaults = array(
				'label'			=> __( 'Menu label', 'wpdlib' ),
				'icon'			=> '',
				'priority'		=> null,
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
