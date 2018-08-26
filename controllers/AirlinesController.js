var app = angular.module('myApp', []);

app.controller('AirlinesController', function($scope, $http, $log, $window, $timeout, $parse) {

	var numberOfAirlines = 0;
	$('#addAirlineAlertDiv').hide();
	$('#successAlertDiv').hide();
	
	$('#airlinesTable').bootstrapTable();
	
	$scope.getAirlines = function() {
        var data = $.param({
            action: 'GET_AIRLINES'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/AirlinesDAO.php', data, config)
        .success(function (data, status, headers, config) {
        	$('#airlinesTable').bootstrapTable('load',data);
        	$('#loadingDiv').hide();
    		numberOfUsers = data.length;
        })
        .error(function (data, status, header, config) {
        });
	}
	
	$('#addAirlineModal').on('show.bs.modal', function (event) {
		var modal = $('#addAirlineModal');
		modal.find('.modal-body #name').val('');
		modal.find('.modal-body #acronym').val('');
		$('#addAirlineAlertDiv').hide();
	});
	
	$('#addAirline').click(function() {
		var modal = $('#addAirlineModal');
		var name = modal.find('.modal-body #name').val();
		var acronym = modal.find('.modal-body #acronym').val();
		
		if(name == null || $.trim(name) == '') {
			showErrorMessage("Please enter Airline Name","addAirlineAlertDiv");
			return;
		}
		
		if(acronym == null || $.trim(acronym) == '') {
			showErrorMessage("Please enter Acronym","addAirlineAlertDiv");
			return;
		}
		
        var data = $.param({
        	name: name,
        	acronym: acronym,
            action: 'ADD_AIRLINE'
        });
    
        var config = {
            headers : {
                'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8;'
            }
        };

        $http.post('../ajax/AirlinesDAO.php', data, config)
        .success(function (data, status, headers, config) {
			modal.find('.modal-body #name').val('');
			modal.find('.modal-body #acronym').val('');
			modal.modal('hide');
//			handleSuccessAlert("Airline added successfully");
			handleSuccessAlert(data);
			addToTable(name,acronym);
        }).error(function (data, status, header, config) {
        });
	});
	
	function addToTable(name, acronym) {
		$('#airlinesTable').bootstrapTable('insertRow', {
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

	$scope.getAirlines();
});