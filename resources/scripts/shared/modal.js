/* global Backbone, _ */

( function ( $ ) {
	'use strict';
	const CLOSE_TRIGGER = 'data-modal-close';
	const FOCUSABLE_ELEMENTS = [
		'a[href]',
		'area[href]',
		'input:not([disabled]):not([type="hidden"]):not([aria-hidden])',
		'.select2-hidden-accessible',
		'select:not([disabled]):not([aria-hidden])',
		'textarea:not([disabled]):not([aria-hidden])',
		'button:not([disabled]):not([aria-hidden])',
		'iframe',
		'object',
		'embed',
		'[contenteditable]',
	];

	/**
	 * Initialize the modal plugin.
	 *
	 * @param {Object} options - Configuration options for the modal.
	 * @return {jQuery} - The jQuery object for chaining.
	 */
	$.fn.eacmodal = function ( options ) {
		options = options || {};
		if ( typeof options === 'object' || ! options ) {
			return this.each( function () {
				const instance = new $.eacmodal( $( this ), options );
				$( this ).data( 'eacmodal', instance ); // Store the instance in data
			} );
		}

		let ret = this;
		const args = Array.prototype.slice.call( arguments, 1 );
		this.each( function () {
			const instance = $( this ).data( 'eacmodal' );
			if ( instance && typeof instance?.view[ options ] === 'function' ) {
				ret = instance.view[ options ].apply( instance.view, args );
			}
		} );
		return ret;
	};

	/**
	 * Modal constructor.
	 * @param {jQuery} $el     - The jQuery element representing the modal.
	 * @param {Object} options - Configuration options for the modal.
	 * @return {$.eacmodal} - The modal instance.
	 */
	$.eacmodal = function ( $el, options ) {
		this.$el = $el;
		this.options = $.extend( {}, $.eacmodal.defaults, options || {} );
		if ( this.options.template ) {
			this.view = new $.eacmodal.View( this.options ).render();
			this.$el.data( 'eacmodal', this );
		}
		return this;
	};

	/**
	 * Modal view constructor.
	 *
	 * @param {Object} options - Configuration options for the modal view.
	 * @return {$.eacmodal.View} - The modal view instance.
	 */
	$.eacmodal.View = wp.Backbone.View.extend( {
		tagName: 'div',
		className: 'eac-modal',
		attributes: {
			'aria-hidden': 'true',
			role: 'dialog',
		},

		/**
		 * Pre-initialization settings for the view.
		 * @param {Object} options - Configuration options for the modal view.
		 */
		preinitialize( options ) {
			this.options = options;
			const { template, data } = this.options;
			if ( template && typeof template === 'string' ) {
				this.template = wp.template( template );
			}
			if ( data && typeof data === 'object' ) {
				this.model = new Backbone.Model( data );
			}
			this.activeElement = null;
			wp.Backbone.View.prototype.preinitialize.apply( this, arguments );
		},

		/**
		 * Returns the options for this view.
		 * @return {Object} The options for this view.
		 */
		prepare() {
			const input = this.model || {};
			return _.pick(
				input,
				_.filter(
					_.keys( input ),
					( key ) =>
						_.isString( input[ key ] ) ||
						_.isNumber( input[ key ] ) ||
						_.isBoolean( input[ key ] )
				)
			);
		},

		/**
		 * Renders the modal content.
		 * @return {$.eacmodal.View} - The modal view instance.
		 */
		render() {
			_.bindAll( this, 'render' );
			wp.Backbone.View.prototype.render.apply( this, arguments );
			this.$el.attr( 'id', _.uniqueId( 'eac-modal-' ) );
			this.$el.wrapInner( '<div class="eac-modal__main" role="main"></div>' );
			this.$el.wrapInner( '<div class="eac-modal__content" tabindex="0"></div>' );
			this.$el.append(
				'<div class="eac-modal__overlay" tabindex="-1" data-modal-close></div>'
			);
			if ( this.options.autoOpen ) {
				this.open();
			}
			return this;
		},

		/**
		 * Opens the modal.
		 */
		open() {
			const events = this.options.events || {};
			this.activeElement = document.activeElement;
			$( 'body' ).css( 'overflow', 'hidden' );
			this.$el.attr( 'aria-hidden', 'false' );
			this.$el.addClass( 'is-open' );
			$( document.body ).append( this.$el );
			$( document.body ).trigger( 'eac_update_ui' );

			this.delegateEvents( {
				keydown: 'onKeydown',
				touchstart: 'onClick',
				click: 'onClick',
			} );
			// this.delegate('click', null, this.onClick.bind(this));

			// Bind events.
			for ( const key in events ) {
				let method = events[ key ];
				if ( typeof method !== 'function' ) {
					method = this.options[ method ];
				}
				const match = key.match( /^(\S+)\s*(.*)$/ );
				this.$el.on( match[ 1 ], match[ 2 ], method.bind( this ) );
			}

			this.setFocus();
			if ( typeof this.options.onOpen === 'function' ) {
				this.options.onOpen.call( this );
			}
		},

		/**
		 * Closes the modal.
		 * @param {Event} e - The event object.
		 */
		close( e = null ) {
			if ( e && e.preventDefault ) {
				e.preventDefault();
			}
			this.$el.removeClass( 'is-open' );
			$( 'body' ).css( 'overflow', '' );
			this.$el.remove();
			this.$el.data( 'eacmodal', null );
			this.undelegateEvents();
			this.activeElement.focus();
			if ( this.options.onClose ) {
				this.options.onClose.call( this );
			}
		},

		/**
		 * Handles click events on the modal.
		 * @param {Event} event - The click event object.
		 */
		onClick( event ) {
			if (
				event.target.hasAttribute( CLOSE_TRIGGER ) ||
				event.target.parentNode.hasAttribute( CLOSE_TRIGGER )
			) {
				event.preventDefault();
				event.stopPropagation();
				this.close( event );
			}
		},

		/**
		 * Handles keydown events for accessibility.
		 * @param {Event} event - The keydown event object.
		 */
		onKeydown( event ) {
			if ( event.keyCode === 27 ) {
				this.close( event );
			} // esc
			if ( event.keyCode === 9 ) {
				this.retainFocus( event );
			} // tab
		},

		/**
		 * Retrieves focusable elements within the modal.
		 * @return {Array} - An array of focusable elements.
		 */
		getFocusableNodes() {
			return this.$el.find( FOCUSABLE_ELEMENTS.join( ',' ) ).toArray();
		},

		/**
		 * Sets focus to the first focusable element in the modal.
		 */
		setFocus() {
			const focusableNodes = this.getFocusableNodes();
			if ( focusableNodes.length === 0 ) {
				return;
			}
			const nodesWhichAreNotCloseTargets = focusableNodes.filter( ( node ) => {
				return ! node.hasAttribute( CLOSE_TRIGGER );
			} );

			if ( nodesWhichAreNotCloseTargets.length > 0 ) {
				if (
					$( nodesWhichAreNotCloseTargets[ 0 ] ).hasClass( 'select2-hidden-accessible' )
				) {
					$( nodesWhichAreNotCloseTargets[ 0 ] ).select2( 'focus' );
				} else {
					$( nodesWhichAreNotCloseTargets[ 0 ] ).focus();
				}
			} else {
				$( focusableNodes[ 0 ] ).focus();
			}
		},

		/**
		 * Retains focus within the modal when navigating with the Tab key.
		 * @param {Event} event - The keydown event object.
		 */
		retainFocus( event ) {
			let focusableNodes = this.getFocusableNodes();
			if ( focusableNodes.length === 0 ) {
				return;
			}
			focusableNodes = focusableNodes.filter( ( node ) => {
				return node.offsetParent !== null;
			} );
			if ( ! this.el.contains( document.activeElement ) ) {
				$( focusableNodes[ 0 ] ).focus();
			} else {
				const focusedItemIndex = focusableNodes.indexOf( document.activeElement );
				if ( event.shiftKey && focusedItemIndex === 0 ) {
					$( focusableNodes[ focusableNodes.length - 1 ] ).focus();
					event.preventDefault();
				}
				if ( ! event.shiftKey && focusedItemIndex === focusableNodes.length - 1 ) {
					$( focusableNodes[ 0 ] ).focus();
					event.preventDefault();
				}
			}
		},

		/**
		 * Retrieve the current values of all form fields.
		 *
		 * @return {Object} - An object containing name-value pairs of form inputs.
		 */
		getValues() {
			const values = {};
			this.$( 'input, select, textarea' ).each( ( _, element ) => {
				const $element = $( element );
				const name = $element.attr( 'name' );
				const type = $element.attr( 'type' );

				if ( name === 'method' ) {
					return;
				} // Skip method field

				if ( type === 'radio' ) {
					values[ name ] = $element.is( ':checked' )
						? $element.val() || 0
						: values[ name ];
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
		},
	} );

	$.eacmodal.defaults = {
		template: '',
		data: {},
		events: {},
		autoOpen: true,
		onOpen() {},
		onClose() {},
	};
} )( jQuery, Backbone, _ );
