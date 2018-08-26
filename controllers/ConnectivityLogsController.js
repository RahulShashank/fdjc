angular.module('myApp', []);
angular.module('myApp').controller('ConnectivityLogsController', function($scope, $http,$window) {

	var firstTime=true;
	$('#loadingDiv').hide();
	$(document).ready(function(){
		
		$('#airline').on('change', function(){
		    angular.element($("#ctrldiv")).scope().loadTailsign();
		  });
		
		$('#tailsign').on('change', function(){
		    angular.element($("#ctrldiv")).scope().getAircraftIdforTable();
		  });
		
		
		$('#airline').selectpicker({                              
	        size: 6
	  	});
		
		$('#tailsign').selectpicker({                              
			 size: 6
		});
		
	});	
	
	$('#airline').selectpicker();
	$('#tailsign').selectpicker();


	$scope.loadAirlines = function() {
		clearAirlines();		
		$.ajax({
			type: "GET",
			dataType: "json",
			url: "../common/AirlineDAO.php",
			data: {
	        	action: "GET_AIRLINES_BY_IDS_ISP",
	            airlineIds: $("#airlineIds").val()
	        },
			success: function(data) {
				var airlineList = JSON.parse(JSON.stringify(data));
				for (var i = 0; i < airlineList.length; i++) {
					var al = airlineList[i];
					$("#airline").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
				}
				if($window.airline!=""){					
					$('#airline').val($window.session_airline);
					$('#airline').selectpicker('refresh');
				}	
				$('#airline').selectpicker('refresh');
					
		        $scope.loadTailsign();
			},
			error: function (err) {
				console.log("Error Received in Airline : ",err);
			}
		});
   };   
    
    $scope.loadTailsign = function() {
    	clearTailsignSelect(); 
		var airlineId = "";
		
		airlineId = getSelectedAirline();
        
		if(airlineId==null){	
			if($("#airlineIds").val()!="-1"){
				airlineId = $("#airlineIds").val().split(',');
			}
		}
		
        $.ajax({
			type: "GET",
			dataType: "json",
			url: "../common/AirlineDAO.php",
			data: {
	        	action: "GET_TS_FOR_AIRLINEARRAY_AND_ISP",
	        	airlineId: airlineId
	        },
			success: function(data) {
				var tailsignList = JSON.parse(JSON.stringify(data));

				for (var i = 0; i < tailsignList.length; i++) {
					var ts = tailsignList[i];
					$("#tailsign").append('<option value="' + ts.tailsign + '">' + ts.tailsign + '</option>');
				}
				if($window.tailsign!=""){					
					$('#tailsign').val($window.session_tailsign);
					$('#tailsign').selectpicker('refresh');
				}	
				$('#tailsign').val(tailsignList[0]);
				$('#tailsign').selectpicker('refresh');
				$scope.getAircraftId();
				
			},
			error: function (err) {
				console.log("Error Received in Config Type : ",err);
			}
		});        
	};
	
	 $scope.getAircraftId=function() {
		$('#airlineId').val($('#airline').val());
        var tailsign = $("#tailsign").val();
        $.ajax({
            type: "GET",
            url: "../ajax/getBiteData.php",
            data: {
                'action': 'getAircraftId',
                'tailsign': tailsign
            },
            success: function(data) {
                aircraftId = data;
                $('#aircraftId').val(aircraftId);
                if(firstTime){
					//getConnectivityLogs();
					firstTime=false;
				}
            },
            error: function(err) {
                console.log('Error', err);
            }
        });
    }

	$scope.loadAirlines();
	
	function clearAirlines() {
        $('#airline').empty();
        $('#airline').selectpicker('refresh');
	}

	function getSelectedAirline() {		
		return $('#airline').val();
	}
	
	function clearTailsignSelect() {
        $('#tailsign').empty();
        $('#tailsign').selectpicker('refresh');
	}
	
	function getSelectedTailsign() {
		return $('#tailsign').val();
	}

});
