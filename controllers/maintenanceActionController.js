$(document).ready(function(){

	$('#airline').selectpicker({
		size: 6
  	});
  	
    $('#platform').selectpicker({
    	size: 6
    });
      	
    $('#configType').selectpicker({
    	size: 6
    });
      	
    $('#tailsign').selectpicker({
    	size: 6
    });
      	
	$('#airline').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadPlatforms();
	});

	$('#platform').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
	});

	$('#configType').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadTailsign();
	});
});

var app = angular.module('myApp', []);

app.controller('maintenanceActionController', function($scope, $http, $filter, $window) {
	console.log("inside maintenanceActionController");
	
	var firstTime=true;
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	
	showLoadingDiv();
	hideNoDataDiv();
	hideDataDiv();
	
	$scope.airlineId = $("#airlineId").val();

	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#tailsign').selectpicker();
	$('#failureCode').selectpicker();
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

	createFailureTable();
	
	function createFailureTable() {
		$('#failureTable').bootstrapTable({
		    formatNoMatches: function () {
		        return 'No data available for the selected duration or filters';
		    }
		});
	}
	
	$scope.loadAirlines = function() {
		clearAirlines();
	    $http.get("../common/AirlineDAO.php", {
	        params: {
	        	action: "GET_AIRLINES_BY_IDS",
	            airlineIds: $("#airlineIds").val()
	        }
	    }).success(function (data,status) {
//	    	console.log("Airlines: " + JSON.stringify(data));
         	var airlineList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < airlineList.length; i++) {
				var al = airlineList[i];
				$("#airline").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
			}
			$('#airline').val(airlineList[0].id);
			$('#airline').selectpicker('refresh');
			
			$scope.loadPlatforms();
			
	    });
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
         	var platformList = JSON.parse(JSON.stringify(data));
         	$("#platform").append('<option value="">All</option>');
			for (var i = 0; i < platformList.length; i++) {
				var pf = platformList[i];
				$("#platform").append('<option value=' + pf.platform + '>' + pf.platform + '</option>');
			}
			
//			$("#platform").val(platformList[0].platform);
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
        	$("#configType").append('<option value="">All</option>');
         	var configTypeList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < configTypeList.length; i++) {
				var config = configTypeList[i];
				$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
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
		
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();		
		
        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            configType: configType,
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
			
			$('#tailsign').selectpicker('refresh');
			if(firstTime){
				$scope.filter();
				firstTime=false;
			}	
        });
	};
	
	$scope.loadFailureCodes = function() {
	    $http.get("../ajax/getMaintenanceActionData.php", {
	        params: {
	        	action: "GET_FAILURE_CODES"
	        }
	    }).success(function (data,status) {
         	var failureCodeList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < failureCodeList.length; i++) {
				var failureCode = failureCodeList[i];
				$("#failureCode").append('<option value=' + failureCode + '>' + failureCode + '</option>');
			}
			$('#failureCode').selectpicker('refresh');
	    });
   };

	$scope.filter = function() {
		showLoadingDiv();
		hideNoDataDiv();
		hideDataDiv();

		$scope.getMaintenanceActionData();
	};
	
	$scope.getMaintenanceActionData = function() {
		console.log('inside getMaintenanceActionData');
		var airlineId = "";
		var platform = "";
		var configType = "";
		var software = "";
		var tailsignList = "";
		var failureCodeList = "";
		
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();
		tailsignList = getSelectedTailsign();
		failureCodeList = getFailureCode();
		startDate = $('#startDateTimePicker').val();
		endDate = $('#endDateTimePicker').val();
		
        var data = $.param({
	    	action: 'GET_FAILURES',
            airlineId: airlineId, 
            platform: platform,
            configType: configType,
            tailsignList: tailsignList,
            failureCodeList: failureCodeList,
            startDate: startDate, 
            endDate: endDate
        });
	    
        var config = {
    		headers : {
    			'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
    		}
        };

        $http.post('../ajax/getMaintenanceActionData.php', data, config)
        .success(function (data, status, headers, config) {
//        	console.log("Failure data: " + JSON.stringify(data));
        	if(data && data.length > 0) {
    			loadFailureTable(data);
        	} else {
        		hideDataDiv();
        		hideLoadingDiv();
        		showNoDataDiv();
        	}
		}).error(function (data, status, headers, config) {});
        
		// To remove the white space below the page content
		page_content_onresize();
	};
    
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
	
	function clearFailureCodeSelect() {
        $('#failureCode').val('');
        $('#failureCode').selectpicker('refresh');
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
	
	function getSelectedTailsign() {
		return $('#tailsign').val();
	}
	
	function getFailureCode() {
		return $('#failureCode').val();
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
	
	function showDataDiv() {
		$('#dataDiv').show();
	}
	
	function hideDataDiv() {
		$('#dataDiv').hide();
	}
	
	$scope.loadAirlines();
	$scope.loadFailureCodes();
	
	$scope.resetSearchPanel = function() {
		clearAirlines();
		clearFailureCodeSelect();
		$('#startDateTimePicker').datetimepicker({value: startDate});
		$('#endDateTimePicker').datetimepicker({value: endDate});
		$scope.loadAirlines();
	};
	
	function loadFailureTable(data) {
    	$('#failureTable').bootstrapTable('destroy');
    	createFailureTable();
		$('#failureTable').bootstrapTable('load',{
			data: data
		});
		showDataDiv();
		hideNoDataDiv();
		hideLoadingDiv();
	}
		
});
