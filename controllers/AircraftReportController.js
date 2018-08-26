var app = angular.module('myApp', []);
app.controller('AircraftReportController', function($scope, $http, $log, $window, $timeout, $parse) {

	$log.log("inside AircraftReportController");
	$('#loadingDiv').hide();
	$scope.airlineId = $("#airlineId").val();
	$scope.showRemarksAlert=false;
	$scope.allData = "";
	
	var airlineId_nav = $window.airlineId_nav;
	var platform_nav = $window.platform_nav;
	var configuration_nav = $window.configuration_nav;
	var software_nav = $window.software_nav;
	var tailsign_nav = $window.tailsign_nav;
	
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	var criticalArray = [];
	var warningArray = [];
	var noIssueArray = [];
	
	var today = new Date();
	var priorDate = new Date(new Date().setDate(today.getDate()-6));
	var startDate = formatDate(priorDate);
	var endDate = formatDate(today);
	$scope.loading = true;
	$scope.startDate = startDate;
	$scope.endDate = endDate;
	var firstTime=true;
	$('#legendId').hide();
	
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
	$('#tailsign').selectpicker();


	$scope.loadAirlines = function() {
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
			
			if(airlineId_nav) {
				$("#airline").val(airlineId_nav);
			}
			
			$('#airline').selectpicker('refresh');
				
	        $scope.loadPlatforms();
	    });
   };

	$scope.resetFlightScore = function() {
		clearAirlines();
		$('#startDateTimePicker').datetimepicker({value: $window.startDateTime});
		$('#endDateTimePicker').datetimepicker({value: $window.endDateTime});
		$scope.loadAirlines();
		//getAircraftId();
	};
	
    $scope.loadPlatforms = function() {
    	clearPlatformSelect();
    	clearConfigurationSelect();
    	clearSoftwareSelect();
    	clearTailsignSelect();    	
    	
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
			console.log("platform from another apge: " + platform_nav);
			if(platform_nav) {
				$("#platform").val(platform_nav);
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
    	clearTailsignSelect();    	
    	
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
			console.log("configuration from another apge: " + configuration_nav);
			if(configuration_nav) {
				$("#configType").val(configuration_nav);
			}

			$('#configType').selectpicker('refresh');

            $scope.loadSoftwares();
        });
    };

    $scope.loadSoftwares = function() {
    	clearSoftwareSelect();
    	clearTailsignSelect();    	

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
			$("#software").val(softwareList[0]);
			
			console.log("software from another apge: " + software_nav);
			if(software_nav) {
				$("#software").val(software_nav);
			}
			
			$('#software').selectpicker('refresh');

            $scope.loadTailsign();
        });
    };
    
	$scope.loadTailsign = function() {
    	clearTailsignSelect();    	

		var airlineId = "";
		var platform = "";
		var configType = "";
		var software = "";
		
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();		
		software = getSelectedSoftwares();
		
        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            configType: configType,
            software: software,
            action: 'GET_TS_AND_ID_FOR_AIRLINE_PLTFRM_CNFG_SW_ACTIVE'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../common/AirlineDAO.php', data, config)
        .success(function (data, status, headers, config) {
			var tailsignList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < tailsignList.length; i++) {
				var ts = tailsignList[i];
				$("#tailsign").append('<option value="' + ts.tailsign + '">' + ts.tailsign + '</option>');
			}
			$('#tailsign').val(tailsignList[0]);
			
			console.log("tailsign from another apge: " + tailsign_nav);
			if(tailsign_nav) {
				$("#tailsign").val(tailsign_nav);
			}
			
			$('#tailsign').selectpicker('refresh');
			if(firstTime){
				//getAircraftId();
				firstTime=false;
			}	
        });
	};
	
	$("#filter").click(function(){
		getAircraftId();
	});
	
	function getAircraftId(){
		var aircraft=$('#tailsign').val();
		var data = $.param({
			tailsign: aircraft,
            action: 'GET_AIRCRAFTID_FOR_TS'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../common/AirlineDAO.php', data, config)
        .success(function (data, status, headers, config) {
			var aircraft = JSON.parse(JSON.stringify(data));
			
			var startDate=$('#startDateTimePicker').val();
			var endDate=$('#endDateTimePicker').val();
			$scope.getAircaftReport(startDate, endDate, aircraft.id);			
        });
	}
	
	
	
	$scope.getAircaftReport = function(startDate, endDate,aircraftId){
		/* Capture ScreenShot For TimelIne code*/
//		var urlForTimeLinePage = 'aircraftTimeLineForReport.php?$param&startDateTimeForScreenShot=' + $('#startDateTimePicker').val() + '&endDateTimeForScreenShot=' +$('#endDateTimePicker').val();
		var urlForTimeLinePage = 'aircraftTimeLineForReport.php?aircraftId='+aircraftId+'&startDateTimeForScreenShot=' + $('#startDateTimePicker').val() + '&endDateTimeForScreenShot=' +$('#endDateTimePicker').val();
		
	    jQuery.ajax({
	         url: '../common/screenCaptures.php',
	         type: 'get',
	         data: {'url': urlForTimeLinePage, 'aircraftId': aircraftId,'tailSign':$('#tailsign').val()},
	         beforeSend: function() {
	          jQuery('.legend').html("<img id=\"loading_spinner\" src=\"../img/loading.gif\" width=\"50px\" height=\"50px\"><br><br><h3>Report is being generated. Please wait...</h3>");
	        },
	        success: function(data) {
	            console.log('success: image captured-');
	            $('#legendId').show();
	             jQuery.ajax({
	               url: '../common/generateAircraftWordReport.php',
	               type: 'get',
	               data: {'aircraftId': aircraftId,'tailSign':$('#tailsign').val(), 'startDateTime': $("#startDateTimePicker").val(), 'endDateTime': $("#endDateTimePicker").val()},             
	               success: function(data) {
	            	   console.log('-'+JSON.stringify(data)+'-');
	                 jQuery('.legend').html("<img id=\"loading_spinner\" src=\"../img/reportOK.png\" width=\"50px\" height=\"50px\"><br><br><h3 align=\"center\">Report has been generated. <a href=\"../reports/"+data+"\">Click here to download it</a>.</h3>");                 
	               },
	               error: function(data) {
	                 //alert("Something went wrong!");
	            	   console.log('-'+JSON.stringify(data)+'-');
	                 jQuery('.legend').html("<img id=\"loading_spinner\" src=\"../img/reportKO.png\" width=\"50px\" height=\"50px\"><br><br><h3 align=\"center\">Something went wrong during the generation!<br><br>"+data.statusText+"</h3>");
	                 jQuery('#loading_spinner').hide();
	               }
	             });
	             
	 
	        },
	        error: function(data) {
	            console.log('error');
	            //alert("Something went wrong!");
	           console.log('-'+JSON.stringify(data)+'-');
	            jQuery('.legend').html("<h3 align=\"center\">Something went wrong during the generation!<br>"+data.statusText+"</h3>");
	            jQuery('#loading_spinner').hide();
	       }
	    });
	    
	    event.preventDefault();
	}


	
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
	
	function clearTailsignSelect() {
        $('#tailsign').empty();
        $('#tailsign').selectpicker('refresh');
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
	
	function getSelectedTailsigns() {
		return $('#tailsign').val();
	}
	
	$scope.resetActiveMaintenance = function() {
		clearAirlines();
		$('#startDateTimePicker').datetimepicker({value: $window.startDateTime});
		$('#endDateTimePicker').datetimepicker({value: $window.endDateTime});
		$scope.loadAirlines();		
	};
	
	$scope.loadAirlines();
	
});
