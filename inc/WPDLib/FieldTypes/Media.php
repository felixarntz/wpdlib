<?php
/**
 * @package WPDLib
 * @version 0.5.2
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
	/**
	 * Class for a media picker field.
	 *
	 * This implementation uses attachment IDs as field values (no URLs!).
	 *
	 * @since 0.5.0
	 */
	class Media extends Base {

		/**
		 * @since 0.5.0
		 * @var integer|null Temporarily stores an attachment ID.
		 */
		protected $temp_val = null;

		/**
		 * Class constructor.
		 *
		 * For an overview of the supported arguments, please read the Field Types Reference.
		 *
		 * @since 0.5.0
		 * @param string $type the field type
		 * @param array $args array of field type arguments
		 */
		public function __construct( $type, $args ) {
			$args = wp_parse_args( $args, array(
				'mime_types'	=> 'all',
			) );
			parent::__construct( $type, $args );
		}

		/**
		 * Displays the input control for the field.
		 *
		 * @since 0.5.0
		 * @param integer $val the current value of the field
		 * @param bool $echo whether to echo the output (default is true)
		 * @return string the HTML output of the field control
		 */
		public function display( $val, $echo = true ) {
			$args = $this->args;
			unset( $args['placeholder'] );
			$args['value'] = $val;

			$args = array_merge( $args, $this->data_atts );

			$mime_types = $this->verify_mime_types( $args['mime_types'] );
			if ( $mime_types ) {
				$args['data-settings'] = json_encode( array(
					'query'				=> array(
						'post_mime_type'	=> $mime_types,
					),
				);
				if ( isset( $args['data-settings'] ) ) {
					$data_settings = array_merge_recursive( json_decode( $args['data-settings'], true ), $data_settings );
				}
				$args['data-settings'] = json_encode( $data_settings );
			}
			unset( $args['mime_types'] );

			$output = '<input type="text"' . FieldManager::make_html_attributes( $args, false, false ) . ' />';

			if ( $echo ) {
				echo $output;
			}

			return $output;
		}

		/**
		 * Validates a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @return integer|WP_Error the validated field value or an error object
		 */
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

		/**
		 * Checks whether a value for the field is considered empty.
		 *
		 * This function is needed to check whether a required field has been properly filled.
		 *
		 * @since 0.5.0
		 * @param integer $val the current value of the field
		 * @return bool whether the value is considered empty
		 */
		public function is_empty( $val ) {
			return absint( $val ) < 1;
		}

		/**
		 * Parses a value for the field.
		 *
		 * @since 0.5.0
		 * @param mixed $val the current value of the field
		 * @param bool|array $formatted whether to also format the value (default is false)
		 * @return integer|string the correctly parsed value (string if $formatted is true)
		 */
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

		/**
		 * Enqueues required assets for the field type.
		 *
		 * The function also generates script vars to be applied in `wp_localize_script()`.
		 *
		 * @since 0.5.0
		 * @return array array which can (possibly) contain a 'dependencies' array and a 'script_vars' array
		 */
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

		/**
		 * Checks a filetype of an attachment.
		 *
		 * @since 0.5.0
		 * @param integer $val the current field value (attachment ID)
		 * @param string|array $accepted_types a string or an array of accepted types (default is 'all' to allow everything)
		 * @return bool whether the file type is valid
		 */
		protected function check_filetype( $val, $accepted_types = 'all' ) {
			$extension = $this->get_attachment_extension( $val );

			if ( $extension ) {
				return $this->check_extension( $extension, $accepted_types );
			}

			return false;
		}

		/**
		 * Returns the file extension of an attachment.
		 *
		 * @since 0.5.0
		 * @param integer $id the current field value (attachment ID)
		 * @return string|false the file extension or false if it could not be detected
		 */
		protected function get_attachment_extension( $id ) {
			$filename = get_attached_file( $id );

			if ( $filename ) {
				$extension = wp_check_filetype( $filename );
				$extension = $extension['ext'];
				if ( $extension ) {
					return $extension;
				}
			}

			return false;
		}

		/**
		 * Checks whether a file extension is among the accepted file types.
		 *
		 * @since 0.5.0
		 * @param string $extension the file extension to check
		 * @param string|array $accepted_types a string or an array of accepted types (default is 'all' to allow everything)
		 * @return bool whether the file type is valid
		 */
		protected function check_extension( $extension, $accepted_types = 'all' ) {
			if ( 'all' == $accepted_types || ! $accepted_types ) {
				return true;
			}

			if ( ! is_array( $accepted_types ) ) {
				$accepted_types = array( $accepted_types );
			}

			// check the file extension
			if ( in_array( strtolower( $extension ), $accepted_types ) ) {
				return true;
			}

			// check the file type (not MIME type!)
			$type = wp_ext2type( $extension );
			if ( $type !== null && in_array( $type, $accepted_types ) ) {
				return true;
			}

			// check the file MIME type (and first part of MIME type)
			$allowed_mime_types = $this->get_all_mime_types();
			if ( isset( $allowed_mime_types[ $extension ] ) ) {
				if ( in_array( $allowed_mime_types[ $extension ], $accepted_types ) ) {
					return true;
				}

				$general_type = explode( '/', $allowed_mime_types[ $extension ] )[0];
				if ( in_array( $general_type, $accepted_types ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Verifies the MIME types whitelist.
		 *
		 * The function ensures that only valid MIME types (full or only general) are provided.
		 * File extensions are parsed into their MIME types while invalid MIME types are stripped out.
		 *
		 * @since 0.5.3
		 * @param string|array $accepted_types a string or an array of accepted types (providing 'all' will allow everything, returning an empty array)
		 * @return array an array of valid MIME types
		 */
		protected function verify_mime_types( $accepted_types ) {
			if ( 'all' === $accepted_types ) {
				return array();
			}

			$validated_mime_types = array();

			if ( ! is_array( $accepted_types ) ) {
				$accepted_types = array( $accepted_types );
			}

			$allowed_mime_types = $this->get_all_mime_types();

			foreach ( $accepted_types as $mime_type ) {
				if ( false === strpos( $mime_type, '/' ) ) {
					switch ( $mime_type ) {
						case 'document':
						case 'spreadsheet':
						case 'interactive':
						case 'archive':
							// documents, spreadsheets, interactive and archive are always MIME type application
							$validated_mime_types[] = 'application';
							break;
						case 'code':
							// code is always MIME type text
							$validated_mime_types[] = 'text';
							break;
						case 'image':
						case 'audio':
						case 'video':
						case 'text':
						case 'application':
							// a valid MIME type
							$validated_mime_types[] = $mime_type;
							break;
						default:
							if ( isset( $allowed_mime_types[ $mime_type ] ) ) {
								// a MIME type for a file extension
								$validated_mime_types[] = $allowed_mime_types[ $mime_type ];
							}
					}
				} elseif ( in_array( $mime_type, $allowed_mime_types ) ) {
					// a fully qualified MIME type (with subtype)
					$validated_mime_types[] = $mime_type;
				}
			}

			return array_unique( $validated_mime_types );
		}

		/**
		 * Returns an array of all MIME types which are allowed by WordPress.
		 *
		 * The array has a file extension as key and its MIME type as value.
		 * Note that therefore it may contain duplicate values.
		 *
		 * @since 0.5.3
		 * @return array an array of generally allowed MIME types
		 */
		protected function get_all_mime_types() {
			$allowed_mime_types = array();

			$_allowed_mime_types = get_allowed_mime_types();

			foreach ( $_allowed_mime_types as $_extensions => $_mime_type ) {
				$extensions = explode( '|', $_extensions );
				foreach ( $extensions as $extension ) {
					$allowed_mime_types[ $extension ] = $_mime_type;
				}
			}

			return $allowed_mime_types;
		}

		/**
		 * Formats an attachment for output.
		 *
		 * The 'mode' key in the $args array specifies in which format it should be returned:
		 * - object (returns the attachment's post object)
		 * - link (returns a link to the attachment)
		 * - image (returns the attachment image, or a mime type icon image if the attachment is not an image)
		 * - template (returns an HTML string where it replaces the template tags by actual attachment values)
		 * - field (returns the value of a specific attachment field)
		 *
		 * If using 'template', you also need to specify a 'template' key in the array which holds the template as an HTML string.
		 * Use attachment field names wrapped in percent signs as template tags (for example '%medium_url%').
		 *
		 * If using 'field', you also need to specify a 'field' key in the array.
		 * Its value will specify which field to retrieve (for example 'medium_url').
		 *
		 * @since 0.5.0
		 * @see WPDLib\FieldTypes\Media::template_replace()
		 * @see WPDLib\FieldTypes\Media::get_attachment_field()
		 * @param integer $id the current value of the field (attachment ID)
		 * @param array $args arguments on how to format
		 * @return integer|string the correctly parsed value (string if $formatted is true)
		 */
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

		/**
		 * Returns the value of a specific attachment field.
		 *
		 * @param integer $id the current field value (attachment ID)
		 * @param string $field the field to get the value for (default is 'url')
		 * @return string the attachment field's value
		 */
		protected function get_attachment_field( $id, $field = 'url' ) {
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

		/**
		 * Returns the value of a specific post field.
		 *
		 * @param integer $id the current field value (attachment ID)
		 * @param string $field the field to get the value for
		 * @return string the post field value (or an empty string if not found)
		 */
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

		/**
		 * Returns the mime type of a specific attachment.
		 *
		 * The second parameter can be set to:
		 * - all (returns for example 'image/jpeg')
		 * - type (returns for example 'image')
		 * - subtype (returns for example 'jpeg')
		 *
		 * @param integer $id the current field value (attachment ID)
		 * @param string $field in which form to return the mime type (default is 'all')
		 * @return string the attachment's mime type
		 */
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

		/**
		 * Returns the directory path or the URL to a specific attachment file.
		 *
		 * @param integer $id the current field value (attachment ID)
		 * @param string $field whether to return a 'path' or a 'url' (default is 'url')
		 * @param string|false $size the image size to return (or false to simply return the original file path/URL)
		 * @return string the path or URL to the desired attachment file
		 */
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

		/**
		 * Callback for the `preg_replace_callback()` call in the `format_attachment()` method (with mode 'template').
		 *
		 * @since 0.5.0
		 * @param array $matches the matches from the regular expression
		 * @return string the replacement
		 */
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
