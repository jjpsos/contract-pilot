/**
 * Contract Pilot form helper: event binding, values, blocking overlay.
 */
(function ($) {
	'use strict';

	/**
	 * @param {jQuery} $element Form element.
	 * @param {Object} options  Handler map and options.
	 */
	$.contract_pilot_form = function ($element, options) {
		if (!$element.length) {
			return;
		}

		this.$el = $element;
		$.extend(this, { events: {} }, options || {});
		this.$el.data('contract_pilot_form', this);
		this._values = this.getValues();
		this.init();
		this.$el.trigger('ready');
	};

	$.contract_pilot_form.prototype.init = function () {
		var events = this.events || {};
		var eventName;
		var handler;
		var parsed;

		for (eventName in events) {
			if (!Object.prototype.hasOwnProperty.call(events, eventName)) {
				continue;
			}

			handler = events[eventName];

			if (typeof handler !== 'function') {
				handler = this[handler];
			}

			if (!handler) {
				continue;
			}

			parsed = eventName.match(/^(\S+)\s*(.*)$/);
			this.on(parsed[1], parsed[2], handler.bind(this));
		}
	};

	$.contract_pilot_form.prototype.getValues = function () {
		var values = {};

		this.$('input, select, textarea').each(function (index, field) {
			var $field = $(field);
			var name = $field.attr('name');
			var type = $field.attr('type');

			if ('method' === name || void 0 === name) {
				return;
			}

			if ('radio' === type) {
				values[name] = $field.is(':checked') ? $field.val() || 0 : values[name];
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
	};

	$.contract_pilot_form.prototype.getValue = function (name) {
		return this.getValues()[name] || null;
	};

	$.contract_pilot_form.prototype.$ = function (selector) {
		return this.$el.find(selector);
	};

	$.contract_pilot_form.prototype.on = function (event, selector, handler) {
		this.$el.on(event, selector, handler);
	};

	$.contract_pilot_form.prototype.reset = function () {
		var initialValues = this._values;
		var name;

		for (name in initialValues) {
			if (!Object.prototype.hasOwnProperty.call(initialValues, name)) {
				continue;
			}

			var $field = this.$('[name="' + name + '"]');
			var type = $field.attr('type');

			if ('radio' === type || 'checkbox' === type) {
				$field.prop('checked', false);

				if ($field.val() === initialValues[name]) {
					$field.prop('checked', true);
				}
			} else {
				$field.val(initialValues[name]);
			}
		}

		return this;
	};

	$.contract_pilot_form.prototype.isDirty = function () {
		var currentValues = this.getValues();
		var name;

		for (name in this._values) {
			if (
				Object.prototype.hasOwnProperty.call(this._values, name) &&
				this._values[name] !== currentValues[name]
			) {
				return true;
			}
		}

		return false;
	};

	$.contract_pilot_form.prototype.block = function () {
		if (this.$el.find('.blockUI').length > 0) {
			return this;
		}

		if ('static' === this.$el.css('position')) {
			this.$el.css('position', 'relative');
		}

		$('<div class="blockUI"></div>')
			.css({
				position: 'absolute',
				top: 0,
				left: 0,
				width: '100%',
				height: '100%',
				backgroundColor: 'rgb(255, 255, 255)',
				opacity: 0.1,
				cursor: 'wait',
				zIndex: 9999,
			})
			.appendTo(this.$el);

		return this;
	};

	$.contract_pilot_form.prototype.unblock = function () {
		$('.blockUI', this.$el).remove();
		return this;
	};

	/**
	 * Initialize a form or call a method on an existing instance.
	 *
	 * @param {Object|string} options Options object or method name.
	 * @returns {jQuery}
	 */
	$.fn.contract_pilot_form = function (options) {
		options = options || {};

		if ('object' === typeof options || !options) {
			return this.each(function () {
				new $.contract_pilot_form($(this), options);
			});
		}

		var args = Array.prototype.slice.call(arguments, 1);
		var returnValue = this;

		this.each(function () {
			var instance = $(this).data('contract_pilot_form');

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
