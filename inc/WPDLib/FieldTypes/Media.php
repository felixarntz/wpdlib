<?php
/**
 * @package WPDLib
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPDLib\FieldTypes;

use WPDLib\Components\Manager as ComponentManager;
use WPDLib\FieldTypes\Manager as FieldManager;
use WP_Error as WPError;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPDLib\FieldTypes\Media' ) ) {

	class Media extends Base {
		protected $temp_val = null;

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

			if ( 'all' !== $args['mime_types'] ) {
				$args['data-settings'] = json_encode( array(
					'query'				=> array(
						'post_mime_type'	=> $args['mime_types'],
					),
				) );
			}

			unset( $args['mime_types'] );

			$output = '<input type="text"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		public function validate( $val = null ) {
			if ( ! $val ) {
				return 0;
			}

			$val = absint( $val );

			if ( 'attachment' != get_post_type( $val ) ) {
				return new WPError( 'invalid_media_post_type', sprintf( __( 'The post with ID %s is not a valid WordPress media file.', 'wpdlib' ), $val ) );
			}

			if ( ! $this->check_filetype( $val, $this->args['mime_types'] ) ) {
				$valid_formats = is_array( $this->args['mime_types'] ) ? implode( ', ', $this->args['mime_types'] ) : $this->args['mime_types'];
				return new WPError( 'invalid_media_mime_type', sprintf( __( 'The media item with ID %1$s is neither of the valid formats (%2$s).', 'wpdlib' ), $val, $valid_formats ) );
			}

			return $val;
		}

		public function is_empty( $val ) {
			return absint( $val ) < 1;
		}

		public function parse( $val, $formatted = false ) {
			$val = absint( $val );
			if ( $formatted ) {
				if ( ! is_array( $formatted ) ) {
					$formatted = array();
				}
				$formatted = wp_parse_args( $formatted, array(
					'mode'		=> 'field',
					'field'		=> 'url',
					'template'	=> '',
				) );
				return $this->format_attachment( $val, $formatted );
			}
			return $val;
		}

		public function enqueue_assets() {
			global $post;

			if ( self::is_enqueued( __CLASS__ ) ) {
				return array();
			}

			$assets_url = ComponentManager::get_base_url() . '/assets';
			$version = ComponentManager::get_dependency_info( 'wp-media-picker', 'version' );

			if ( $post ) {
				wp_enqueue_media( array( 'post' => $post->ID ) );
			} else {
				wp_enqueue_media();
			}

			wp_enqueue_style( 'wp-media-picker', $assets_url . '/vendor/wp-media-picker/wp-media-picker.min.css', array(), $version );
			wp_enqueue_script( 'wp-media-picker', $assets_url . '/vendor/wp-media-picker/wp-media-picker.min.js', array( 'jquery', 'media-editor' ), $version, true );

			return array(
				'dependencies'		=> array( 'media-editor', 'wp-media-picker' ),
				'script_vars'		=> array(
					'media_i18n_add'		=> __( 'Choose a File', 'wpdlib' ),
					'media_i18n_replace'	=> __( 'Choose another File', 'wpdlib' ),
					'media_i18n_remove'		=> __( 'Remove', 'wpdlib' ),
					'media_i18n_modal'		=> __( 'Choose a File', 'wpdlib' ),
					'media_i18n_button'		=> __( 'Insert File', 'wpdlib' ),
				),
			);
		}

		protected function check_filetype( $val, $desired_types = 'all' ) {
			$extension = $this->get_attachment_extension( $val );

			if ( $extension ) {
				return $this->check_extension( $extension, $desired_types );
			}

			return false;
		}

		protected function get_attachment_extension( $id ) {
			$filename = get_attached_file( $val );

			if ( $filename ) {
				$extension = wp_check_filetype( $filename );
				$extension = $extension['ext'];
				if ( $extension ) {
					return $extension;
				}
			}

			return false;
		}

		protected function check_extension( $extension, $desired_types = 'all' ) {
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

			return false;
		}

		protected function format_attachment( $id, $args = array() ) {
			switch ( $args['mode'] ) {
				case 'object':
					return get_post( $id );
				case 'link':
					return wp_get_attachment_link( $id, 'thumbnail', false, true );
				case 'image':
					return wp_get_attachment_image( $id, 'thumbnail', true );
				case 'template':
					if ( ! empty( $args['template'] ) && $id ) {
						$this->temp_val = $id;
						$output = preg_replace_callback( '/%([A-Za-z0-9_\-]+)%/', array( $this, 'template_replace_callback' ), $args['template'] );
						$this->temp_val = null;
						return $output;
					}
					return '';
				case 'field':
				default:
					return $this->get_attachment_field( $id, $args['field'] );
			}

			return $id;
		}

		protected function get_attachment_field( $id, $field ) {
			switch ( $field ) {
				case 'id':
				case 'ID':
					return $id;
				case 'title':
					return get_the_title( $id );
				case 'alt':
					return get_post_meta( $id, '_wp_attachment_image_alt', true );
				case 'caption':
					return $this->get_post_field( $id, 'post_excerpt' );
				case 'description':
					return $this->get_post_field( $id, 'post_content' );
				case 'mime':
				case 'mime_type':
				case 'type':
				case 'subtype':
					return $this->get_attachment_mime_type( $id, $field );
				case 'mime_icon':
				case 'mime_type_icon':
					return wp_mime_type_icon( $id );
				case 'filename':
					return wp_basename( get_attached_file( $id ) );
				case 'link':
					return get_attachment_link( $id );
				case 'url':
				case 'path':
				default:
					if ( '_path' === substr( $field, -5 ) ) {
						return $this->get_attachment_path( $id, 'path', substr( $field, 0, -5 ) );
					} elseif ( '_url' === substr( $field, -4 ) ) {
						return $this->get_attachment_path( $id, 'url', substr( $field, 0, -4 ) );
					}
					return $this->get_attachment_path( $id, $field );
			}
		}

		protected function get_post_field( $id, $field ) {
			$attachment = get_post( $id );
			if ( ! $attachment ) {
				return '';
			}

			if ( ! isset( $attachment->$field ) ) {
				return '';
			}

			return $attachment->$field;
		}

		protected function get_attachment_mime_type( $id, $mode = 'all' ) {
			$mime_type = get_post_mime_type( $id );

			if ( in_array( $mode, array( 'type', 'subtype' ) ) ) {
				list( $type, $subtype ) = explode( '/', $mime_type );
				if ( 'type' === $mode ) {
					return $type;
				}
				return $subtype;
			}

			return $mime_type;
		}

		protected function get_attachment_path( $id, $mode = 'url', $size = false ) {
			$url = wp_get_attachment_url( $id );
			if ( $size ) {
				$src = wp_get_attachment_image_src( $id, $size, false );
				if ( is_array( $src ) ) {
					$url = $src[0];
				}
			}

			if ( $url && 'path' === $mode ) {
				$upload_dir = wp_upload_dir();
				$path = '';
				if ( strpos( $url, $upload_dir['baseurl'] ) !== false ) {
					$path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
				}
				return $path;
			}

			return $url;
		}

		protected function template_replace_callback( $matches ) {
			if ( null === $this->temp_val ) {
				return '';
			}

			if ( ! isset( $matches[1] ) ) {
				return '';
			}

			return $this->get_attachment_field( $this->temp_val, $matches[1] );
		}
	}

}
