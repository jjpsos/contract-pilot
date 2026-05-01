jQuery( document ).ready( ( $ ) => {
	'use strict';
	/**
	 * EAC Exporter Plugin
	 *
	 * This jQuery plugin handles the export functionality through AJAX request.
	 * It manages form submission, progress indication, and error handling.
	 *
	 * @param {HTMLFormElement} form    - The form element to be processed.
	 * @param {Object}          options - Custom options for the plugin.
	 */
	$.eac_exporter = function ( form, options ) {
		this.defaults = {};
		this.form = form;
		this.$form = $( form );
		this.$submit = $( 'input[type="submit"]', this.$form );
		this.options = $.extend( this.defaults, options );
		this.action = 'eac_ajax_export';
		this.nonce = this.$form.data( 'nonce' );
		this.type = this.$form.data( 'type' );
		const plugin = this;

		/**
		 * Submit form handler.
		 *
		 * Prevents default form submission, disables the submit button,
		 * resets the form state, and initiates the export process.
		 *
		 * @param {Event} e - The submit event.
		 * @return {boolean} - Returns false if the button is disabled, otherwise undefined.
		 */
		this.submit = function ( e ) {
			e.preventDefault();
			if ( plugin.$submit.hasClass( 'disabled' ) ) {
				return false;
			}

			// Disable submit button
			plugin.$submit.addClass( 'disabled' );

			// Reset previous states
			plugin.reset();

			// Add spinner to the submit button
			plugin.$submit.parent( 'p' ).append( '<span class="spinner is-active"></span>' );

			// Add progress bar to the form
			plugin.$form.append( '<div class="eac-progress"><div></div></div>' );

			// Start processing the first step
			plugin.process_step( 1 );
		};

		/**
		 * Process a specific step of the export operation.
		 *
		 * Sends an AJAX request to process the given step,
		 * updates progress, and handles success or error responses.
		 *
		 * @param {number} step - The current step number of the process.
		 */
		this.process_step = function ( step ) {
			window.wp.ajax.send( plugin.action, {
				data: {
					_wpnonce: plugin.nonce,
					type: plugin.type,
					step,
				},
				success( res ) {
					if ( res.step === 'done' ) {
						// Reset and handle completion
						plugin.reset();
						plugin.$form.append(
							'<div class="notice updated"><p>' + res.message + '</p></div></div>'
						);
						window.location = res.url;
						return false;
					}

					// Update progress bar width
					plugin.$form.find( '.eac-progress div' ).animate(
						{
							width: res.percentage + '%',
						},
						50,
						function () {}
					);

					// Continue processing the next step
					plugin.process_step( parseInt( res.step, 10 ) );
				},
				error( error ) {
					// Reset and handle error response
					plugin.reset();
					if ( error.message ) {
						plugin.$form.append(
							'<div class="notice error"><p>' + error.message + '</p></div>'
						);
					}
					console.warn( error );
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
			plugin.$submit.removeClass( 'disabled' );
			$( '.spinner', plugin.$form ).remove();
		};

		this.$form.on( 'submit', this.submit );
		return this;
	};

	$.fn.eac_exporter = function ( options ) {
		return this.each( function () {
			new $.eac_exporter( this, options );
		} );
	};

	$( 'form.eac_exporter' ).eac_exporter();
} );
