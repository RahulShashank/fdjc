var app = angular.module('myApp', []);
app.directive('hcChart', function () {
	return {
		restrict: 'E',
		template: '<div></div>',
		scope: {
			options: '='
		},
		link: function (scope, element) {
			Highcharts.chart(element[0], scope.options);
		}
	};
});
app.controller('ActiveMaintenanceController', function($scope, $http, $log, $window, $timeout, $parse) {

	$log.log("inside ActiveMaintenanceController");
	$('#loadingDiv').hide();
	$scope.airlineId = $("#airlineId").val();
	$scope.showRemarksAlert=false;
	$scope.allData = "";
	
	var startDate = $window.startDateTime;
	var endDate = $window.endDateTime;
	var criticalArray = [];
	var warningArray = [];
	var noIssueArray = [];
	
	var today = new Date();
	var priorDate = new Date(new Date().setDate(today.getDate()-30));
	var startDate = formatDate(priorDate);
	var endDate = formatDate(today);
	$scope.loading = true;
	$scope.startDate = startDate;
	$scope.endDate = endDate;
	var firstTime=true;

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

	$('#airline').selectpicker();
	$('#platform').selectpicker();
	$('#configType').selectpicker();
	$('#software').selectpicker();
	$('#tailsign').selectpicker();


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
			$('#airline').selectpicker('refresh');
				
	        $scope.loadPlatforms();
	    });
   };

	$scope.resetFlightScore = function() {
		clearAirlines();
		$('#startDateTimePicker').datetimepicker({value: $window.startDateTime});
		$('#endDateTimePicker').datetimepicker({value: $window.endDateTime});
		$scope.loadAirlines();
		//getAircraftId();
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
			$('#platform').selectpicker('refresh');
            
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
			$('#configType').selectpicker('refresh');

            $scope.loadSoftwares();
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
            software: software,
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
			$('#tailsign').val(tailsignList[0]);
			$('#tailsign').selectpicker('refresh');
			if(firstTime){
				getAircraftId();
				firstTime=false;
			}	
        });
	};
	
	$("#filter").click(function(){
		getAircraftId();
	});
	
	function getAircraftId(){
		var aircraft=$('#tailsign').val();
		console.log('tailsign: ' + JSON.stringify(tailsign));
		var data = $.param({
			tailsign: aircraft,
            action: 'GET_AIRCRAFTID_FOR_TS'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../common/AirlineDAO.php', data, config)
        .success(function (data, status, headers, config) {
			var aircraft = JSON.parse(JSON.stringify(data));
			console.log(aircraft.id);
			var startDate=$('#startDateTimePicker').val();
			var endDate=$('#endDateTimePicker').val();
			$scope.getFaultCount(startDate, endDate, aircraft.id);			
        });
		
		// To remove the white space below the page content
		page_content_onresize();
	}
	
	$scope.getFaultCount = function(startDate, endDate,aircraftId){
		$scope.dataNotAvailable=false;
		if(new Date(startDate)<=new Date(endDate)){
			$scope.dateError = false;
			$("#startDateTimePicker").removeClass("date-Error");
			$("#endDateTimePicker").removeClass("date-Error");
			destroyTable();
			$scope.loading = true;
			$http.get("../ajax/aircraftActiveMaintenanceData.php", {
			params: { 
				'aircraftId': aircraftId,
				'startDate': startDate,
				'endDate': endDate
			}
			}).then(function(response) {
				if(response.data.status=="success"){
					//console.log("data", response.data);
					var dateRange = getDates(startDate, endDate);
					//console.log(dateRange);
					var headers = prepareHeader(dateRange);
					//console.log(headers);
					//console.log(response.data);
					var formatData = formatDataByDateRange(response.data, dateRange);
					//console.log(formatData);
					$.fn.dataTable.ext.errMode = 'none';
					$('#resultTable').on('error.dt', function(e,settings,techNote,message ) {
						console.log( 'DataTable Error: ', message );
					});
					//var nameType = $.fn.dataTable.absoluteOrder('Daily Offloads Taken');
					var columnConfig = [{ targets: 0,  width: 100,minWidth: '100px' }]
					for(i=1; i<headers.length; i++){
						columnConfig.push({
							createdCell: function(td, cellData, rowData, row, col) {
								if(row!=0){
									var cellVal = Number.parseInt(cellData);
									if(cellVal>0 && cellVal<5){
										$(td).addClass('cell_bkg_yellow');								
										$(td).addClass('center_align');								
									}
									else if(cellVal>=5 && cellVal<10){
										$(td).addClass('cell_bkg_orange');
										$(td).addClass('center_align');										
									}
									else if(cellVal>=10){
										$(td).addClass('cell_bkg_red');
										$(td).addClass('center_align');										
									}
									else{
										$(td).addClass('cell_bkg_light');
									}
								}
								else{									
									$(td).addClass('center_align');
									$(td).addClass('text_bold');								
									
								}
							},
							defaultContent: ""
						});
					}
					
					var $tr = $('#resultTable tr.odd');
					var myrow = $tr.prop('outerHTML');
					$tr.remove();
					var tailsignName=$('#tailsign').val();
					$('#resultTable').dataTable( {
					  columnDefs: headers,
					  fixedHeader: true,
					  paging: true,
					  paginationType:"full_numbers",
					  columns: columnConfig,
					  data: formatData,	
  					  iDisplayLength: 25,
					  oLanguage: { oPaginate: { sFirst:"<<",sLast:">>",sPrevious: "<", sNext: ">"}, sLengthMenu: '_MENU_records per page',sSearch: "",sSearchPlaceholder: "Search" }, 
			          info: "Showing page _PAGE_ of _PAGES_ rows",
					  aLengthMenu: [[10, 25, 50, 100,-1], [10, 25, 50, 100,"All"]],
					  dom: '<"search_filter"fB>rt<"bottom" ilp><"clear">',
					  
					  buttons: [
						{
						    extend: 'collection',
						    text: '<i class="fa fa-bars"></i> Export Data',
						    className:'btn btn-primary active',
						    buttons: [
								{	extend: 'csvHtml5',
									text:      '<img src="../img/icons/csv.png" width="24px"> CSV',
									className:'dropdownlist',
									exportOptions: {
										columns: ':visible'},
									title: tailsignName + ' - Active Maintenance - ' + startDate + ' to ' + endDate
								},
								{	extend: 'excelHtml5',
									text:      '<img src="../img/icons/xls.png" width="24px"> Excel',
									className:'dropdownlist',
									exportOptions: {
										columns: ':visible'},
									title: tailsignName + ' - Active Maintenance - ' + startDate + ' to ' + endDate
								}
						    ]
						},
					  ],					  
					  scrollX: true,
					  //scrollY: '200px',
					  createdRow:function(row,data,index){
			            	if(index==0){
			            		$(row).addClass('no_sorting');	
			            		myrow=$(row);			            		
			            		$(row).remove();
			            	}
					  },
					  fnDrawCallback: function() {
			            	$('#resultTable tbody').prepend(myrow);
					  },
					});
					setTimeout(function(){ $("th:first-child").click(); $("th:first-child").click(); }, 1500);
					$scope.dataNotAvailable=false;
				}else{
					console.log(response.data.message);
					$scope.dataNotAvailable=true;
				}
				$scope.loading = false;
			}, function myError(response) {
				console.log("error : ", response);
				$scope.data = response.statusText;
				$scope.loading = false;
			});
		}else{
			$scope.dateError = true;
			$("#startDateTimePicker").addClass("date-Error");
			$("#endDateTimePicker").addClass("date-Error");
		}
		
		// To remove the white space below the page content
		page_content_onresize();
	}

	function getDates(startDate, endDate) {
		var dateArray = new Array();
		var startDate = startDate.split("-");
		var currentDate = new Date(parseInt(startDate[0]), parseInt(startDate[1])-1, parseInt(startDate[2]));
		var endDate = endDate.split("-");
		var stopDate = new Date(parseInt(endDate[0]), parseInt(endDate[1])-1, parseInt(endDate[2]));
		while(currentDate <= stopDate) {
			dateArray.push(formatDate(currentDate));
			var nextDate = new Date(currentDate.valueOf())
			nextDate.setDate(nextDate.getDate() + 1);
			currentDate = nextDate;
		}
		return dateArray;
	}
	
	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
	}
	
	function formatDataByDateRange(data, dateRange){
		var faultData = [{"name":"Daily Offloads Taken","TotalCount":0}];
		dateRange.forEach(function(date){
			faultData[0][date] = 0;
			if(data.flightCountData.hasOwnProperty(date)){
				faultData[0][date] = data.flightCountData[date];
				faultData[0]["TotalCount"] = faultData[0]["TotalCount"]+parseInt(data.flightCountData[date]);
			}
		});
		for(var hostname in data.faultCountData){
			if (data.faultCountData.hasOwnProperty(hostname)) {
				var dataFlag = false;
				var hostObj = {"name":hostname,"TotalCount":0};
				dateRange.forEach(function(date){
					if(data.faultCountData[hostname].hasOwnProperty(date)){
						//hostObj[date] = data.faultCountData[hostname][date];
						hostObj[date] = data.faultCountData[hostname][date];
						hostObj["Dates"]=date;
						hostObj["TotalCount"] = hostObj["TotalCount"]+parseInt(data.faultCountData[hostname][date],10);
						dataFlag = true;
					}
					else{
						//if data is not present for that date then set the default data to display
						//not needed anymore since we are using the defaultContent attribute in columnConfig
						//hostObj[date] = 0;
					}
				});
				if(dataFlag){
					faultData.push(hostObj);
				}
			}
		}
		return faultData;
	}
	
	function prepareHeader(dateRange){
		// var headers = [{ "title": "First", "targets": 0, data: 'first' },{ "title": "Middle", "targets": 1, data: 'middle' },{ "title": "Last", "targets": 2, data: 'last' },{ "title": "Aircraft", "targets": 3, data: 'aircraft.name' },{ "title": "Aircraft", "targets": 4, data: 'aircraft.type' },];
		var headers = [{"title":"Date", "targets": 0, "data": "name"}];
		
//		var header1 = {"title": "Total", "targets": 1, "data": "TotalCount"};
		var header1 = {"title": "Total", "targets": 1, "data": "TotalCount",
			"render": function(data, type, row, meta){
	            if(data!=null){
	            	if(row.name!="Daily Offloads Taken"){
	            		var url="javascript:getFaultCountDetails('"+row.name+"','');";
	            		data = '<a onclick='+url+' style="cursor: pointer; width:100%; text-decoration:none;" data-toggle="modal" data-target="#myModal"><div style="width: 100%;">' + data + '</div></a>';
	            	}
	            }

	            return data;
	         }
		};
		headers.push(header1);
		var index = 2;
		dateRange.forEach(function(date){
			var header = {"title": date, "targets": index, "data": date,
					"render": function(data, type, row, meta){
			            if(data!=null){
			            	if(row.name!="Daily Offloads Taken"){
			            		$scope.rowHostname=String(row.name);
			            		var url="javascript:getFaultCountDetails('"+row.name+"','"+date+"');";
			            		data = '<a onclick='+url+' style="cursor: pointer; width:100%; text-decoration:none;" data-toggle="modal" data-target="#myModal"><div style="width: 100%;">' + data + '</div></a>';
			            	}
			            }

			            return data;
			         }
				};
			headers.push(header);
			index += 1;
		});		
		
		return headers;
	}
	
	function destroyTable(){
		if ($.fn.dataTable.isDataTable('#resultTable')) {
			table = $('#resultTable').DataTable();
			table.destroy();
			$("#resultTable *").remove();
		}
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
	
	$scope.resetActiveMaintenance = function() {
		clearAirlines();
		$('#startDateTimePicker').datetimepicker({value: $window.startDateTime});
		$('#endDateTimePicker').datetimepicker({value: $window.endDateTime});
		$scope.loadAirlines();		
	};
	
	$scope.loadAirlines();
	
});
