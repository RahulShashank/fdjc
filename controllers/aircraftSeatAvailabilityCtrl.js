app.controller('aircraftSeatAvailabilityCtrl', function($scope, $http, $window) {
    init();
	var ctx = document.getElementById("myChart").getContext("2d");
	//console.log("Hello World!");
	getData();
	function init() {
	}
	
	function getData() {
		$http({
				url: "../common/getAircraftAvailability.php",
				method: "POST",
				data: {'aircraftDB': aircraftDB},
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
				}).success(function (data, status, headers, config) {
					//console.log(data);
					$('.progress').hide();
					if (data['success'] == true) {
						buildTable(data);
					}
			}).error(function (data, status, headers, config) {});
	}
	
	function buildTable(data) {
		var seatAvailabilities = data['seatAvailabilities'];
		var aircraftDB = data['aircraft'];
		var average = data['average'];
		//var elapsedTime = data['elapsedTime'];
		var startDates = data['startDates'];
		//console.log(elapsedTime);
		var data = {
			labels: startDates,
			datasets: [
				{
					label: "Seat Availability",
					fillColor: "rgba(151,187,205,0.2)",
					strokeColor: "rgba(151,187,205,1)",
					pointColor: "rgba(151,187,205,1)",
					pointStrokeColor: "#fff",
					pointHighlightFill: "#fff",
					pointHighlightStroke: "rgba(151,187,205,1)",
					data: seatAvailabilities
				}, 
				{
					label: "Average",
					fillColor: "rgba(169,151,205,0)",
					strokeColor: "rgba(160, 205, 151,1)",
					pointColor: "rgba(160, 205, 151,1)",
					pointStrokeColor: "#fff",
					pointHighlightFill: "#fff",
					pointHighlightStroke: "rgba(151,187,205,1)",
					data: average
				}
			]
		};
		
		var myNewChart = new Chart(ctx).Line(data, options);
		document.getElementById("legendDiv").innerHTML = myNewChart.generateLegend();
	}
	
	
	
	var options = {
		animation: false,
		responsive: true,
		scaleStartValue: 0,
		bezierCurve : true,
		bezierCurveTension : 0.1,
	};
	
	
	
	

});