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
	$('#loadingData').hide();
	
	$('#myModal').on('show.bs.modal', function (event) {
		var values = [];
		var button = $(event.relatedTarget);
		var configname = button.data('configname');
		var platform = button.data('platform');
		var previoussw = button.data('previoussw');
		var previousswversion = button.data('previousswversion');
		var latestsw = button.data('latestsw');
		var latestswversion = button.data('latestswversion');
		var futuresw = button.data('futuresw');
		var futureswdate = button.data('futureswdate');

		var modal = $(this)
		modal.find('.modal-body #configname').val(configname);
		modal.find('.modal-body #platform').val(platform);
		modal.find('.modal-body #previoussw').val(previoussw);
		modal.find('.modal-body #previousswversion').val(previousswversion);
		modal.find('.modal-body #latestsw').val(latestsw);
		modal.find('.modal-body #latestswversion').val(latestswversion);
		modal.find('.modal-body #futuresw').val(futuresw);
		modal.find('.modal-body #futureswdate').val(futureswdate);

		
		var errorDiv = document.getElementById("alert");
		if (errorDiv) {
			$('#alert').remove();
		}
	});
	
	$('#editaircraft').click(function() {
		var modal = $('#myModal');
		var airlineId = $('#airline').val();
		console.log(airlineId);
		var configname = modal.find('.modal-body #configname').val();
		var platform = modal.find('.modal-body #platform').val();
		var previoussw = modal.find('.modal-body #previoussw').val();
		var previousswversion = modal.find('.modal-body #previousswversion').val();
		var latestsw = modal.find('.modal-body #latestsw').val();
		var latestswversion = modal.find('.modal-body #latestswversion').val();
		var futuresw = modal.find('.modal-body #futuresw').val();
		var futureswdate = modal.find('.modal-body #futureswdate').val();
	
		$.ajax({
            type: "POST",
            url: "../ajax/editConfigData.php",
            data: {
					'configname': configname,
					'platform': platform,
					'previoussw': previoussw,
					'previousswversion': previousswversion,
					'latestsw': latestsw,
					'latestswversion': latestswversion,
					'futuresw': futuresw,
					'futureswdate':futureswdate,
					'airlineId':airlineId
              },
            success: function(data) {
               //console.log(data);			   

            },
            error: function (err) {
                console.log('Error', err);
            }
        });
 
	});
});

var app = angular.module('myApp', []);

app.controller('fleetConfigurationController', function($scope, $http, $filter, $window) {
	var charts = [];

	console.log("inside fleetConfigurationController");
	
	showLoading();
	hideNoDataDiv();
//	hideChartDiv();
	hideAircraftsTable();
	$scope.airlineId = $("#airlineId").val();
	
	var firstTime=true;
	$('#errorInfo').hide();
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
        
        if(firstTime) {
        	getAirlineAcroynm();
            getFleetConfigDetailsData();
            firstTime = false;
        }        
    };

	$scope.filter = function(){
		getAirlineAcroynm();
		getFleetConfigDetailsData();
	};
	
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
    // Retrieve aircrafts data
    function getFleetConfigDetailsData() {
    	$('#loadingData').show();
    	$('#tableData').hide();
    	$('#configTable').bootstrapTable('destroy');
        $.ajax({
            type: "GET",
            url: "../ajax/getConfiguration.php",
            data: {
				'airlineId': getSelectedAirline(),
				'platform': getSelectedPlatform(),
				'configuration': getSelectedConfigType()
            },
            success: function(data) {
            	var jsonData = $.parseJSON(data);

				var d = new Date();
				var day = d.getDate();
				var month = d.getMonth() + 1;
				var year = d.getFullYear();
				console.log(jsonData.length);
				if(jsonData.length>0){
					$('#errorInfo').hide();
					$('#tableInfo').show();
				}else{
					$('#errorInfo').show();
					$('#tableInfo').hide();
				}
                $('#configTable').bootstrapTable({
                    data: jsonData,
					exportOptions: {
						fileName:  $("#acronym").val()+'_Fleet_Configurations_' + year + '_' + month + '_' + day
					}
                });
                $('#tableData').show();
                $('#loadingData').hide();
            },
            error: function (err) {
            	$('#loadingData').hide();
                console.log('Error', err);
            }
        });
		
		// To remove the white space below the page content
		page_content_onresize();
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
		$scope.loadAirlines();
	};
});
