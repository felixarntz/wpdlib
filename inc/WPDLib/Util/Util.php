<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\Util;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\Util\Util' ) ) {

	final class Util {
		private static $sort_by = '';

		// insert item into array in a sorted manner
		public static function object_array_insert( $arr, $item, $key = 'slug', $sort_by = '' ) {
			if ( empty( $sort_by ) ) {
				$arr[ $item->$key ] = $item;
			} else {
				if ( ! isset( $item->$sort_by ) || null === $item->$sort_by || 0 == count( $arr ) ) {
					$arr[ $item->$key ] = $item;
				} else {
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
				}
			}

			return $arr;
		}

		// merge multiple sorted arrays into a single sorted array
		// PHP unstable sort will be run on the items that have the $sort_by property defined
		// so it is "almost" stable
		public static function object_array_merge( $arrs, $key = 'slug', $sort_by = '' ) {
			$result = array();

			$instance_counts = array();

			if ( empty( $sort_by ) ) {
				foreach ( $arrs as $arr ) {
					foreach ( $arr as $item ) {
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
					unset( $item );
				}
				unset( $arr );
			} else {
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
					unset( $item );
				}
				unset( $arr );

				$sortables = self::object_array_sort( $sortables, $sort_by );

				foreach ( $sortables as $item ) {
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
				unset( $item );

				foreach ( $nulls as $item ) {
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
				unset( $item );
			}

			return $result;
		}

		// PHP unstable sort; do not use this on arrays containing objects without the $sort_by property defined
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
