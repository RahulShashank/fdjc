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

app.controller('HardwareRevisionsModsController', function($scope, $http, $filter, $window) {
	console.log("inside HardwareRevisionsModsController");
	
	var firstTime=true;
	var lruTypesData = $window.lruTypesData;
	console.log("lruTypesData: " + lruTypesData);

	var airlineId_nav = $window.airlineId_nav;
	var platform_nav = $window.platform_nav;
	var configuration_nav = $window.configuration_nav;
	var software_nav = $window.software_nav;
	var tailsign_nav = $window.tailsign_nav;
	
	showLoading();
	hideNoConfigDataDiv();
	hideConfigDataTable();
	hideNoRevsAndModsDiv();
	hideRevsAndModsTable();
	$scope.airlineId = $("#airlineId").val();

	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#tailsign').selectpicker();

	createConfigDataTable();
	createRevsAndModsTable();
	
	function createConfigDataTable() {
		$('#configDataTable').bootstrapTable({
		    formatNoMatches: function () {
		        return 'No data available for the selected duration or filters';
		    }
		});
	}
	
	function createRevsAndModsTable() {
		$('#revsAndModsTable').bootstrapTable({
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
			
			if(airlineId_nav) {
				$("#airline").val(airlineId_nav);
			}
			
			if($window.airlineIdfromAirlines!=''){		
				$('#airline').val(Number($window.airId));
			}
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
			for (var i = 0; i < platformList.length; i++) {
				var pf = platformList[i];
				$("#platform").append('<option value=' + pf.platform + '>' + pf.platform + '</option>');
			}
			
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
         	var configTypeList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < configTypeList.length; i++) {
				var config = configTypeList[i];
				$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
			}
			
			if(configuration_nav) {
				$("#configType").val(configuration_nav);
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
//			$('#tailsign').val(tailsignList[0].tailsign);
			
			if(tailsign_nav) {
				$("#tailsign").val(tailsign_nav);
			}
			
			$('#tailsign').selectpicker('refresh');
			if(firstTime){
				$scope.loadConfigTabData();
				$scope.loadRevsAndModsData();
				firstTime=false;
			}	
        });
	};
	
	$scope.filter = function() {
		showLoading();
		hideNoRevsAndModsDiv();
		hideNoConfigDataDiv();
		hideRevsAndModsTable();
		hideConfigDataTable();

		$scope.loadConfigTabData();
		$scope.loadRevsAndModsData();
	};
	
	$scope.loadConfigTabData = function() {
		console.log('inside loadConfigTabData');
		var airlineId = "";
		var platform = "";
		var configType = "";
		var tailsign = "";
		
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();		
		tailsign = getSelectedTailsign();
		
        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            configType: configType,
            tailsign: tailsign,
            action: 'GET_CONFIG_DETAILS'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/HardwareRevisionsModsDAO.php', data, config)
        .success(function (data, status, headers, config) {console.log('here success');
        	if(data && data.length > 0) {console.log('here if');
            	loadConfigDataTable(data);
        	} else {console.log('here else');
        		hideLoading();
        		showNoConfigDataDiv();
        		hideConfigDataTable();
        	}
        }).error(function (data, status, headers, config) {});
		// To remove the white space below the page content
		page_content_onresize();
	};
    
	$scope.loadRevsAndModsData = function() {
		console.log('inside loadRevsAndModsData');
		var airlineId = "";
		var platform = "";
		var configType = "";
		var tailsign = "";
		
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();		
		tailsign = getSelectedTailsign();
		
        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            configType: configType,
            tailsign: tailsign
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/getAircraftLrusHardwareData.php', data, config)
        .success(function (data, status, headers, config) {
        	if(data && data.length > 0) {
        		loadRevsAndModsTable(data);
        	} else {
        		hideLoading();
        		showNoRevsAndModsDiv();
        		hideRevsAndModsTable();
        	}
        }).error(function (data, status, headers, config) {
    		showNoRevsAndModsDiv();
    		hideRevsAndModsTable();
        });
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
	
	function showLoading() {
		$('#loadingDiv').show();
	}
	
	function hideLoading() {
		$('#loadingDiv').hide();
	}
	
	function showConfigDataTable() {
		$('#configDataTableDiv').show();
	}
	
	function hideConfigDataTable() {
		$('#configDataTableDiv').hide();
	}
	
	function showRevsAndModsTable() {
		$('#revsAndModsTableDiv').show();
	}
	
	function hideRevsAndModsTable() {
		$('#revsAndModsTableDiv').hide();
	}
	
	function showNoConfigDataDiv() {
		$('#noConfigDataDiv').show();
	}
	
	function hideNoConfigDataDiv() {
		$('#noConfigDataDiv').hide();
	}
	
	function showNoRevsAndModsDiv() {
		$('#noRevsAndModsDiv').show();
	}
	
	function hideNoRevsAndModsDiv() {
		$('#noRevsAndModsDiv').hide();
	}
	
	$scope.loadAirlines();
	
	$scope.resetSearchPanel = function() {
		clearAirlines();
		$scope.loadAirlines();
	};
	
	function loadConfigDataTable(data) {
    	$('#configDataTable').bootstrapTable('destroy');
    	createConfigDataTable();
		$('#configDataTable').bootstrapTable('load',{
			data: data
		});
		showConfigDataTable();
		hideNoConfigDataDiv();
		hideLoading();
	}
		
	function loadRevsAndModsTable(data) {
    	$('#revsAndModsTable').bootstrapTable('destroy');
    	createRevsAndModsTable();
		$('#revsAndModsTable').bootstrapTable('load',{
			data: data
		});
		showRevsAndModsTable();
		hideNoRevsAndModsDiv();
		hideLoading();
	}
	
});
