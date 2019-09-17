
$(function() {
	'use strict';
	
	/*----LineChart----*/
	var line = new Morris.Line({
		element: 'line-chart',
		resize: true,
		data: data,
		xkey: 'y',
		ykeys: ['item1'],
		labels: ['Item 1'],
		lineColors: ['#5d61bf'],
		hideHover: 'auto'
	});
	
	/*----DonutChart----*/
	var donut = new Morris.Donut({
		element: 'sales-chart',
		resize: true,
		colors: ['#f47b25', '#5d61bf', '#3ebaef'],
		data: data1,
		hideHover: 'auto'
	});
	
	/*----BarChart----*/
	var options = {
		chart: {
			height: 350,
			type: 'bar',
		},
		plotOptions: {
			bar: {
				horizontal: false,
				endingShape: 'rounded',
				columnWidth: '55%',
			},
		},
		dataLabels: {
			enabled: false
		},
		stroke: {
			show: true,
			width: 2,
			colors: ['transparent']
		},
		colors: ['#f47b25', '#5d61bf', '#3ebaef'],
		series: [{
			name: 'Net Profit',
			data: [44, 55, 57, 56, 61, 58, 63, 60, 66]
		}, {
			name: 'Revenue',
			data: [76, 85, 101, 98, 87, 105, 91, 114, 94]
		}, {
			name: 'Free Cash Flow',
			data: [35, 41, 36, 26, 45, 48, 52, 53, 41]
		}],
		xaxis: {
			categories: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		},
		yaxis: {
			title: {
				text: '$ (thousands)'
			}
		},
		fill: {
			opacity: 1
		},
		tooltip: {
			y: {
				formatter: function(val) {
					return "$ " + val + " thousands"
				}
			}
		}
	}
// 	var chart = new ApexCharts(document.querySelector("#barchart"), options);
// 	chart.render();
	
	/*----HorizontalBarChart----*/
	var options = {
		chart: {
			height: 350,
			type: 'bar',
		},
		plotOptions: {
			bar: {
				horizontal: true,
			}
		},
		dataLabels: {
			enabled: false
		},
		colors: '#5d61bf',
		series: [{
			data: [400, 430, 448, 470, 540, 580, 690, 1100, 1200, 1380]
		}],
		xaxis: {
			categories: ['United States', 'South Korea', 'Canada', 'United Kingdom', 'Netherlands', 'Italy', 'France', 'Japan', 'China', 'Germany'],
			axisBorder: {
				show: true,
				color: '#000',
				height: 1,
				width: '100%',
				offsetX: 0,
				offsetY: 0
			},
		},
		yaxis: {},
		tooltip: {}
	}
	var chart = new ApexCharts(document.querySelector("#barchart1"), options);
	chart.render();
});