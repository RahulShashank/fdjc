angular.module('myApp', []);
angular.module('myApp').controller('ConnectivityStatusController', function($scope, $http,$window) {

	var firstTime=true;
	$('#loadingDiv').hide();
	$(document).ready(function(){		
		
		$('#airlinetable').on('change', function(){
		    angular.element($("#ctrldiv")).scope().loadTailsignforTable();
		  });
		
		$('#tailsigntable').on('change', function(){
		    angular.element($("#ctrldiv")).scope().getAircraftIdforTable();
		  });
		
		$('#airlinetable').selectpicker({                              
	        size: 6
	  	});
		
		$('#tailsigntable').selectpicker({                              
			 size: 6
		});
		
	});	

	var firstTime=true;	
	
	$scope.loadAirlinesforTable = function() {
		clearTableAirlines();		
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
					$("#airlinetable").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
				}
				
				if($window.airline!=""){					
					$('#airlinetable').val($window.session_airline);
					$('#airlinetable').selectpicker('refresh');
				}				
				$('#airlinetable').selectpicker('refresh');
				
		        $scope.loadTailsignforTable();
			},
			error: function (err) {
				console.log("Error Received in Airline : ",err);
			}
		});
   };   
    
    $scope.loadTailsignforTable = function() {
    	clearTailsignTableSelect(); 
		var airlineId = "";
		
		airlineId = getSelectedAirlineTable();
        
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
					$("#tailsigntable").append('<option value="' + ts.tailsign + '">' + ts.tailsign + '</option>');
				}
				$('#tailsigntable').val(tailsignList[0]);
				
				if($window.tailsign!=""){					
					$('#tailsigntable').val($window.session_tailsign);
					$('#tailsigntable').selectpicker('refresh');
				}				
				$('#tailsigntable').selectpicker('refresh');
				$scope.getAircraftIdforTable();
			},
			error: function (err) {
				console.log("Error Received in Config Type : ",err);
			}
		});        
	};
	
	 $scope.getAircraftIdforTable=function() {
	        var tailsign = $("#tailsigntable").val();
	        $.ajax({
	            type: "GET",
	            url: "../ajax/getBiteData.php",
	            data: {
	                'action': 'getAircraftId',
	                'tailsign': tailsign
	            },
	            success: function(data) {
	                aircraftId = data;
	                $('#aircraftIdTable').val(aircraftId);
	                if($window.session_firstTime==false){
	                	getConnectivityStatus();
	                	$window.firstTime=true;
	                }	                
	            },
	            error: function(err) {
	                console.log('Error', err);
	            }
	        });
	 }
	 
	 function clearTableAirlines() {
		 $('#airlinetable').empty();
		 $('#airlinetable').selectpicker('refresh');
	 }

	function getSelectedAirlineTable() {		
		return $('#airlinetable').val();
	}
		
	function clearTailsignTableSelect() {
		$('#tailsigntable').empty();
		$('#tailsigntable').selectpicker('refresh');
	}
		
	$scope.loadAirlinesforTable();

});
