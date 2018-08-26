app.controller('FlightScoringController', function($scope, $http, $log, $window, $timeout, $parse) {

	$log.log("inside FlightScoringController");
	showLoadingDiv();
	hideNoDataDiv();
	hideFlightScoreTableDiv();
	
	$scope.airlineId = $("#airlineId").val();
	$('#showRemarksAlert').hide();
	$scope.allData = "";
	
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	var criticalArray = [];
	var warningArray = [];
	var noIssueArray = [];

	var flightScoreVisited=$window.flightScoreVisited;
	console.log('flightScoreVisited ...'+flightScoreVisited);
	
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

	$('#lruWeightTable').bootstrapTable();
	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#software').selectpicker();
	$('#tailsign').selectpicker();
	loadLRUWeightData();
	createFlightScoringTable();
	
	function createFlightScoringTable() {
		$('#flightScoringTable').bootstrapTable({
		    formatNoMatches: function () {
		        return 'No data available for the selected duration or selected filters';
		    }
		});
	}

	$scope.loadAirlines = function() {
	    $http.get("../common/AirlineDAO.php", {
	        params: {
	        	action: "GET_AIRLINES_BY_IDS",
	            airlineIds: $("#airlineIds").val()
	        }
	    }).success(function (data,status) {
			$("#airline").append('<option value="">All</option>');
         	var airlineList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < airlineList.length; i++) {
				var al = airlineList[i];
				$("#airline").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
			}
			$('#airline').selectpicker('refresh');
			if(flightScoreVisited){
				$('#airline').val($window.session_AirlineId);
				$('#airline').selectpicker('refresh');
			}				
	        $scope.loadPlatforms();
	    });
   };

	$scope.resetFlightScore = function() {
//		showLoadingDiv();
//		hideNoDataDiv();
		
		if(flightScoreVisited){
			var url = "FlightScore.php?flightScoreVisited=false";
			var win = window.open(url, '_self');
			win.focus();
		}else{
			clearAirlines();
			$('#startDateTimePicker').datetimepicker({value: $window.startDateTime});
			$('#endDateTimePicker').datetimepicker({value: $window.endDateTime});
//	    	$('#flightScoringTable').bootstrapTable('destroy');
	//    	$('#flightScoringTable').bootstrapTable();
//	    	createFlightScoringTable();
	
			flightScoreVisited = false;
			$scope.loadAirlines();
//			$scope.getFlightScore();
		}
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
			$("#platform").append('<option value="">All</option>');
         	var platformList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < platformList.length; i++) {
				var pf = platformList[i];
				$("#platform").append('<option value="' + pf.platform + '">' + pf.platform + '</option>');
			}
			$('#platform').selectpicker('refresh');
			if(flightScoreVisited){
				$('#platform').val($window.session_Platform);
				$('#platform').selectpicker('refresh');
			}
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
			$("#configType").append('<option value="">All</option>');
         	var configTypeList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < configTypeList.length; i++) {
				var config = configTypeList[i];
				$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
			}
			$('#configType').selectpicker('refresh');
			if(flightScoreVisited){
				$('#configType').val($window.session_Config);
				$('#configType').selectpicker('refresh');
			}
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
			$('#software').selectpicker('refresh');
			if(flightScoreVisited){				
				var string = $window.session_Software;
            	var array = string.split(",");
            	if(array!=''){
                	var software=[];
                	for(r in array){												
						var str = array[r];
						software.push(str);
                    }  
            	}
				$('#software').val(software);
				$('#software').selectpicker('refresh');
			}
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
            action: 'GET_TS_FOR_AIRLINE_PLTFRM_CNFG_SW'
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
				$("#tailsign").append('<option value=' + ts.tailsign + '>' + ts.tailsign + '</option>');
			}
			$('#tailsign').selectpicker('refresh');
			if(flightScoreVisited){
				
				var string = $window.session_Tailsign;
				var array = string.split(",");
            	if(array!=''){
                	var tailsign=[];
                	for(r in array){												
						var str = array[r];
						tailsign.push(str);
                    }  
            	}
            	$('#tailsign').val(tailsign);
				$('#tailsign').selectpicker('refresh');
				$('#startDateTimePicker').val($window.session_StartDate);
				$('#endDateTimePicker').val($window.session_EndDate);
				$scope.getFlightScore();
			}
        });
	};

	$scope.getFlightScore = function() {
		var airlineId = "";
		var platform = "";
		var configType = "";
		var software = "";
		var tailsignList = "";
		
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();		
		software = getSelectedSoftwares();
		tailsignList = getSelectedTailsigns();
		
		startDate = $('#startDateTimePicker').val();
		endDate = $('#endDateTimePicker').val();
		
		console.log('software : '+software);
		console.log('tailsignList : '+tailsignList);
		
		getFlightScoreData(airlineId, platform, configType, software, tailsignList, startDate, endDate);
	};


	function getFlightScoreData(airlineId, platform, configType, software, tailsignList, startDate, endDate) {
		var filteredArray = [];

		showLoadingDiv();
		hideNoDataDiv();
		hideFlightScoreTableDiv();
//		$('#loadingDiv').show();
//		$('#flightScoringDiv').hide();
		
        var data = $.param({
	    	action: 'GET_FLIGHT_SCORE',
            airlineId: airlineId, 
            platform: platform,
            configType: configType,
            software: software,
            tailsignList: tailsignList,
            startDate: startDate, 
            endDate: endDate
        });
	    
        var config = {
    		headers : {
    			'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
    		}
        };

        $http.post('../ajax/getFlightScoring.php', data, config)
        .success(function (data, status, headers, config) {
			if(fsSliderSet) {
				minFlightScore = $fsSlider.data('slider').getValue()[0];
				maxFlightScore = $fsSlider.data('slider').getValue()[1];
				
				for(var i=0; i < data.length; i++) {
					if(data[i].flightScore >= minFlightScore  && data[i].flightScore <= maxFlightScore) {
						filteredArray.push(data[i]);
					}
				}
				loadFlightScoreTable(filteredArray);
				data = filteredArray;
			}
			
        	$scope.allData = data;

        	criticalArray.length=0;
			warningArray.length=0;
			noIssueArray.length=0;
			
			for(var i=0; i < data.length; i++) {
				if(data[i].flightScore >= 0 && data[i].flightScore <90) {
					criticalArray.push(data[i]);
				} else if(data[i].flightScore >= 90 && data[i].flightScore <=98) {
					warningArray.push(data[i]);
				} else {
					noIssueArray.push(data[i]);
				}
			}

			loadFlightScoreTable(data);
			
			if(data && data.length > 0) {
				hideLoadingDiv();
				hideNoDataDiv();
				showFlightScoreTableDiv();
			} else {
				hideLoadingDiv();
				showNoDataDiv();
				hideFlightScoreTableDiv();
			}

		}).error(function (data, status, headers, config) {});
		
		// To remove the white space below the page content
		page_content_onresize();
	}
	
	$scope.filterCategory = function(category) {
		 
		 if(category == 'critical') {
			 loadFlightScoreTable(criticalArray);
		 } else if(category == 'warning') {
			 loadFlightScoreTable(warningArray);
		 } else if(category == 'noissue') {
			 loadFlightScoreTable(noIssueArray);
		 } else if(category == 'all') {
			 loadFlightScoreTable($scope.allData);
		 }
	};
	
	$scope.updateRemarks = function(flightLegId, aircraftId, value) {
        var data = $.param({
	    	action: 'UPDATE_REMARKS',
	    	flightLegId: flightLegId, 
	    	aircraftId: aircraftId,
            remark: value 
        });
	    
        var config = {
    		headers : {
    			'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
    		}
        };

        $http.post('../ajax/getFlightScoring.php', data, config)
        .success(function (data, status, headers, config) {
			$scope.showRemarksAlert = true;
			$('#showRemarksAlert').show();
			$timeout(function() {
				$('#showRemarksAlert').hide();
		     }, 2000);
		}).error(function (data, status, headers, config) {});
	};
	
	function loadLRUWeightData() {
        var data = $.param({
	    	action: 'GET_LRU_WEIGHT'
        });
	    
        var config = {
    		headers : {
    			'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
    		}
        };

        $http.post('../ajax/getFlightScoring.php', data, config)
        .success(function (data, status, headers, config) {
			$('#lruWeightTable').bootstrapTable('load',{
				data: data
			});
			
			angular.forEach(data, function(value, key){
				var varName = value.lruName + '_weight';
				var model = $parse(varName);
				model.assign($scope, value.lruWeight);
		   });
			
			$scope.lruData = data;
		}).error(function (data, status, headers, config) {});
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
	
	function showFlightScoreTableDiv() {
		$('#flightScoringDiv').show();
		$('#seatResetsChartLegend').show();
	}
	
	function hideFlightScoreTableDiv() {
		$('#flightScoringDiv').hide();
		$('#seatResetsChartLegend').hide();
	}
	
	function showLoadingDiv() {
		$('#loadingDiv').show();
	}
	
	function hideLoadingDiv() {
		$('#loadingDiv').hide();
	}
	
	function showNoDataDiv() {
		$('#noDataDiv').show();
	}
	
	function hideNoDataDiv() {
		$('#noDataDiv').hide();
	}
	
	function loadFlightScoreTable(data) {
    	$('#flightScoringTable').bootstrapTable('destroy');
//    	$('#flightScoringTable').bootstrapTable();
    	createFlightScoringTable();
		$('#flightScoringTable').bootstrapTable('load',{
			data: data
		});
	}
	
	$scope.loadAirlines();
	$scope.getFlightScore();
	
});
