var app = angular.module('myApp', []);

app.controller('MaintenanceActivitiesController', function($scope, $http, $log, $window, $timeout, $parse) {

	$log.log("inside MaintenanceActivitiesController");
	$('#loadingDiv').hide();
	$scope.airlineId = $("#airlineId").val();
	$scope.showRemarksAlert=false;
	$scope.allData = "";
	
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	var criticalArray = [];
	var warningArray = [];
	var noIssueArray = [];
	
	var airlineId_nav = $window.airlineId_nav;
	var platform_nav = $window.platform_nav;
	var configuration_nav = $window.configuration_nav;
	var software_nav = $window.software_nav;
	var tailsign_nav = $window.tailsign_nav;
	
	var today = new Date();
	var priorDate = new Date(new Date().setDate(today.getDate()-6));
	var startDate = formatDate(priorDate);
	var endDate = formatDate(today);
	$scope.loading = true;
	$scope.startDate = startDate;
	$scope.endDate = endDate;
	var firstTime=true;
	var aircraft;
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
	$('#errorInfo').hide();	
	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#software').selectpicker();
	$('#tailsign').selectpicker();

	var timeline;
	var flightLegs = '';
	
	var maintenanceActivitiesVisited=$window.maintenanceActivitiesVisited;
	console.log('maintenanceActivitiesVisited ...'+maintenanceActivitiesVisited);

	$scope.loadAirlines = function() {
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

			if(airlineId_nav) {
				$("#airline").val(airlineId_nav);
			}
			
			$('#airline').selectpicker('refresh');
			if(maintenanceActivitiesVisited){
				$('#airline').val($window.session_AirlineId);
				$('#airline').selectpicker('refresh');
			}
	        $scope.loadPlatforms();
	    });
   };

    $scope.loadPlatforms = function() {
    	clearPlatformSelect();
    	clearConfigurationSelect();
    	clearSoftwareSelect();
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
			//$("#platform").append('<option value="">All</option>');
         	var platformList = JSON.parse(JSON.stringify(data));

			for (var i = 0; i < platformList.length; i++) {
				var pf = platformList[i];
				$("#platform").append('<option value=' + pf.platform + '>' + pf.platform + '</option>');
			}
			
			if(platform_nav) {
				$("#platform").val(platform_nav);
			}
			
			$('#platform').selectpicker('refresh');
			if(maintenanceActivitiesVisited){
				//$('#platform').val($window.session_Platform);
				if($window.session_Platform!='' && $window.session_Platform!=undefined){
					var string = $window.session_Platform;
			    	var array = string.split(",");
			    	$('#platform').val(array);	
					$('#platform').selectpicker('refresh');
				}
				
			}
            $scope.loadConfigTypes();
        })
        .error(function (data, status, header, config) {
        });
    };

    $scope.loadConfigTypes = function() {
    	clearConfigurationSelect();
    	clearSoftwareSelect();
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
			//$("#configType").append('<option value="">All</option>');
         	var configTypeList = JSON.parse(JSON.stringify(data));

			for (var i = 0; i < configTypeList.length; i++) {
				var config = configTypeList[i];
				$("#configType").append('<option value="' + config.configType + '">' + config.configType + '</option>');
			}
			
			if(configuration_nav) {
				$("#configType").val(configuration_nav);
			}

			$('#configType').selectpicker('refresh');
			if(maintenanceActivitiesVisited){
				//$('#configType').val($window.session_Config);
				if($window.session_Config!='' && $window.session_Config!=undefined){
					var string = $window.window.session_Config;
			    	var array = string.split(",");
			    	$('#configType').val(array);
					$('#configType').selectpicker('refresh');
				}
			}
            //$scope.loadSoftwares();
			$scope.loadTailsign();
        });
    };

    $scope.loadSoftwares = function() {
    	clearSoftwareSelect();
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
            action: 'GET_SW_FOR_AIRLINE_PLATFORM_CNFG'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../common/AirlineDAO.php', data, config)
        .success(function (data, status, headers, config) {
         	var softwareList = JSON.parse(JSON.stringify(data));

			for (var i = 0; i < softwareList.length; i++) {
				var sw = softwareList[i];
				$("#software").append('<option value="' + sw.software+ '">' + sw.software + '</option>');
			}
			$("#software").val(softwareList[0]);
			
			if(software_nav) {
				$("#software").val(software_nav);
			}
			
			$('#software').selectpicker('refresh');

            $scope.loadTailsign();
        });
    };
    
	$scope.loadTailsign = function() {
    	clearTailsignSelect();    	

		var airlineId = "";
		var platform = "";
		var configType = "";
		var software = "";
		
		airlineId = getSelectedAirline();
		platform = getSelectedPlatform();
		configType = getSelectedConfigType();		
		software = getSelectedSoftwares();
		
        var data = $.param({
            airlineId: airlineId,
            platform: platform,
            configType: configType,
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
				$("#tailsign").append('<option value="' + ts.tailsign + '">' + ts.tailsign + '</option>');
			}
			$('#tailsign').val(tailsignList[0]);

			if(tailsign_nav) {
				$("#tailsign").val(tailsign_nav);
			}
			
			$('#tailsign').selectpicker('refresh');
			if(firstTime){
				if(maintenanceActivitiesVisited){
					if($window.session_Tailsign!='' && $window.session_Tailsign!=undefined){
						var string = $window.window.session_Tailsign;
				    	var array = string.split(",");
				    	$('#tailsign').val(array);
						$('#tailsign').selectpicker('refresh');
						
						$('#startDateTimePicker').val($window.session_StartDate);
						$('#endDateTimePicker').val($window.session_EndDate);
						$('#hwPartNumberRemoval').val($window.hwPartNumberRemoval);
						$('#serialNumberRemoval').val($window.serialNumberRemoval);
						$('#hostnameRemoval').val($window.hostnameRemoval);
						getMaintenanceData();
						firstTime=false;
					}else{
						if($window.session_StartDate!='' && $window.session_StartDate!=undefined){
							$('#startDateTimePicker').val($window.session_StartDate);
							$('#endDateTimePicker').val($window.session_EndDate);
						}	
						$('#hwPartNumberRemoval').val($window.hwPartNumberRemoval);
						$('#serialNumberRemoval').val($window.serialNumberRemoval);
						$('#hostnameRemoval').val($window.hostnameRemoval);
						getMaintenanceData();
					}
				}else{
					getMaintenanceData();
					firstTime=false;
				}
			}else{
				if(maintenanceActivitiesVisited){
					if($window.session_Tailsign!='' && $window.session_Tailsign!=undefined){
						var string = $window.window.session_Tailsign;
				    	var array = string.split(",");
				    	$('#tailsign').val(array);
						$('#tailsign').selectpicker('refresh');
						
						$('#startDateTimePicker').val($window.session_StartDate);
						$('#endDateTimePicker').val($window.session_EndDate);
						$('#hwPartNumberRemoval').val($window.hwPartNumberRemoval);
						$('#serialNumberRemoval').val($window.serialNumberRemoval);
						$('#hostnameRemoval').val($window.hostnameRemoval);
						getMaintenanceData();
					}
					getMaintenanceData();
				}else{
					if(firstTime){
						getMaintenanceData();
						firstTime=false;
					}
				}
			}
        });
	};
	
	$("#filter").click(function(){
		getMaintenanceData();		
	});
	
	function getAircraftId(){
		var aircraftN=$('#tailsign').val();
//		$('#dataInfo').hide();
		$('#tableInfo').hide();
        $('#errorInfo').hide();
		var data = $.param({
			tailsign: aircraftN,
            action: 'GET_AIRCRAFTID_FOR_TS'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../common/AirlineDAO.php', data, config)
        .success(function (data, status, headers, config) {
			aircraft = JSON.parse(JSON.stringify(data));
			console.log(aircraft.id);
			var startDate=$('#startDateTimePicker').val();
			var endDate=$('#endDateTimePicker').val();
			var data={
					aircraftId:aircraft.id,
					maintenanceTimeline: true,
					startDateTime: $("#startDateTimePicker").val(), 
			        endDateTime: $("#endDateTimePicker").val(),
			};
			if(timeline != null) {
				if(timeline.body != null){
            		timeline.destroy();
            	}
		        $('#loadingTimeline').toggle();
		    }
			
			$('#removalsTable').bootstrapTable('destroy');
			$('#loadingTable').toggle();
			//getTimeLineData(data);
			getMaintenanceData();
        });
	}
	

	function getTimeLineData(data) {
	    $.ajax({
	        type: "POST",
	        dataType: "json",
	        url: "../ajax/getAircraftTimeLineData.php",
	        data: data,
	        success: function(data) {	        	
	            // console.log(data);
	            $('#startDateTimePicker').datetimepicker({
					format: "Y-m-d H:i",
	                value: data.options.start,
	                step:15,
	                weeks:true
	            });

	            $('#endDateTimePicker').datetimepicker({
					format: "Y-m-d H:i",
	                value: data.options.end,
	                step:15,
	                weeks:true
	            });
	            //createTimeline(data);
				
				// now that we have the data, get the data for the table
	            
	        },
	        error: function (err) {
	            console.log('Error', err);
//	            $('#dataInfo').hide();
	            $('#tableInfo').hide();
	            $('#errorInfo').show();	 
	            $('#loadingTimeline').hide(); 
	            
	        }
	    });
	    getMaintenanceData();
	}
	

		function getMaintenanceData(type, table) {
			$('#loadingTimeline').show(); 
//			$('#dataInfo').hide();
			$('#tableInfo').hide();
			$('#errorInfo').hide();	
			airlineId = getSelectedAirline();
			platform = getSelectedPlatform();
			configType = getSelectedConfigType();		
			software = getSelectedSoftwares();
			tailsign = getSelectedTailsigns();
			//var hwPartNumber=$('#hwPartNumber').val();
			var hwPartNumber=$('#hwPartNumberRemoval').val();
			var serialNumber=$('#serialNumberRemoval').val();
			var hostname=$('#hostnameRemoval').val();
			
			$('#removalsTable').bootstrapTable("destroy");
			$.ajax({
				type: "GET",
				url: "../ajax/getMaintenanceData.php",
				data: {
					//aircraftId:aircraft.id,
					airlineId:  airlineId, 
	                platform: platform,
	                configType: configType,	                
	                tailsign: tailsign,
	                //hwPartNumber: hwPartNumber,
	                hwPartNumber: hwPartNumber,
	                serialNumber: serialNumber,
	                hostname: hostname,
					startDateTime: $("#startDateTimePicker").val(), 
					endDateTime: $("#endDateTimePicker").val(),
					submenu:'lruRemoval'
				},
				success: function(data) {
					if (data.indexOf("Error creating statement") >= 0) {
						$('#loadingTimeline').hide(); 
						$('#errorInfo').show();
					}else{
						var jsonData = $.parseJSON(data);
						$('#removalsTable').bootstrapTable({
							data: jsonData,
							exportOptions: {
								fileName: 'MaintenanceActivities'
							}
						});										

						$('#loadingTimeline').hide();
						if(jsonData.length>0){
							$('#errorInfo').hide();	
							$('#dataInfo').show();
							$('#tableInfo').show();
						}else{
							$('#errorInfo').show();	
							$('#dataInfo').show();
							$('#tableInfo').hide();
						}
						$('#removalsTable').bootstrapTable("refresh");
					}
				},
				error: function (err) {
					console.log('Error', err);
//					$('#dataInfo').hide();
		            $('#tableInfo').hide();
		            $('#errorInfo').show();
		            $('#dataInfo').show();
		            $('#loadingTimeline').hide(); 
		            $('#loadingTable').hide();
				}
			});
		}
	

	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}

	function createTimeline(data) {
	    //$('#loadingTimeline').hide();

	    var container = document.getElementById('atimeline');

	    var groups = new vis.DataSet(
	        data.groups
	        );

	    var items = new vis.DataSet(
	        data.items
	        );

	    var options = {
	        start: data.options.start,
	        end: data.options.end,
	        min: data.options.min,
	        max: data.options.max,
	        clickToUse: true,
	        stack: false,
	        multiselect: true
	    };

	    timeline = new vis.Timeline(container, items,  groups, options);
	    timeline.on('contextmenu', function (props) {
	      //alert('Right click!');
	      props.event.preventDefault();
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
	
	function clearSoftwareSelect() {
        $('#software').empty();
        $('#software').selectpicker('refresh');
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
	
	function getSelectedSoftwares() {
		return $('#software').val();
	}
	
	function getSelectedTailsigns() {
		return $('#tailsign').val();
	}
	
	$scope.resetMaintenance = function() {
		clearAirlines();
		maintenanceActivitiesVisited=false;
		$('#startDateTimePicker').datetimepicker({value: $window.startDateTime});
		$('#endDateTimePicker').datetimepicker({value: $window.endDateTime});
		$scope.loadAirlines();	
		$('#serialNumberRemoval').val('');
		$('#hostnameRemoval').val('');
		$('#hwPartNumberRemoval').val('');
	};
	
	$scope.loadAirlines();
	
	$("#serialNumberRemoval").keyup(function(){        
        if($("#serialNumberRemoval").val().trim()!=''){        	
        	var element =document.getElementById('hostnameRemoval'); 
        	element.disabled=true; 
        }else{
        	var element =document.getElementById('hostnameRemoval'); 
        	element.disabled=false; 
        }
    });
	$("#hostnameRemoval").keyup(function(){        
        if($("#hostnameRemoval").val().trim()!=''){        	
        	var element =document.getElementById('serialNumberRemoval'); 
        	element.disabled=true; 
        }else{
        	var element =document.getElementById('serialNumberRemoval'); 
        	element.disabled=false; 
        }
    });
	
	$('#hwPartNumberRemoval').val('');
	$('#serialNumberRemoval').val('');
	$('#hostnameRemoval').val('');
	

	
});
