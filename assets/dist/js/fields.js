/*!
 * WPDLib (https://github.com/felixarntz/wpdlib/)
 * By Felix Arntz (https://leaves-and-love.net)
 * Licensed under GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
( function( $ ) {
	if ( typeof _wpdlib_data === 'undefined' ) {
		console.error( 'WPDLib data object not found' );
	}

	var WPDLibFieldManager = {
		/**
		 * Global arguments coming from `wp_localize_script()`
		 * @type object
		 */
		args: _wpdlib_data,

		/**
		 * Whether Select2 is enabled
		 * @type bool
		 */
		select2_enabled: typeof $.fn.select2 !== 'undefined',

		/**
		 * Arguments for Select2 initialization
		 * @type object
		 */
		select2_args: {},

		/**
		 * Whether DateTimePicker is enabled
		 * @type bool
		 */
		datetimepicker_enabled: typeof $.fn.datetimepicker !== 'undefined',

		/**
		 * Arguments for DateTimePicker initialization
		 * @type object
		 */
		datetimepicker_args: {},

		/**
		 * Whether WPColorPicker is enabled
		 * @type bool
		 */
		colorpicker_enabled: typeof $.fn.wpColorPicker !== 'undefined',

		/**
		 * Arguments for WPColorPicker initialization
		 * @type object
		 */
		colorpicker_args: {},

		/**
		 * Whether WPMediaPicker is enabled
		 * @type bool
		 */
		mediapicker_enabled: typeof $.fn.wpMediaPicker !== 'undefined',

		/**
		 * Arguments for WPMediaPicker initialization
		 * @type object
		 */
		mediapicker_args: {},

		/**
		 * Whether WPMapPicker is enabled
		 * @type bool
		 */
		mappicker_enabled: typeof $.fn.wpMapPicker !== 'undefined',

		/**
		 * Arguments for WPMapPicker initialization
		 * @type object
		 */
		mappicker_args: {},

		/**
		 * Initializes all the fields.
		 *
		 * This is the only function that should be accessed publically.
		 */
		init: function() {
			var self = WPDLibFieldManager;

			self.select2_args = {
				width: 'element',
				closeOnSelect: true,
				templateResult: self._formatSelect2,
				templateSelection: self._formatSelect2,
				minimumResultsForSearch: 8
			};

			self.datetimepicker_args = {
				formatDate: 'Y-m-d',
				formatTime: 'H:i',
				dayOfWeekStart: self.args.start_of_week
			};

			self.colorpicker_args = {
				mode: 'hsv'
			};

			self.mediapicker_args = {
				filterable: false,
				label_add: self.args.media_i18n_add,
				label_replace: self.args.media_i18n_replace,
				label_remove: self.args.media_i18n_remove,
				label_modal: self.args.media_i18n_modal,
				label_button: self.args.media_i18n_button
			};

			self.mappicker_args = {};

			if ( this.datetimepicker_enabled ) {
				$.datetimepicker.setLocale( self.args.language );
			}

			self._initJQueryPluginFields();

			self._setupRange( '.wpdlib-input-range' );
			self._setupRadio( '.wpdlib-input-radio' );
			self._setupMultibox( '.wpdlib-input-multibox' );
			self._setupRepeatable( '.wpdlib-input-repeatable' );
		},

		/**
		 * Initializes all fields that rely on jQuery plugins.
		 *
		 * @param string selector_prefix an optional (parent) selector string to prefix all the selectors with
		 */
		_initJQueryPluginFields: function( selector_prefix ) {
			var self = WPDLibFieldManager;

			if ( typeof selector_prefix !== 'string' ) {
				selector_prefix = '';
			} else {
				selector_prefix += ' ';
			}

			self._setupSelect2( selector_prefix + '.wpdlib-input-select' );
			self._setupSelect2( selector_prefix + '.wpdlib-input-multiselect' );
			self._setupDatetimepicker( selector_prefix + '.wpdlib-input-datetime' );
			self._setupDatepicker( selector_prefix + '.wpdlib-input-date' );
			self._setupTimepicker( selector_prefix + '.wpdlib-input-time' );
			self._setupColorpicker( selector_prefix + '.wpdlib-input-color' );
			self._setupMediapicker( selector_prefix + '.wpdlib-input-media' );
			self._setupMappicker( selector_prefix + '.wpdlib-input-map' );
		},

		/**
		 * Initializes Select2 on a selection of fields.
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 */
		_setupSelect2: function( selector ) {
			if ( ! this.select2_enabled ) {
				return;
			}

			var $fields = this._getJQuery( selector );

			$fields.select2( this.select2_args );
		},

		/**
		 * Initializes DateTimePicker for datetime inputs on a selection of fields.
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 */
		_setupDatetimepicker: function( selector ) {
			if ( ! this.datetimepicker_enabled ) {
				return;
			}

			var $fields = this._getJQuery( selector );

			$fields.datetimepicker( $.extend( {
				format: this.args.date_format + ' ' + this.args.time_format,
				onShow: this._datetimeOnShow
			}, this.datetimepicker_args ) );
		},

		/**
		 * Initializes DateTimePicker for date inputs on a selection of fields.
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 */
		_setupDatepicker: function( selector ) {
			if ( ! this.datetimepicker_enabled ) {
				return;
			}

			var $fields = this._getJQuery( selector );

			$fields.datetimepicker( $.extend( {
				format: this.args.date_format,
				timepicker: false,
				onShow: this._dateOnShow
			}, this.datetimepicker_args ) );
		},

		/**
		 * Initializes DateTimePicker for time inputs on a selection of fields.
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 */
		_setupTimepicker: function( selector ) {
			if ( ! this.datetimepicker_enabled ) {
				return;
			}

			var $fields = this._getJQuery( selector );

			$fields.datetimepicker( $.extend( {
				format: this.args.time_format,
				datepicker: false,
				onShow: this._timeOnShow
			}, this.datetimepicker_args ) );
		},

		/**
		 * Initializes WPColorPicker on a selection of fields.
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 */
		_setupColorpicker: function( selector ) {
			if ( ! this.colorpicker_enabled ) {
				return;
			}

			var $fields = this._getJQuery( selector );

			$fields.wpColorPicker( this.colorpicker_args );
		},

		/**
		 * Initializes WPMediaPicker on a selection of fields.
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 */
		_setupMediapicker: function( selector ) {
			if ( ! this.mediapicker_enabled ) {
				return;
			}

			var $fields = this._getJQuery( selector );

			$fields.wpMediaPicker( this.mediapicker_args );
		},

		/**
		 * Initializes WPMapPicker on a selection of fields.
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 */
		_setupMappicker: function( selector ) {
			if ( ! this.mappicker_enabled ) {
				return;
			}

			var $fields = this._getJQuery( selector );

			$fields.wpMapPicker( this.mappicker_args );
		},

		/**
		 * Adds listeners to handle range inputs.
		 *
		 * @param string selector a selector
		 */
		_setupRange: function( selector ) {
			$( document ).on( 'change', selector, function() {
				$( this ).prev( 'input' ).val( $( this ).val() );
			});

			$( document ).on( 'change', selector + '-viewer', function() {
				$( this ).next( 'input' ).val( $( this ).val() );
			});
		},

		/**
		 * Adds listeners to handle radio inputs with images/colors.
		 *
		 * @param string selector a selector
		 */
		_setupRadio: function( selector ) {
			$( document ).on( 'click', selector + ' .wpdlib-radio div', function() {
				var input_id = $( this ).attr( 'id' ).replace( '-asset', '' );

				$( '#' + input_id ).prop( 'checked', true ).trigger( 'change' );
			});

			$( document ).on( 'change', selector + ' .wpdlib-radio input', function() {
				var input_id = $( this ).attr( 'id' );

				$( this ).parent().parent().find( '.wpdlib-radio div' ).removeClass( 'checked' );
				$( '#' + input_id ).addClass( 'checked' );
			});
		},

		/**
		 * Adds listeners to handle multibox inputs with images/colors.
		 *
		 * @param string selector a selector
		 */
		_setupMultibox: function( selector ) {
			$( document ).on( 'click', selector + ' .wpdlib-checkbox div', function() {
				var input_id = $( this ).attr( 'id' ).replace( '-asset', '' );

				if ( $( this ).hasClass( 'checked' ) ) {
					$( this ).removeClass( 'checked' );

					$( '#' + input_id ).prop( 'checked', false );
				} else {
					$( this ).addClass( 'checked' );

					$( '#' + input_id ).prop( 'checked', true );
				}
			});
		},

		/**
		 * Initializes repeatable elements
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 */
		_setupRepeatable: function( selector ) {
			var self = WPDLibFieldManager;
			var $fields = this._getJQuery( selector );

			$fields.each(function() {
				$( this ).on( 'click', '.wpdlib-new-repeatable-button', function( e ) {
					var parent_selector = '#' + e.delegateTarget.id;
					var $parent = $( parent_selector );
					var limit = parseInt( $parent.data( 'limit' ));
					var id = $parent.attr( 'id' );
					var key = $parent.find( '.wpdlib-repeatable-row' ).length;

					if ( typeof self.args.repeatable_field_templates[ id ] !== 'undefined' ) {
						var output = self.args.repeatable_field_templates[ id ].replace( /{{KEY}}/g, key ).replace( /{{KEY_PLUSONE}}/g, key + 1 );

						$parent.find( '.wpdlib-repeatable-table' ).show();
						$parent.find( '.wpdlib-repeatable-table' ).append( output );

						if ( limit > 0 && limit === key + 1 ) {
							$parent.find( '.wpdlib-new-repeatable-button' ).hide();
						}

						$( document ).trigger( 'wpdlib-added-repeatable-item', [ parent_selector ] );
					}

					e.preventDefault();
				});

				$( this ).on( 'click', '.wpdlib-remove-repeatable-button', function( e ) {
					var $parent = $( '#' + e.delegateTarget.id );

					var $rows = $parent.find( '.wpdlib-repeatable-row' );

					var number = parseInt( $( this ).data( 'number' ) ) + 1;

					e.preventDefault();

					$rows.filter( ':nth-child(' + ( number + 1 ) + ')' ).remove();

					$rows.filter( ':gt(' + ( number - 1 ) + ')' ).each(function() {
						var $row = $( this );

						var number = parseInt( $row.find( '.wpdlib-remove-repeatable-button' ).data( 'number' ) );

						var target = number - 1;

						$row.find( 'span:first' ).html( $row.find( 'span:first' ).html().replace( ( number + 1 ).toString(), ( target + 1 ).toString() ) );

						$row.find( '.wpdlib-repeatable-col input, .wpdlib-repeatable-col select, .wpdlib-repeatable-col img, .wpdlib-repeatable-col a' ).each(function() {
							if ( $( this ).attr( 'id' ) ) {
								$( this ).attr( 'id', $( this ).attr( 'id' ).replace( number.toString(), target.toString() ) );
							}

							if ( $( this ).attr( 'name' ) ) {
								$( this ).attr( 'name', $( this ).attr( 'name' ).replace( number.toString(), target.toString() ) );
							}
						});

						$row.find('.wpdlib-remove-repeatable-button').data( 'number', target.toString() );
					});

					var limit = parseInt( $parent.data( 'limit' ) );

					if ( limit > 0 && limit > $parent.find( '.wpdlib-repeatable-row' ).length ) {
						$parent.find( '.wpdlib-new-repeatable-button' ).show();
					}
					if ( $parent.find( '.wpdlib-repeatable-row' ).length < 1 ) {
						$parent.find( '.wpdlib-repeatable-table' ).hide();
					}
				});
			});

			$( document ).on( 'wpdlib-added-repeatable-item', function( e, parent_selector ) {
				self._initJQueryPluginFields( parent_selector );
			});
		},

		/**
		 * Returns a jQuery object from a string.
		 *
		 * @param string|jQuery selector a selector or a jQuery object
		 * @return jQuery a jQuery object
		 */
		_getJQuery: function( selector ) {
			if ( typeof selector === 'string' ) {
				return $( selector );
			}
			return selector;
		},

		/**
		 * Formatting function passed to Select2.
		 *
		 * @param object selection contains information about the current selection
		 * @return string the formatted (HTML) text
		 */
		_formatSelect2: function( selection ) {
			if ( typeof selection.id === 'undefined' ) {
				return selection.text;
			}

			var $option = $( selection.element );
			var option_data = $option.data();

			if ( option_data ) {
				if ( option_data.hasOwnProperty( 'image' ) ) {
					return $( '<div class="wpdlib-option-box-wrap"><div class="wpdlib-option-box" style="background-image:url(' + $option.data( 'image' ) + ');"></div><span class="wpdlib-option-box-text">' + selection.text + '</span></div>' );
				} else if ( option_data.hasOwnProperty( 'color' ) ) {
					return $( '<div class="wpdlib-option-box-wrap"><div class="wpdlib-option-box" style="background-color:#' + $option.data( 'color' ) + ';"></div><span class="wpdlib-option-box-text">' + selection.text + '</span></div>' );
				}
			}

			return selection.text;
		},

		/**
		 * OnShow function passed to DateTimePicker for datetime inputs.
		 *
		 * @param object ct contains information about the current datetime
		 * @param jQuery $input the input element
		 */
		_datetimeOnShow: function( ct, $input ) {
			var helper = '';
			if ( $input.attr( 'min' ) ) {
				helper = $input.attr( 'min' ).split( ' ' );
				if ( helper.length === 2 ) {
					this.setOptions({
						minDate: helper[0],
						minTime: helper[1]
					});
				} else if( helper.length === 1 ) {
					this.setOptions({
						minDate: helper[0]
					});
				}
			}

			if ( $input.attr( 'max' ) ) {
				helper = $input.attr( 'max' ).split( ' ' );
				if ( helper.length === 2 ) {
					this.setOptions({
						maxDate: helper[0],
						maxTime: helper[1]
					});
				} else if( helper.length === 1 ) {
					this.setOptions({
						maxDate: helper[0]
					});
				}
			}

			if ( $input.attr( 'step' ) ) {
				this.setOptions({
					step: parseInt( $input.attr( 'step' ) )
				});
			}
		},

		/**
		 * OnShow function passed to DateTimePicker for date inputs.
		 *
		 * @param object ct contains information about the current date
		 * @param jQuery $input the input element
		 */
		_dateOnShow: function( ct, $input ) {
			if ( $input.attr( 'min' ) ) {
				this.setOptions({
					minDate: $input.attr( 'min' )
				});
			}

			if ( $input.attr( 'max' ) ) {
				this.setOptions({
					maxDate: $input.attr( 'max' )
				});
			}
		},

		/**
		 * OnShow function passed to DateTimePicker for time inputs.
		 *
		 * @param object ct contains information about the current time
		 * @param jQuery $input the input element
		 */
		_timeOnShow: function( ct, $input ) {
			if ( $input.attr( 'min' ) ) {
				this.setOptions({
					minTime: $input.attr( 'min' )
				});
			}

			if ( $input.attr( 'max' ) ) {
				this.setOptions({
					maxTime: $input.attr( 'max' )
				});
			}

			if ( $input.attr( 'step' ) ) {
				this.setOptions({
					step: parseInt( $input.attr( 'step' ) )
				});
			}
		}
	};

	$( document ).ready( function() {
		WPDLibFieldManager.init();
	});
}( jQuery ) );
