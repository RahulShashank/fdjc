$.blockUI.defaults.overlayCSS.opacity = '0.3';
$.blockUI.defaults.css = { 
    padding:        0, 
    margin:         0, 
    width:          '30%', 
    top:            '40%', 
    left:           '35%', 
    textAlign:      'center', 
    cursor:         'wait' 
};

$(document).ready(function(){
	showView('timeline');

	$('#airline').selectpicker({
		size: 6
  	});
  	
    $('#platform').selectpicker({
    	size: 6
    });
      	
    $('#configType').selectpicker({
    	size: 6
    });
      	
    $('#software').selectpicker({
    	size: 6
    });
      	
	$('#airline').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadPlatforms();
	});

	$('#platform').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
	});

	$('#configType').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadSoftwares();
	});

});

var app = angular.module('myApp', ['ui.bootstrap']);

app.controller('AirlineDashboardController', function($scope, $http, $filter, $window, $uibModal) {
	var charts = [];

	console.log("inside AirlineDashboardController");
	var dashboardVisited=$window.dashboardVisited;
	
	showLoading();
	$scope.airlineId = $("#airlineId").val();
	
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	var firstTime=true;

	$('#startDateTimePicker').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: startDate
	});
	
	$('#endDateTimePicker').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: endDate
	});

	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#software').selectpicker();

	$scope.loadAirlines = function() {
		clearAirlines();
	    $http.get("../common/AirlineDAO.php", {
	        params: {
	        	action: "GET_AIRLINES_BY_IDS",
	            airlineIds: $("#airlineIds").val()
	        }
	    }).success(function (data,status) {
			//$("#airline").append('<option value="">All</option>');
         	var airlineList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < airlineList.length; i++) {
				var al = airlineList[i];
				$("#airline").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
			}
			//$('#airline').val(airlineList[0].id);
			if(dashboardVisited){
				$('#airline').val($window.session_AirlineId);
			}				
			$('#airline').selectpicker('refresh');
				
	        $scope.loadPlatforms();
	    });
   };

    $scope.loadPlatforms = function() {
    	clearPlatformSelect();
    	clearConfigurationSelect();
    	clearSoftwareSelect();
    	
		var airlineId = "";
		airlineId = getSelectedAirline();
		
        var data = $.param({
            airlineId: airlineId,
            action: 'GET_PLATFORMS_FOR_AIRLINE'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../common/AirlineDAO.php', data, config)
        .success(function (data, status, headers, config) {
			//$("#platform").append('<option value="">All</option>');
         	var platformList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < platformList.length; i++) {
				var pf = platformList[i];
				$("#platform").append('<option value=' + pf.platform + '>' + pf.platform + '</option>');
			}
			
			if(dashboardVisited && $window.session_Platform){
				var platform = $window.session_Platform.split(",");
				$('#platform').val(platform);
			}
			$('#platform').selectpicker('refresh');
            
            $scope.loadConfigTypes();
        })
        .error(function (data, status, header, config) {
        });
    };

    $scope.loadConfigTypes = function() {
    	clearConfigurationSelect();
    	clearSoftwareSelect();
    	
		var airlineId = "";
		var platform = "";

		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();

        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            action: 'GET_CONFIG_TYPE_FOR_AIRLINE_PLATFORM'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../common/AirlineDAO.php', data, config)
        .success(function (data, status, headers, config) {
			//$("#configType").append('<option value="">All</option>');
         	var configTypeList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < configTypeList.length; i++) {
				var config = configTypeList[i];
				$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
			}
			$('#configType').selectpicker('refresh');
			if(dashboardVisited && $window.session_Config){
				var configType = $window.session_Config.split(",");
				$('#configType').val(configType);
			}
			$('#configType').selectpicker('refresh');

            $scope.loadSoftwares();
        });
    };

    $scope.loadSoftwares = function() {
    	clearSoftwareSelect();

		var airlineId = "";
		var platform = "";
		var configType = "";

		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();

        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            configType: configType,
            action: 'GET_SW_FOR_AIRLINE_PLATFORM_CNFG'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../common/AirlineDAO.php', data, config)
        .success(function (data, status, headers, config) {
         	var softwareList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < softwareList.length; i++) {
				var sw = softwareList[i];
				$("#software").append('<option value="' + sw.software+ '">' + sw.software + '</option>');
			}
			if(dashboardVisited){
				var software = $window.session_Software.split(",");
				$('#software').val(software);
				$('#startDateTimePicker').val($window.session_StartDate);
				$('#endDateTimePicker').val($window.session_EndDate);
			} else {
				$("#software").val(softwareList[0]);
			}
			$('#software').selectpicker('refresh');

			if(firstTime) {
				$scope.filter();
				firstTime = false;
			}
        });
    };
    
	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}
	
	function clearAirlines() {
        $('#airline').empty();
        $('#airline').selectpicker('refresh');
	}
	
	function clearPlatformSelect() {
        $('#platform').empty();
        $('#platform').selectpicker('refresh');
	}
	
	function clearConfigurationSelect() {
        $('#configType').empty();
        $('#configType').selectpicker('refresh');
	}
	
	function clearSoftwareSelect() {
        $('#software').empty();
        $('#software').selectpicker('refresh');
	}
	
	function getSelectedAirline() {
		return $('#airline').val();
	}
	
	function getSelectedPlatform() {
		return $('#platform').val();
	}
	
	function getSelectedConfigType() {
		return $('#configType').val();
	}
	
	function getSelectedSoftwares() {
		return $('#software').val();
	}
	
	function getStartDate() {
		return $('#startDateTimePicker').val()
	}
	
	function getEndDate() {
		return $('#endDateTimePicker').val()
	}
	
	function showLoading() {
		$('#loadingDiv').show();
		$('#dataDiv').hide();
	}
	
	function hideLoading() {
		$('#loadingDiv').hide();
		$('#dataDiv').show();
	}
	
	$scope.loadAirlines();
	
	$scope.options = {
		timepicker: false,
		format: "Y/m/d",
		angularFormat: "yyyy/MM/dd",
		dayOfWeekStart: 1
	};
	
	$scope.filter = function() {
		showLoading();
//	    destroyCharts();
		downloadStatusTimelineData();
		getAircrafts();
//		downloadFlightsData();
//		downloadFailuresRankingData();
//		downloadResetsRankingData();
	}
	
	$scope.resetSearchPanel = function() {
		if(dashboardVisited){
			dashboardVisited=false;
			firstTime=true;
		}
		
		clearAirlines();
		$('#startDateTimePicker').datetimepicker({value: startDate});
		$('#endDateTimePicker').datetimepicker({value: endDate});
		$scope.loadAirlines();
	}
	
    function getAircrafts() {    	
		var airlineId = getSelectedAirline();

		var platform = "";
		if(getSelectedPlatform()) {
			platform = getSelectedPlatform();
		}

		var configType = "";
		if(getSelectedConfigType()) {
			configType = getSelectedConfigType();
		}
		
		var software = "";
		if(getSelectedSoftwares()) {
			software = getSelectedSoftwares();
		}
		
        // $http.get("../ajax/getAircrafts.php?airlineId="+airlineId + "&platform=" + platform + "&configuration=" + configType + "&software=" + software)
        // .success(function (data) {
        //    console.log(data);
        //     $scope.aircrafts = data;
        //     $("#loadingDiv").hide();
		// });
		
		var data = $.param({
            airlineId: getSelectedAirline(),
            platform: getSelectedPlatform(),
            configType: getSelectedConfigType(),
            software: getSelectedSoftwares()
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/getAircrafts.php', data, config)
        .success(function (data, status, headers, config) {
            $scope.aircrafts = data;
            $("#loadingDiv").hide();
        });
		
		// To remove the white space below the page content
		page_content_onresize();
    }
    
	$scope.editMaintenanceStatus = function(aircraft){
		$scope.originalStatus = aircraft.maintenanceStatus ;
		$scope.aircraftId = aircraft.id ;
		$scope.tailsign = aircraft.tailsign ;
		$('#error').html("");
		$("#newStatus").val("").selectpicker('refresh');// Need to refresh so option is selected;
		$("#myModal").modal();
	};
	
	$scope.updateStatusVersion = function(){
		if( $("#newStatus").val() == '' ) {
			$('#error').html("<div class=\"alert alert-danger alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>Please select a status.</div>")
			return;
		}
		var reqUrl = "../ajax/updateAircraft.php";
		$http({
			url: reqUrl,
			method: "POST",
			data: {
				aircraftId : $("#aircraftId").val(),
				newStatus : $("#newStatus").val(),
			},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		}).success(function (data, status, headers, config) {
			//console.log(data);
			if(data.state == -1) {
				console.log('error'); 
				$('#error').html("<div class=\"alert alert-danger alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><strong>Error!</strong> "+ data.message +"</div>")
			} else {
				/*
				$('#error').html("<div class=\"alert alert-success alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><strong>Success!</strong> "+ data.message +"</div>")
				setTimeout(function(){
					//Nothing to do.
				}, 2000);
				*/
				$("#myModal").modal('hide');
				getAircrafts();
			}
		});
	};

	function downloadStatusTimelineData() {
		$('#seatResetsChartLegend').hide();
		
        var data = $.param({
            airlineId: getSelectedAirline(),
            platform: getSelectedPlatform(),
            configType: getSelectedConfigType(),
            software: getSelectedSoftwares(),
            startDate: getStartDate(),
            endDate: getEndDate()
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/getFleetStatusDataTimeline.php', data, config)
        .success(function (data, status, headers, config) {
        	createTimeline(data);
        });
		
		// To remove the white space below the page content
		page_content_onresize();
	}
	
	function createTimeline(data) {

		$('#visualization').html('');
	    var container = document.getElementById('visualization');

	    var groups = new vis.DataSet(
	        data.groups
	        );

	    var items = new vis.DataSet(
	        data.items
	        );

	    var options = {
	        orientation: 'both',
            start: data.options.start,
            end: data.options.end,
            min: data.options.min,
            max: data.options.max,
	        clickToUse: true,
	        stack: false
	    };

	    timeline = new vis.Timeline(container, items,  groups, options);
	    timeline.on('select', function (properties) {
	        var event = properties.items[0];
	        if(event) {
		        var res = event.split("/");
		        var url = 'FlightAnalysis.php?aircraftId='+res[1]+'&flightLegs='+res[3]+"&mainmenu=DASHBOARD";
		        var win = window.open(url, '_self');
		        win.focus();
	        }
	    });
		hideLoading();
		$('#visualization').show();
		$('#seatResetsChartLegend').show();
		
		// To remove the white space below the page content
		page_content_onresize();
	}
	
	function downloadFlightsData() {
        var data = $.param({
            airlineId: getSelectedAirline(),
            platform: getSelectedPlatform(),
            configType: getSelectedConfigType(),
            software: getSelectedSoftwares(),
            startDate: getStartDate(),
            endDate: getEndDate(),
            dataChartType: 'flights'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/getFleetDataChart.php', data, config)
        .success(function (data, status, headers, config) {
        	if(data.labels && data.labels.length > 0) {
                var ctx = document.getElementById("maintenanceFlightsChart").getContext("2d");
                var lineChart = new Chart(ctx).Line(data, {
                    animation: false,
                    responsive: true,
                    scaleBeginAtZero: true,
                    bezierCurveTension : 0.1,
                });

                charts.push(lineChart);
                $('#maintenanceFlightsChart').show();
                $('#flightsFilterAlert').hide();
            } else {
                $('#flightsFilterAlert').show();
                $('#maintenanceFlightsChart').hide();
            }
            $('#flightsLoading').hide();
        });
	}

	function downloadFailuresRankingData() {
		$('#failuresLoading').show();
        var data = $.param({
            airlineId: getSelectedAirline(),
            platform: getSelectedPlatform(),
            configType: getSelectedConfigType(),
            software: getSelectedSoftwares(),
            startDate: getStartDate(),
            endDate: getEndDate(),
            dataChartType: 'failures'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/getFleetDataChart.php', data, config)
        .success(function (data, status, headers, config) {
            if(data.labels && data.labels.length > 0) {
            	$('#failuresFilterAlert').hide();
                var ctx = document.getElementById("failuresRankingChart").getContext("2d");
                var failuresBarChart = new Chart(ctx).Bar(data, {
                    animation: false,
                    responsive: true,
					barStrokeWidth : 1
                });
                
                charts.push(failuresBarChart);
                $("#failuresRankingChart").click( 
	                    function(evt){
	                        var activeBars = failuresBarChart.getBarsAtEvent(evt);
	                        $.ajax({
	                            url: '../ajax/getAircraftId.php',
	                            type: 'post',
	                            data: {'tailsign': activeBars[0].label},
	                            success: function(data, status) {
	                                var url = 'aircraftDashboard.php?aircraftId='+data;
	                                var win = window.open(url, '_blank');
//	                                win.focus();
	                            },
	                            error: function(xhr, desc, err) {
	                                console.log(xhr);
	                                console.log("Details: " + desc + "\nError:" + err);
	                            }
	                        });
	                    }
	                );
                $('#failuresRankingChart').show();
            } else {
            	$('#failuresFilterAlert').show();
            	$('#failuresRankingChart').hide();
            }
        });
        $('#failuresLoading').hide();
	}
	
	function downloadResetsRankingData() {
		$('#resetsLoading').show();
        var data = $.param({
            airlineId: getSelectedAirline(),
            platform: getSelectedPlatform(),
            configType: getSelectedConfigType(),
            software: getSelectedSoftwares(),
            startDate: getStartDate(),
            endDate: getEndDate(),
            dataChartType: 'resets'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/getFleetDataChart.php', data, config)
        .success(function (data, status, headers, config) {
            if(data.labels && data.labels.length > 0) {
            	$('#resetsFilterAlert').hide();
                var ctx = document.getElementById("resetsRankingChart").getContext("2d");
                var resetsBarChart = new Chart(ctx).Bar(data, {
                    animation: false,
                    responsive: true,
					barStrokeWidth : 1
                });

                charts.push(resetsBarChart);

                $("#resetsRankingChart").click( 
                    function(evt){
                        var activeBars = resetsBarChart.getBarsAtEvent(evt);
                        $.ajax({
                            url: '../ajax/getAircraftId.php',
                            type: 'post',
                            data: {'tailsign': activeBars[0].label},
                            success: function(data, status) {
                                var url = 'aircraftDashboard.php?aircraftId='+data;
                                var win = window.open(url, '_blank');
                                win.focus();
                            },
                            error: function(xhr, desc, err) {
                                console.log(xhr);
                                console.log("Details: " + desc + "\nError:" + err);
                            }
                        });
                    }
                );
                $('#resetsRankingChart').show();
            } else {
            	$('#resetsFilterAlert').show();
            	$('#resetsRankingChart').hide();
            }
        });
        $('#resetsLoading').hide();
	}
	
	function destroyCharts(){
	    for (var i = 0; i < charts.length; i++) {
	        charts[i].destroy();
	    };
	}
});
