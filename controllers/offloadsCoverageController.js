function rowStyle(value, row, index) {
	return {
		classes: 'text-nowrap'			
	};
}

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

app.controller('offloadsCoverageController', function($scope, $http, $filter, $window) {
	console.log("inside offloadsCoverageController");
	
	hideNoDataDiv();
	hideOffloadsCoverageTable();
	showLoading();
	$scope.airlineId = $("#airlineId").val();
	
	var firstTime=true;
	var today = new Date();
	var priorDate = new Date(new Date().setDate(today.getDate()-6));
	var startDate = formatDate(priorDate);
	var endDate = formatDate(today);

	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#tailsign').selectpicker();
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


	createOffloadsCoverageTable();
	
	function createOffloadsCoverageTable() {
		$('#offloadsCoverageTable').bootstrapTable({
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
//        	console.log("Platforms: " + JSON.stringify(data));
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
//        	console.log("Config Types: " + JSON.stringify(data));
         	var configTypeList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < configTypeList.length; i++) {
				var config = configTypeList[i];
				$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
			}
			$('#configType').selectpicker('refresh');
        });
        
        $scope.loadTailsign();
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
			
			$('#tailsign').selectpicker('refresh');
			if(firstTime){
				getOffloadsCoverageData();
				firstTime=false;
			}
			getAirlineAcroynm();
        });
	};
	
	$scope.filter = function() {		
		showLoading();
		hideNoDataDiv();
		hideOffloadsCoverageTable();
        getOffloadsCoverageData();
	};
	
    // Retrieve aircrafts data
    function getOffloadsCoverageData() {
    	
        $.ajax({
            type: "GET",
            url: "../ajax/getOffloadsCoverage.php",
            data: {
				'airlineId': getSelectedAirline(),
				'platform': getSelectedPlatform(),
				'configuration': getSelectedConfigType(),
				'tailsign': getSelectedTailsign(),
				'startDate': getStartDate(),
				'endDate': getEndDate()
            },
            success: function(data) {
                loadOffloadsCoverageTable($.parseJSON(data));
            },
            error: function (err) {
                console.log('Error', err);
            }
        });
    }
	
	function loadOffloadsCoverageTable(data) {
    	$('#offloadsCoverageTable').bootstrapTable('destroy');
    	
		var tableColumns = [], tableRows = [];
		
		var columns = data.columns;
		for (i = 0; i < columns.length; i++) {
            tableColumns.push({
				field: columns[i],
                title: columns[i],
				align: 'center',
				cellStyle: function cellStyle(value, row, index, field) {
					if( !isNaN(value) ) {
						if (value > 0) {
							return {
								classes: 'success'			
							};
						} else {
							return {
								classes: 'warning'			
							};
						}
					} else {
						return {
							classes: ''			
						};
					}
				}
            });
        }
		
		var tailsigns = data.tailsigns;
		//console.table(tailsigns);
		for (i = 0; i < tailsigns.length; i++) {
            tableRows.push(tailsigns[i]);
        }
		
		$('#offloadsCoverageTable').bootstrapTable({
            columns: tableColumns,
            data: tableRows,
            //fixedColumns: true,
            //fixedNumber: 1
			exportOptions: {
				"fileName": $("#acronym").val()+"_Offloads_Coverage"
			}
        });
		$('#offloadsCoverageTable').bootstrapTable('refresh');
		
		showOffloadsCoverageTable();
		hideNoDataDiv();
		hideLoading();
		
		// To remove the white space below the page content
		page_content_onresize();
	}
	
	function getAirlineAcroynm(){
		$.ajax({
            type: "GET",
            url: "../ajax/getBiteData.php",
            data: {
                'action': 'getAirlineAcroynm',
                'airlineId': getSelectedAirline()
            },
            success: function(data) {
                airlineAcronym = JSON.parse(data);                                
                $("#acronym").val(airlineAcronym);
                
            },
            error: function(err) {
                console.log('Error', err);
            }
        });
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
	
	function getStartDate() {
		return $('#startDateTimePicker').val();
	}
	
	function getEndDate() {
		return $('#endDateTimePicker').val();
	}
	
	function showLoading() {
		$('#loadingDiv').show();
	}
	
	function hideLoading() {
		$('#loadingDiv').hide();
	}
	
	function showOffloadsCoverageTable() {
		$('#offloadsCoverageTableDiv').show();
	}
	
	function hideOffloadsCoverageTable() {
		$('#offloadsCoverageTableDiv').hide();
	}
	
	function showNoDataDiv() {
		$('#noDataDiv').show();
	}
	
	function hideNoDataDiv() {
		$('#noDataDiv').hide();
	}
	
	$scope.loadAirlines();
	
	$scope.resetSearchPanel = function() {
		firstTime = true;
		showLoading();
		hideNoDataDiv();
		hideOffloadsCoverageTable();
		$scope.loadAirlines();
	};
	
	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}
	
});
