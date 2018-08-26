function totalText(data) {
      return '<font style="font-weight: bold; color:#D73A31;">Total<font>';
}

function manualProcessedFormatter(data, field) { 
    var total = 0;
    $.each(data, function (i, row) {
        total += parseInt(row.manual_processed_count);
    });
    return '<font style="font-weight: bold; color:#D73A31;">'+total+'<font>';
}

function manualRejectedFormatter(data, field) { 
    var total = 0;
    $.each(data, function (i, row) {
        total += parseInt(row.manual_rejected_count);
    });
    return '<font style="font-weight: bold; color:#D73A31;">'+total+'<font>';
}

function automaticProcessedFormatter(data, field) { 
    var total = 0;
    $.each(data, function (i, row) {
        total += parseInt(row.automatic_processed_count);
    });
    return '<font style="font-weight: bold; color:#D73A31;">'+total+'<font>';
}

function automaticRejectedFormatter(data, field) { 
    var total = 0;
    $.each(data, function (i, row) {
        total += parseInt(row.automatic_rejected_count);
    });
    return '<font style="font-weight: bold; color:#D73A31;">'+total+'<font>';
}

function totalFormatter(data, field) { 
    var total = 0;
    $.each(data, function (i, row) {
        total += parseInt(row.total_count);
    });
    return '<font style="font-weight: bold; color:#D73A31;">'+total+'<font>';
}

var app = angular.module('myApp', []);

app.controller('biteUploadMatrixController', function($scope, $http, $log, $window) {

	var chart;
	console.log("inside biteUploadMatrixController");
	$('#loadingDiv').show();
//	$('#biteUploadMatrixTableDiv').hide();
	
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	var airlineIds = $window.airlineIds;
// console.log("AirlineIds: " + airlineIds);

	$('#startDate').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: startDate
	});
	
	$('#endDate').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: endDate
	});

//	createBiteUploadMatrixTable();
	
	function createBiteUploadMatrixTable() {
		$('#biteUploadMartixTable').bootstrapTable({
		    formatNoMatches: function () {
		        return 'No data available for the selected duration or selected filters';
		    }
		});
	}
	
	$scope.filter = function() {
		$('#loadingDiv').show();
		$('#chartDiv').hide();
		$('#noDataDiv').hide();
		$('#dataDiv').hide();
		
		startDate = $('#startDate').val();
		endDate = $('#endDate').val();
		
		getBiteUploadMatrixData(startDate, endDate);
	};
	
	$scope.resetSearchPanel = function() {
		var startDate = $window.startDateTime;
		var endDate = $window.endDateTime;		
		$('#startDate').val(startDate);
		$('#endDate').val(endDate);
//		$scope.filter();
	};
	
	function getBiteUploadMatrixData(startDate, endDate) {
		var filteredArray = [];
		
//		$('#loadingDiv').show();
//		$('#biteUploadMatrixTableDiv').hide();
		
        var data = $.param({
	    	action: 'GET_HISTORY',
            startDate: startDate, 
            endDate: endDate,
            airlineIds: airlineIds
        });
	    
        var config = {
    		headers : {
    			'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
    		}
        };

        $http.post('../ajax/getBiteUploadMatrixData.php', data, config)
        .success(function (data, status, headers, config) {
// console.log("Matrix Data: " + JSON.stringify(data));
        	if(data && data.length > 0) {
    			generateMatrixBarChart(data);
    			loadBITEFilesUploadHistoryTable(data);
    			$('#loadingDiv').hide();
        		$('#noDataDiv').hide();
        		$('#chartDiv').show();
        		$('#dataDiv').show();
        	} else {
        		$('#loadingDiv').hide();
        		$('#noDataDiv').show()
        		$('#chartDiv').hide();
        		$('#dataDiv').hide();
        	}
		}).error(function (data, status, headers, config) {});
        getNotAssignedCount(startDate, endDate);
		
	}
	
	function getNotAssignedCount(startDate, endDate) {
		var filteredArray = [];
		
        var data = $.param({
	    	action: 'GET_NOT_ASSIGNED_FILES',
            startDate: startDate, 
            endDate: endDate,
            airlineIds: airlineIds
        });
	    
        var config = {
    		headers : {
    			'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
    		}
        };

        $http.post('../ajax/getBiteUploadMatrixData.php', data, config)
        .success(function (data, status, headers, config) {	
// console.log('Not Assigned Count: ' + JSON.stringify(data));
        	data = data.replace(/"/g, "");
// $('#notAssignedFiles').html(data);
			$('#notAssignedFiles').text(data);
		}).error(function (data, status, headers, config) {});
	}
	
	function loadBITEFilesUploadHistoryTable(data) {
    	$('#biteUploadMartixTable').bootstrapTable('destroy');
    	createBiteUploadMatrixTable();
		$('#biteUploadMartixTable').bootstrapTable('load',{
			data: data,
			exportOptions: {
				fileName: 'BiteUploadMartixData'
			}
		});
	}
	
	function generateMatrixBarChart(data) {
		$('#matrixBarChart').remove();
		$('#matrixBarChartDiv').append('<canvas id="matrixBarChart"></canvas>');
		var labelArray = [];
		var manualProcessedArray = [];
		var manualRejectedArray = [];
		var automaticProcessedArray = [];
		var automaticRejectedArray = [];
		
		$.each(data, function(idx, obj) {
			labelArray.push(obj.airline_name);
			manualProcessedArray.push(obj.manual_processed_count);
			manualRejectedArray.push(obj.manual_rejected_count);
			automaticProcessedArray.push(obj.automatic_processed_count);
			automaticRejectedArray.push(obj.automatic_rejected_count);
		});
		
		chart = new Chart(document.getElementById("matrixBarChart"), {
			type: 'horizontalBar',
		    data: {
		      labels: labelArray,
		      datasets: [
		    	  {
		    		  label: "Manual Processed",
		    		//   backgroundColor: "#4776b9",
		    		  backgroundColor: "#5C5C61", // dark grey
		    		//   borderColor: "white",
		    		//   borderWidth: 1,
		    		  data: manualProcessedArray,
		    		  stack: 1
	    		  },
	    		  {
			          label: "Manual Rejected",
			          backgroundColor: "#95CEFF", // medium blue
		    		//   borderColor: "white",
		    		//   borderWidth: 1,
			          data: manualRejectedArray,
			          stack: 1
		          },
		          {
		        	  label: "Automatic Processed",
		        	  backgroundColor: "#8e5ea2", //purple
		    		//   borderColor: "white",
		    		//   borderWidth: 1,
		        	  data: automaticProcessedArray,
		        	  stack: 2
		        },
		        {
			          label: "Automatic Rejected",
//			          backgroundColor: "#a0c5cf",
					//   backgroundColor: "#bb9ec7",
					  backgroundColor: "#FFBC75", // orange
		    		//   borderColor: "white",
		    		//   borderWidth: 1,
			          data: automaticRejectedArray,
			          stack: 2
		        }]
		    },
		    options: {
//		    	responsive: true,
		    	maintainAspectRatio: false,
//				barPercentage: 1.0,
//				categoryPercentage: 0.75,
// tooltips: {
// enabled: false
// },
		    	legend: {
					position: 'top',
					labels: {
                        boxWidth: 13
                    }
				},
				scales: {
					xAxes: [{
						display: true,
		                stacked: true,
						scaleLabel: {
							display: true,
		                    fontSize: 14,
							labelString: 'File Count'
						}
					}],
					yAxes: [{
						display: true,
						ticks:{
							stepSize : 1,
						},
						barThickness: 15,
						scaleLabel: {
							display: true,
							fontSize: 14,
							labelString: 'Airlines'
						}
					}]
				},
				plugins: {
		            datalabels: {
		            	color: 'white',
						font: {
							size: '12',
							weight: 'bold'
						},
		                formatter: function(value, context) {
		                	if(value == 0) value = "";
		                	
		                	return value;
		                }
		            }
		        }
		    }
		});
		chart.canvas.parentNode.style.height = ((labelArray.length * 40) + 100) + 'px';
	}
	
//	getBiteUploadMatrixData(startDate, endDate);
	$scope.filter();
});
