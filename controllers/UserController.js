var app = angular.module('myApp', []);
app.controller('UserController', function($scope, $http, $window) {
    init();
	var $table = $('#table');
	var $remove = $('#remove');
	var $submitEdit = $('#submit');
	var $resetPass = $('#resetPass');
	var $addUser = $('#addUser');
	var $addModal = $('#addUserModal');
	var numberOfUsers = 0;
	var selections = [];
	$('#addUserAlertDiv').hide();
	$('#successAlertDiv').hide();
	//var aircraftAcronyms = getAircraftAcronyms();
	//console.log(aircraftAcronyms);
	
	function getAircraftAcronyms() {
		$http.get("../common/getAircraftCodes.php")
			.success(function (data) {
				//console.log(data);
				return data;
			});
	}
	
	$(document).ready(function() {
		$('#airline').selectpicker();
		$('#role').selectpicker();
		$('#editAirline').selectpicker();
		$('#editRole').selectpicker();
		
//        $('#airline').multiselect({
//			nonSelectedText: 'Select Airline(s)',
//			enableCaseInsensitiveFiltering: true,
//			filterBehavior: 'both',
//			buttonWidth: '400px',
//			maxHeight: 200,
//			includeSelectAllOption: true,
//			enableFiltering: true,
//			dropCenter: true,
//			onChange: function(option, checked, select, value) {
//				var modal = $('#myModal');
//				var values = [];
//				if (modal.find('.modal-body #role').val() == "Customer") {
//					$('#airline option').each(function() {
//						//console.log($(this).val());
//                        if ($(this).val() !== $(option).val()) {
//                            values.push($(this).val());
//                        }
//                    });
//					$('#airline').multiselect('deselect', values);
//				}
//			}
//		});

//        $('#airline').selectpicker({
//        	noneSelectedText: 'Select Airline(s)',
//			onChange: function(option, checked, select, value) {
//				var modal = $('#myModal');
//				var values = [];
//				if (modal.find('.modal-body #role').val() == "Customer") {
//					$('#airline option').each(function() {
//						//console.log($(this).val());
//                        if ($(this).val() !== $(option).val()) {
//                            values.push($(this).val());
//                        }
//                    });
//					$('#airline').selectpicker('deselect', values);
//				}
//			}
//		});
        
//		$('#role').multiselect({
//			buttonWidth: '163px',
//			nonSelectedText: 'Select Role',
//			onChange: function(option, checked, select, value) {
//				var role = $(option).val();
//				if (role == "Admin") {
//					var values = [];
//					$('#airline').multiselect('disable');
//					$('#airline option').each(function() {
//						values.push($(this).val());
//					});
//					$('#airline').multiselect('deselect', values);
//				} else if (role == "Customer") {
//					var values = [];
//					$('#editAirline option').each(function() {
//                        values.push($(this).val());
//                    });
//					$('#editAirline').multiselect('deselect', values);
//				} else {
//					$('#airline').multiselect('enable');
//				}
//				$('#airline').multiselect('deselect', values);
//			}
//		});
		
//		$('#editAirline').multiselect({
//			nonSelectedText: 'Select Airline(s)',
//			enableCaseInsensitiveFiltering: true,
//			filterBehavior: 'both',
//			buttonWidth: '400px',
//			maxHeight: 200,
//			includeSelectAllOption: true,
//			enableFiltering: true,
//			dropCenter: true,
//			onChange: function(option, checked, select, value) {
//				var modal = $('#editUserModal');
//				var values = [];
//				if (modal.find('.modal-body #editRole').val() == "Customer") {
//					$('#editAirline option').each(function() {
//                        if ($(this).val() !== $(option).val()) {
//                            values.push($(this).val());
//                        }
//                    });
//					$('#editAirline').multiselect('deselect', values);
//				}
//			}
//		});
//		$('#editRole').multiselect({
//			buttonWidth: '163px',
//			nonSelectedText: 'Check an option!',
//			onChange: function(option, checked, select, value) {
//				var role = $(option).val();
//				if (role == "Admin") {
//					var values = [];
//					$('#editAirline').multiselect('disable');
//					$('#editAirline option').each(function() {
//						values.push($(this).val());
//					});
//					$('#editAirline').multiselect('deselect', values);
//				} else if (role == "Customer") {
//					var values = [];
//					$('#editAirline option').each(function() {
//                        values.push($(this).val());
//                    });
//					$('#editAirline').multiselect('deselect', values);
//				}  else {
//					$('#editAirline').multiselect('enable');
//				}
//				$('#editAirline').multiselect('deselect', values);
//			}
//		});
		
		$('#editRole').on('changed.bs.select', function (e) {
			role = $('#editRole').val();
			console.log("Role selected: " + $('#editRole').val());
			if(role == 'Admin') {
				disableFilter('editAirline');
			} else {
				enableFilter('editAirline');
			}
			
		});
		
    });
	var submit = $('#submit');
	submit.click(function () {
		var modal = $('#addUserModal');
		var airline = modal.find('.modal-body #airline').val();
    });
	
	
    function init() {
		var userArray = [];
		getUsers();
    }
	
	function onlineFormatter(value) {
		if (value === 'Online') {
			return '<div  style="color: green">' +
                value.substring(1) +
                '</div>';
		} else {
			return '<div >' +
                value.substring(1) +
                '</div>';
		}
	}
	
	function buildTable() {
		
		userArray = [];
		var status = '';
		numberOfUsers = $scope.users.length;
		for (var i = 0; i < $scope.users.length; i++) {
			if ($scope.users[i]['status'] === "Online") {
				status ='<div  style="color: green">Online</div>';
			} else {
				status = "Offline";
			}
			userArray[i] = { index: i, userid: $scope.users[i][0], email: $scope.users[i][1], role: $scope.users[i][2], 
			airlineid: $scope.users[i]['approvedAirlines'], account: $scope.users[i]["isactive"], status: status,
			edit: '<a role="button" style="cursor:pointer;" data-toggle="modal" data-target="#editUserModal" data-index=' + i + ' data-uid="' + $scope.users[i][0] + '" data-email="' + $scope.users[i]['email'] + '" data-role="' + $scope.users[i][2] + '" data-airline="' + $scope.users[i][4] + '" data-isactive="' + $scope.users[i][3] + '""><span class="fa fa-edit"></span></a>',
			resetPass: '<a role="button" style="cursor:pointer;" data-toggle="modal" data-target="#resetPasswordModal" data-uid="' + $scope.users[i][0] + '"><span class="fa fa-retweet"></span></a>',
			lastActive: $scope.users[i]['lastActive']};
			
		}
		$('#table').bootstrapTable({
			
			columns: [{
					field: 'state',
					checkbox: true
				},{
					field: 'userid',
					title: 'ID',
					sortable: true,
					switchable: true,
					align: 'center',
					valign: 'top'
				}, {
					field: 'email',
					title: 'Email',
					sortable: true,
					switchable: false,
					align: 'left',
					valign: 'top'
				}, {
					field: 'role',
					title: 'Role',
					sortable: true,
					align: 'center',
					valign: 'top'
				}, {
					field: 'airlineid',
					title: 'Airline ID',
					sortable: true,
					align: 'center',
					valign: 'top'
				}, {
					field: 'account',
					title: 'Account',
					sortable: true,
					align: 'center',
					valign: 'top'
				},{
					field: 'status',
					title: 'Status',
					sortable: true,
					align: 'center',
					valign: 'top'
				},{
					field: 'lastActive',
					title: 'Last Activity',
					align: 'center',
					valign: 'top',
					sortable: true
				},{
					field: 'resetPass',
					title: 'Reset Pwd',
					align: 'center',
					valign: 'top'
				},{
					field: 'edit',
					title: 'Edit User',
					align: 'center',
					valign: 'top'
				}],
				checkboxHeader: false,
				checkboxEnable: true,
				striped: true,
				pagination: true,
				pageSize: 25,
				pageList: [25, 50, 100],
				search: true,
				data: userArray
		});
		
		$('#loadingDiv').hide();
	}
	
	function getUsers() {
		$http.get("../common/getUsers.php")
			.success(function (data) {
//				console.log(JSON.stringify('User: ' + data));
				$scope.users = data;
				buildTable();
			});
	}
	
	function postData() {

		$http({
			url: "../common/deleteUsers.php",
			method: "POST",
			data: {'selectedUsers': $scope.selectedUsers,},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				if ($scope.selectedUsers.length > 0) {
					location.reload();
				}

			}).error(function (data, status, headers, config) {});
	}
	
	$table.on('check.bs.table uncheck.bs.table ' +
                'check-all.bs.table uncheck-all.bs.table', function () {
            $remove.prop('disabled', !$table.bootstrapTable('getSelections').length);
            // save your data, here just save the current page
            selections = getIdSelections();
            // push or splice the selections if you want to save all data selections
        });
	$table.on('sort.bs.table', function (name, order) {
		var uid;
		var table = document.getElementById("table");
		//console.log(name);
		for (var i = 0, row; row = table.rows[i]; i++) {
			uid = row.cells[1].innerHTML;
			//console.log("Index: " + i + " UID: " + uid);
		}
	});
	
	function postData(ids) {

		$http({
			url: "../common/deleteUsers.php",
			method: "POST",
			data: {'selectedUsers': ids},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				var dataSuccess = Boolean(data['success']);
				if (dataSuccess) {
					handleSuccessAlert(data['message']);
					$table.bootstrapTable('remove', {
						field: 'userid',
						values: ids
					});
					$remove.prop('disabled', true);
					numberOfUsers -= ids.length;
				}
			}).error(function (data, status, headers, config) {});
	}	
	
	function confirmDelete() {
		var ids = getIdSelections();
		if (ids.length == 0) return;
		notyConfirm();
	}
	
    function notyConfirm(){
        noty({
            text: 'Are you sure you want to delete selected users?',
            layout: 'topCenter',
            buttons: [
                    {addClass: 'btn btn-success btn-clean', text: 'Ok', onClick: function($noty) {
            			var ids = getIdSelections();
            			postData(ids);
                        $noty.close();
                    }
                    },
                    {addClass: 'btn btn-danger btn-clean', text: 'Cancel', onClick: function($noty) {
                        $noty.close();
                        }
                    }
                ]
        })                                                    
    }                                            
	
	$remove.click(function () {
		confirmDelete()
        });
	
	function getIdSelections() {
        return $.map($table.bootstrapTable('getSelections'), function (row) {
            return row.userid
        });
    }
	
	function runEffect() {
		setTimeout(function(){
			var selectedEffect = '';
			var options = {};
			$("#successAlertDiv").hide();
		 }, 5000);
}
	
	function handleSuccessAlert(message) {
		$alertDiv = $('#successAlertDiv');
		$alertDiv.html(message);
		$alertDiv.show();
        runEffect();
	}
	
	function addToTable(userid, email, role, airlineid, airlineString) {
		var airlinesString = '';
		var airlinesIdString = '';
		if (airlineid) {
			for (var i = 0; i < airlineid.length; i++) {
				airlinesIdString += airlineid[i].toString() + ", ";
			}
		}
		airlinesIdString = airlinesIdString.substring(0, airlinesIdString.length - 2);
		if (role == "Admin") {
			airlinesIdString = "0";
		}
		$table.bootstrapTable('insertRow', {
			index: ++numberOfUsers,
			row: {
				userid: userid,
				email: email,
				role: role,
				airlineid: airlineString,
				account: "Active",
				status: "Offline",
				lastActive: "-",
				edit: '<a role="button" style="cursor:pointer;" data-toggle="modal" data-target="#editUserModal" data-index="' + numberOfUsers + '" data-uid="' + userid + '" data-email="' + email + '" data-role="' + role + '" data-airline="' + airlinesIdString + '" data-isactive=1><span class="fa fa-edit"></span></a>',
				resetPass: '<a role="button" style="cursor:pointer;" data-toggle="modal" data-target="#resetPasswordModal" data-uid="' + userid + '"><span class="fa fa-retweet"></span></a>'
			}
		});
	}
	
	function handleAirlineArray(airline) {
		if (airline) {
			for (var i = 0; i < airline.length ; i++) {
				airline[i] = parseInt(airline[i]);
			}
			if (airline[0] == -1) {
				airline = [-1];
			}
		}
		return airline
	}

	$addUser.click(function() {
		var modal = $('#addUserModal');
		var email = modal.find('.modal-body #email').val();
		var password = modal.find('.modal-body #password').val();
		var confirmPassword = modal.find('.modal-body #confirmpassword').val();
		var role = modal.find('.modal-body #role').val();
		var airline = modal.find('.modal-body #airline').val();
		
		console.log('Role: ' + role);
		
		if(email == null || $.trim(email) == '') {
			showErrorMessage("Please enter Email","addUserAlertDiv");
			return;
		}
		
		if(password == null || $.trim(password) == '') {
			showErrorMessage("Please enter Password","addUserAlertDiv");
			return;
		}
		
		if(confirmPassword == null || $.trim(confirmPassword) == '') {
			showErrorMessage("Please enter Confirm Password","addUserAlertDiv");
			return;
		}
		
		if($.trim(password) !== $.trim(confirmPassword)) {
			showErrorMessage("Password and Confirm Password are not same","addUserAlertDiv");
			return;
		}
		
		if (role == null) {
			showErrorMessage("Please select a Role","addUserAlertDiv");
			return;
		}
		
		if (airline == null && role != "Admin") {
			showErrorMessage("Please select an airline","addUserAlertDiv");
			return;
		}
		
		$http({
			url: "../common/addUser.php",
			method: "POST",
			data: { 'email': email,
					'password': password,
					'confirmpassword': confirmPassword,
					'role': role,
					'airline': airline
				},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				var dataSuccess = Boolean(data['success']);
				if (dataSuccess) {
					addToTable(data['uid'], email, role, airline, data['acronyms']);
					modal.find('.modal-body #email').val('');
					modal.find('.modal-body #password').val('');
					modal.find('.modal-body #confirmpassword').val('');
					
					deselectAllInFilter('airline');
					$('#role').selectpicker('val', 'None');		
					$('#addUserAlertDiv').hide();

					modal.modal('hide');
					handleSuccessAlert(data['message']);
					
				} else if (data['success'] == false) {
//					handleAlerts(data['message'], "myModalLabel");
					showErrorMessage(data['message'], 'addUserAlertDiv');
					/*
					var alert = document.createElement("div");
					alert.className += "alert alert-danger";
					var text = document.createTextNode(data['message']);
					alert.appendChild(text);
					var modalTitle = document.getElementById("myModalLabel");
					modalTitle.appendChild(alert);
					*/
				}
			}).error(function (data, status, headers, config) {});
		
	});
	
	function handleAlerts(message, modalLabel) {
		var errorDiv = document.getElementById("alert");
		//console.log(errorDiv);
		if (errorDiv) {
			errorDiv.innerHTML = message;
			return;
		} else {
			var alert = document.createElement("div");
			alert.className += "alert alert-danger";
			alert.id = "alert";
			alert.role = "alert";
			var text = document.createTextNode(message);
			alert.appendChild(text);
			var modalTitle = document.getElementById(modalLabel);
			modalTitle.appendChild(alert);
		}
	}
	
	function isActiveString(isactive) {
		switch(parseInt(isactive)) {
			case 1:
				return "Active";
			case 0:
				return "Inactive";
		}
	}
	
	function isActiveCode(isactive) {
		switch(isactive) {
			case "Active":
				return 1;
			case "Inactive":
				return 0;
		}
	}
	
	function findIndex(email) {
		var userEmail;
		var table = document.getElementById("table");
		for (var i = 0, row; row = table.rows[i]; i++) {
			userEmail = row.cells[2].innerHTML;
			if (userEmail == email) return i;
		}	
	}
	
	function editTable(index, userid, email, role, airline, isactive, acronyms) {
		airlinesIdString = '';
		if (airline) {
			for (var i = 0; i < airline.length; i++) {
				airlinesIdString += airline[i].toString() + ", ";
			}
		}
		airlinesIdString = airlinesIdString.substring(0, airlinesIdString.length - 2);
		if (role == "Admin") {
			airlinesIdString = "0";
		}
		
		$table.bootstrapTable('updateRow', {
			index: index,
			row: {
				index: index,
				userid: userid,
				email: email,
				role: role,
				airlineid: acronyms,
				account: isActiveString(isactive),
				status: "Offline",
				edit: '<a role="button" style="cursor:pointer;" data-toggle="modal" data-target="#editUserModal" data-index="' + index + '" data-uid="' + userid + '" data-email="' + email + '" data-role="' + role + '" data-airline="' + airlinesIdString + '" data-isactive="' + isactive + '""><span class="fa fa-edit"></span></a>',
				resetPass: '<a role="button" style="cursor:pointer;" data-toggle="modal" data-target="#resetPasswordModal" data-uid="' + userid + '"><span class="fa fa-retweet"></span></a>'
			}
		});
	}
	
	$resetPass.click(function() {
		var modal = $('#resetPasswordModal');
		var uid = modal.find('.modal-body #uid').val();
		var password = modal.find('.modal-body #password').val();
		var confirmPassword = modal.find('.modal-body #confirmpassword').val();
		
		if(password == null || $.trim(password) == '') {
			showErrorMessage("Please enter Password","resetPasswordAlertDiv");
			return;
		}
		
		if(confirmPassword == null || $.trim(confirmPassword) == '') {
			showErrorMessage("Please enter Confirm Password","resetPasswordAlertDiv");
			return;
		}
		
		if($.trim(password) !== $.trim(confirmPassword)) {
			showErrorMessage("Password and Confirm Password are not same","resetPasswordAlertDiv");
			return;
		}
		
		$http({
			url: "../common/changePassword.php",
			method: "POST",
			data: { 'uid': uid,
					'currentPassword': ' ',
					'password': password,
					'confirmPassword': confirmPassword,
					'isAdmin': 1
				},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				//console.log(data);
				var dataSuccess = Boolean(data['success']);
				if (dataSuccess) {
					modal.modal('hide');
					modal.find('.modal-body #password').val('');
					modal.find('.modal-body #confirmpassword').val('');
					handleSuccessAlert(data['message']);
				} else if (data['success'] == false) {
					showErrorMessage(data['message'], 'resetPasswordAlertDiv');
				}
			});
		
	});
	
	$submitEdit.click(function() {
		var modal = $('#editUserModal');
		var uid = modal.find('.modal-body #uid').val();
		var email = modal.find('.modal-body #email').val();
		var role = modal.find('.modal-body #editRole').val();
		var airline = modal.find('.modal-body #editAirline').val();
		var isactive = modal.find('.modal-body #isactive').val();
		
		if (airline == null && role != "Admin") {
			showErrorMessage("Please select an Airline", "editUserAlertDiv");
			return;
		}

		$http({
			url: "../common/updateUser.php",
			method: "POST",
			data: { 'uid': uid,
					'email': email,
					'role': role,
					'airline': airline,
					'isactive': isactive
				},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				var dataSuccess = Boolean(data['success']);
				var oldEmail = data['oldEmail'];
				var index = findIndex(oldEmail) - 1;
				console.log('Index: ' + index);
				if (dataSuccess) {
					modal.modal('hide');
					console.log('Old Email: ' + oldEmail);
					console.log('Email: ' + email);
					editTable(index, uid, email, role, airline, isactive, data['acronyms']);
					handleSuccessAlert(data['message']);
				} else if (data['success'] == false) {
					showErrorMessage(data['message'], "editUserAlertDiv"); 	
				}
			}).error(function (data, status, headers, config) {});
		});
		
		
	
	$('#resetPasswordModal').on('show.bs.modal', function (event) {
		var modal = $(this);
		var button = $(event.relatedTarget);
		var uid = button.data('uid');
		modal.find('.modal-body #uid').val(uid);
//		var errorDiv = document.getElementById("alert");
//		if (errorDiv) {
//			$('#alert').remove();
//		}
		$('#resetPasswordAlertDiv').hide();
	});
	
	$('#editUserModal').on('show.bs.modal', function (event) {
		deselectAllInFilter('editAirline');
		
		var button = $(event.relatedTarget);
		var index = button.data('index');
		var uid = button.data('uid');
		var email = button.data('email');
		var role = button.data('role');
		var airlines = ''+button.data('airline');
		var status = button.data('isactive');
		var modal = $(this);
		
		modal.find('.modal-title').text('Edit user: ' + email);
		modal.find('.modal-body #index').val(index);
		modal.find('.modal-body #uid').val(uid);
		modal.find('.modal-body #email').val(email);
		$('#isactive').selectpicker('val', status);
		//console.log(index);
		
		deselectAllInFilter("editRole");
		$('#editRole').selectpicker('val', role);
		
//		var values = [];
//		$('#editRole option').each(function() {
//			values.push($(this).val());
//		});
//		$('#editRole').multiselect('deselect', values);
		
		if (airlines == "-1" || airlines == "-1, ") {
//			$('.modal-body #editAirline').multiselect('selectAll', false);
			deselectAllInFilter('editAirline');
		} else if (airlines == 0) {
			
		} else {
//			airlines = airlines.toString() + ", ";
//			airlines = airlines.split(", ");
			console.log('Airlines in edit: ' + airlines);
//			$('.modal-body #editAirline').multiselect('select', airlines);
//			$('#editAirline').selectpicker('val', airlines);
			if (airlines.indexOf(',') > -1) {
				airlines = airlines.replace(/, /g, ",");
				var airlineArray = airlines.split(",");
				$('#editAirline').selectpicker('val', airlineArray);
			} else {
				$('#editAirline').selectpicker('val', airlines);
			}
		}
		if (role == "Admin") {
//			var values = [];
//			$('#editAirline').multiselect('disable');
//			$('#editAirline option').each(function() {
//				values.push($(this).val());
//			});
//			$('#editAirline').multiselect('deselect', values);
			deselectAllInFilter('editAirline');
			disableFilter('editAirline');
		} else {
//			$('#editAirline').multiselect('enable');
			$('#editAirline').selectpicker('val', airlineArray);
			enableFilter('editAirline');
		}
		$('#editUserAlertDiv').hide();
	});
	
	$('#addUserModal').on('show.bs.modal', function (event) {
		var modal = $('#addUserModal');
		modal.find('.modal-body #email').val('');
		modal.find('.modal-body #password').val('');
		modal.find('.modal-body #confirmpassword').val('');
		deselectAllInFilter('airline');
		$('#role').selectpicker('val', 'None');		
		$('#addUserAlertDiv').hide();
	});
	
	function showErrorMessage(message, divId) {
		$("#"+divId).text(message);
		$("#"+divId).show();
	}
	
	function deselectAllInFilter(filter) {
		$('#'+filter).selectpicker('deselectAll');
		$('#'+filter).selectpicker('refresh');
	}
	
	function disableFilter(filter) {
		$('#'+filter).prop('disabled', true);
		$('#'+filter).selectpicker('refresh');		
	}
	
	function enableFilter(filter) {
		$('#'+filter).prop('disabled', false);
		$('#'+filter).selectpicker('refresh');		
	}
});