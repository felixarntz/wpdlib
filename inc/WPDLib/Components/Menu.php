<?php
/**
 * @package WPDLib
 * @version 0.5.2
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Components;

use WPDLib\Components\Base as Base;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Components\Menu' ) ) {
	/**
	 * Class for a menu component.
	 *
	 * This denotes a top level menu item in the WordPress admin menu.
	 * The component's children need to have an `add_to_menu()` method so that they can be added to the menu automatically.
	 *
	 * @internal
	 * @since 0.5.0
	 */
	class Menu extends Base {

		/**
		 * @since 0.5.0
		 * @var bool Stores whether the menu has been added yet.
		 */
		protected $added = false;

		/**
		 * @since 0.5.0
		 * @var string Holds the actual menu slug of the menu.
		 */
		protected $menu_slug = '';

		/**
		 * @since 0.5.0
		 * @var string|bool Temporarily holds the first submenu label of the menu (true if it has been already set, false it it has not been detected yet).
		 */
		protected $first_submenu_label = false;

		/**
		 * Class constructor.
		 *
		 * @since 0.5.0
		 * @param string $slug the menu slug
		 * @param array $args array of menu properties
		 */
		public function __construct( $slug, $args ) {
			parent::__construct( $slug, $args );
			$this->validate_filter = 'wpdlib_menu_validated';
		}

		/**
		 * Checks whether the menu already exists (for example when it is a WordPress Core menu).
		 *
		 * If it has already been added, the class variables are set up accordingly.
		 *
		 * @since 0.5.0
		 * @param string $screen_slug a screen slug (slug of one of the menu component's child components)
		 * @return bool whether the menu has already been added
		 */
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

		/**
		 * Creates the admin menu.
		 *
		 * If the menu has the slug 'none', the children are added as a page, but not added into any menu.
		 * Otherwise the children will simply be added to the menu. The first child will be added using `add_menu_page()`.
		 *
		 * @since 0.5.0
		 * @see WPDLib\Components\Menu::add_non_menu_page()
		 * @see WPDLib\Components\Menu::add_menu_page()
		 * @see WPDLib\Components\Menu::add_submenu_page()
		 */
		public function create() {
			foreach ( $this->get_children() as $menu_item ) {
				if ( is_callable( array( $menu_item, 'add_to_menu' ) ) ) {
					if ( 'none' == $this->slug ) {
						$this->add_non_menu_page( $menu_item );
					} elseif ( ! $this->is_already_added( $menu_item->slug ) ) {
						$this->add_menu_page( $menu_item );
					} else {
						$this->add_submenu_page( $menu_item );
					}
				}
			}
		}

		/**
		 * Validates the arguments array.
		 *
		 * @since 0.5.0
		 * @param null $parent null (since a menu is a top-level component)
		 * @return bool|WPDLib\Util\Error an error object if an error occurred during validation, true if it was validated, false if it did not need to be validated
		 */
		public function validate( $parent = null ) {
			$status = parent::validate( $parent );

			if ( $status === true ) {
				if ( null !== $this->args['position'] ) {
					$this->args['position'] = floatval( $this->args['position'] );
				}
			}

			return $status;
		}

		/**
		 * Returns the keys of the arguments array and their default values.
		 *
		 * Read the plugin guide for more information about the menu arguments.
		 *
		 * @since 0.5.0
		 * @return array
		 */
		protected function get_defaults() {
			$defaults = array(
				'label'			=> __( 'Menu label', 'wpdlib' ),
				'icon'			=> '',
				'position'		=> null,
			);

			/**
			 * This filter can be used by the developer to modify the default values for each menu component.
			 *
			 * @since 0.5.0
			 * @param array the associative array of default values
			 */
			return apply_filters( 'wpdlib_menu_defaults', $defaults );
		}

		/**
		 * Returns whether this component supports multiple parents.
		 *
		 * @since 0.5.0
		 * @return bool
		 */
		protected function supports_multiparents() {
			return false;
		}

		/**
		 * Returns whether this component supports global slugs.
		 *
		 * If it does not support global slugs, the function either returns false for the slug to be globally unique
		 * or the class name of a parent component to ensure the slug is unique within that parent's scope.
		 *
		 * @since 0.5.0
		 * @return bool|string
		 */
		protected function supports_globalslug() {
			return true;
		}

		/**
		 * Searches for an existing admin page for a specific slug.
		 *
		 * @since 0.5.0
		 * @see WPDLib\Components\Menu::check_for_admin_page_func()
		 * @see WPDLib\Components\Menu::check_for_post_type_menu()
		 * @param string $slug the slug to check for
		 * @param array $admin_page_hooks array of all the existing page hooks
		 * @return string|false either the page hook of the page or false if no admin page was found
		 */
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

		/**
		 * Checks whether a slug matches a specific WordPress `add_{slug}_page()` function.
		 *
		 * @since 0.5.0
		 * @param string $slug the slug to check for
		 * @return string|false either the page hook of the page or false if no admin page was found
		 */
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

		/**
		 * Searches for an existing post type admin page for a specific slug.
		 *
		 * @since 0.5.0
		 * @param string $slug the slug to check for
		 * @param array $admin_page_hooks array of all the existing page hooks
		 * @return string|false either the page hook of the page or false if no admin page was found
		 */
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

		/**
		 * Adds a new menu page.
		 *
		 * This function is only executed once for each menu component.
		 * The first child will be added using this function.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $menu_item the component to add to the menu
		 * @return string|bool either the first submenu label (defined by the child component) or a boolean whether the component was successfully added
		 */
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

		/**
		 * Adds a new submenu page.
		 *
		 * All component children except for the first one will be added using this function.
		 * The function will also adjust the menu so that the first submenu item (which was actually added by `add_menu_page()`) has the proper label.
		 *
		 * @since 0.5.0
		 * @see WPDLib\Components\Menu::maybe_adjust_first_submenu_label()
		 * @param WPDLib\Components\Base $menu_item the component to add to the menu
		 * @return bool whether the component was successfully added
		 */
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

		/**
		 * Adds a new page to the WordPress admin which should not be part of any menu.
		 *
		 * This function is only used for the special case when a menu component's slug is set to 'none'.
		 * This allows you to add pages that are not visible in any menu.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $menu_item the component to add to the menu
		 * @return bool whether the component was successfully added
		 */
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

		/**
		 * Adjusts the menu so that the first submenu item has the proper label.
		 *
		 * @since 0.5.0
		 */
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
