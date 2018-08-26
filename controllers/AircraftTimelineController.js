/*app.controller('AircraftTimelineController', function($scope, $http, $log, $window, $timeout, $parse) {

	$log.log("inside AircraftTimelineController");
	$('#loadingDiv').hide();
	$scope.airlineId = $("#airlineId").val();
	$('#errorInfo').hide();
	$('#dataTimeline').hide();
	$('#loadingData').hide();
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	var firstTime=true;
	var timeline;
	var flightLegs = '';
	$scope.aircraftInfo={};
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
				$("#airline").append('<option value=' + al.id + '>' + al.name + '</option>');
			}
			$('#airline').selectpicker('refresh');
			$scope.selectedAirline = airlineList[0]; 
	        $scope.loadPlatforms();
	    });
   };

	$scope.resetFilters = function() {
		clearAirlines();
		$('#startDateTimePicker').datetimepicker({value: $window.startDateTime});
		$('#endDateTimePicker').datetimepicker({value: $window.endDateTime});

		$scope.loadAirlines();
		//$scope.getAircraftInfoData();
	};
	
    $scope.loadPlatforms = function() {
    	clearPlatformSelect();
    	clearConfigurationSelect();    	
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
			$('#platform').selectpicker('refresh');
            
            $scope.loadConfigTypes();
        })
        .error(function (data, status, header, config) {
        });
    };

    $scope.loadConfigTypes = function() {
    	clearConfigurationSelect();
    	
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
				$("#configType").append('<option value=' + config.configType + '>' + config.configType + '</option>');
			}
			$('#configType').selectpicker('refresh');

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
            action: 'GET_TS_AND_ID_FOR_AIRLINE_PLTFRM_CNFG_SW'
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
				$("#tailsign").append('<option value=' + ts.id + '>' + ts.tailsign + '</option>');
			}
			$('#tailsign').selectpicker('refresh');
			if(firstTime){
				$scope.getAircraftInfoData();
				firstTime=false;
			}
        });
	};

	$scope.getAircraftInfoData = function() {
		var airlineId = "";
		var platform = "";
		var configType = "";
		var software = "";
		var tailsignList = "";
		$('#errorInfo').hide();
		$('#dataTimeline').hide();
		$('#timeline').hide();
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();		
		software = getSelectedSoftwares();
		tailsignList = getSelectedTailsigns();
		
		startDate = $('#startDateTimePicker').val();
		endDate = $('#endDateTimePicker').val();
		
		var aircraftId = $("#tailsign").val();
    	data={aircraftId:aircraftId};
    	var output="";
    	$.ajax({
            type: "GET",
            dataType: "json",
            url: "../ajax/GetAircraftInfo.php",
            data: data,
            success: function(data) {
                //console.log(data);
                $scope.aircraftInfo=data;
                $scope.loadAircraftData($scope.aircraftInfo);
                if(timeline != null) {
                	if(timeline.body != null){
                		timeline.destroy();
                	}
                    //$('#loadingTimeline').toggle();
                }
                getTimeLineData();	//Reload timeline     
            },
            error: function (err) {
                //console.log('Error', err);
            	$('#errorInfo').show();
	            $('#loadingData').hide();
            }
        });
    	
    	
		
	};
	
	$scope.loadAircraftData = function(aircraftData) {
		 $("#Atailsign").text((aircraftData.tailsign?aircraftData.tailsign:'-'));
		 $("#msn").text((aircraftData.msn?aircraftData.msn:'-'));
		 $("#eis").text((aircraftData.EIS?aircraftData.EIS:'-'));
		 $("#ac_type").text((aircraftData.Ac_Configuration?aircraftData.Ac_Configuration:'-'));
		 $("#config").text((aircraftData.Ac_Configuration?aircraftData.Ac_Configuration:'-'));
		 $("#Aplatform").text((aircraftData.platform?aircraftData.platform:'-'));
		 $("#sw_baseline").text((aircraftData.SW_Baseline?aircraftData.SW_Baseline:'-'));
		 $("#software").text((aircraftData.software?aircraftData.software:'-'));
		 $("#sw_installed").text((aircraftData.SW_installed?aircraftData.SW_installed:'-'));
		 $("#sw_partno").text((aircraftData.SW_PartNo?aircraftData.SW_PartNo:'-'));
		 $("#mapVersion").text((aircraftData.Map_Version?aircraftData.Map_Version:'-'));
		 $("#content").text((aircraftData.Content ? aircraftData.Content : '-'));
	}
	
	function getTimeLineData() {		
		var aircraftId = $("#tailsign").val();
		$('#loadingData').show();
		var dataParam={
					aircraftId:aircraftId,
					startDateTime: $("#startDateTimePicker").val(), 
					endDateTime: $("#endDateTimePicker").val()
		        };
	    $.ajax({
	        type: "POST",
	        dataType: "json",
	        url: "../ajax/getAircraftTimeLineData.php",
	        data: dataParam,
	        success: function(data) {	            
	            $('#dataTimeline').show();
	            $('#timeline').show();
	            createTimeline(data);
	        },
	        error: function (err) {
	            //console.log('Error', err);
	            $('#errorInfo').show();
	            $('#loadingData').hide();
	        }
	    });
	}
	
	function createTimeline(data) {
	    $('#loadingTimeline').hide();
	    $('#errorInfo').hide();
	    var container = document.getElementById('timeline');

	    var groups = new vis.DataSet(
	        data.groups
	        );

	    var items = new vis.DataSet(
	        data.items
	        );

	    var options = {
	        start: data.options.start,
	        end: data.options.end,
	        min: data.options.min,
	        max: data.options.max,
	        clickToUse: true,
	        stack: false,
	        multiselect: true
	    };

	    timeline = new vis.Timeline(container, items,  groups, options);
	    timeline.on('select', function (properties) {
	        var selectedItems = properties.items;
	        flightLegs = ''; // reset flight Legs

	        if(selectedItems != null) {
	            for (i = 0; i < selectedItems.length; i++) { 
	                var selectedItem = selectedItems[i];
	                var res = selectedItem.split("/");
	                if(res[0] == 'FLI') {
	                    if(flightLegs != '') {
	                        flightLegs += ',';
	                    }
	                    flightLegs += res[1];
	                }
	            }
	        }
	    });
	    timeline.on('contextmenu', function (props) {	      
	      props.event.preventDefault();
	    });
	    $('#loadingData').hide();
	}


	$("#analyzeFlightLegs").click(function(){		
	    if(flightLegs != '') {
	    	var aircraftId = $("#tailsign").val();	        
			var url = "FlightAnalysis.php?aircraftId="+aircraftId+"&flightLegs="+flightLegs;			
	        var win = window.open(url, '_self');
	        win.focus();
	    }
	});

	
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
	

	
	$scope.loadAirlines();
	
});
*/