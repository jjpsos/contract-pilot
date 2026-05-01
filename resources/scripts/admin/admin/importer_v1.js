jQuery( document ).ready( ( $ ) => {
	'use strict';

	/**
	 * EAC Importer Plugin
	 *
	 * This jQuery plugin handles the import functionality through AJAX requests.
	 * It manages file uploads, mapping of fields, and progress indication.
	 *
	 * @param {HTMLFormElement} form    - The form element to be processed.
	 * @param {Object}          options - Custom options for the plugin.
	 */
	$.eac_importer = function ( form, options ) {
		this.defaults = {};
		this.$form = $( form );
		this.$submit = $( 'input[type="submit"]', this.$form );
		this.options = $.extend( this.defaults, options );
		this.file = '';
		this.action = 'eac_ajax_import';
		this.nonce = this.$form.data( 'nonce' );
		this.type = this.$form.data( 'type' );
		const plugin = this;

		/**
		 * Submit form handler.
		 *
		 * Prevents default form submission, checks if the submit button is disabled,
		 * and handles the file upload process including mapping.
		 *
		 * @param {Event} e - The submit event.
		 * @return {boolean} - Returns false if the button is disabled, otherwise undefined.
		 */
		this.submit = function ( e ) {
			e.preventDefault();

			if ( plugin.$submit.hasClass( 'disabled' ) ) {
				return false;
			}

			plugin.$submit.attr( 'disabled', 'disabled' );

			plugin.reset();

			plugin.$submit.closest( 'p' ).append( '<span class="spinner is-active"></span>' );

			plugin.$form.append( '<div class="eac-progress"><div></div></div>' );

			// Prepare FormData for the file upload
			const data = new FormData();
			data.append( 'upload', $( 'input[type="file"]', plugin.$form )[ 0 ].files[ 0 ] );
			data.append( 'action', plugin.action );
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
				success: plugin.analyze_response,
				error( error ) {
					plugin.$submit.removeAttr( 'disabled' );
					$( '.spinner', plugin.$form ).remove();
					plugin.$form.append(
						'<div class="updated error"><p>' + error.message + '</p></div>'
					);
				},
			} );
		};

		/**
		 * Process a specific step of the import operation.
		 *
		 * Sends an AJAX request to process the given step and updates progress.
		 *
		 * @param {number} step - The current step in the import process.
		 */
		this.process_step = function ( step ) {
			window.wp.ajax.send( plugin.action, {
				data: {
					_wpnonce: plugin.nonce,
					type: plugin.type,
					file: plugin.file,
					step,
				},
				success: plugin.analyze_response,
				error( error ) {
					plugin.reset();

					if ( error.message ) {
						plugin.$form.append(
							'<div class="notice error"><p>' + error.message + '</p></div>'
						);
					}
				},
			} );
		};

		/**
		 * Analyze the response from the server and determine the next step.
		 *
		 * @param {Object} res - The response from the server.
		 */
		this.analyze_response = function ( res ) {
			if ( res.step === 'done' ) {
				$( 'input[type="submit"]', plugin.$form ).remove();
				plugin.$form.find( '.eac-progress' ).remove();

				plugin.$form.append(
					'<div class="notice updated"><p>' + res.message + '</p></div></div>'
				);
				return;
			}

			// Update progress bar width
			plugin.$form.find( '.eac-progress div' ).animate( { width: res.percentage + '%' }, 50 );

			// Continue processing the next step
			plugin.process_step( parseInt( res.step, 10 ) );
		};

		/**
		 * Handle the error response from the server.
		 *
		 * @param {Object} error - The error response from the server.
		 * @return {void}
		 */
		this.handle_error = function ( error ) {};

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

		this.$form.on( 'submit', this.submit );
		return this;
	};

	/**
	 * jQuery Plugin Wrapper for EAC Importer.
	 *
	 * Initializes the EAC Importer for each matched element.
	 *
	 * @param {Object} options - Custom options for the importer.
	 * @return {jQuery} - The jQuery object for chaining.
	 */
	$.fn.eac_importer = function ( options ) {
		return this.each( function () {
			new $.eac_importer( this, options );
		} );
	};

	$( 'form.eac_importer' ).eac_importer();
} );
