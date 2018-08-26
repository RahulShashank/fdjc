function rowStyle(value, row, index) {
	return {
		classes: 'text-nowrap'			
	};
}

function cellStyle(value, row, index) {
	var classes = ['success', 'warning'];

	var id=row.id;
	var nwsoftware=row.software;
	var nwCustsoftware=row.LatestCustSW;
	
	if(nwCustsoftware!=null){
		if (nwsoftware==nwCustsoftware) {
			return {
				classes: 'success'
			};
		}else{
			return {
				classes: 'warning'
			};
		}
	}else{
		return {
			classes: ''			
		};
	}
	return {};
}

function linkTimeline(value, row, index, field) {
	return "<a href=\"AircraftTimeline.php?aircraftVisited=false&aircraftId=" + row['id'] + "\" style=\"text-decoration: none;\">" + value + "</a>";
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
	
	$('#editAircraftModal').on('show.bs.modal', function (event) {
		$('#editAircraftsSuccessDiv').hide();
		$('#editAircraftsFailureDiv').hide();
		var values = [];
		var button = $(event.relatedTarget);
		var tailsign = button.data('tailsign');
		var msn = button.data('msn');
		var status = button.data('status');
		var software = button.data('software');
		var swpartno = button.data('swpartno');
		var swinstalled = button.data('swinstalled');
		var swbaseline = button.data('swbaseline');
		var mapversion = button.data('mapversion');
		var content = button.data('content');
		var modal = $(this)
		modal.find('.modal-body #tailsign').val(tailsign);
		modal.find('.modal-body #msn').val(msn);
		modal.find('.modal-body #status').val(status).selectpicker('refresh');
		modal.find('.modal-body #software').val(software);
		modal.find('.modal-body #swpartno').val(swpartno);
		modal.find('.modal-body #swinstalled').val(swinstalled);
		modal.find('.modal-body #swbaseline').val(swbaseline);
		modal.find('.modal-body #mapversion').val(mapversion);
		modal.find('.modal-body #content').val(content);
		
		var errorDiv = document.getElementById("alert");
		if (errorDiv) {
			$('#alert').remove();
		}
	});
	
	$('#editaircraft').click(function(event) {
		
		var modal = $('#editAircraftModal');
		var tailsign = modal.find('.modal-body #tailsign').val();
		var msn = modal.find('.modal-body #msn').val();
		var status = modal.find('.modal-body #status').val();
		var swbaseline = modal.find('.modal-body #swbaseline').val();
		var software = modal.find('.modal-body #software').val();
		var swpartno = modal.find('.modal-body #swpartno').val();
		var swinstalled = modal.find('.modal-body #swinstalled').val();
		var mapversion = modal.find('.modal-body #mapversion').val();
		var content = modal.find('.modal-body #content').val();
	
		$.ajax({
            type: "POST",
            url: "../ajax/editAircraft.php",
            data: {
					'tailsign': tailsign,
					'msn':msn,
					'swbaseline':swbaseline,
					'software': software,
					'swpartno': swpartno,
					'swinstalled': swinstalled,
					'mapversion': mapversion,
					'content':content,
					'maintenanceStatus': status
              },
            success: function(data) {
            	var response = JSON.parse(data);
            	if(response.state == 0) { 
	        		$('#editAircraftsSuccessDiv').html(response.message);
	        		$('#editAircraftsSuccessDiv').show();
            		setTimeout(function(){
            			$("#closeModal").click();
            			$("#filter").click();
            		}, 2000);
               } else {
            	   $('#editAircraftsFailureDiv').html(response.message);
            	   $('#editAircraftsFailureDiv').show();
               }
            },
            error: function (err) {
                console.log('Error', err);
            }
        });
	});
});

var app = angular.module('myApp', []);

app.controller('fleetStatusController', function($scope, $http, $filter, $window) {
	var charts = [];

	console.log("inside fleetStatusController");
	
	showLoading();
	hideNoDataDiv();
//	hideChartDiv();
	hideAircraftsTable();
	$scope.airlineId = $("#airlineId").val();
	
	var firstTime=true;

	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#tailsign').selectpicker();

	createAircraftsTable();
	
	function createAircraftsTable() {
		$('#aircraftsTable').bootstrapTable({
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
				$('#airline').selectpicker('refresh');
				$scope.loadPlatforms();
			}else{
				$scope.loadPlatforms();
			}
				
	        
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
        
        if(firstTime) {
//            loadChartData('software');
            loadChart();
            getFleetDetailsData();
            firstTime = false;
        }
    };

	$scope.filter = function() {
		showLoading();
		hideNoDataDiv();
		hideAircraftsTable();
//		hideChartDiv();
//		loadChartData('software');
        getFleetDetailsData();
        loadChart();
		hideLoading();
	};
	
	function loadChart() {
		$.ajax({
			type: "GET",
			url: "../ajax/getFleetStatus.php",
			data: {
				'airlineId': getSelectedAirline(),
				'platform': getSelectedPlatform(),
				'configuration': getSelectedConfigType(),
				'dataChartType': 'software'
			},
			success: function(data) {
				$('#softwareChart').html(data);
			},
			error: function (err) {
				console.log('Error', err);
			}
		});
	}
	
    // Retrieve aircrafts data
    function getFleetDetailsData() {
    	
        $.ajax({
            type: "GET",
            url: "../ajax/getAircrafts.php",
            data: {
				'airlineId': getSelectedAirline(),
				'platform': getSelectedPlatform(),
				'configuration': getSelectedConfigType()
            },
            success: function(data) {
                loadAircraftsTable($.parseJSON(data));
            },
            error: function (err) {
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
	
	function showLoading() {
		$('#loadingDiv').show();
	}
	
	function hideLoading() {
		$('#loadingDiv').hide();
	}
	
	function showChartDiv() {
		$('#softwareChart').show();
	}
	
	function hideChartDiv() {
		$('#softwareChart').hide();
	}
	
	function showAircraftsTable() {
		$('#aircraftsTableDiv').show();
	}
	
	function hideAircraftsTable() {
		$('#aircraftsTableDiv').hide();
	}
	
	function showNoDataDiv() {
		$('#noDataDiv').show();
	}
	
	function hideNoDataDiv() {
		$('#noDataDiv').hide();
	}
	
	$scope.loadAirlines();
	
	$scope.resetSearchPanel = function() {
//		firstTime = true;
//		showLoading();
//		hideNoDataDiv();
//		hideAircraftsTable();
//		hideChartDiv();
		$scope.loadAirlines();
	};
	
	function loadAircraftsTable(data) {
    	$('#aircraftsTable').bootstrapTable('destroy');
    	createAircraftsTable();
		$('#aircraftsTable').bootstrapTable('load',{
			data: data
		});
		showAircraftsTable();
		hideNoDataDiv();
		hideLoading();
	}
});
