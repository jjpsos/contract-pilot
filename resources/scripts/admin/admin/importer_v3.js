jQuery( document ).ready( ( $ ) => {
	'use strict';

	/**
	 * EAC Importer Plugin
	 *
	 * This jQuery plugin handles the import functionality through AJAX requests.
	 * It manages file uploads, mapping of fields, and progress indication.
	 *
	 * @param {jQuery} $form - The form element to be processed.
	 */
	$.eac_importer = function ( $form ) {
		this.$form = $form;
		this.$submit = $( 'input[type="submit"]', this.$form );
		this.$progress = $( '<div class="eac-progress"><div></div></div>' );
		this.$spinner = $( '<span class="spinner is-active" style="float: none"></span>' );
		this.$notice = $( '<div class="notice is-dismissible"><p></p></div>' );
		this.nonce = this.$form.data( 'nonce' );
		this.type = this.$form.data( 'type' );

		// Number of import successes/failures.
		this.imported = 0;
		this.failed = 0;
		this.updated = 0;
		this.skipped = 0;

		const plugin = this;

		/**
		 * Submit form handler.
		 *
		 * @param {Event} e - The submit event.
		 */
		this.submit = function ( e ) {
			e.preventDefault();
			if ( plugin.$submit.hasClass( 'disabled' ) ) {
				return false;
			}

			plugin.reset();
			plugin.$submit.attr( 'disabled', 'disabled' );
			plugin.$form.append( plugin.$spinner );
			plugin.$form.append( plugin.$progress );

			const data = new FormData();
			data.append( 'upload', $( 'input[type="file"]', plugin.$form )[ 0 ].files[ 0 ] );
			data.append( 'action', 'eac_upload_import_file' );
			data.append( '_wpnonce', plugin.nonce );
			data.append( 'type', plugin.type );

			// Send AJAX request for file upload
			window.wp.ajax.send( {
				type: 'POST',
				data,
				dataType: 'json',
				cache: false,
				contentType: false,
				processData: false,
				success: ( response ) => {
					if ( response.success ) {
						$( 'input[type="file"]', plugin.$form ).val( '' );
						plugin.import( { ...response.data, action: 'eac_ajax_import' } );
					} else if ( response.data.message ) {
						plugin.error( response.data );
					}
				},
				error: ( error ) => {
					plugin.error( error );
				},
			} );
		};

		/**
		 * Make request to import data.
		 *
		 * @param {Object} data - The data to be sent to the server.
		 * @return {void}
		 */
		this.import = function ( data ) {
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data,
				success( response ) {
					if ( response.success ) {
						plugin.process( response.data );
					} else {
						plugin.error( response.data );
					}
				},
				error( response ) {
					plugin.error( response );
				},
			} );
		};

		/**
		 * Reset the form state to its initial condition.
		 *
		 * Removes old notices, re-enables the submit button,
		 * and removes any loading indicators.
		 */
		this.reset = function () {
			// Remove old notice
			$( '.notice', plugin.$form ).remove();
			$( '.eac-progress', plugin.$form ).remove();
			$( '.spinner', plugin.$form ).remove();
			plugin.$submit.removeClass( 'disabled' );
		};

		/**
		 * Handle the error response.
		 *
		 * @param {Object} response - The response object.
		 * @return {void}
		 */
		this.error = function ( response ) {
			plugin.reset();
			if ( response.message ) {
				plugin.$form.append( plugin.$notice );
				plugin.$notice.addClass( 'notice-error' );
				plugin.$notice.find( 'p' ).text( response.message );
			}
		};

		this.$form.on( 'submit', this.submit );
	};

	/**
	 * jQuery Plugin Wrapper for EAC Importer.
	 *
	 * Initializes the EAC Importer for each matched element.
	 *
	 * @return {jQuery} - The jQuery object for chaining.
	 */
	$.fn.eac_importer = function () {
		return this.each( function () {
			new $.eac_importer( $( this ) );
			return this;
		} );
	};

	$( 'form.eac_importer' ).eac_importer();
} );
