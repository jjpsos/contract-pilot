/**
 * External dependencies
 */
import money from '@eac/money';

jQuery( document ).ready( ( $ ) => {
	'use strict';

	const initializeUI = function () {
		// Select2.
		$( '.eac_select2' )
			.filter( ':not(.enhanced)' )
			.each( function () {
				const $this = $( this );
				const options = {
					allowClear:
						( $this.data( 'allow-clear' ) && ! $this.prop( 'multiple' ) ) || true,
					placeholder: $this.data( 'placeholder' ) || '',
					width: '100%',
					minimumInputLength: $this.data( 'minimum-input-length' ) || 0,
					readOnly: $this.data( 'readonly' ) || false,
					ajax: {
						url: eac_admin_vars.ajax_url,
						dataType: 'json',
						delay: 250,
						method: 'POST',
						data( params ) {
							return {
								term: params.term,
								action: $this.data( 'action' ),
								type: $this.data( 'type' ),
								subtype: $this.data( 'subtype' ),
								_wpnonce: eac_admin_vars.search_nonce,
								exclude: $this.data( 'exclude' ),
								include: $this.data( 'include' ),
								limit: $this.data( 'limit' ),
							};
						},
						processResults( data ) {
							data.page = data.page || 1;
							return data;
						},
						cache: true,
					},
				};
				// if data-action is not defined then return.
				if ( ! $this.data( 'action' ) ) {
					delete options.ajax;
				}
				$this.addClass( 'enhanced' ).selectWoo( options );
			} );

		// Datepicker.
		$( '.eac_datepicker' )
			.filter( ':not(.enhanced)' )
			.each( function () {
				const $this = $( this );
				const options = {
					dateFormat: $this.data( 'format' ) || 'yy-mm-dd',
					changeMonth: true,
					changeYear: true,
					showButtonPanel: true,
					showOtherMonths: true,
					selectOtherMonths: true,
					yearRange: '-100:+10',
				};
				$this.addClass( 'enhanced' ).datepicker( options );
			} );

		// Datetimepicker.
		$( '.eac_datetimepicker' )
			.filter( ':not(.enhanced)' )
			.each( function () {
				const $this = $( this );
				const options = {
					dateFormat: $this.data( 'format' ) || 'yy-mm-dd',
					timeInput: true,
					timeFormat: 'HH:mm:ss',
					showSecond: false,
					showHour: false,
					showMinute: false,
				};

				$this.addClass( 'enhanced' ).datetimepicker( options );

				// Optional: Parse time if needed separately
				const parsed = $.datepicker.parseTime( options.timeFormat, $this.val() );
			} );

		// Tooltip.
		$( '.eac_tooltip' )
			.filter( ':not(.enhanced)' )
			.each( function () {
				const $this = $( this );
				const options = {
					position: {
						my: 'center bottom-15',
						at: 'center top',
					},
					tooltipClass: 'eac_tooltip',
				};
				$this.addClass( 'enhanced' ).tooltip( options );
			} );

		// Currency.
		$( ':input.eac_amount' )
			.not( '.enhanced' )
			.each( function () {
				const $input = $( this );
				const $form = $input.closest( 'form' );
				const $source = $form.find( $input.data( 'source' ) );
				const originalName = $input.attr( 'name' );
				const originalValue = $input.val() || '';

				const maskOptions = money.getCurrencyMaskOptions( $input.data( 'currency' ) );

				const normalizeValue = ( value ) => {
					const num = parseFloat( ( value || '' ).replace( /[^\d.-]/g, '' ) );
					return isNaN( num )
						? ''
						: num.toFixed( maskOptions.digits ).replace( '.', maskOptions.radixPoint );
				};

				// Remove existing hidden fields with same name
				$input.siblings( 'input[type="hidden"][name="' + originalName + '"]' ).remove();

				// Create hidden input to store normalized value
				const $hidden = $( '<input>', {
					type: 'hidden',
					name: originalName,
					value: originalValue,
				} ).insertAfter( $input );

				$input
					.addClass( 'enhanced' )
					.val( normalizeValue( originalValue ) )
					.inputmask( maskOptions )
					.on( 'input', function () {
						const radixPoint = $input.inputmask( 'option', 'radixPoint' ) || '.';
						const unmasked = $input.inputmask( 'unmaskedvalue' ) || '0';
						$hidden.val( unmasked.replace( radixPoint, '.' ) ).trigger( 'change' );
					} );

				// Update mask when currency source changes
				if ( $source.length ) {
					$source.on( 'change', function () {
						const code =
							$( this ).attr( 'name' ) === 'currency'
								? $source.val()
								: $source.select2( 'data' )?.[ 0 ]?.currency;
						$input.inputmask( 'option', money.getCurrencyMaskOptions( code ) );
						$input.data( 'currency', code );
					} );
				}
			} );

		// exchange rate.
		$( ':input.eac_exchange_rate' )
			.filter( ':not(.enhanced)' )
			.each( function () {
				const $this = $( this );
				const $source = $this.closest( 'form' ).find( $this.data( 'source' ) );

				$this
					.inputmask( {
						...money.getExchangeRateMaskOptions( $this.data( 'currency' ) ),
					} )
					.addClass( 'enhanced' )
					.attr( 'readonly', $this.data( 'currency' ) === eac_base_currency );

				if ( $source.length ) {
					$source.on( 'change', function () {
						const code =
							$( this ).attr( 'name' ) === 'currency'
								? $source.val()
								: $source.select2( 'data' )?.[ 0 ]?.currency;
						$this.inputmask( 'option', money.getExchangeRateMaskOptions( code ) );
						$this.inputmask( 'setvalue', money.getRate( code ) );
						$this.attr( 'readonly', code === eac_base_currency );
					} );
				}
			} );

		// inputMask.
		$( '.eac_inputmask' )
			.filter( ':not(.enhanced)' )
			.each( function () {
				const $this = $( this );
				const options = {
					alias: $this.data( 'alias' ) || 'decimal',
					rightAlign: false,
					allowMinus: false,
					placeholder: $this.data( 'placeholder' ) || '',
					clearIncomplete: $this.data( 'clear-incomplete' ) || false,
					removeMaskOnSubmit: true,
				};
				$this.addClass( 'enhanced' ).inputmask( options );
			} );

		// Polyfill for card padding for firefox.
		$( '.eac-card' ).each( function () {
			if (
				! $( this ).children( '[class*="eac-card__"]' ).length &&
				! parseInt( $( this ).css( 'padding' ) )
			) {
				$( this ).css( 'padding', '8px 12px' );
			}
		} );

		// remove .eac-card__body if it is empty.
		$( '.eac-card__body' ).each( function () {
			// check for text or any html tags and not it can
			if ( ! $( this ).text().trim().length && $( this ).children().length === 0 ) {
				$( this ).remove();
			}
		} );
	};

	// Initialize UI.
	initializeUI();

	// Reinitialize UI when document body triggers 'eac-update-ui'.
	$( document.body ).on( 'eac_update_ui', initializeUI );

	// Media Uploader.
	$( '.eac-file-upload' )
		.filter( ':not(.enhanced)' )
		.each( function () {
			const $this = $( this );
			const $button = $this.find( '.eac-file-upload__button' );
			const $value = $this.find( '.eac-file-upload__value' );
			const $preview = $this.find( '.eac-file-upload__icon img' );
			const $name = $this.find( '.eac-file-upload__name a' );
			const $size = $this.find( '.eac-file-upload__size' );
			const $remove = $this.find( 'a.eac-file-upload__remove' );

			$button.on( 'click', function ( e ) {
				e.preventDefault();
				const frame = wp.media( {
					title: $button.data( 'uploader-title' ),
					multiple: false,
				} );
				frame.on( 'ready', function () {
					frame.uploader.options.uploader.params = {
						type: 'eac_file',
					};
				} );
				frame.on( 'select', function () {
					const attachment = frame.state().get( 'selection' ).first().toJSON();
					const src = attachment.type === 'image' ? attachment.url : attachment.icon;
					$value.val( attachment.id );
					$preview.attr( 'src', src ).show();
					$preview.attr( 'alt', attachment.filename );
					$name.text( attachment.filename ).attr( 'href', attachment.url );
					$size.text( attachment.filesizeHumanReadable );
					$remove.show();
					$this.addClass( 'has--file' );
				} );
				frame.open();
			} );

			$remove.on( 'click', function ( e ) {
				e.preventDefault();
				$this.removeClass( 'has--file' );
				$value.val( '' );
				$preview.attr( 'src', '' ).hide();
				$name.text( '' ).attr( 'href', '' );
				$size.text( '' );
			} );
		} );

	// Delete confirmation.
	$( '.del_confirm' ).on( 'click', function ( e ) {
		if ( ! confirm( eac_admin_vars.i18n.confirm_delete ) ) {
			e.preventDefault();
			return false;
		}
	} );

	// Notes.
	$( document.body )
		.on( 'keydown', '#eac-note', function ( e ) {
			if ( e.keyCode === 13 && ( e.metaKey || e.ctrlKey ) ) {
				e.preventDefault();
				$( '#eac-add-note' ).click();
			}
		} )
		.on( 'click', '#eac-add-note', function ( e ) {
			e.preventDefault();
			const $button = $( this );
			const $note = $( '#eac-note' );
			const $notes = $( '.eac-notes' );

			const data = {
				action: 'eac_add_note',
				nonce: $button.data( 'nonce' ),
				parent_id: $button.data( 'parent_id' ),
				parent_type: $button.data( 'parent_type' ),
				content: $note.val(),
			};

			if ( data.content ) {
				$button.prop( 'disabled', true );

				$.ajax( {
					type: 'POST',
					url: eac_admin_vars.ajax_url,
					data,
					success( response ) {
						let res = wpAjax.parseAjaxResponse( response );
						res = res.responses[ 0 ];
						$notes.prepend( res.data );
						$note.val( '' );
						$button.prop( 'disabled', false );
						$notes.find( '.no-items' ).hide();
					},
				} ).fail( function () {
					$button.prop( 'disabled', false );
				} );
			}
		} )
		.on( 'click', '.eac-notes .note__delete', function ( e ) {
			e.preventDefault();
			const $button = $( this );
			const $note = $button.closest( '.note' );
			const $notes = $note.closest( '.eac-notes' );
			const data = {
				action: 'eac_delete_note',
				nonce: $button.data( 'nonce' ),
				note_id: $button.data( 'note_id' ),
			};

			if ( confirm( eac_admin_vars.i18n.confirm_delete ) ) {
				$.ajax( {
					type: 'POST',
					url: eac_admin_vars.ajax_url,
					data,
					success( response ) {
						if ( '1' === response ) {
							$note.remove();
						}

						if ( $notes.find( 'li' ).length === 0 ) {
							$notes.find( '.no-items' ).show();
						}
					},
				} );
			}
		} );

	// print.
	$( '.eac_print_document' ).on( 'click', function ( e ) {
		e.preventDefault();
		const $target = $( $( this ).data( 'target' ) );
		if ( ! $target.length ) {
			return;
		}

		$target.printThis( {
			printContainer: true,
			printDelay: 0,
			header: null,
			footer: null,
		} );
	} );

	// Block UI
	$.fn.block = function ( destroy ) {
		return this.each( function () {
			const $el = $( this );
			if ( destroy && $el.find( '.blockUI' ).length ) {
				$el.find( '.blockUI' ).remove();
				return;
			}

			// If destroy is false, just create the overlay if it doesn't exist
			if ( ! $el.find( '.blockUI' ).length ) {
				// Ensure position is relative
				if ( $el.css( 'position' ) === 'static' ) {
					$el.css( 'position', 'relative' );
				}

				// Create overlay
				$( '<div class="blockUI"></div>' )
					.css( {
						position: 'absolute',
						top: 0,
						left: 0,
						width: '100%',
						height: '100%',
						backgroundColor: 'rgb(255, 255, 255)',
						opacity: 0.1,
						cursor: 'wait',
						zIndex: 9999,
					} )
					.appendTo( $el );
			}
		} );
	};
} );
