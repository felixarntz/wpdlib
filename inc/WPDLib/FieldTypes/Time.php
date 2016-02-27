<?php
/**
 * @package WPDLib
 * @version 0.6.2
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Time' ) ) {
	/**
	 * Class for a time field.
	 *
	 * @since 0.5.0
	 */
	class Time extends Datetime {
		// class empty since they 'type' argument already handles the differences between datetime / date / time fields
	}

}
