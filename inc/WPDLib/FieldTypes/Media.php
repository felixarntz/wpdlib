<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Media' ) ) {

	class Media extends \WPDLib\FieldTypes\Base {
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'mime_types'	=> 'all',
			) );
			parent::__construct( $type, $args );
		}

		public function display( $val, $echo = true ) {
			$args = $this->args;
			unset( $args['placeholder'] );
			$args['value'] = $val;

			unset( $args['mime_types'] );

			$text_args = array(
				'id'	=> $args['id'] . '-media-title',
				'class'	=> 'wpdlib-media-title',
				'value'	=> $val ? basename( get_attached_file( $val ) ) : '',
			);

			$button_args = array(
				'id'	=> $args['id'] . '-media-button',
				'class'	=> 'wpdlib-media-button button',
				'href'	=> '#',
			);

			$output = '<input type="hidden"' . \WPDLib\FieldTypes\Manager::make_html_attributes( $args, false, false ) . ' />';
			$output .= '<input type="text"' . \WPDLib\FieldTypes\Manager::make_html_attributes( $text_args, false, false ) . ' />';
			$output .= '<a' . \WPDLib\FieldTypes\Manager::make_html_attributes( $button_args, false, false ) . '>' . __( 'Choose / Upload a File', 'wpdlib' ) . '</a>';

			if ( $val ) {
				if ( $this->check_filetype( $val, 'image' ) ) {
					$image_args = array(
						'id'	=> $args['id'] . '-media-image',
						'class'	=> 'wpdlib-media-image',
						'src'	=> wp_get_attachment_url( $val ),
					);
					$output .= '<img' . \WPDLib\FieldTypes\Manager::make_html_attributes( $image_args, false, false ) . ' />';
				} else {
					$link_args = array(
						'id'	=> $args['id'] . '-media-link',
						'class'	=> 'wpdlib-media-link',
						'href'	=> wp_get_attachment_url( $val ),
						'target'=> '_blank',
					);
					$output .= '<a' . \WPDLib\FieldTypes\Manager::make_html_attributes( $link_args, false, false ) . '>' . __( 'Open File', 'wpdlib' ) . '</a>';
				}
			}

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( $val === null ) {
				return 0;
			}

			$val = absint( $val );

			if ( 'attachment' != get_post_type( $val ) ) {
				return new \WP_Error( 'invalid_media_post_type', sprintf( __( 'The post with ID %s is not a valid WordPress media file.', 'wpdlib' ), $val ) );
			}

			if ( ! $this->check_filetype( $val, $this->args['mime_types'] ) ) {
				$valid_formats = is_array( $this->args['mime_types'] ) ? implode( ', ', $this->args['mime_types'] ) : $this->args['mime_types'];
				return new \WP_Error( 'invalid_media_mime_type', sprintf( __( 'The media item with ID %1$s is neither of the valid formats (%2$s).', 'wpdlib' ), $val, $valid_formats ) );
			}

			return $val;
		}

		public function is_empty( $val ) {
			return absint( $val ) < 1;
		}

		public function parse( $val, $formatted = false ) {
			return absint( $val );
		}

		public function enqueue_assets() {
			if ( self::is_enqueued( __CLASS__ ) ) {
				return array();
			}

			wp_enqueue_media();

			return array(
				'dependencies'		=> array( 'media-editor' ),
				'script_vars'		=> array(
					'i18n_open_file'	=> __( 'Open file', 'wpdlib' ),
				),
			);
		}

		protected function check_filetype( $val, $desired_types = 'all' ) {
			$filename = get_attached_file( $val );
			if ( $filename ) {
				$extension = wp_check_filetype( $filename );
				$extension = $extension['ext'];
				if ( $extension ) {
					if ( 'all' == $desired_types || ! $desired_types ) {
						return true;
					}

					if ( ! is_array( $desired_types ) ) {
						$desired_types = array( $desired_types );
					}

					if ( in_array( strtolower( $extension ), $desired_types ) ) {
						return true;
					}

					$type = wp_ext2type( $extension );

					if ( $type !== null && in_array( $type, $desired_types ) ) {
						return true;
					}
				}
			}
			return false;
		}
	}

}
