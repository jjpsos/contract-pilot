/**
 * Contract Pilot modal dialogs (Backbone view + focus trap).
 */
(function ($) {
	'use strict';

	var MODAL_CLOSE_ATTR = 'data-modal-close';

	var FOCUSABLE_SELECTORS = [
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
	 * @param {jQuery} $trigger Element that opened the modal.
	 * @param {Object} options   Template, data, events, callbacks.
	 */
	$.contract_pilot_modal = function ($trigger, options) {
		this.$el = $trigger;
		this.options = $.extend({}, $.contract_pilot_modal.defaults, options || {});

		if (this.options.template) {
			this.view = new $.contract_pilot_modal.View(this.options).render();
			this.$el.data('contract_pilot_modal', this);
		}

		return this;
	};

	$.contract_pilot_modal.defaults = {
		template: '',
		data: {},
		events: {},
		autoOpen: true,
		onOpen: function () {},
		onClose: function () {},
	};

	$.contract_pilot_modal.View = wp.Backbone.View.extend({
		tagName: 'div',
		className: 'contract-pilot-modal',
		attributes: {
			'aria-hidden': 'true',
			role: 'dialog',
		},

		preinitialize: function (options) {
			this.options = options;

			var templateId = this.options.template;
			var templateData = this.options.data;

			if (templateId && 'string' === typeof templateId) {
				this.template = wp.template(templateId);
			}

			if (templateData && 'object' === typeof templateData) {
				this.model = new Backbone.Model(templateData);
			}

			this.activeElement = null;
			wp.Backbone.View.prototype.preinitialize.apply(this, arguments);
		},

		prepare: function () {
			var modelData = this.model || {};

			return _.pick(
				modelData,
				_.filter(_.keys(modelData), function (key) {
					var value = modelData[key];
					return (
						_.isString(value) || _.isNumber(value) || _.isBoolean(value)
					);
				})
			);
		},

		render: function () {
			_.bindAll(this, 'render');
			wp.Backbone.View.prototype.render.apply(this, arguments);

			this.$el.attr('id', _.uniqueId('contract-pilot-modal-'));
			this.$el.wrapInner(
				'<div class="contract-pilot-modal__main" role="main"></div>'
			);
			this.$el.wrapInner(
				'<div class="contract-pilot-modal__content" tabindex="0"></div>'
			);
			this.$el.append(
				'<div class="contract-pilot-modal__overlay" tabindex="-1" ' +
					MODAL_CLOSE_ATTR +
					'></div>'
			);

			if (this.options.autoOpen) {
				this.open();
			}

			return this;
		},

		open: function () {
			var events = this.options.events || {};
			var eventKey;
			var handler;
			var parsed;

			this.activeElement = document.activeElement;
			$('body').css('overflow', 'hidden');
			this.$el.attr('aria-hidden', 'false');
			this.$el.addClass('is-open');
			$(document.body).append(this.$el);
			$(document.body).trigger('contract_pilot_update_ui', [this.$el]);

			this.delegateEvents({
				keydown: 'onKeydown',
				touchstart: 'onClick',
				click: 'onClick',
			});

			for (eventKey in events) {
				if (!Object.prototype.hasOwnProperty.call(events, eventKey)) {
					continue;
				}

				handler = events[eventKey];

				if ('function' !== typeof handler) {
					handler = this.options[handler];
				}

				parsed = eventKey.match(/^(\S+)\s*(.*)$/);
				this.$el.on(parsed[1], parsed[2], handler.bind(this));
			}

			this.setFocus();

			if ('function' === typeof this.options.onOpen) {
				this.options.onOpen.call(this);
			}
		},

		close: function (event) {
			if (event && event.preventDefault) {
				event.preventDefault();
			}

			this.$el.removeClass('is-open');
			$('body').css('overflow', '');
			this.$el.remove();
			this.$el.data('contract_pilot_modal', null);
			this.undelegateEvents();

			if (this.activeElement) {
				this.activeElement.focus();
			}

			if ('function' === typeof this.options.onClose) {
				this.options.onClose.call(this);
			}
		},

		onClick: function (event) {
			if (
				event.target.hasAttribute(MODAL_CLOSE_ATTR) ||
				(event.target.parentNode &&
					event.target.parentNode.hasAttribute(MODAL_CLOSE_ATTR))
			) {
				event.preventDefault();
				event.stopPropagation();
				this.close(event);
			}
		},

		onKeydown: function (event) {
			if (27 === event.keyCode) {
				this.close(event);
			}

			if (9 === event.keyCode) {
				this.retainFocus(event);
			}
		},

		getFocusableNodes: function () {
			return this.$el.find(FOCUSABLE_SELECTORS.join(',')).toArray();
		},

		setFocus: function () {
			var focusable = this.getFocusableNodes();

			if (0 === focusable.length) {
				return;
			}

			var withoutClose = focusable.filter(function (node) {
				return !node.hasAttribute(MODAL_CLOSE_ATTR);
			});

			if (withoutClose.length > 0) {
				if ($(withoutClose[0]).hasClass('select2-hidden-accessible')) {
					$(withoutClose[0]).select2('focus');
				} else {
					$(withoutClose[0]).focus();
				}
			} else {
				$(focusable[0]).focus();
			}
		},

		retainFocus: function (event) {
			var focusable = this.getFocusableNodes();

			if (0 === focusable.length) {
				return;
			}

			focusable = focusable.filter(function (node) {
				return null !== node.offsetParent;
			});

			if (!this.el.contains(document.activeElement)) {
				$(focusable[0]).focus();
				return;
			}

			var currentIndex = focusable.indexOf(document.activeElement);

			if (event.shiftKey && 0 === currentIndex) {
				$(focusable[focusable.length - 1]).focus();
				event.preventDefault();
			} else if (!event.shiftKey && currentIndex === focusable.length - 1) {
				$(focusable[0]).focus();
				event.preventDefault();
			}
		},

		getValues: function () {
			var values = {};

			this.$('input, select, textarea').each(function (index, field) {
				var $field = $(field);
				var name = $field.attr('name');
				var type = $field.attr('type');

				if ('method' === name) {
					return;
				}

				if ('radio' === type) {
					values[name] = $field.is(':checked')
						? $field.val() || 0
						: values[name];
				} else if ('checkbox' === type) {
					values[name] = values[name] || [];

					if ($field.is(':checked')) {
						values[name].push($field.val());
					}
				} else {
					values[name] = $field.val() || '';
				}
			});

			return values;
		},
	});

	/**
	 * Open a modal from a trigger element or invoke a view method.
	 *
	 * @param {Object|string} options Options object or method name.
	 * @returns {jQuery}
	 */
	$.fn.contract_pilot_modal = function (options) {
		options = options || {};

		if ('object' === typeof options || !options) {
			return this.each(function () {
				new $.contract_pilot_modal($(this), options);
			});
		}

		var args = Array.prototype.slice.call(arguments, 1);
		var returnValue = this;

		this.each(function () {
			var instance = $(this).data('contract_pilot_modal');

			if (
				instance &&
				instance.view &&
				'function' === typeof instance.view[options]
			) {
				returnValue = instance.view[options].apply(instance.view, args);
			}
		});

		return returnValue;
	};
})(jQuery);
