angular.module('myApp', []);
angular.module('myApp').controller('ConnectivityController', function($scope, $http,$window) {

	var firstTime=true;
	$('#loadingDiv').hide();
	$('#loadingTabData').hide();
	$(document).ready(function(){
		$('#airline').on('change', function(){
		    angular.element($("#ctrldiv")).scope().loadTailsign();
		  });
		
		$('#tailsign').on('change', function(){
		    angular.element($("#ctrldiv")).scope().getAircraftIdnISP();
		  });
		
		$('#airlinetable').on('change', function(){
		    angular.element($("#ctrldiv")).scope().loadTailsignforTable();
		  });
		
		$('#tailsigntable').on('change', function(){
		    angular.element($("#ctrldiv")).scope().getAircraftIdforTable();
		  });
		
		$('#airline').selectpicker({                              
	        size: 6
	  	});
		
		$('#tailsign').selectpicker({                              
			 size: 6
		});
		
		$('#airlinetable').selectpicker({                              
	        size: 6
	  	});
		
		$('#tailsigntable').selectpicker({                              
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
				//$('#airline').val(airlineList[0].id);
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
				$('#tailsign').val(tailsignList[0]);
				$('#tailsign').selectpicker('refresh');
				$scope.getAircraftIdnISP();
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
            },
            error: function(err) {
                console.log('Error', err);
            }
        });
    }
	
	 $scope.getAircraftIdnISP=function() {
			$('#airlineId').val($('#airline').val());
	        var tailsign = $("#tailsign").val();
	        $.ajax({
	            type: "GET",
	            url: "../ajax/getBiteData.php",
	            data: {
	                'action': 'getAircraftIdnISP',
	                'tailsign': tailsign
	            },
	            success: function(data) {
	                var jsonData = JSON.parse(data);
	                $('#aircraftId').val(jsonData[0].id);
	                $('#aircraftISP').val(jsonData[0].ISP);
	                var str=$('#aircraftISP').val();
	                if (str=="KaNoVAR") {
	                	var element =document.getElementById('connectivityDropzone'); 
	                	element.action="uploadKaConnectivityFiles.php";
	                }else{
	                	var element =document.getElementById('connectivityDropzone'); 
	                	element.action="uploadConnectivityFiles.php";
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
	        	action: "GET_TS_ARRAY_FOR_AIRLINEARRAY_AND_ISP",
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
				//$scope.getAircraftIdforTable();
				if(firstTime){
					getConnectivityHistory();
					firstTime=false;
				}
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
