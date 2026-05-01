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
		 * Make request to import data.
		 *
		 * @param {Object}   data     - The data to be sent to the server.
		 * @param {Function} callback - The callback function to be executed after the request.
		 * @return {void}
		 */
		this.import = function ( data, callback ) {
			$.ajax( {
				url: ajaxurl,
				type: 'POST',
				data,
				success( response ) {
					if ( response.success ) {
						callback( response.data );
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
		 * Handle the file upload.
		 *
		 * @param {Event} event - The event object.
		 * @return {void}
		 */
		this.upload = function ( event ) {
			event.preventDefault();
			const formData = new FormData( plugin.$form[ 0 ] );
			plugin.$submit.prop( 'disabled', true );
			plugin.import(
				{
					action: plugin.action,
					nonce: plugin.nonce,
					type: plugin.type,
					data: formData,
				},
				plugin.process
			);
		};

		/**
		 * Process the import response.
		 *
		 * @param {Object} response - The response data from the server.
		 * @return {void}
		 */
		this.process = function ( response ) {

		}
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
