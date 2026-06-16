/**
 * Contract Pilot: admin line charts (Canvas 2D).
 *
 * Reads chart config from canvas data attributes (data-datasets, data-currency)
 * or from a config object passed to create().
 */
(function (global) {
	'use strict';

	var PADDING = { top: 16, right: 16, bottom: 40, left: 64 };
	var GRID_COLOR = '#e8e8e8';
	var AXIS_COLOR = '#757575';
	var FONT =
		'12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
	var DEFAULT_COLORS = [
		'#3644ff',
		'#f2385a',
		'#00d48f',
		'#4CAF50',
		'#F44336',
	];

	function formatAmount(value, currencySuffix) {
		var number = Number(value);

		if (isNaN(number)) {
			number = 0;
		}

		return (
			number.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,') +
			(currencySuffix || '')
		);
	}

	function colorForDataset(dataset, index) {
		return (
			dataset.borderColor ||
			dataset.backgroundColor ||
			DEFAULT_COLORS[index % DEFAULT_COLORS.length]
		);
	}

	function computeYRange(datasets) {
		var min = 0;
		var max = 0;
		var hasNegative = false;

		datasets.forEach(function (dataset) {
			(dataset.data || []).forEach(function (value) {
				var numeric = Number(value);

				if (isNaN(numeric)) {
					numeric = 0;
				}

				if (numeric < 0) {
					hasNegative = true;
				}

				if (numeric < min) {
					min = numeric;
				}

				if (numeric > max) {
					max = numeric;
				}
			});
		});

		if (!hasNegative) {
			min = 0;
		}

		if (max === min) {
			max = min + 1;
		}

		var range = max - min;
		var pad = range * 0.08 || 1;

		return {
			min: min - (hasNegative ? pad : 0),
			max: max + pad,
		};
	}

	function buildYTicks(min, max, tickCount) {
		var range = max - min;
		var roughStep = range / Math.max(tickCount - 1, 1);
		var magnitude = Math.pow(10, Math.floor(Math.log10(roughStep)));
		var normalized = roughStep / magnitude;
		var step;

		if (normalized <= 1) {
			step = magnitude;
		} else if (normalized <= 2) {
			step = 2 * magnitude;
		} else if (normalized <= 5) {
			step = 5 * magnitude;
		} else {
			step = 10 * magnitude;
		}

		var ticks = [];
		var start = Math.floor(min / step) * step;
		var end = Math.ceil(max / step) * step;
		var value = start;

		while (value <= end + step * 0.001) {
			if (value >= min - step * 0.001 && value <= max + step * 0.001) {
				ticks.push(value);
			}
			value += step;
		}

		if (!ticks.length) {
			ticks.push(min, max);
		}

		return ticks;
	}

	function LineChart(canvas, config) {
		this.canvas = canvas;
		this.ctx = canvas.getContext('2d');
		this.labels = config.labels || [];
		this.datasets = config.datasets || [];
		this.currencySuffix = config.currencySuffix || '';
		this.hoverIndex = -1;
		this.resizeObserver = null;
		this.tooltipEl = null;

		this.ensureTooltipElement();
		this.bindEvents();
		this.observeResize();
		this.resize();
	}

	LineChart.prototype.ensureTooltipElement = function () {
		var parent = this.canvas.parentElement || document.body;

		if (getComputedStyle(parent).position === 'static') {
			parent.style.position = 'relative';
		}

		this.tooltipEl = document.createElement('div');
		this.tooltipEl.className = 'contract-pilot-line-chart-tooltip';
		this.tooltipEl.setAttribute('role', 'tooltip');
		this.tooltipEl.hidden = true;
		parent.appendChild(this.tooltipEl);
	};

	LineChart.prototype.bindEvents = function () {
		this.onMouseMove = this.handleMouseMove.bind(this);
		this.onMouseLeave = this.handleMouseLeave.bind(this);

		this.canvas.addEventListener('mousemove', this.onMouseMove);
		this.canvas.addEventListener('mouseleave', this.onMouseLeave);
	};

	LineChart.prototype.observeResize = function () {
		var self = this;
		var target = this.canvas.parentElement || this.canvas;

		if (typeof global.ResizeObserver === 'function') {
			this.resizeObserver = new global.ResizeObserver(function () {
				self.resize();
			});
			this.resizeObserver.observe(target);
			return;
		}

		this.onWindowResize = function () {
			self.resize();
		};
		global.addEventListener('resize', this.onWindowResize);
	};

	LineChart.prototype.getPlotArea = function () {
		var width = this.canvas.clientWidth;
		var height = this.canvas.clientHeight;

		return {
			left: PADDING.left,
			top: PADDING.top,
			width: Math.max(width - PADDING.left - PADDING.right, 1),
			height: Math.max(height - PADDING.top - PADDING.bottom, 1),
			canvasWidth: width,
			canvasHeight: height,
		};
	};

	LineChart.prototype.resize = function () {
		var rect = this.canvas.getBoundingClientRect();
		var dpr = global.devicePixelRatio || 1;
		var width = Math.max(rect.width, 1);
		var height = Math.max(rect.height, 1);

		this.canvas.width = Math.round(width * dpr);
		this.canvas.height = Math.round(height * dpr);
		this.canvas.style.width = width + 'px';
		this.canvas.style.height = height + 'px';

		this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		this.draw();
	};

	LineChart.prototype.valueToY = function (value, plot, yRange) {
		var ratio = (value - yRange.min) / (yRange.max - yRange.min);

		return plot.top + plot.height - ratio * plot.height;
	};

	LineChart.prototype.indexToX = function (index, plot, labelCount) {
		if (labelCount <= 1) {
			return plot.left + plot.width / 2;
		}

		return plot.left + (index / (labelCount - 1)) * plot.width;
	};

	LineChart.prototype.getIndexFromX = function (x, plot, labelCount) {
		if (labelCount <= 1) {
			return 0;
		}

		var ratio = (x - plot.left) / plot.width;

		if (ratio <= 0) {
			return 0;
		}

		if (ratio >= 1) {
			return labelCount - 1;
		}

		return Math.round(ratio * (labelCount - 1));
	};

	LineChart.prototype.draw = function () {
		var ctx = this.ctx;
		var plot = this.getPlotArea();
		var yRange = computeYRange(this.datasets);
		var yTicks = buildYTicks(yRange.min, yRange.max, 6);
		var labelCount = Math.max(this.labels.length, 1);

		ctx.clearRect(0, 0, plot.canvasWidth, plot.canvasHeight);
		ctx.font = FONT;
		ctx.textBaseline = 'middle';

		ctx.strokeStyle = GRID_COLOR;
		ctx.lineWidth = 1;

		yTicks.forEach(function (tick) {
			var y = this.valueToY(tick, plot, yRange);

			ctx.beginPath();
			ctx.moveTo(plot.left, y);
			ctx.lineTo(plot.left + plot.width, y);
			ctx.stroke();

			ctx.fillStyle = AXIS_COLOR;
			ctx.textAlign = 'right';
			ctx.fillText(
				formatAmount(tick, this.currencySuffix),
				plot.left - 8,
				y
			);
		}, this);

		for (var gridIndex = 0; gridIndex < labelCount; gridIndex++) {
			var gridX = this.indexToX(gridIndex, plot, labelCount);

			ctx.beginPath();
			ctx.moveTo(gridX, plot.top);
			ctx.lineTo(gridX, plot.top + plot.height);
			ctx.stroke();
		}

		ctx.strokeStyle = AXIS_COLOR;
		ctx.beginPath();
		ctx.moveTo(plot.left, plot.top);
		ctx.lineTo(plot.left, plot.top + plot.height);
		ctx.lineTo(plot.left + plot.width, plot.top + plot.height);
		ctx.stroke();

		ctx.fillStyle = AXIS_COLOR;
		ctx.textAlign = 'center';
		ctx.textBaseline = 'top';

		this.labels.forEach(function (label, index) {
			var x = this.indexToX(index, plot, labelCount);

			ctx.fillText(String(label), x, plot.top + plot.height + 8);
		}, this);

		this.datasets.forEach(function (dataset, datasetIndex) {
			var color = colorForDataset(dataset, datasetIndex);
			var points = (dataset.data || []).map(function (value, index) {
				return {
					x: this.indexToX(index, plot, labelCount),
					y: this.valueToY(Number(value) || 0, plot, yRange),
				};
			}, this);

			if (!points.length) {
				return;
			}

			ctx.strokeStyle = color;
			ctx.lineWidth = 2;
			ctx.lineJoin = 'round';
			ctx.lineCap = 'round';
			ctx.beginPath();
			ctx.moveTo(points[0].x, points[0].y);

			for (var pointIndex = 1; pointIndex < points.length; pointIndex++) {
				ctx.lineTo(points[pointIndex].x, points[pointIndex].y);
			}

			ctx.stroke();

			if (this.hoverIndex >= 0 && points[this.hoverIndex]) {
				var hoverPoint = points[this.hoverIndex];

				ctx.fillStyle = color;
				ctx.beginPath();
				ctx.arc(hoverPoint.x, hoverPoint.y, 4, 0, Math.PI * 2);
				ctx.fill();
				ctx.strokeStyle = '#ffffff';
				ctx.lineWidth = 2;
				ctx.stroke();
			}
		}, this);
	};

	LineChart.prototype.handleMouseMove = function (event) {
		var rect = this.canvas.getBoundingClientRect();
		var x = event.clientX - rect.left;
		var plot = this.getPlotArea();
		var labelCount = Math.max(this.labels.length, 1);
		var index = this.getIndexFromX(x, plot, labelCount);

		if (
			x < plot.left ||
			x > plot.left + plot.width ||
			labelCount === 0
		) {
			this.hideTooltip();
			return;
		}

		if (index !== this.hoverIndex) {
			this.hoverIndex = index;
			this.draw();
		}

		this.showTooltip(index, event.clientX - rect.left, event.clientY - rect.top);
	};

	LineChart.prototype.handleMouseLeave = function () {
		this.hoverIndex = -1;
		this.hideTooltip();
		this.draw();
	};

	LineChart.prototype.showTooltip = function (index, offsetX, offsetY) {
		var lines = [];
		var title = this.labels[index];

		if (title) {
			lines.push('<div class="contract-pilot-line-chart-tooltip__title">' + escapeHtml(String(title)) + '</div>');
		}

		this.datasets.forEach(function (dataset, datasetIndex) {
			var value = dataset.data ? dataset.data[index] : undefined;
			var label = dataset.label || '';
			var color = colorForDataset(dataset, datasetIndex);

			if (value === undefined && !label) {
				value = 0;
			}

			lines.push(
				'<div class="contract-pilot-line-chart-tooltip__row">' +
					'<span class="contract-pilot-line-chart-tooltip__swatch" style="background-color:' +
					escapeHtml(color) +
					'"></span>' +
					'<span>' +
					escapeHtml(label) +
					': ' +
					escapeHtml(formatAmount(value, this.currencySuffix)) +
					'</span>' +
					'</div>'
			);
		}, this);

		this.tooltipEl.innerHTML = lines.join('');
		this.tooltipEl.hidden = false;

		var parent = this.tooltipEl.parentElement;
		var parentWidth = parent.clientWidth;
		var parentHeight = parent.clientHeight;
		var tooltipWidth = this.tooltipEl.offsetWidth;
		var tooltipHeight = this.tooltipEl.offsetHeight;
		var left = offsetX + 12;
		var top = offsetY - tooltipHeight - 12;

		if (left + tooltipWidth > parentWidth - 8) {
			left = offsetX - tooltipWidth - 12;
		}

		if (top < 8) {
			top = offsetY + 12;
		}

		if (top + tooltipHeight > parentHeight - 8) {
			top = Math.max(8, parentHeight - tooltipHeight - 8);
		}

		this.tooltipEl.style.left = Math.max(8, left) + 'px';
		this.tooltipEl.style.top = Math.max(8, top) + 'px';
	};

	LineChart.prototype.hideTooltip = function () {
		if (this.tooltipEl) {
			this.tooltipEl.hidden = true;
		}
	};

	LineChart.prototype.destroy = function () {
		this.canvas.removeEventListener('mousemove', this.onMouseMove);
		this.canvas.removeEventListener('mouseleave', this.onMouseLeave);

		if (this.resizeObserver) {
			this.resizeObserver.disconnect();
		}

		if (this.onWindowResize) {
			global.removeEventListener('resize', this.onWindowResize);
		}

		if (this.tooltipEl && this.tooltipEl.parentElement) {
			this.tooltipEl.parentElement.removeChild(this.tooltipEl);
		}
	};

	function escapeHtml(text) {
		return String(text)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function normalizeConfig(rawConfig, currencySuffix) {
		if (!rawConfig || typeof rawConfig !== 'object') {
			return {
				labels: [],
				datasets: [],
				currencySuffix: currencySuffix || '',
			};
		}

		return {
			labels: rawConfig.labels || [],
			datasets: rawConfig.datasets || [],
			currencySuffix: currencySuffix || '',
		};
	}

	function create(canvas, config) {
		if (!canvas || !canvas.getContext) {
			return null;
		}

		return new LineChart(canvas, normalizeConfig(config, config.currencySuffix));
	}

	function initCanvas(canvas) {
		var datasetsAttr = canvas.getAttribute('data-datasets');
		var currencySuffix = canvas.getAttribute('data-currency') || '';
		var config = null;

		if (datasetsAttr) {
			try {
				config = JSON.parse(datasetsAttr);
			} catch (error) {
				console.warn('Contract Pilot line chart: invalid data-datasets JSON.', error);
				return null;
			}
		}

		if (!config) {
			console.warn('Contract Pilot line chart: missing chart data.');
			return null;
		}

		return create(canvas, normalizeConfig(config, currencySuffix));
	}

	function initAll(selector) {
		var elements = document.querySelectorAll(selector || 'canvas.contract-pilot-chart');
		var charts = [];

		elements.forEach(function (canvas) {
			var chart = initCanvas(canvas);

			if (chart) {
				charts.push(chart);
			}
		});

		return charts;
	}

	global.ContractPilotLineChart = {
		create: create,
		initAll: initAll,
	};
})(window);
