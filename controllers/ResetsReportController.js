$.blockUI.defaults.overlayCSS.opacity = '0.3';
$.blockUI.defaults.css = { 
    padding:        0, 
    margin:         0, 
    width:          '30%', 
    top:            '40%', 
    left:           '35%', 
    textAlign:      'center', 
    cursor:         'wait' 
};

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
      	
    $('#software').selectpicker({                              
			 size: 6
      	});
      	
    $('#tailsign').selectpicker({                              
			 size: 6
  	});

    $('#reportBy').selectpicker({                              
			 size: 6
  	});

    $('#period').selectpicker({                              
			 size: 6
  	});

	$('#airline').on('change', function(){
	    angular.element($("#ctrldiv")).scope().loadPlatforms();
	  });

	$('#platform').on('change', function(){
		if($('#platform').val()) {
			disableSelectOption('platform');
		} else {
			enableSelectOption('platform');
		}
		enableSelectOption('actype');
		
	    angular.element($("#ctrldiv")).scope().loadConfigTypes();
	  });

	$('#configType').on('change', function(){
		if($('#configType').val()) {
			disableSelectOption('actype');
		} else {
			enableSelectOption('actype');
		}
		
	    angular.element($("#ctrldiv")).scope().loadSoftwares();
	  });

	function disableSelectOption(val) {
		$('option[value="' + val + '"]').prop("disabled",true);
		$('#reportBy').selectpicker('refresh');
	}
	
	function enableSelectOption(val) {
		$('option[value="' + val + '"]').prop("disabled",false);
		$('#reportBy').selectpicker('refresh');
	}
	
//	$('#commandedResetsTable').dataTable( {
//        "sDom": '<"top"i>rt<"bottom"flp><"clear">'
//    });
});
var app = angular.module('myApp', ['ui.bootstrap', 'datatables', 'datatables.buttons', 'chart.js']);
// var app = angular.module('myApp', ['ui.bootstrap', 'chart.js']);
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
app.controller('ResetsReportController', function($scope, $http, $filter, $window, $uibModal, DTOptionsBuilder, DTColumnDefBuilder) {
//app.controller('ResetsReportController', function($scope, $http, $filter, $window, $uibModal) {

	console.log("inside ResetsReportController");
	
//	$('#loadingDiv').hide();
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

//            $scope.loadTailsign();
			if(firstTime) {
				$scope.filter();
				firstTime = false;
			}
        });
    };
    
	function getAircraftId(){
		var aircraft=$('#tailsign').val();
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
			var startDate=$('#startDateTimePicker').val();
			var endDate=$('#endDateTimePicker').val();
			$scope.getFaultCount(startDate, endDate, aircraft.id);			
        });
	}
	
	function formatDate(date){
		var month = date.getMonth() + 1;
		month = (month<10) ? "0"+month : month;
		var day = date.getDate();
		day = (day<10) ? "0"+day : day;
		return date.getFullYear() + '-' + month + '-' + day;
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
	
	function getSelectedPerdiod() {
		return $('#period').val();
	}
	
	function getSelectedReportBy() {
		return $('#reportBy').val();
	}	
	
	$scope.loadAirlines();
	
	// Resets Report functions
    $scope.displayModel = "commandedResets";
    if(firstTime) {
    	$scope.period = "daily";
    	$scope.reportType = "aircraft";
    }
	
	$scope.loading = true;
	
	$scope.options = {
		timepicker: false,
		format: "Y/m/d",
		angularFormat: "yyyy/MM/dd",
		dayOfWeekStart: 1
	};
	
	$scope.dtOptions = DTOptionsBuilder.newOptions()
//        .withDOM('<"top"i>rt<"bottom"flp><"clear">')
        .withDOM('<"top"f><"html5buttons"B>rt<"bottom"ilp><"clear">')
        .withButtons([{
		    extend: 'collection',
		    text: '<i class="fa fa-bars"></i> Export Data',
		    className:'btn btn-primary active',
		    buttons: [
				{	extend: 'csvHtml5',
					text:      '<img src="../img/icons/csv.png" width="24px"> CSV',
					className:'dropdownlist',
					exportOptions: {
						columns: ':visible'},
					title: 'Export CSV'
				},
				{	extend: 'excelHtml5',
					text:      '<img src="../img/icons/xls.png" width="24px"> Excel',
					className:'dropdownlist',
					exportOptions: {
						columns: ':visible'},
					title: 'Export Excel'
				}
		    ]
		}])
		.withOption('autoWidth', false)
		.withOption('width', '100%')
        .withOption('scrollX', true)
        .withPaginationType('full_numbers')
        .withLanguage({
                "paginate": {
                    "first": '«',
                    "last": '»',
                    "next": '›',
                    "previous": '‹'
                },
                "lengthMenu" : "_MENU_records per page",
                "search": "",
                "searchPlaceholder": "Search"
            })
        .withOption('lengthMenu', [ [25, 50, 100, -1], [25, 50, 100, "All"] ])        
        .withOption('pageLength', 25);
	
		
	$scope.add = function(a,b) {
		if( (a !== '') && (b !== '') ) {
			return parseInt(a) + parseInt(b);
		} else {
			return '';
		}
	}
	
	$scope.subtract = function(a,b) {
		if( (a !== '') && (b !== '') ) {
			var value = parseInt(a) - parseInt(b);
			
			if(value < 0 ) {
				value = 0;
			}
			
			return value;
		} else {
			return '';
		}
	}
	
	$scope.resetsPerHour = function(a,b) {
		if( (a !== '') && (b !== '') ) {
			return $filter('number')(parseInt(a) / parseFloat(b), 2);
		} else {
			return '';
		}
	}
	
	$scope.totalCommandedResetHour = function(a) {
		var total=0.00;
		angular.forEach(a, function(item, key) {
			var tot=$scope.resetsPerHour(item.totalCommandedResets, item.totalCruise);
			if(tot!=''){
				total=total+parseFloat(tot);
			}			
		});
		return total.toFixed(2);;
	}
	
	$scope.totalUncommandedResetHour = function(a) {
		var total=0.00;
		angular.forEach(a, function(item, key) {
			var tot=$scope.resetsPerHour($scope.subtract(item.totalUncommandedResets,item.systemResetsCount), item.totalCruise);
			if(tot!=''){
				total=total+parseFloat(tot);
			}			
		});
		return total.toFixed(2);;
	}
	
	$scope.totalResetHour = function(a) {
		var total=0.00;		
		angular.forEach(a, function(item, key) {
			var tot=$scope.resetsPerHour($scope.add(item.totalCommandedResets,$scope.subtract(item.totalUncommandedResets,item.systemResetsCount)), item.totalCruise);
			if(tot!=''){
				total=total+parseFloat(tot);
			}			
		});
		return total.toFixed(2);;
	}

	$scope.filter = function() {
		$scope.loading = true;
		$scope.displayModel="";
		$scope.startDate = $('#startDateTimePicker').val();
		$scope.endDate = $('#endDateTimePicker').val();
		var airlineId = getSelectedAirline();
		
		var selectedPlatformArray=[];
		 if (getSelectedPlatform() != null) {
             for (r in getSelectedPlatform()) {
                 var str = getSelectedPlatform()[r].split(':');
                 var str1 = "'" + str[0] + "'";
                 selectedPlatformArray.push(str1);
             }
         }
		 
		 var selectedConfigArray=[];
		 if (getSelectedConfigType() != null) {
             for (r in getSelectedConfigType()) {
                 var str = getSelectedConfigType()[r].split(':');
                 var str1 = "'" + str[0] + "'";
                 selectedConfigArray.push(str1);
             }
         }
		 
		 var selectedSoftwareArray=[];
		 if (getSelectedSoftwares() != null) {
             for (r in getSelectedSoftwares()) {
                 var str = getSelectedSoftwares()[r].split(':');
                 var str1 = "'" + str[0] + "'";
                 selectedSoftwareArray.push(str1);
             }
         }
		 
		$http.get("../ajax/ResetsReportDAO.php", {
			params: { 
				'airlineId': airlineId,
				'startDateTime': $('#startDateTimePicker').val(),
				'endDateTime': $('#endDateTimePicker').val(),
				'reportType': getSelectedReportBy(),
				'period': getSelectedPerdiod(),
				'platform': (getSelectedPlatform()!=null?selectedPlatformArray.toString():''), 
				'config':  (getSelectedConfigType()!=null?selectedConfigArray.toString():''), 
				'software':  (getSelectedSoftwares()!=null?selectedSoftwareArray.toString():'')
			}
		}).then(function(response) {
//			console.log("output data:", JSON.stringify(response.data));
			$scope.fields = response.data.periods;
			$scope.items = response.data.items;
			
			angular.forEach($scope.items, function(value, key) {
				$scope.totalCommandedReset=0;
				$scope.totalUncommandedReset=0;
				angular.forEach(value.data, function(item, key) {
					if(item.totalCommandedResets!=""){
						$scope.totalCommandedReset=$scope.totalCommandedReset+parseInt(item.totalCommandedResets);
					}	
					if(item.totalUncommandedResets!=""){
						var value=(parseInt(item.totalUncommandedResets)-parseInt(item.systemResetsCount));
						if(value < 0 ) {
							value = 0;
						}
						$scope.totalUncommandedReset=$scope.totalUncommandedReset+value;
					}
				});
				value.totalCommandedReset=$scope.totalCommandedReset;
				value.totalUncommandedReset=$scope.totalUncommandedReset;	
				value.totalReset=$scope.totalCommandedReset+$scope.totalUncommandedReset;
				
			});
			$scope.loading = false;
			$scope.displayModel="commandedResets";
			
			
			// To remove the white space below the page content
			page_content_onresize();
		}, function myError(response) {
			console.log("error : ", response);
			$scope.data = response.statusText;
			
			$scope.loading = false;
		});
		
		// To remove the white space below the page content
		page_content_onresize();
	}
	
	$scope.resetSearchPanel = function() {
		$scope.loading = true;
		//$scope.loadAirlines();
		$('#reportBy').val('aircraft');
		$('#period').val('daily');
		$('#period').selectpicker('refresh');
		$('#reportBy').selectpicker('refresh');
		clearAirlines();
		
		var today = new Date();
		var priorDate = new Date(new Date().setDate(today.getDate()-30));
		var startDate = formatDate(priorDate);
		var endDate = formatDate(today);
		
		$('#startDateTimePicker').datetimepicker({value: startDate});
		$('#endDateTimePicker').datetimepicker({value: endDate});
//		firstTime = true;
		$scope.loadAirlines();
		$scope.loading = false;
	}
	
	$scope.displayChart = function(index, display) {
		// console.log("display chart for aircraft at index ", index);
		// console.log("periods: ", $scope.fields);
		// console.log("data ", $scope.items[index]);
		// console.log("display ", display);
		
		var modalInstance = $uibModal.open({
			templateUrl: 'modalAircraftResetsChart.html',
			controller: ModalResetsChart,
			size: 'lg',
			resolve: {
				tailsign: function() {
					return $scope.items[index].tailsign
				},
				periods: function() {
					return $scope.fields;
				},
				data: function() {
					return $scope.items[index].data;
				},
				display: function() {
					return display;
				}
			}
		});
	}
	
	function ModalResetsChart($scope, $uibModalInstance, $filter, $timeout, tailsign, periods, data, display) {
		// $scope.displayModel = "total";
		$scope.displayModel = "hcTotal";
		$scope.tailsign = tailsign;
		
		$scope.labels = periods;
		$scope.series = ['Commanded', 'Uncommanded'];

		// Get Total Resets
		var commandedResets = [];
		var uncommandedResets = [];
		angular.forEach(data, function(value, key) {
			commandedResets.push(parseFloat(value.totalCommandedResets));
			
			//Smita: added code for sync up for table and chart data
			var uncommandedValue = ((value.totalUncommandedResets)-(value.systemResetsCount));
			if(uncommandedValue < 0){
				uncommandedValue = 0;
			}else{
				uncommandedValue;
			}
			uncommandedResets.push(uncommandedValue);
		});
		$scope.dataTotal= [commandedResets, uncommandedResets]
		
		// Highchart
		var chartOptionsTotal = {
			chart: {
				type: 'column'
			},
			title: {
				text: ' '
			},
			xAxis: {
				categories: periods
			},
			yAxis: {
				min: 0,
				title: {
					text: 'Total Resets'
				},
				/*
				stackLabels: {
					enabled: true,
					style: {
						fontWeight: 'bold',
						color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
					},
				}
				*/
			},
			plotOptions: {
				column: {
					stacking: 'normal',
					dataLabels: {
						enabled: true,
						formatter:function() {
							if(this.y != 0) {
								return this.y;
							}
						}
					}
				}
			},
			exporting: {
				filename: $scope.tailsign + "_total_resets"
			},
			series: [{
				name: 'Uncommanded',
				color: '#d9534f',
				data: uncommandedResets
			}, {
				name: 'Commanded',
				color: '#286090',
				data: commandedResets				
			}]
		};
		
		// Get Resets / Hour
		commandedResets = [];
		uncommandedResets = [];
		angular.forEach(data, function(value, key) {
			commandedResets.push(parseFloat($filter('number')((value.totalCommandedResets / value.totalCruise),2)));
			
			//Smita: added code for sync up for table and chart data
			var uncommandedValue = ((value.totalUncommandedResets)-(value.systemResetsCount));
			if(uncommandedValue < 0){
				uncommandedValue = 0;
			}else{
				uncommandedValue;
			}
			uncommandedResets.push(parseFloat($filter('number')((uncommandedValue / value.totalCruise),2)));
		});
		$scope.dataPerHour = [commandedResets, uncommandedResets]
		
		$scope.options = {
			animation: false,
			legend: {
				display: true,
				position: 'bottom'
			},
			scales: {
				yAxes: [{
					stacked: true
				}],
				xAxes: [{
					stacked: true
				}]
			}
		}
		// Working OK
		//$scope.colors = [ "#C94C49", "#D68550", "#EEA638", "#A7A737", "#85A963", "#89AAAF", "#39AAAF"];
		$scope.colors = [ "#97bbcd", "#f7464a", "#fdb45c"];
		
		$scope.datasetOverride = [
			{
				borderWidth: 1,
			},
			{
				borderWidth: 1,
			}
		];
			
		$scope.export = function() {
			var chartName = "";
			
			if($scope.displayModel == "total") {
				chartName = "#totalBarChart";
			} else if ($scope.displayModel == "perHour") {
				chartName = "#perHourBarChart";
			}
			
			$(chartName).get(0).toBlob(function(blob) {
				saveAs(blob, $scope.tailsign + "_" + $scope.displayModel);
			});
		}
			
		$scope.close = function() {
			$uibModalInstance.dismiss('cancel');
		}
		
		// Highchart Per Hour
		var chartOptionsPerHour = {
			chart: {
				type: 'column'
			},
			title: {
				text: ' '
			},
			xAxis: {
				categories: periods
			},
			yAxis: {
				min: 0,
				title: {
					text: 'Resets per Hour'
				},
			},
			plotOptions: {
				column: {
					stacking: 'normal',
					dataLabels: {
						enabled: true,
						formatter:function() {
							if(this.y != 0) {
								return this.y;
							}
						}
					}
				}
			},
			exporting: {
				filename: $scope.tailsign + "_resets_per_hour"
			},
			series: [{
				name: 'Uncommanded',
				color: '#d9534f',
				data: uncommandedResets
			}, {
				name: 'Commanded',
				color: '#286090',
				data: commandedResets				
			}]
		};
		
		$timeout(function() {
			$scope.chartOptionsTotal = chartOptionsTotal;
			$scope.chartOptionsPerHour = chartOptionsPerHour;
		});		
	}
});
