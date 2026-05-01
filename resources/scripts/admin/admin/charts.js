jQuery( document ).ready( ( $ ) => {
	'use strict';
	const charts = $( '.eac-chart' );
	// we will check if .eac-chart exists in the page and Chart is available in the window object.
	// if class exists abut Chart is not available then we bail with a console warning.
	if ( charts.length && 'undefined' === typeof window.Chart ) {
		console.warn( 'Chart is not available. Make sure to include Chart.js library.' );
		return;
	}

	// we will loop through each .eac-chart element and create a chart for each.
	charts.each( function () {
		const $this = $( this );
		const datasets = $this.data( 'datasets' );
		const type = $this.data( 'type' ) || 'line';
		const currency = $this.data( 'currency' ) || '';

		// if datasets is not available then bail.
		if ( 'undefined' === typeof datasets ) {
			console.warn( 'Chart datasets is not available.' );
			return;
		}

		// create a new Chart instance.
		const chart = new window.Chart( $this[ 0 ].getContext( '2d' ), {
			type,
			data: datasets,
			options: {
				tooltips: {
					displayColors: true,
					YrPadding: 12,
					backgroundColor: '#000000',
					bodyFontColor: '#e5e5e5',
					bodySpacing: 4,
					intersect: 0,
					mode: 'nearest',
					position: 'nearest',
					titleFontColor: '#ffffff',
					callbacks: {
						label( tooltipItem, data ) {
							let value =
								data.datasets[ tooltipItem.datasetIndex ].data[ tooltipItem.index ];
							const datasetLabel =
								data.datasets[ tooltipItem.datasetIndex ].label || '';
							if (
								'undefined' === typeof value ||
								'undefined' === typeof datasetLabel
							) {
								value = 0;
							}

							return (
								datasetLabel +
								': ' +
								Number( value )
									.toFixed( 2 )
									.replace( /\d(?=(\d{3})+\.)/g, '$&,' ) +
								currency
							);
						},
					},
				},
				scales: {
					xAxes: [
						{
							stacked: false,
							gridLines: {
								display: true,
							},
						},
					],
					yAxes: [
						{
							stacked: false,
							ticks: {
								beginAtZero: true,
								callback( value, index, ticks ) {
									return (
										Number( value )
											.toFixed( 2 )
											.replace( /\d(?=(\d{3})+\.)/g, '$&,' ) + currency
									);
								},
							},
							type: 'linear',
							barPercentage: 0.4,
						},
					],
				},
				responsive: true,
				maintainAspectRatio: false,
				legend: { display: false },
			},
		} );
	} );
} );
