( function ( $ ) {
	'use strict';

	/**
	 * jQuery plugin to initialize a form with various functionalities.
	 *
	 * @param {Object} [options]        - Configuration options for the form.
	 * @param {string} [options.events] - An object where keys are event types and values are handlers.
	 * @return {jQuery|*} - Returns the jQuery object for chaining, or the result of a method call.
	 */
	$.fn.eac_form = function ( options ) {
		options = options || {};

		if ( typeof options === 'object' || ! options ) {
			return this.each( function () {
				const instance = new $.eac_form( $( this ), options );
				$( this ).data( 'eac_form', instance ); // Store the instance in data
			} );
		}

		const args = Array.prototype.slice.call( arguments, 1 );
		return this.each( function () {
			const instance = $( this ).data( 'eac_form' );
			if ( instance && typeof instance.view[ options ] === 'function' ) {
				instance.view[ options ].apply( instance.view, args );
			}
		} );
	};

	/**
	 * Constructor for the eac_form.
	 *
	 * @param {jQuery} $el       - The jQuery element representing the form.
	 * @param {Object} [options] - Configuration options for the form.
	 * @class
	 */
	$.eac_form = function ( $el, options ) {
		const defaults = {
			events: {},
		};

		// Bail if no element is found.
		if ( ! $el.length ) {
			return;
		}

		this.$el = $el;
		$.extend( this, defaults, options || {} );
		this.$el.data( 'eac_form', this );

		// Store initial values
		this._values = this.getValues();

		// Bind events
		this.init();

		// trigger ready event.
		this.$el.trigger( 'ready' );
	};

	/**
	 * Initialize the form by binding events and any other necessary setup.
	 */
	$.eac_form.prototype.init = function () {
		const events = this.events || {};
		// Bind events
		for ( const key in events ) {
			let method = events[ key ];
			if ( typeof method !== 'function' ) {
				method = this[ method ];
			}
			if ( ! method ) {
				continue;
			}

			const match = key.match( /^(\S+)\s*(.*)$/ );
			this.on( match[ 1 ], match[ 2 ], method.bind( this ) );
		}
	};

	/**
	 * Retrieve the current values of all form fields.
	 *
	 * @return {Object} - An object containing name-value pairs of form inputs.
	 */
	$.eac_form.prototype.getValues = function () {
		const values = {};
		this.$( 'input, select, textarea' ).each( ( _, element ) => {
			const $element = $( element );
			const name = $element.attr( 'name' );
			const type = $element.attr( 'type' );

			if ( name === 'method' || undefined === name ) {
				return;
			} // Skip field

			if ( type === 'radio' ) {
				values[ name ] = $element.is( ':checked' ) ? $element.val() || 0 : values[ name ];
			} else if ( type === 'checkbox' ) {
				values[ name ] = values[ name ] || [];
				if ( $element.is( ':checked' ) ) {
					values[ name ].push( $element.val() );
				}
			} else {
				values[ name ] = $element.val() || '';
			}
		} );
		return values;
	};

	/**
	 * Retrieve the value of a single form element by its name.
	 *
	 * @param {string} name - The name attribute of the form element whose value is to be retrieved.
	 * @return {string|Array|null} - The value of the form element, or null if not found.
	 */
	$.eac_form.prototype.getValue = function ( name ) {
		return this.getValues()[ name ] || null;
	};

	/**
	 * Find and return a jQuery object representing a subset of form elements.
	 *
	 * @param {string} selector - A selector string to filter the form elements.
	 * @return {jQuery} - A jQuery object containing the matched elements.
	 */
	$.eac_form.prototype.$ = function ( selector ) {
		return this.$el.find( selector );
	};

	/**
	 * Bind an event to the form element.
	 *
	 * @param {string}   event    - The name of the event to bind (e.g., 'click').
	 * @param {string}   selector - A selector string to specify which elements should trigger the event.
	 * @param {Function} callback - The function to execute when the event is triggered.
	 */
	$.eac_form.prototype.on = function ( event, selector, callback ) {
		this.$el.on( event, selector, callback );
	};

	/**
	 * Reset all form fields to their initial values stored at initialization.
	 *
	 * @return {$.eac_form} - Returns the current instance for chaining.
	 */
	$.eac_form.prototype.reset = function () {
		for ( const name in this._values ) {
			const $element = this.$( `[name="${ name }"]` );
			const type = $element.attr( 'type' );

			if ( type === 'radio' || type === 'checkbox' ) {
				$element.prop( 'checked', false );
				if ( $element.val() === this._values[ name ] ) {
					$element.prop( 'checked', true );
				}
			} else {
				$element.val( this._values[ name ] );
			}
		}

		return this;
	};

	/**
	 * Check if the form has been modified since it was last reset.
	 *
	 * @return {boolean} - Returns true if the form is dirty; otherwise, false.
	 */
	$.eac_form.prototype.isDirty = function () {
		const currentValues = this.getValues();

		// Compare current values with initial values
		for ( const name in this._values ) {
			if ( this._values[ name ] !== currentValues[ name ] ) {
				return true; // Form is dirty
			}
		}
		return false; // Form is not dirty
	};

	/**
	 * Disable all form fields to block user interaction.
	 *
	 * @return {$.eac_form} - Returns the current instance for chaining.
	 */
	$.eac_form.prototype.block = function () {
		// Check if already blocked
		if ( this.$el.find( '.blockUI' ).length > 0 ) {
			return this;
		}

		// Ensure position is relative
		if ( this.$el.css( 'position' ) === 'static' ) {
			this.$el.css( 'position', 'relative' );
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
			.appendTo( this.$el );

		return this;
	};

	/**
	 * Enable all form fields to allow user interaction.
	 *
	 * @return {$.eac_form} - Returns the current instance for chaining.
	 */
	$.eac_form.prototype.unblock = function () {
		console.log($( '.blockUI', this.$el ).length)
		$( '.blockUI', this.$el ).remove(); // Remove overlay
		return this;
	};
} )( jQuery );
