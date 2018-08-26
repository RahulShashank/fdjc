var app = angular.module('myApp', []);

app.controller('AircraftsController', function($scope, $http, $log, $window, $timeout, $parse) {

	var rowCount = 0;
	$('#addAircraftsAlertDiv').hide();
	$('#successAlertDiv').hide();
	var aircraftsArray = [];

	$('#eis').datetimepicker({
		timepicker:false,
		format:'Y-m-d'
	});
	
	$('#swInstallation').datetimepicker({
		timepicker:false,
		format:'Y-m-d'
	});
	
	$scope.getAircrafts = function() {
		$('#loadingDiv').show();

        var data = $.param({
            action: 'GET_AIRCRAFTS'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/AircraftDAO.php', data, config)
        .success(function (data, status, headers, config) {
			$scope.aircraftsData = data;
			buildTable();
    		rowCount = data.length;
    		$('#loadingDiv').hide();
        })
        .error(function (data, status, header, config) {
        });
	};
	
	$scope.loadAircraftSeatConfiguration = function() {
        var data = $.param({
            action: 'GET_AIRCRAFT_SEAT_CONFIG'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/AircraftDAO.php', data, config)
        .success(function (data, status, headers, config) {
			for (var i = 0; i < data.length; i++) {
				console.log(JSON.stringify(data));
				var aircraftSeatConfiguration = data[i];
				$('#aircraftSeatConfiguration').append('<option value=' + aircraftSeatConfiguration.id + '>' + aircraftSeatConfiguration.configurationName + '</option>');
		    }
			
			$('#aircraftSeatConfiguration').selectpicker('refresh');
        });
	};
	
	$('#airline').change(function(){
		$('#aircraftConfiguration').empty();
		var airline = $(this).val();               
		var data = $.param({
		    action: 'GET_AIRCRAFT_CONFIG',
		    airlineId: airline
		});
		
		var config = {
		    headers : {
		        'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
		    }
		};
		
		$http.post('../ajax/AircraftDAO.php', data, config)
		.success(function (data, status, headers, config) {
			for (var i = 0; i < data.length; i++) {
				console.log(JSON.stringify(data));
				var aircraftConfiguration = data[i];
				$('#aircraftConfiguration').append('<option value=' + aircraftConfiguration.configuration + '>' + aircraftConfiguration.configuration + '</option>');
		    }
			
			$('#aircraftConfiguration').selectpicker('refresh');
		});
	});
	
	function buildTable() {
		$('#aircraftsTable').bootstrapTable('destroy');
		aircraftsArray = [];
		var status = '';
		rowCount = $scope.aircraftsData.length;
		for (var i = 0; i < $scope.aircraftsData.length; i++) {
			aircraftsArray[i] = {
					index: i, 
					id: $scope.aircraftsData[i]['id'], 
					tailsign: $scope.aircraftsData[i]['tailsign'], 
					airline: $scope.aircraftsData[i]['name'], 
					msn: $scope.aircraftsData[i]['msn'], 
					type: $scope.aircraftsData[i]['type'], 
					platform: $scope.aircraftsData[i]['platform'], 
					software: $scope.aircraftsData[i]['software']};
		}
		
		$('#aircraftsTable').bootstrapTable({
			columns: [{
				field: 'id',
				title: 'Id',
				visible: false
			}, {
				field: 'tailsign',
				title: 'Tailsign',
				sortable: true,
				align: 'left',
				valign: 'top'
			}, {
				field: 'airline',
				title: 'Airline',
				sortable: true,
				align: 'left',
				valign: 'top'
			}, {
				field: 'msn',
				title: 'MSN',
				sortable: false,
				align: 'center',
				valign: 'top'
			}, {
				field: 'type',
				title: 'Type',
				align: 'center',
				valign: 'top'
			},{
				field: 'platform',
				title: 'Platform',
				align: 'center',
				valign: 'top'
			},{
				field: 'software',
				title: 'Software',
				align: 'center',
				valign: 'top'
			}],
				data: aircraftsArray
		});
	}
	
	$('#addAircraftsModal').on('show.bs.modal', function (event) {
		$('#addAircraftsAlertDiv').hide();

		$('#airline').empty();
		$('#platform').empty();
		$('#configuration').empty();
    	
		var modal = $('#addAircraftsModal');
		modal.find('.modal-body #dsuCount').val('');
		modal.find('.modal-body #laicCount').val('');
		modal.find('.modal-body #icmtCount').val('');
		modal.find('.modal-body #adbgCount').val('');
		modal.find('.modal-body #qsebCount').val('');
		modal.find('.modal-body #sdbCount').val('');
		modal.find('.modal-body #svduCount').val('');
		modal.find('.modal-body #tpmuCount').val('');
		modal.find('.modal-body #tpcuCount').val('');

		loadAirlines();
		$('#addAirlineAlertDiv').hide();
	});
	
    function loadAirlines() {
    	$http.get("../common/AirlineDAO.php?action=GET_AIRLINES_BY_IDS")
		.success(function (data) {
			for (var i = 0; i < data.length; i++) {
				var airline = data[i];
				$('#airline').append('<option value=' + airline.id + '>' + airline.name + " (" + airline.acronym + ")" + '</option>');
		    }
			
			$('#airline').selectpicker('refresh');
			
	    	loadPlatform();
		});
    	
    }
    
    function loadPlatform() {
    	$http.get("../ajax/AircraftDAO.php?action=GET_AIRCRAFT_PLATFORMS_ACTION")
		.success(function (data) {
			for (var i = 0; i < data.length; i++) {
				var platform = data[i];
				$('#platform').append('<option value="' + platform.name + '">' + platform.name + '</option>');
		    }
			
			$('#platform').selectpicker('refresh');
	    	loadTypes();
		});
    }
    
    function loadTypes() {
    	$http.get("../ajax/AircraftDAO.php?action=GET_AIRCRAFT_TYPES_ACTION")
		.success(function (data) {
			for (var i = 0; i < data.length; i++) {
				var type = data[i];
				$('#type').append('<option value="' + type.type + '">' + type.type + '</option>');
		    }
			
			$('#type').selectpicker('refresh');
		});
    }
    
    $('#addAircraftsModal').on('show.bs.modal', function (event) {
		var modal = $('#addAircraftsModal');
		modal.find('.modal-body #tailsign').val('');
		modal.find('.modal-body #noseNumber').val('');
		modal.find('.modal-body #airline').val('');
		modal.find('.modal-body #msn').val('');
		modal.find('.modal-body #type').val('');
		modal.find('.modal-body #aircraftSeatConfiguration').val('');
		modal.find('.modal-body #aircraftConfiguration').val('');
		modal.find('.modal-body #platform').val('');
		modal.find('.modal-body #isp').val('');
		modal.find('.modal-body #eis').val('');
		modal.find('.modal-body #swBaseLine').val('');
		modal.find('.modal-body #customerSw').val('');
		modal.find('.modal-body #swInstallation').val('');
		
		$('#addAircraftsAlertDiv').hide();
	});
	
	$('#addAircrafts').click(function() {
		var modal = $('#addAircraftsModal');
		
		var tailsign = modal.find('.modal-body #tailsign').val();
		var noseNumber = modal.find('.modal-body #noseNumber').val();
        var airlineId = modal.find('.modal-body #airline').val();
		var msn = modal.find('.modal-body #msn').val();
		var type = modal.find('.modal-body #type').val();
		var aircraftSeatConfiguration = modal.find('.modal-body #aircraftSeatConfiguration').val();
		var aircraftConfiguration = modal.find('.modal-body #aircraftConfiguration').val();
		var platform = modal.find('.modal-body #platform').val();
		var isp = modal.find('.modal-body #isp').val();
		var eis = modal.find('.modal-body #eis').val();
		var swBaseLine = modal.find('.modal-body #swBaseLine').val();
		var customerSw = modal.find('.modal-body #customerSw').val();
		var swinstalled = modal.find('.modal-body #swInstallation').val();

		var msnRE = /^[0-9]{1,5}$/;
		var swRE = /^.{1,10}$/;

		if(tailsign == null || $.trim(tailsign) == '') {
			showErrorMessage("Please enter Tailsign","addAircraftsAlertDiv");
			return;
		}
		
		if(airlineId == null || $.trim(airlineId) == '') {
			showErrorMessage("Please select Airline","addAircraftsAlertDiv");
			return;
		}
		
		if(msn == null || $.trim(msn) == '' || !msnRE.test(msn)) {
			showErrorMessage("MSN is invalid","addAircraftsAlertDiv");
			return;
		}
		
		if(type == null || $.trim(type) == '') {
			showErrorMessage("Please select Type","addAircraftsAlertDiv");
			return;
		}
		
		if(aircraftConfiguration == null || $.trim(aircraftConfiguration) == '') {
			showErrorMessage("Please select Aircraft Configuration","addAircraftsAlertDiv");
			return;
		}
		
		if(platform == null || $.trim(platform) == '') {
			showErrorMessage("Please select Platform","addAircraftsAlertDiv");
			return;
		}
		
		if(isp == null || $.trim(isp) == '') {
			showErrorMessage("Please select ISP","addAircraftsAlertDiv");
			return;
		}
		
		if(swBaseLine == null || $.trim(swBaseLine) == '') {
			showErrorMessage("Please enter Software BaseLine","addAircraftsAlertDiv");
			return;
		}
		
		if(customerSw == null || $.trim(customerSw) == '') {
			showErrorMessage("Please enter Customer Software","addAircraftsAlertDiv");
			return;
		}
		
        var data = $.param({
        	action: "ADD_AIRCRAFT",
        	tailsign: tailsign,
        	noseNumber: noseNumber,
        	airlineId: airlineId,
        	msn: msn,
        	type: type,
        	aircraftSeatConfiguration: aircraftSeatConfiguration,
        	aircraftConfiguration: aircraftConfiguration,
        	platform: platform,
        	isp: isp,
        	eis: eis,
        	swBaseLine: swBaseLine,
        	customerSw: customerSw,
        	swinstalled: swinstalled
        });

        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/AircraftDAO.php', data, config)
        .success(function (data, status, headers, config) {
            if(data.state == -1) {
				showErrorMessage(data.message, 'addAircraftsAlertDiv');
				console.log('error');
            } else {
				modal.modal('hide')
            	handleSuccessAlert("Aircraft added successfully");
                $scope.getAircrafts();
            }
        });
	});
	
	function showErrorMessage(message, divId) {
		$("#"+divId).text(message);
		$("#"+divId).show();
	}
	
	function handleSuccessAlert(message) {
		$alertDiv = $('#successAlertDiv');
		$alertDiv.html(message);
		$alertDiv.show();
        runEffect();
	}
	
	function runEffect() {
		setTimeout(function(){
			var selectedEffect = '';
			var options = {};
			$("#successAlertDiv").hide();
		 }, 3000);
	}
	
	function deselectAllInFilter(filter) {
		$('#'+filter).selectpicker('deselectAll');
		$('#'+filter).selectpicker('refresh');
	}
	
	$scope.getAircrafts();
	$scope.loadAircraftSeatConfiguration();
});