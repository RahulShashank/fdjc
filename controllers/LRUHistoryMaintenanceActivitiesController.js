app.controller('LRUHistoryMaintenanceActivitiesController', function($scope, $http, $log, $window, $timeout, $parse) {

	$log.log("inside LRUHistoryMaintenanceActivitiesController");
	$('#dataDiv').hide();  
	$('#ErrorDiv').show(); 
	$scope.airlineId = $("#airlineId").val();
	$scope.showRemarksAlert=false;
	$scope.allData = "";
	
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	var criticalArray = [];
	var warningArray = [];
	var noIssueArray = [];
	
	var today = new Date();
	var priorDate = new Date(new Date().setDate(today.getDate()-6));
	var startDate = formatDate(priorDate);
	var endDate = formatDate(today);
	$scope.loading = true;
	$scope.startDate = startDate;
	$scope.endDate = endDate;
	var firstTime=true;
	var aircraft;
	$('#startDateTimePickerlru').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: startDate
	});
	
	$('#endDateTimePickerlru').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: endDate
	});
	
	$('#startDateTimePickerhw').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: startDate
	});
	
	$('#endDateTimePickerhw').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		value: endDate
	});
	
	$('#errorInfo').hide();	
	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#software').selectpicker();
	$('#tailsign').selectpicker();

	var timeline;
	var flightLegs = '';

	$scope.loadAirlineslru = function() {
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
				$("#airlinelru").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
				
			}
			$('#airlinelru').selectpicker('refresh');
			if(maintenanceActivitiesVisited){
				$('#airlinelru').val($window.session_AirlineIdlru);
				$('#airlinelru').selectpicker('refresh');
				if($window.session_StartDatelru!='' && $window.session_StartDatelru!=undefined){
					$('#startDateTimePickerlru').val($window.session_StartDatelru);
					$('#endDateTimePickerlru').val($window.session_EndDatelru);
				}	
				
				$('#serialNumberlru').val($window.serialNumberlru);
				getSerialNumberData();
			}
				
	       // $scope.loadPlatformshw();
	    });
   };
   
   $scope.loadAirlineshw = function() {
	   clearAirlineshw();
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
				
				$("#airlinehw").append('<option value=' + al.id + '>' + al.name + ' ('+al.acronym+')</option>');
			}
			
			$('#airlinehw').selectpicker('refresh');
			if(maintenanceActivitiesVisited){
				$('#airlinehw').val($window.session_AirlineIdhw);
				$('#airlinehw').selectpicker('refresh');
			}	
	        $scope.loadPlatformshw();
	    });
  };

    $scope.loadPlatformshw = function() {
    	clearPlatformSelecthw();
    	clearConfigurationSelecthw();
    	clearSoftwareSelecthw();
    	clearTailsignSelecthw();    	
    	
		var airlineId = "";
		airlineId = getSelectedAirlinehw();
		
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
			//$("#platform").append('<option value="">All</option>');
         	var platformList = JSON.parse(JSON.stringify(data));
         	
			for (var i = 0; i < platformList.length; i++) {
				var pf = platformList[i];
				$("#platformhw").append('<option value=' + pf.platform + '>' + pf.platform + '</option>');
			}
			$('#platformhw').selectpicker('refresh');
			if(maintenanceActivitiesVisited){
				if($window.session_Platformhw!='' && $window.session_Platformhw!=undefined){
					var string = $window.session_Platformhw;
			    	var array = string.split(",");
			    	$('#platformhw').val(array);	
					$('#platformhw').selectpicker('refresh');
				}
			}
            $scope.loadConfigTypeshw();
        })
        .error(function (data, status, header, config) {
        });
    };

    $scope.loadConfigTypeshw = function() {
    	clearConfigurationSelecthw();
    	clearSoftwareSelecthw();
    	clearTailsignSelecthw();    	
    	
		var airlineId = "";
		var platform = "";

		airlineId = getSelectedAirlinehw();
		platform = getSelectedPlatformhw();

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
			//$("#configType").append('<option value="">All</option>');
         	var configTypeList = JSON.parse(JSON.stringify(data));
         	
			for (var i = 0; i < configTypeList.length; i++) {
				var config = configTypeList[i];
				$("#configTypehw").append('<option value="' + config.configType + '">' + config.configType + '</option>');
			}
			$('#configTypehw').selectpicker('refresh');
			if(maintenanceActivitiesVisited){
				if($window.session_Confighw!='' && $window.session_Confighw!=undefined){
					var string = $window.window.session_Confighw;
			    	var array = string.split(",");
			    	$('#configTypehw').val(array);
					$('#configTypehw').selectpicker('refresh');
				}
			}
            $scope.loadTailsignhw();
        });
    };
    
	$scope.loadTailsignhw = function() {
    	clearTailsignSelecthw();    	

		var airlineId = "";
		var platform = "";
		var configType = "";
		var software = "";
		
		airlineId = getSelectedAirlinehw();
		platform = getSelectedPlatformhw();
		configType = getSelectedConfigTypehw();		
		software = getSelectedSoftwareshw();
		
        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            configType: configType,
            software: software,            
            action: 'GET_TS_FOR_PLATFORM_CONFIG'
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
				$("#tailsignhw").append('<option value="' + ts.tailsign + '">' + ts.tailsign + '</option>');
			}
			//$('#tailsignhw').val(tailsignList[0]);
			$('#tailsignhw').selectpicker('refresh');
			if(firstTime){
				if(maintenanceActivitiesVisited){
					//$('#tailsign').val($window.session_Tailsign);
					if($window.session_Tailsignhw!='' && $window.session_Tailsignhw!=undefined){
						var string = $window.window.session_Tailsignhw;
				    	var array = string.split(",");
				    	$('#tailsignhw').val(array);
						$('#tailsignhw').selectpicker('refresh');
						
						$('#startDateTimePickerhw').val($window.session_StartDatehw);
						$('#endDateTimePickerhw').val($window.session_EndDatehw);
						$('#hostname').val($window.hostNamelru);
						getHWPartNumberData();
						firstTime=false;
					}else{
						if($window.session_StartDatehw!='' && $window.session_StartDatehw!=undefined){
							$('#startDateTimePickerhw').val($window.session_StartDatehw);
							$('#endDateTimePickerhw').val($window.session_EndDatehw);
						}	
						$('#hostname').val($window.hostNamelru);
						getHWPartNumberData();
						firstTime=false;
					}
				}else{
					getHWPartNumberData();
					firstTime=false;
				}
			}else{
				if(maintenanceActivitiesVisited){
					if($window.session_Tailsignhw!='' && $window.session_Tailsignhw!=undefined){
						var string = $window.window.session_Tailsignhw;
				    	var array = string.split(",");
				    	$('#tailsignhw').val(array);
						$('#tailsignhw').selectpicker('refresh');
					}else{
						if($window.session_StartDatehw!='' && $window.session_StartDatehw!=undefined){
							$('#startDateTimePickerhw').val($window.session_StartDatehw);
							$('#endDateTimePickerhw').val($window.session_EndDatehw);
						}						
						$('#hostname').val($window.hostNamelru);
						getHWPartNumberData();
					}
				}else{
					if(firstTime){
						getHWPartNumberData();
						firstTime=false;
					}
				}
			}	
        });
	};
	

	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}

	
	
	function clearAirlineslru() {
        $('#airlinelru').empty();
        $('#airlinelru').selectpicker('refresh');
	}
	
	function clearAirlineshw() {
        $('#airlinehw').empty();
        $('#airlinehw').selectpicker('refresh');
	}
	
	function clearPlatformSelecthw() {
        $('#platformhw').empty();
        $('#platformhw').selectpicker('refresh');
	}
	
	function clearConfigurationSelecthw() {
        $('#configTypehw').empty();
        $('#configTypehw').selectpicker('refresh');
	}
	
	function clearSoftwareSelecthw() {
        $('#softwarehw').empty();
        $('#softwarehw').selectpicker('refresh');
	}
	
	function clearTailsignSelecthw() {
        $('#tailsignhw').empty();
        $('#tailsignhw').selectpicker('refresh');
	}
	
	function getSelectedAirlinelru() {
		return $('#airlinelru').val();
	}
	
	function getSelectedAirlinehw() {
		return $('#airlinehw').val();
	}	
	
	function getSelectedPlatformhw() {
		return $('#platformhw').val();
	}
	
	function getSelectedConfigTypehw() {
		return $('#configTypehw').val();
	}
	
	function getSelectedSoftwareshw() {
		return $('#softwarehw').val();
	}
	
	function getSelectedTailsignshw() {
		return $('#tailsignhw').val();
	}
	
	$scope.resetlru = function() {
		clearAirlineslru();
		$('#startDateTimePickerlru').datetimepicker({value: $window.startDateTime});
		$('#endDateTimePickerlru').datetimepicker({value: $window.endDateTime});
		$scope.loadAirlineslru();
		$('#serialNumberlru').val('');
	};
	
	$scope.loadAirlineslru();
	
	// Prevent submitting form on enter key
	$(window).keydown(function(event){
		if(event.keyCode == 13) {
			event.preventDefault();
			return false;
		}
	});
	
	var data = {
		removals: {
			groups: [],
			items: [],
		}
	};
	//createTimeline(data);
	
    $("#search").click(function() {
    	if($("#serialNumberlru").val().trim()!=''){   
    		document.getElementById("errormsgForSerial").style.display = "none";        	
        	getSerialNumberData();
        }else{        	
        	document.getElementById("errormsgForSerial").style.display = "block";
        }
    	
    });
    
    $("#resetlru").click(function() { 
    	$scope.resetlru();
    });
    
    $("#searchSerial").click(function() { 
    	if($("#hostname").val().trim()!=''){   
    		document.getElementById("errormsgForhostname").style.display = "none";
    		getHWPartNumberData();
        }else{        	
        	document.getElementById("errormsgForhostname").style.display = "block";
        }    	
    });
    
    $("#resetsrc").click(function() { 
    	clearAirlineshw();
		$('#startDateTimePickerhw').datetimepicker({value: $window.startDateTime});
		$('#endDateTimePickerhw').datetimepicker({value: $window.endDateTime});		
		$('#hostname').val('');
    	$scope.loadAirlineshw();
    });
    
    
    function getSerialNumberData(type, table) {
    	$('#dataDiv').show(); 
    	$('#loadingHistoryTimeline').show(); 
    	$('#ErrorDiv').hide(); 
    	$('#dataHistoryDiv').hide();
		airlineId = getSelectedAirlinelru();
		var serialNumber=$('#serialNumberlru').val();
		$('#serialTable').bootstrapTable("destroy");
		$.ajax({
			type: "GET",
			url: "../ajax/getSerialNumberDataSearch.php",
			data: {
				//aircraftId:aircraft.id,
				airlineId:  airlineId, 
                serialNumber: serialNumber,
				startDateTime: $("#startDateTimePickerlru").val(), 
				endDateTime: $("#endDateTimePickerlru").val(),
				submenu:'lruHistory_SerialNumber'
			},
			success: function(data) {
				if (data.indexOf("Error creating statement") >= 0) {
					$('#loadingHistoryTimeline').hide(); 
					$('#dataDiv').hide();  
			    	$('#ErrorDiv').show(); 
				}else{
					var jsonData = $.parseJSON(data);
					$('#serialTable').bootstrapTable({
						data: jsonData,
						exportOptions: {
							fileName: 'LRUHistory_SerialNumber'
						}
					});			    	
					if(jsonData.length>0){
						$('#dataHistoryDiv').show();
						$('#loadingHistoryTimeline').hide();
						$('#serialTable').bootstrapTable("refresh");
					}else{
						$('#loadingHistoryTimeline').hide(); 
						$('#dataDiv').hide();  
				    	$('#ErrorDiv').show(); 
					}
				}
			},
			error: function (err) {
				console.log('Error', err);
				$('#loadingHistoryTimeline').hide(); 
				
			}
		});
	}
    
    function getHWPartNumberData(type, table) {
    	$('#dataDivhw').show(); 
    	$('#loadingHistoryTimelinehw').show(); 
    	$('#ErrorDivhw').hide(); 
    	$('#dataHistoryDivHW').hide();
		airlineId = getSelectedAirlinehw();
		platform = getSelectedPlatformhw();
		configType = getSelectedConfigTypehw();	
		tailsign = getSelectedTailsignshw();
		
		var hostName=$('#hostname').val();
		$('#hwTable').bootstrapTable("destroy");
		$.ajax({
			type: "GET",
			url: "../ajax/getHWPartNumberData.php",
			data: {
				//aircraftId:aircraft.id,
				airlineId:  airlineId, 
                platform:platform,
                configType:configType,
                tailsign:tailsign,
                hostname:hostName,
				startDateTime: $("#startDateTimePickerhw").val(), 
				endDateTime: $("#endDateTimePickerhw").val(),
				submenu:'lruHistory_partNumber'
			},
			success: function(data) {
				if (data.indexOf("Error creating statement") >= 0) {
					$('#loadingHistoryTimelinehw').hide(); 
					$('#dataDivhw').hide();  
			    	$('#ErrorDivhw').show(); 
				}else{
					var jsonData = $.parseJSON(data);
					$('#hwTable').bootstrapTable({
						data: jsonData,
						exportOptions: {
							fileName: 'LRUHistory_HWPartNumber'
						}
					});
					if(jsonData.length>0){
						$('#dataHistoryDivHW').show();
						$('#loadingHistoryTimelinehw').hide();
						$('#hwTable').bootstrapTable("refresh");
					}else{
						$('#loadingHistoryTimelinehw').hide(); 
						$('#dataDivhw').hide();  
				    	$('#ErrorDivhw').show(); 
					}
			    	
				}
			},
			error: function (err) {
				console.log('Error', err);
				$('#loadingHistoryTimelinehw').hide(); 
				
			}
		});
	}
	
	$scope.showlruHistoryData=function(){
		console.log('changed');
		if($('#lruHistoryData').val()=='serialNumber'){
			$('#serialNumberDiv').show();
			$('#partNumberDiv').hide();
			var today = new Date();
			var priorDate = new Date(new Date().setDate(today.getDate()-6));
			var startDate = formatDate(priorDate);
			var endDate = formatDate(today);
			$('#startDateTimePickerlru').datetimepicker({
				timepicker:false,
				format:'Y-m-d',
				value: startDate
			});
	
			$('#endDateTimePickerlru').datetimepicker({
				timepicker:false,
				format:'Y-m-d',
				value: endDate
			});
			
		}else{
			$('#serialNumberDiv').hide();
			$('#partNumberDiv').show();
			getHWPartNumberData();
			$scope.loadAirlineshw();
			var today = new Date();
			var priorDate = new Date(new Date().setDate(today.getDate()-6));
			var startDate = formatDate(priorDate);
			var endDate = formatDate(today);
			$('#startDateTimePickerhw').datetimepicker({
				timepicker:false,
				format:'Y-m-d',
				value: startDate
			});
	
			$('#endDateTimePickerhw').datetimepicker({
				timepicker:false,
				format:'Y-m-d',
				value: endDate
			});
		}
		document.getElementById("errormsgForSerial").style.display = "none";
		document.getElementById("errormsgForhostname").style.display = "none";
	}
	
	$('#serialNumberDiv').show();
	$('#partNumberDiv').hide();
	$scope.loadAirlineshw();
	
	$("#serialNumberlru").keyup(function(){        
		if($("#serialNumberlru").val().trim()!=''){   
    		document.getElementById("errormsgForSerial").style.display = "none";
        }else{        	
        	document.getElementById("errormsgForSerial").style.display = "block";
        }
    });
	
	$("#hostname").keyup(function(){        
		if($("#hostname").val().trim()!=''){   
    		document.getElementById("errormsgForhostname").style.display = "none";
        }else{        	
        	document.getElementById("errormsgForhostname").style.display = "block";
        }
    });
	

});

