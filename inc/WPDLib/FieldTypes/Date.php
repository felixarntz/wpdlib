<?php
/**
 * WPDLib\FieldTypes\Date class
 *
 * @package WPDLib
 * @subpackage FieldTypes
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
 */

namespace WPDLib\FieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Date' ) ) {
	/**
	 * Class for a date field.
	 *
	 * @since 0.5.0
	 */
	class Date extends Datetime {
		// class empty since they 'type' argument already handles the differences between datetime / date / time fields
	}

}
