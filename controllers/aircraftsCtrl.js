$(document).ready(function(){

	$('#airline').selectpicker({                              
        size: 6
  	});
  	
    $('#configType').selectpicker({                              
			 size: 6
  	});
      	
    $('#platform').selectpicker({                              
			 size: 6
  	});
      	
    $('#status').selectpicker({                              
			 size: 6
  	});

	$('#airline').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadPlatforms();
	});

	$('#platform').on('change', function(){		
	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
	});
});

var app = angular.module('myApp', []);

app.controller('aircraftsCtrl', function($scope, $http, $filter, $window) {

	console.log("inside aircraftsController");
	
	$('#loadingDiv').hide();
	$scope.airlineId = $("#airlineId").val();
	$scope.allData = "";
	var firstTime=true;
	
	$('#airline').selectpicker();
	$('#configType').selectpicker();
	$('#platform').selectpicker();
	$('#status').selectpicker();


	$scope.loadAirlines = function() {
		console.log('here with airlineIds: ' + $('#airlineIds').val());
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
			$('#airline').selectpicker('refresh');
			$scope.loadPlatforms();
	    });
   };
   
   $scope.loadPlatforms = function() {console.log('inside load platform');
	   clearPlatformSelect();
	   clearConfigurationSelect();
	   
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
       	console.log("Platforms: " + JSON.stringify(data));
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
	   
	   var airlineId = "";
	   var platform = "";

	   airlineId = getSelectedAirline();
	   platform = getSelectedPlatform();
	   
	   console.log('airline Id in loadConfigType(): ' + airlineId);
	   console.log('platform in loadConfigType(): ' + platform);
	   
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
       	console.log("Config Types: " + JSON.stringify(data));
        	var configTypeList = JSON.parse(JSON.stringify(data));
			for (var i = 0; i < configTypeList.length; i++) {
				var config = configTypeList[i];
				$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
			}
			$('#configType').selectpicker('refresh');
       });
       
       if(firstTime) {console.log('first Time #################');
    	   getAircrafts();
           firstTime = false;
       }
   };

    $('#filter').click(function(){ 
    	getAircrafts();
    });
    
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
		
        $http.get("../ajax/getAircrafts.php?airlineId="+airlineId + "&platform=" + platform + "&configuration=" + configType)
//        $http.get("../ajax/getAircrafts.php?airlineId="+airlineId)
        .success(function (data) {
            console.log(data);
            $scope.aircrafts = data;
            $("#loadingDiv").hide();
        });
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

    
    $('#reset').click(function(){ 
    	$scope.loadAirlines();
    	 $('#status').val('');
    	 $('#status').selectpicker('refresh');
    });
	
	function clearAirlines() {
        $('#airline').empty();
        $('#airline').selectpicker('refresh');
	}
	
	function clearConfigurationSelect(){
		$('#configType').empty();
        $('#configType').selectpicker('refresh');
	}
	
	function clearPlatformSelect() {
        $('#platform').empty();
        $('#platform').selectpicker('refresh');
	}
	
	function clearstatusSelect() {
        $('#status').empty();
        $('#status').selectpicker('refresh');
	}

	function getSelectedAirline() {
		return $('#airline').val();
	}

	function getSelectedConfigType() {
		return $('#configType').val();
	}

	function getSelectedPlatform() {
		return $('#platform').val();
	}
	
	function showLoadingDiv() {
        $("#loadingDiv").show();
	}

	function hideLoadingDiv() {
        $("#loadingDiv").hide();
	}

	$scope.loadAirlines();
	

	
});
