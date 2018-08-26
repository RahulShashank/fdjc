var app = angular.module('myApp', []);

app.controller('LRUCountController', function($scope, $http, $log, $window, $timeout, $parse) {

	var rowCount = 0;
	$('#addLRUCountAlertDiv').hide();
	$('#successAlertDiv').hide();
	var lruCountArray = [];
	
	$scope.getLRUCount = function() {
		$('#loadingDiv').show();

        var data = $.param({
            action: 'GET_LRU_COUNT'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/LRUCountDAO.php', data, config)
        .success(function (data, status, headers, config) {
			$scope.lruCountData = data;
			buildTable();
    		rowCount = data.length;
    		$('#loadingDiv').hide();
        })
        .error(function (data, status, header, config) {
        });
	}
	
	function buildTable() {
		lruCountArray = [];
		var status = '';
		rowCount = $scope.lruCountData.length;
		for (var i = 0; i < $scope.lruCountData.length; i++) {
			lruCountArray[i] = {
					index: i, 
					id: $scope.lruCountData[i]['id'], 
					airlineId: $scope.lruCountData[i]['airlineId'], 
					airlineName: $scope.lruCountData[i]['airlineName'], 
					platform: $scope.lruCountData[i]['platform'], 
					configName: $scope.lruCountData[i]['configName'], 
					dsuCount: $scope.lruCountData[i]['dsuCount'], 
					laicCount: $scope.lruCountData[i]['laicCount'], 
					icmtCount: $scope.lruCountData[i]['icmtCount'], 
					adbgCount: $scope.lruCountData[i]['adbgCount'], 
					qsebCount: $scope.lruCountData[i]['qsebCount'], 
					sdbCount: $scope.lruCountData[i]['sdbCount'], 
					svduCount: $scope.lruCountData[i]['svduCount'], 
					tpmuCount: $scope.lruCountData[i]['tpmuCount'], 
					tpcuCount: $scope.lruCountData[i]['tpcuCount'], 
					edit: '<a role="button" style="cursor:pointer;" data-toggle="modal" data-target="#editLRUCountModal" data-index="' + i + '"><span class="fa fa-edit"></span></a>'};
		}
		
		$('#lruCountTable').bootstrapTable({
			columns: [{
				field: 'id',
				title: 'Id',
				visible: false
			}, {
				field: 'airlineId',
				title: 'Airline Id',
				visible: false
			}, {
				field: 'airlineName',
				title: 'Airline Name',
				sortable: true,
				switchable: false,
				align: 'left',
				width: '125px',
				valign: 'top'
			}, {
				field: 'platform',
				title: 'Platform',
				sortable: true,
				align: 'center',
				width: '75px',
				valign: 'top'
			}, {
				field: 'configName',
				title: 'Config',
				sortable: true,
				align: 'center',
				width: '200px',
				valign: 'top'
			}, {
				field: 'dsuCount',
				title: 'DSU Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'laicCount',
				title: 'LAIC Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'icmtCount',
				title: 'ICMT Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'adbgCount',
				title: 'ADBG Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'qsebCount',
				title: 'QSEB Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'sdbCount',
				title: 'SDB Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'svduCount',
				title: 'SVDU Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'tpmuCount',
				title: 'TPMU Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'tpcuCount',
				title: 'TPCU Count',
				align: 'center',
				width: '75px',
				valign: 'top'
			},{
				field: 'edit',
				title: 'Edit',
				align: 'center',
				width: '75px',
				valign: 'top'
			}],
				striped: true,
				pagination: true,
				pageSize: 25,
				pageList: [25, 50, 100],
				search: true,
				data: lruCountArray
		});
	}
	
	$('#addLRUCountModal').on('show.bs.modal', function (event) {
		$('#addLRUCountAlertDiv').hide();

		$('#airline').empty();
		$('#platform').empty();
		$('#configuration').empty();
    	
		var modal = $('#addLRUCountModal');
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
	    	loadConfigurations();
		});
    }
    
    function loadConfigurations() {
    	$http.get("../ajax/AircraftDAO.php?action=GET_AIRCRAFT_TYPES_ACTION")
		.success(function (data) {
			for (var i = 0; i < data.length; i++) {
				var configuration = data[i];
				$('#configuration').append('<option value="' + configuration.type + '">' + configuration.type + '</option>');
		    }
			
			$('#configuration').selectpicker('refresh');
		});
    }
	
	
	$('#addLRUCount').click(function() {
		var modal = $('#addLRUCountModal');
		var airlineId = modal.find('.modal-body #airline').val();
		var platform = modal.find('.modal-body #platform').val();
		var configuration = modal.find('.modal-body #configuration').val();
		var dsuCount = modal.find('.modal-body #dsuCount').val();
		var laicCount = modal.find('.modal-body #laicCount').val();
		var icmtCount = modal.find('.modal-body #icmtCount').val();
		var adbgCount = modal.find('.modal-body #adbgCount').val();
		var qsebCount = modal.find('.modal-body #qsebCount').val();
		var sdbCount = modal.find('.modal-body #sdbCount').val();
		var svduCount = modal.find('.modal-body #svduCount').val();
		var tpmuCount = modal.find('.modal-body #tpmuCount').val();
		var tpcuCount = modal.find('.modal-body #tpcuCount').val();
		
		if(airlineId == null || $.trim(airlineId) == '') {
			showErrorMessage("Please select Airline Name","addLRUCountAlertDiv");
			return;
		}
		
		if(platform == null || $.trim(platform) == '') {
			showErrorMessage("Please select Platform","addLRUCountAlertDiv");
			return;
		}
		
		if(configuration == null || $.trim(configuration) == '') {
			showErrorMessage("Please select Configuration","addLRUCountAlertDiv");
			return;
		}
		
		if(dsuCount == null || $.trim(dsuCount) == '') {
			showErrorMessage("Please enter DSU Count","addLRUCountAlertDiv");
			return;
		}
		
		if(laicCount == null || $.trim(laicCount) == '') {
			showErrorMessage("Please enter LAIC Count","addLRUCountAlertDiv");
			return;
		}
		
		if(icmtCount == null || $.trim(icmtCount) == '') {
			showErrorMessage("Please enter ICMT Count","addLRUCountAlertDiv");
			return;
		}
		
		if(adbgCount == null || $.trim(adbgCount) == '') {
			showErrorMessage("Please enter ADBG Count","addLRUCountAlertDiv");
			return;
		}
		
		if(qsebCount == null || $.trim(qsebCount) == '') {
			showErrorMessage("Please enter QSEB Count","addLRUCountAlertDiv");
			return;
		}
		
		if(sdbCount == null || $.trim(sdbCount) == '') {
			showErrorMessage("Please enter SDB Count","addLRUCountAlertDiv");
			return;
		}
		
		if(svduCount == null || $.trim(svduCount) == '') {
			showErrorMessage("Please enter SVDU Count","addLRUCountAlertDiv");
			return;
		}
		
		if(tpmuCount == null || $.trim(tpmuCount) == '') {
			showErrorMessage("Please enter TPMU Count","addLRUCountAlertDiv");
			return;
		}
		
		if(tpcuCount == null || $.trim(tpcuCount) == '') {
			showErrorMessage("Please enter TPCU Count","addLRUCountAlertDiv");
			return;
		}
		
        var data = $.param({
        	action: "ADD_LRU_COUNT",
        	airlineId: airlineId,
        	platform: platform,
        	configType: configuration,
        	dsuCount: dsuCount,
        	laicCount: laicCount,
        	icmtCount: icmtCount,
        	adbgCount: adbgCount,
        	qsebCount: qsebCount,
        	sdbCount: sdbCount,
        	svduCount: svduCount,
        	tpmuCount: tpmuCount,
        	tpcuCount: tpcuCount
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/LRUCountDAO.php', data, config)
        .success(function (data, status, headers, config) {
        	if(data == "EXISTS") {
				showErrorMessage("This airline, platform and configuration information is already added", 'addLRUCountAlertDiv');
        	} else if(data == "SUCCESS") {
				modal.modal('hide');
            	handleSuccessAlert("LRU Count added successfully");
            	$('#lruCountTable').bootstrapTable('destroy');
            	$scope.getLRUCount();
        	} else if (data == "ERROR") {
        		showErrorMessage("Unexpected error occured while adding LRU Count", 'addLRUCountAlertDiv');
        	}
        });
	});
	
	$('#editLRUCountModal').on('show.bs.modal', function (event) {
		$('#editLRUCountAlertDiv').hide();
		var button = $(event.relatedTarget);
		var index = button.data('index');
		
		var modal = $('#editLRUCountModal');
		modal.find('.modal-body #id').val(lruCountArray[index]['id']);
		modal.find('.modal-body #airline').val(lruCountArray[index]['airlineName']);
		modal.find('.modal-body #platform').val(lruCountArray[index]['platform']);
		modal.find('.modal-body #configuration').val(lruCountArray[index]['configName']);
		modal.find('.modal-body #dsuCount').val(lruCountArray[index]['dsuCount']);
		modal.find('.modal-body #laicCount').val(lruCountArray[index]['laicCount']);
		modal.find('.modal-body #icmtCount').val(lruCountArray[index]['icmtCount']);
		modal.find('.modal-body #adbgCount').val(lruCountArray[index]['adbgCount']);
		modal.find('.modal-body #qsebCount').val(lruCountArray[index]['qsebCount']);
		modal.find('.modal-body #sdbCount').val(lruCountArray[index]['sdbCount']);
		modal.find('.modal-body #svduCount').val(lruCountArray[index]['svduCount']);
		modal.find('.modal-body #tpmuCount').val(lruCountArray[index]['tpmuCount']);
		modal.find('.modal-body #tpcuCount').val(lruCountArray[index]['tpcuCount']);

	});
	
	$('#editLRUCount').click(function() {
		var modal = $('#editLRUCountModal');
		var id = modal.find('.modal-body #id').val();
		var dsuCount = modal.find('.modal-body #dsuCount').val();
		var laicCount = modal.find('.modal-body #laicCount').val();
		var icmtCount = modal.find('.modal-body #icmtCount').val();
		var adbgCount = modal.find('.modal-body #adbgCount').val();
		var qsebCount = modal.find('.modal-body #qsebCount').val();
		var sdbCount = modal.find('.modal-body #sdbCount').val();
		var svduCount = modal.find('.modal-body #svduCount').val();
		var tpmuCount = modal.find('.modal-body #tpmuCount').val();
		var tpcuCount = modal.find('.modal-body #tpcuCount').val();
		
		if(dsuCount == null || $.trim(dsuCount) == '') {
			showErrorMessage("Please enter DSU Count","editLRUCountAlertDiv");
			return;
		}
		
		if(laicCount == null || $.trim(laicCount) == '') {
			showErrorMessage("Please enter LAIC Count","editLRUCountAlertDiv");
			return;
		}
		
		if(icmtCount == null || $.trim(icmtCount) == '') {
			showErrorMessage("Please enter ICMT Count","editLRUCountAlertDiv");
			return;
		}
		
		if(adbgCount == null || $.trim(adbgCount) == '') {
			showErrorMessage("Please enter ADBG Count","editLRUCountAlertDiv");
			return;
		}
		
		if(qsebCount == null || $.trim(qsebCount) == '') {
			showErrorMessage("Please enter QSEB Count","editLRUCountAlertDiv");
			return;
		}
		
		if(sdbCount == null || $.trim(sdbCount) == '') {
			showErrorMessage("Please enter SDB Count","editLRUCountAlertDiv");
			return;
		}
		
		if(svduCount == null || $.trim(svduCount) == '') {
			showErrorMessage("Please enter SVDU Count","editLRUCountAlertDiv");
			return;
		}
		
		if(tpmuCount == null || $.trim(tpmuCount) == '') {
			showErrorMessage("Please enter TPMU Count","editLRUCountAlertDiv");
			return;
		}
		
		if(tpcuCount == null || $.trim(tpcuCount) == '') {
			showErrorMessage("Please enter TPCU Count","editLRUCountAlertDiv");
			return;
		}
		
        var data = $.param({
        	action: "UPDATE_LRU_COUNT",
        	id: id,
        	dsuCount: dsuCount,
        	laicCount: laicCount,
        	icmtCount: icmtCount,
        	adbgCount: adbgCount,
        	qsebCount: qsebCount,
        	sdbCount: sdbCount,
        	svduCount: svduCount,
        	tpmuCount: tpmuCount,
        	tpcuCount: tpcuCount
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/LRUCountDAO.php', data, config)
        .success(function (data, status, headers, config) {
        	if(data == "1") {
				modal.modal('hide');
        		handleSuccessAlert("LRU Count updated successfully");
            	$('#lruCountTable').bootstrapTable('destroy');
            	$scope.getLRUCount();
        	} else {
        		showErrorMessage("Unable to update the LRU Count details", 'editLRUCountAlertDiv');
        	}
        });
	});
	
	function addToTable(name, acronym) {
		$('#lruCountTable').bootstrapTable('insertRow', {
			index: ++numberOfUsers,
			row: {
				name: name,
				acronym: acronym
			}
		});
	}
	
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
	
	$scope.getLRUCount();
});