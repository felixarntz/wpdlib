<?php
/**
 * @package WPDLib
 * @version 0.5.3
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Util;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Util\Util' ) ) {
	/**
	 * This class contains some utility functions.
	 *
	 * @since 0.5.0
	 */
	final class Util {
		/**
		 * @since 0.5.0
		 * @var string Temporarily holds the property to sort by in the below methods.
		 */
		private static $sort_by = '';

		/**
		 * Transforms a component object into its slug.
		 *
		 * This can be used in `array_map()` for example.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $component the component to transform
		 * @return string the component's slug
		 */
		public static function component_to_slug( $component ) {
			return $component->slug;
		}

		/**
		 * Checks whether the current user can access a specific component.
		 *
		 * @since 0.5.0
		 * @param WPDLib\Components\Base $component the component to check access for
		 * @return bool whether the user can access the component
		 */
		public static function current_user_can( $component ) {
			$cap = $component->capability;

			if ( null === $cap || current_user_can( $cap ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Returns an array of post IDs and their titles.
		 *
		 * Those can be used to generate dropdown options for example.
		 *
		 * @since 0.5.0
		 * @param string|array $post_type the post type / post types to get posts for
		 * @return array the array of `$post_id => $post_title`
		 */
		public static function get_posts_options( $post_type = 'any' ) {
			if ( ! is_string( $post_type ) && ! is_array( $post_type ) || empty( $post_type ) ) {
				$post_type = 'any';
			}

			$posts = get_posts( array(
				'posts_per_page'	=> -1,
				'post_type'			=> $post_type,
				'post_status'		=> 'publish',
				'orderby'			=> 'post_title',
				'order'				=> 'asc',
				'fields'			=> 'ids',
			) );

			$results = array();
			foreach ( $posts as $post_id ) {
				$results[ $post_id ] = get_the_title( $post_id );
			}

			return $results;
		}

		/**
		 * Returns an array of term IDs and their names.
		 *
		 * Those can be used to generate dropdown options for example.
		 *
		 * @since 0.5.0
		 * @param string|array $taxonomy the taxonomy / taxonomies to get terms for
		 * @return array the array of `$term_id => $term_name`
		 */
		public static function get_terms_options( $taxonomy = 'any' ) {
			if ( ! is_string( $taxonomy ) && ! is_array( $taxonomy ) || empty( $taxonomy ) || 'any' === $taxonomy ) {
				$taxonomy = array();
			}

			return get_terms( $taxonomy, array(
				'number'			=> 0,
				'hide_empty'		=> false,
				'orderby'			=> 'name',
				'order'				=> 'asc',
				'fields'			=> 'id=>name',
			) );
		}

		/**
		 * Returns an array of user IDs and their names.
		 *
		 * Those can be used to generate dropdown options for example.
		 *
		 * @since 0.5.0
		 * @param string $role the role to get users for
		 * @return array the array of `$user_id => $user_display_name`
		 */
		public static function get_users_options( $role = 'any' ) {
			if ( is_array( $role ) ) {
				if ( count( $role ) > 0 ) {
					$role = $role[0];
				}
			}

			if ( ! is_string( $role ) || 'any' === $role ) {
				$role = '';
			}

			$users = get_users( array(
				'number'			=> 0,
				'role'				=> $role,
				'orderby'			=> 'display_name',
				'order'				=> 'asc',
				'fields'			=> array( 'ID', 'display_name' ),
			) );

			$results = array();
			foreach ( $users as $user ) {
				$results[ $user->ID ] = $user->display_name;
			}

			return $results;
		}

		/**
		 * Formats a numeric value with a specific unit, depending on how big the value is.
		 *
		 * You need to specify a $units array and a base value.
		 * The keys of the units in the $units array are used to exponentiate the base value.
		 * The $base_unit must be the unit that the $value is specified in (usually this would be the first unit in the array).
		 *
		 * Two examples of how to use the function:
		 *
		 * - `format_unit( 244, array( 'mm', 'cm', 'dm', 'm' ), 10, 'mm' );` will produce 2.44 dm
		 * - `format_unit( 1235, array( 'B', 'kB', 'MB', 'GB', 'TB' ), 1024, 'B' );` will produce 1.21 kB
		 *
		 * @param integer|float $value the value to format with a unit
		 * @param array $units the array of units in an ascending order
		 * @param integer|float $base the base value (for exponentiation)
		 * @param string $base_unit the base unit (optional, default is the first unit in the array)
		 * @param integer $decimals (number of decimals to display, default is 2)
		 * @return string the formatted value
		 */
		public static function format_unit( $value, $units, $base, $base_unit = '', $decimals = 2 ) {
			$value = floatval( $value );

			if ( empty( $base_unit ) ) {
				$base_unit = $units[0];
			}

			if ( $base_unit != $units[0] ) {
				$value *= pow( $base, array_search( $base_unit, $units ) );
			}

			for ( $i = count( $units ) - 1; $i >= 0; $i-- ) {
				if ( $value > pow( $base, $i ) ) {
					return number_format_i18n( $value / pow( $base, $i ), $decimals ) . ' ' . $units[ $i ];
				} elseif ( 0 == $i ) {
					return number_format_i18n( $value, $decimals ) . ' ' . $units[0];
				}
			}

			return $value;
		}

		/**
		 * Inserts an object into an array in a sorted manner.
		 *
		 * If no $sort_by parameter is provided, the arrays won't be sorted.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $arr the array to insert the item into
		 * @param object $item the object to insert
		 * @param string $key the property in the object to use as array key (default is 'slug')
		 * @param string $sort_by the property in the object to sort by (default is none for no sort order)
		 * @return array the array containing the object
		 */
		public static function object_array_insert( $arr, $item, $key = 'slug', $sort_by = '' ) {
			if ( empty( $sort_by ) ) {
				$arr[ $item->$key ] = $item;
				return $arr;
			} elseif ( ! isset( $item->$sort_by ) || null === $item->$sort_by || 0 == count( $arr ) ) {
				$arr[ $item->$key ] = $item;
				return $arr;
			}

			return self::object_array_insert_sorted( $arr, $item, $sort_by, $key );
		}

		/**
		 * Merges multiple sorted arrays of objects into a single sorted array.
		 *
		 * If no $sort_by parameter is provided, the arrays won't be sorted.
		 *
		 * PHP's native (unstable) sort will be run on all the items that have the $sort_by property defined.
		 * This way the sorting is "almost" stable.
		 * This means that items where the $sort_by property is 'null' will be ordered in the way they were originally specified in.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $arrs the arrays to merge into one array
		 * @param string $key the property in the objects to use as array key (default is 'slug')
		 * @param string $sort_by the property in the objects to sort by (default is none for no sort order)
		 * @return array the resulting array
		 */
		public static function object_array_merge( $arrs, $key = 'slug', $sort_by = '' ) {
			if ( empty( $sort_by ) ) {
				$result = $instance_counts = array();

				foreach ( $arrs as $arr ) {
					$result = self::object_array_merge_items( $arr, $result, $instance_counts, $key );
				}

				return $result;
			}

			return self::object_array_merge_sorted( $arrs, $sort_by, $key );
		}

		/**
		 * Sorts an array of objects with PHP's native unstable sort.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $arr the array to sort
		 * @param string $sort_by the property in the objects to sort by
		 * @param bool $associative whether the array is associative
		 * @return array the sorted array
		 */
		public static function object_array_sort( $arr, $sort_by, $associative = false ) {
			if ( ! empty( $sort_by ) ) {
				self::$sort_by = $sort_by;

				if ( $associative ) {
					uasort( $arr, array( __CLASS__, 'sort_objects_callback' ) );
				} else {
					usort( $arr, array( __CLASS__, 'sort_objects_callback' ) );
				}

				self::$sort_by = '';
			}

			return $arr;
		}

		/**
		 * Integrates an object into an array of objects in a way that the array is properly sorted.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $arr the array to integrate the item into
		 * @param object $item the object to integrate
		 * @param string $sort_by the property in the object to sort by
		 * @param string $key the property in the object to use as array key (default is 'slug')
		 * @return array the array containing the object
		 */
		private static function object_array_insert_sorted( $arr, $item, $sort_by, $key = 'slug' ) {
			$new_arr = array();
			$new_arr[ $item->$key ] = $item;

			$split_key = 0;
			foreach ( $arr as $c ) {
				if ( null === $c->$sort_by || $c->$sort_by > $item->$sort_by ) {
					break;
				}
				$split_key++;
			}

			if ( 0 == $split_key ) {
				$arr = array_merge( $new_arr, $arr );
			} else {
				$begin_arr = array_slice( $arr, 0, $split_key );
				$end_arr = array_slice( $arr, $split_key );
				$arr = array_merge( $begin_arr, $new_arr, $end_arr );
			}

			return $arr;
		}

		/**
		 * Merges multiple sorted arrays of objects into a single sorted array.
		 *
		 * The function splits the arrays into two arrays, one where the objects have a valid $sort_by property, the other with objects without one.
		 * Those arrays are then merged together, the array with valid $sort_by properties first.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $arrs the arrays to merge into one array
		 * @param string $sort_by the property in the objects to sort by
		 * @param string $key the property in the objects to use as array key (default is 'slug')
		 * @return array the resulting array
		 */
		private static function object_array_merge_sorted( $arrs, $sort_by, $key = 'slug' ) {
			$result = $instance_counts = array();

			list( $sortables, $nulls ) = self::object_array_merge_split( $arrs, $sort_by );

			$result = self::object_array_merge_items( $sortables, $result, $instance_counts, $key );

			$result = self::object_array_merge_items( $nulls, $result, $instance_counts, $key );

			return $result;
		}

		/**
		 * Splits arrays into two big arrays.
		 *
		 * The first array will be a sorted array where the objects have a valid $sort_by property.
		 * The second array will be an array where the objects do not have a valid $sort_by property.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $arrs the arrays to split
		 * @param string $sort_by the property in the objects to sort by
		 * @return array the resulting array
		 */
		private static function object_array_merge_split( $arrs, $sort_by ) {
			$sortables = array();
			$nulls = array();

			foreach ( $arrs as $arr ) {
				foreach ( $arr as $item ) {
					if ( ! isset( $item->$sort_by ) || null === $item->$sort_by ) {
						$nulls[] = $item;
					} else {
						$sortables[] = $item;
					}
				}
			}

			$sortables = self::object_array_sort( $sortables, $sort_by );

			return array( $sortables, $nulls );
		}

		/**
		 * Merges objects of one array into another array.
		 *
		 * If an object's key property already exists in the array, the array key will be counted up so that nothing gets overwritten.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $items the array of items to merge into $result
		 * @param array $result the array the $items should be merged into
		 * @param array $instance_counts an array of counts of how many items of a certain key exist in the target array
		 * @param string $key the property in the objects to use as array key (default is 'slug')
		 * @return array the resulting array
		 */
		private static function object_array_merge_items( $items, $result, &$instance_counts, $key = 'slug' ) {
			foreach ( $items as $item ) {
				if ( ! isset( $result[ $item->$key ] ) ) {
					$result[ $item->$key ] = $item;
				} else {
					if ( ! isset( $instance_counts[ $item->$key ] ) ) {
						$instance_counts[ $item->$key ] = 1;
					}
					$instance_counts[ $item->$key ]++;
					$result[ $item->$key . '-' . $instance_counts[ $item->$key ] ] = $item;
				}
			}

			return $result;
		}

		/**
		 * Callback function for PHP's native array sorting functionality.
		 *
		 * It will sort objects by the $sort_by property (temporarily stored in a class-wide helper variable).
		 *
		 * @internal
		 * @since 0.5.0
		 * @param object $a an object to compare
		 * @param object $b another object to compare
		 * @return integer -1 if the first item should be before the second, 1 if the second item should be before the first, 0 if they are equal
		 */
		private static function sort_objects_callback( $a, $b ) {
			$sort_by = self::$sort_by;

			if ( ( ! isset( $a->$sort_by ) || null === $a->$sort_by ) && ( ! isset( $b->$sort_by ) || null === $b->$sort_by ) ) {
				return 0;
			} elseif ( ! isset( $a->$sort_by ) || null === $a->$sort_by ) {
				return 1;
			} elseif ( ! isset( $b->$sort_by ) || null === $b->$sort_by ) {
				return -1;
			}

			if ( $a->$sort_by == $b->$sort_by ) {
				return 0;
			}

			return ( $a->$sort_by < $b->$sort_by ? -1 : 1 );
		}
	}

}
