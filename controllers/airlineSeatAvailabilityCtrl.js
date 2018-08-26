app.controller('airlineSeatAvailabilityCtrl', function($scope, $http, $window) {
    init();
	var ctx = document.getElementById("myChart").getContext("2d");
	getDatabases();
	function init() {
	}
	
	function getDatabases() {
		/*
		$http.get("../common/getAirlineDatabases.php")
			.success(function (data) {
				buildTable(data);
				$('.progress').hide();
				//getSeatAvailabilities(data);
			});
		*/
		$http({
			url: "../common/getAirlineDatabases.php",
			method: "POST",
			data: {'airlineId': airlineId},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				console.log(data);
				$('.progress').hide();
				buildTable(data);				
			}).error(function (data, status, headers, config) {});
	}
	
	function postGetSeats(databaseName) {
		$http({
			url: "../common/getSeatAvailabilityData.php",
			method: "POST",
			data: {'databaseName': databaseName},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				buildTable(data);

			}).error(function (data, status, headers, config) {});
	}
	
	function getSeatAvailabilities(types) {
		var availabilityArray = [];
		var databases = [];
		console.log(types);
		for (var type in types) {
			databases = types[type];
			for (var i = 0; i < databases.length; i++) {
				console.log(databases[i]);
				postGetSeats(databases[i]);
			}
		}
	}
	
	function buildTable(data) {
		//var seatAvailabilities = data['seatAvailabilities'];
		var colors = [
			{
				fillColor: "rgba(151,187,205,0)",
				strokeColor: "rgba(151,187,205,1)"
			},
			{
				fillColor: "rgba(205,151,160,0)",
				strokeColor: "rgba(205,151,160,1)"
			},
			{
				fillColor: "rgba(151,205,169,0)",
				strokeColor: "rgba(151,205,169,1)"
			},
			{
				fillColor: "rgba(169,151,205,0)",
				strokeColor: "rgba(169,151,205,1)"
			},
			{
				fillColor: "rgba(220,220,220,0)",
				strokeColor: "rgba(220,220,220,1)"
			}
			
			
		]
		var elapsedTime = data['elapsedTime'];
		var startDates = data['dates'];
		console.log(elapsedTime);
		var datasets = [];
		var c = 0;
		for (var i = 0; i < data['keys'].length; i++) {
			var label = data['keys'][i];
			datasets.push({
				label: label,
				fillColor: colors[c]['fillColor'],
				strokeColor: colors[c]['strokeColor'],
				pointColor: colors[c]['strokeColor'],
				pointStrokeColor: "#fff",
				pointHighlightFill: "#fff",
				pointHighlightStroke: colors[c]['strokeColor'],
				data: data[label]
				});
			if(c < colors.length){
				c++;
			} else {
				c = 0;
			}
		}
		var data = {
			labels:startDates,
			datasets: datasets
		};
		var options = {
		responsive: true,
		scaleShowLabels: true,
		animation: false,
		bezierCurve : true,
		bezierCurveTension : 0.1,
		multiTooltipTemplate: "<%= datasetLabel %> - <%= value %>"
		};
		var myNewChart = new Chart(ctx).Line(data, options);
		document.getElementById("legendDiv").innerHTML = myNewChart.generateLegend();
	}
});