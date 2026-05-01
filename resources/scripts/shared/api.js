/* global Backbone, eac_api_vars, eac_api, _ */

( function ( window, undefined ) {
	'use strict';
	window.eac_api = window.eac_api || {};

	/**
	 * Base API model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	const Model = Backbone.Model.extend( {
		/**
		 * API root.
		 */
		apiRoot: eac_api_vars.root || '/wp-json',

		/**
		 * API namespace.
		 */
		namespace: 'eac/v1',

		/**
		 * API endpoint.
		 */
		endpoint: '',

		/**
		 * Request nonce.
		 */
		nonce: eac_api_vars.nonce || '',

		/**
		 * Get the URL for the model.
		 *
		 * @return {string}.
		 */
		url() {
			let url =
				this.apiRoot.replace( /\/+$/, '' ) +
				'/' +
				this.namespace.replace( /\/+$/, '' ) +
				'/' +
				this.endpoint.replace( /\/+$/, '' );
			if ( ! _.isUndefined( this.get( 'id' ) ) ) {
				url += '/' + this.get( 'id' );
			}

			// remove the trailing slash.
			return url.replace( /\/+$/, '' );
		},

		/**
		 * Set nonce header before every Backbone sync.
		 *
		 * @param {string}         method
		 * @param {Backbone.Model} model
		 * @param {Object}         options
		 * @return {*}
		 */
		sync( method, model, options ) {
			let beforeSend;
			options = options || {};
			// if cached is not set then set it to true.
			if ( _.isUndefined( options.cache ) ) {
				options.cache = true;
			}

			// Include the nonce with requests.
			if ( ! _.isEmpty( model.nonce ) ) {
				beforeSend = options.beforeSend;
				options.beforeSend = function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', model.nonce );

					if ( beforeSend ) {
						return beforeSend.apply( this, arguments );
					}
				};

				// Update the nonce when a new nonce is returned with the response.
				options.complete = function ( xhr ) {
					const returnedNonce = xhr.getResponseHeader( 'X-WP-Nonce' );

					if (
						returnedNonce &&
						_.isEmpty( model.nonce ) &&
						model.nonce !== returnedNonce
					) {
						model.set( 'nonce', returnedNonce );
					}
				};
			}

			return Backbone.sync( method, model, options );
		},
	} );
	eac_api.Model = Model;

	/**
	 * Account model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Account = Model.extend( {
		endpoint: 'accounts',

		defaults: {
			id: '',
			type: 'bank',
			name: '',
			number: '',
			opening_balance: 0,
			bank_name: '',
			bank_phone: '',
			bank_address: '',
			currency_code: 'USD',
			creator_id: '',
			thumbnail_id: '',
			status: 'active',
			updated_at: '',
			created_at: '',
		},
	} );

	/**
	 * Contact model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Contact = Model.extend( {
		endpoint: 'contacts',

		defaults: {
			id: null,
			type: 'customer',
			name: '',
			company: '',
			email: '',
			phone: '',
			website: '',
			address: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
			vat_number: '',
			vat_exempt: false,
			currency_code: '',
			thumbnail_id: null,
			user_id: null,
			status: 'active',
			created_via: 'api',
			creator_id: null,
			updated_at: '',
			created_at: '',
		},
	} );

	/**
	 * Customer model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Customer = eac_api.Contact.extend( {
		endpoint: 'customers',

		defaults: Object.assign( {}, eac_api.Contact.prototype.defaults, {
			type: 'customer',
		} ),
	} );

	/**
	 * Category model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Category = Model.extend( {
		endpoint: 'categories',

		defaults: {
			id: null,
			type: '',
			name: '',
			description: '',
			status: '',
			updated_at: '',
			created_at: '',
		},
	} );

	/**
	 * Document model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Document = Model.extend( {
		defaults: {
			id: '',
			type: '',
			status: 'draft',
			number: '',
			reference: '',
			subtotal: 0,
			discount: 0,
			tax: 0,
			total: 0,
			discount_value: 0,
			discount_type: 'fixed',

			contact_id: '',
			contact_name: '',
			contact_company: '',
			contact_email: '',
			contact_phone: '',
			contact_address: '',
			contact_city: '',
			contact_state: '',
			contact_zip: '',
			contact_country: '',
			contact_tax_number: '',
			note: '',
			terms: '',
			issue_date: '',
			due_date: '',
			sent_date: '',
			payment_date: '',
			currency_code: '',
			exchange_rate: '',
			created_via: '',
			creator_id: '',
			uuid: '',
			updated_at: '',
			created_at: '',
			items: eac_api.DocumentItems,

			// Flags
			is_fetching: false,
		},

		getNextNumber( options = {} ) {
			return this.fetch( {
				url: `${ this.apiRoot }${ this.namespace }/utilities/next-number?type=${ this.get(
					'type'
				) }`,
				type: 'GET',
				...options,
			} );
		},

		/**
		 * Update Amount
		 *
		 * @return {void}
		 */
		updateAmounts() {
			console.log( '=== Bill.updateAmounts() ===' );
			let items_total = 0,
				subtotal = 0,
				discount = 0,
				tax = 0,
				total = 0,
				discount_type = this.get( 'discount_type' ) || 'fixed',
				discount_value = parseFloat( this.get( 'discount_value' ), 10 ) || 0;

			// Prepare items for calculation
			_.each( this.get( 'items' ).models, ( item ) => {
				const _price = parseFloat( item.get( 'price' ), 10 ) || 0;
				const _quantity = parseFloat( item.get( 'quantity' ), 10 ) || 0;
				const _subtotal = _price * _quantity;
				const _type = item.get( 'type' ) || 'standard';
				// if standard, add to items_total.
				if ( _type === 'standard' ) {
					items_total += _subtotal;
				}

				item.set( {
					price: _price,
					quantity: _quantity,
					subtotal: _subtotal,
					type: _type,
					discount: 0,
				} );
			} );

			discount =
				discount_type === 'percent'
					? ( items_total * discount_value ) / 100
					: discount_value;
			discount = discount > items_total ? items_total : discount;

			_.each( this.get( 'items' ).models, ( item ) => {
				const _type = item.get( 'type' ) || 'standard',
					_subtotal = item.get( 'subtotal' ) || 0,
					_discount = _type === 'standard' ? ( discount / items_total ) * _subtotal : 0,
					_disc_subtotal = Math.max( _subtotal - _discount, 0 );

				// Simple tax calculation.
				_.each( item.get( 'taxes' ).models, ( tax ) => {
					const _tax_rate = parseFloat( tax.get( 'rate' ), 10 ) || 0;
					const _tax_amount = ! tax.get( 'compound' )
						? _disc_subtotal * ( _tax_rate / 100 )
						: 0;
					tax.set( {
						rate: _tax_rate,
						amount: _tax_amount,
					} );
				} );

				const _prev_tax = _.reduce(
					item.get( 'taxes' ).models,
					( sum, tax ) => {
						return sum + tax.get( 'amount' );
					},
					0
				);

				_.each( item.get( 'taxes' ).where( { compound: true } ), ( tax ) => {
					const _tax_rate = parseFloat( tax.get( 'rate' ), 10 ) || 0;
					const _tax_amount = ( _disc_subtotal + _prev_tax ) * ( _tax_rate / 100 );
					tax.set( {
						rate: _tax_rate,
						amount: _tax_amount,
					} );
				} );

				const _tax = _.reduce(
					item.get( 'taxes' ).models,
					( sum, tax ) => {
						return sum + tax.get( 'amount' );
					},
					0
				);

				item.set( {
					discount: _discount,
					subtotal: _disc_subtotal,
					tax: _tax,
					total: _disc_subtotal + _tax,
				} );

				subtotal += _disc_subtotal;
				tax += _tax;
			} );

			total = subtotal + tax;
			this.set( {
				subtotal,
				discount,
				tax,
				total,
			} );
		},
	} );

	/**
	 * DocumentItem model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.DocumentItem = Model.extend( {
		endpoint: 'line-items',

		defaults: {
			id: null,
			type: 'standard',
			name: '',
			price: 0,
			quantity: 1,
			subtotal: 0,
			discount: 0,
			tax: 0,
			total: 0,
			desc: '',
			unit: '',
			item_id: null,
			updated_at: '',
			created_at: '',

			// Relationships
			taxes: [],
		},
	} );

	/**
	 * DocumentTax model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.DocumentTax = Model.extend( {
		endpoint: 'line-taxes',

		defaults: {
			id: null,
			name: '',
			rate: 0,
			compound: false,
			amount: 0,
			item_id: 0,
			tax_id: 0,
			document_id: 0,
			updated_at: '',
			created_at: '',
		},
	} );

	/**
	 * Invoice model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Invoice = eac_api.Document.extend( {
		defaults: Object.assign( {}, eac_api.Document.prototype.defaults, {
			type: 'invoice',
		} ),
	} );

	/**
	 * Bill model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Bill = eac_api.Document.extend( {
		defaults: Object.assign( {}, eac_api.Document.prototype.defaults, {
			type: 'bill',
		} ),
	} );

	/**
	 * Transaction model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Transaction = Model.extend( {
		endpoint: 'transactions',

		defaults: {
			id: '',
			type: 'expense',
			status: 'draft',
			number: '',
			contact_id: '',
			account_id: '',
			amount: 0,
			currency_code: '',
			exchange_rate: '',
			reference: '',
			note: '',
			date: '',
			created_via: '',
			creator_id: '',
			uuid: '',
			updated_at: '',
			created_at: '',
		},
	} );

	/**
	 * Payment model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Payment = eac_api.Transaction.extend( {
		endpoint: 'payments',

		defaults: Object.assign( {}, eac_api.Transaction.prototype.defaults, {
			type: 'payment',
		} ),
	} );

	/**
	 * Expense model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Expense = eac_api.Transaction.extend( {
		defaults: Object.assign( {}, eac_api.Transaction.prototype.defaults, {
			type: 'expense',
		} ),
	} );

	/**
	 * Item model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Item = Model.extend( {
		endpoint: 'items',

		defaults: {
			id: '',
			name: '',
			description: '',
			price: 0,
			unit: '',
			updated_at: '',
			created_at: '',
		},
	} );

	/**
	 * Tax model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Tax = Model.extend( {
		endpoint: 'taxes',

		defaults: {
			id: '',
			name: '',
			rate: 0,
			compound: false,
			updated_at: '',
			created_at: '',
		},
	} );

	/**
	 * Note model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Note = Model.extend( {
		endpoint: 'notes',

		defaults: {
			id: '',
			type: '',
			object_id: '',
			content: '',
			updated_at: '',
			created_at: '',
		},
	} );

	/**
	 * Vendor model
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Vendor = eac_api.Contact.extend( {
		endpoint: 'vendors',

		defaults: Object.assign( {}, eac_api.Contact.prototype.defaults, {
			type: 'vendor',
		} ),
	} );

	/**
	 * Base API collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	const Collection = Backbone.Collection.extend( {
		/**
		 * API root.
		 */
		apiRoot: eac_api_vars.root || '/wp-json',

		/**
		 * API namespace.
		 */
		namespace: eac_api_vars.namespace || 'eac/v1',

		/**
		 * API endpoint.
		 */
		endpoint: '',

		/**
		 * Request nonce.
		 */
		nonce: eac_api_vars.nonce || '',

		/**
		 * Setup default state.
		 * @param models
		 * @param options
		 */
		preinitialize( models, options ) {
			this.options = options;
		},

		/**
		 * Get the URL for the model.
		 *
		 * @return {string}.
		 */
		url() {
			return (
				this.apiRoot.replace( /\/+$/, '' ) +
				'/' +
				this.namespace.replace( /\/+$/, '' ) +
				'/' +
				this.endpoint.replace( /\/+$/, '' )
			);
		},

		/**
		 * Set nonce header before every Backbone sync.
		 *
		 * @param {string}          method.
		 * @param {Backbone.Model}  model.
		 * @param {{beforeSend}, *} options.
		 * @param                   method
		 * @param                   model
		 * @param                   options
		 * @return {*}.
		 */
		sync( method, model, options ) {
			let beforeSend;
			options = options || {};
			// if cached is not set then set it to true.
			if ( _.isUndefined( options.cache ) ) {
				options.cache = true;
			}

			// Include the nonce with requests.
			if ( ! _.isEmpty( model.nonce ) ) {
				beforeSend = options.beforeSend;
				options.beforeSend = function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', model.nonce );

					if ( beforeSend ) {
						return beforeSend.apply( this, arguments );
					}
				};

				// Update the nonce when a new nonce is returned with the response.
				options.complete = function ( xhr ) {
					const returnedNonce = xhr.getResponseHeader( 'X-WP-Nonce' );
					if ( ! _.isEmpty( returnedNonce ) ) {
						model.nonce = returnedNonce;
					}
				};
			}

			return Backbone.sync( method, model, options );
		},
	} );
	eac_api.Collection = Collection;

	/**
	 * Account collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Accounts = Collection.extend( {
		endpoint: 'accounts',
		model: eac_api.Account,
	} );

	/**
	 * Contact collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Contacts = Collection.extend( {
		endpoint: 'contacts',
		model: eac_api.Contact,
	} );

	/**
	 * Customer collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Customers = Collection.extend( {
		endpoint: 'customers',
		model: eac_api.Customer,
	} );

	/**
	 * Category collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Categories = Collection.extend( {
		endpoint: 'categories',
		model: eac_api.Category,
	} );

	/**
	 * Document collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Documents = Collection.extend( {
		endpoint: 'documents',
		model: eac_api.Document,
	} );

	/**
	 * DocumentItem collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.DocumentItems = Collection.extend( {
		endpoint: 'document/items',
		model: eac_api.DocumentItem,
	} );

	/**
	 * DocumentTax collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.DocumentTaxes = Collection.extend( {
		endpoint: 'line-taxes',
		model: eac_api.DocumentTax,
	} );

	/**
	 * DocumentAddress collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.DocumentAddresses = Collection.extend( {
		endpoint: 'addresses',
		model: eac_api.DocumentAddress,
	} );

	/**
	 * Invoice collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Invoices = Collection.extend( {
		endpoint: 'invoices',
		model: eac_api.Invoice,
	} );

	/**
	 * Bill collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Bills = Collection.extend( {
		endpoint: 'bills',
		model: eac_api.Bill,
	} );

	/**
	 * Transaction collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Transactions = Collection.extend( {
		endpoint: 'transactions',
		model: eac_api.Transaction,
	} );

	/**
	 * Payment collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Payments = Collection.extend( {
		endpoint: 'payments',
		model: eac_api.Payment,
	} );

	/**
	 * Expense collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Expenses = Collection.extend( {
		endpoint: 'expenses',
		model: eac_api.Expense,
	} );

	/**
	 * Item collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Items = Collection.extend( {
		endpoint: 'items',
		model: eac_api.Item,
	} );

	/**
	 * Tax collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Taxes = Collection.extend( {
		endpoint: 'taxes',
		model: eac_api.Tax,
	} );

	/**
	 * Note collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Notes = Collection.extend( {
		endpoint: 'notes',
		model: eac_api.Note,
	} );

	/**
	 * Vendor collection
	 *
	 * @type {Object}
	 * @since 1.0.0
	 */
	eac_api.Vendors = Collection.extend( {
		endpoint: 'vendors',
		model: eac_api.Vendor,
	} );
} )( window );
