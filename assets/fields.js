jQuery( document ).ready( function( $ ) {

	if ( typeof $.fn.select2 !== 'undefined' ) {
		// select2 setup
		function formatSelect2( option ) {
			var $option = $( option.element );

			if ( $option.data().hasOwnProperty( 'image' ) ) {
				return '<div class="wpdlib-option-box" style="background-image:url(' + $option.data( 'image' ) + ');"></div>' + option.text;
			} else if ( $option.data().hasOwnProperty( 'color' ) ) {
				return '<div class="wpdlib-option-box" style="background-color:#' + $option.data( 'color' ) + ';"></div>' + option.text;
			} else {
				return option.text;
			}
		}

		var select2_args = {
			containerCss : {
				'width': '100%',
				'max-width': '500px'
			},
			closeOnSelect: false,
			formatResult: formatSelect2,
			formatSelection: formatSelect2,
			escapeMarkup: function(m) { return m; },
			minimumResultsForSearch: 8
		};

		$( '.wpdlib-input-select' ).select2( select2_args );
	}

	if ( typeof $.fn.datetimepicker !== 'undefined' )
		// datetimepicker setup
		var dtp_datetimepicker_args = {
			lang: _wpdlib_data.language,
			formatDate: 'Y-m-d',
			formatTime: 'H:i',
			dayOfWeekStart: _wpdlib_data.start_of_week
		};

		var dtp_datetime_args = $.extend({
			format: _wpdlib_data.date_format + ' ' + _wpdlib_data.time_format,
			onShow: function( ct, $input ) {
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
			}
		}, dtp_datetimepicker_args );

		var dtp_date_args = $.extend({
			format: _wpdlib_data.date_format,
			timepicker: false,
			onShow: function( ct, $input ) {
				if ( $input.attr( 'min' ) ) {
					this.setOptions({
						minDate: $input.attr('min')
					});
				}

				if ( $input.attr( 'max' ) ) {
					this.setOptions({
						maxDate: $input.attr('max')
					});
				}
			}
		}, dtp_datetimepicker_args );

		var dtp_time_args = $.extend({
			format: _wpdlib_data.time_format,
			datepicker: false,
			onShow: function( ct, $input ) {
				if ( $input.attr( 'min' ) ) {
					this.setOptions({
						minTime: $input.attr('min')
					});
				}

				if ( $input.attr( 'max' ) ) {
					this.setOptions({
						maxTime: $input.attr('max')
					});
				}

				if ( $input.attr( 'step' ) ) {
					this.setOptions({
						step: parseInt( $input.attr( 'step' ) )
					});
				}
			}
		}, dtp_datetimepicker_args );

		$( '.wpdlib-input-datetime' ).datetimepicker( dtp_datetime_args );
		$( '.wpdlib-input-date' ).datetimepicker( dtp_date_args );
		$( '.wpdlib-input-time' ).datetimepicker( dtp_time_args );
	}

	// viewer handling for fields without visible output
	$( document ).on( 'change', '.wpdlib-input-range, .wpdlib-input-color', function() {
		$( this ).prev( 'input' ).val( $( this ).val() );
	});

	$( document ).on( 'change', '.wpdlib-input-range-viewer, .wpdlib-input-color-viewer', function() {
		$( this ).next( 'input' ).val( $( this ).val() );
	});

	// radio handling
	$( document ).on( 'click', '.wpdlib-input-radio .radio div', function() {
		var input_id = $( this ).attr( 'id' ).replace( '-asset', '' );

		$( this ).parent().parent().find( '.radio div' ).removeClass( 'checked' );

		$( this ).addClass( 'checked' );

		$( '#' + input_id ).prop( 'checked', true );
	});

	$( document ).on( 'change', '.wpdlib-input-radio .radio input', function() {
		$( this ).parent().parent().find( '.radio div' ).removeClass( 'checked' );
	});

	// multibox handling
	$( document ).on( 'click', '.wpdlib-input-multibox .checkbox div', function() {
		var input_id = $( this ).attr( 'id' ).replace( '-asset', '' );

		if ( $( this ).hasClass( 'checked' ) ) {
			$( this ).removeClass( 'checked' );

			$( '#' + input_id ).prop( 'checked', false );
		} else {
			$( this ).addClass( 'checked' );

			$( '#' + input_id ).prop( 'checked', true );
		}
	});

	// media uploader
	if ( typeof wp.media !== 'undefined' ) {
		var _custom_media = true;

		var _orig_send_attachment = wp.media.editor.send.attachment;

		$( document ).on( 'click', '.wpdlib-input-media-button', function() {
			var $button = $( this );

			var search_id = $button.attr( 'id' ).replace( '-media-button', '' );

			_custom_media = true;

			wp.media.editor.send.attachment = function( props,attachment ) {
				if ( _custom_media ) {
					$( '#' + search_id ).val( attachment.id );
					var name = attachment.url.split( '/' );
					name = name[ name.length - 1 ];
					$( '#' + search_id + '-media-title' ).val( name );
					if ( attachment.type === 'image' ) {
						if ( $( '#' + search_id + '-media-image' ).length > 0 ) {
							$( '#' + search_id + '-media-image' ).attr( 'src', attachment.url );
						} else {
							$( '#' + search_id + '-media-button' ).after( '<img id="' + search_id + '-media-image" class="wpdlib-media-image" src="' + attachment.url + '" />' );
						}
					} else {
						if ( $( '#' + search_id + '-media-link' ).length > 0 ) {
							$( '#' + search_id + '-media-link' ).attr( 'href', attachment.url );
						} else {
							$( '#' + search_id + '-media-button' ).after( '<a id="' + search_id + '-media-link" class="wpdlib-media-link" href="' + attachment.url + '" target="_blank">' + _wpdlib_data.i18n_open_file + '</a>' );
						}
					}
				} else {
					return _orig_send_attachment.apply( this, [ props, attachment ] );
				}
			};

			wp.media.editor.open( $button );

			return false;
		});

		$( '.add_media' ).on( 'click', function() {
			_custom_media = false;
		});
	}

	// repeatable fields
	if ( $( '.wpdlib-input-repeatable' ).length > 0 && _wpdlib_data !== undefined ) {
		$( '.wpdlib-input-repeatable' ).each(function() {
			$( this ).on( 'click', '.wpdlib-new-repeatable-button', function( e ) {
				var $parent = $( '#' + e.delegateTarget.id );
				var limit = parseInt( $parent.data( 'limit' ));
				var id = $parent.attr( 'id' );
				var key = $parent.find( '.wpdlib-repeatable-row' ).length;

				if ( typeof _wpdlib_data.repeatable_field_templates[ id ] !== 'undefined' ) {
					var output = _wpdlib_data.repeatable_field_templates[ id ].replace( /{{KEY}}/g, key ).replace( /{{KEY_PLUSONE}}/g, key + 1 );

					$parent.append( output );
					if ( typeof $.fn.select2 !== 'undefined' ) {
						$parent.find( '.wpdlib-input-select' ).select2( select2_args );
					}
					if ( typeof $.fn.datetimepicker !== 'undefined' ) {
						$parent.find( '.wpdlib-input-datetime' ).datetimepicker( dtp_datetime_args );
						$parent.find( '.wpdlib-input-date' ).datetimepicker( dtp_date_args );
						$parent.find( '.wpdlib-input-time' ).datetimepicker( dtp_time_args );
					}

					if ( limit > 0 && limit === key + 1 ) {
						$parent.find( '.wpdlib-new-repeatable-button' ).hide();
					}
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

				var limit = parseInt( $( '#' + e.delegateTarget.id ).data( 'limit' ) );

				if ( limit > 0 && limit > $( '#' + e.delegateTarget.id ).find( '.wpdlib-repeatable-row' ).length) {
					$( '#' + e.delegateTarget.id ).find( '.wpdlib-new-repeatable-button' ).show();
				}
			});
		});
	}

});
