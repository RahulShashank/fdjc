app.controller('userCtrl', function($scope, $http, $window) {
    init();
	var $table = $('#table');
	var $remove = $('#remove');
	var $submitEdit = $('#submit');
	var $resetPass = $('#resetPass');
	var $addUser = $('#addUser');
	var $addModal = $('#myModal');
	var numberOfUsers = 0;
	var selections = [];
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
        $('#airline').multiselect({
			nonSelectedText: 'Select Airline(s)',
			enableCaseInsensitiveFiltering: true,
			filterBehavior: 'both',
			buttonWidth: '400px',
			maxHeight: 200,
			includeSelectAllOption: true,
			enableFiltering: true,
			dropCenter: true,
			onChange: function(option, checked, select, value) {
				var modal = $('#myModal');
				var values = [];
				if (modal.find('.modal-body #role').val() == "Customer") {
					$('#airline option').each(function() {
						//console.log($(this).val());
                        if ($(this).val() !== $(option).val()) {
                            values.push($(this).val());
                        }
                    });
					$('#airline').multiselect('deselect', values);
				}
			}
		});
		$('#role').multiselect({
			buttonWidth: '163px',
			nonSelectedText: 'Select Role',
			onChange: function(option, checked, select, value) {
				var role = $(option).val();
				if (role == "Admin") {
					var values = [];
					$('#airline').multiselect('disable');
					$('#airline option').each(function() {
						values.push($(this).val());
					});
					$('#airline').multiselect('deselect', values);
				} else if (role == "Customer") {
					var values = [];
					$('#editAirline option').each(function() {
                        values.push($(this).val());
                    });
					$('#editAirline').multiselect('deselect', values);
				} else {
					$('#airline').multiselect('enable');
				}
				$('#airline').multiselect('deselect', values);
			}
		});
		$('#editAirline').multiselect({
			nonSelectedText: 'Select Airline(s)',
			enableCaseInsensitiveFiltering: true,
			filterBehavior: 'both',
			buttonWidth: '400px',
			maxHeight: 200,
			includeSelectAllOption: true,
			enableFiltering: true,
			dropCenter: true,
			onChange: function(option, checked, select, value) {
				var modal = $('#myModal1');
				var values = [];
				if (modal.find('.modal-body #editRole').val() == "Customer") {
					$('#editAirline option').each(function() {
                        if ($(this).val() !== $(option).val()) {
                            values.push($(this).val());
                        }
                    });
					$('#editAirline').multiselect('deselect', values);
				}
			}
		});
		$('#editRole').multiselect({
			buttonWidth: '163px',
			nonSelectedText: 'Check an option!',
			onChange: function(option, checked, select, value) {
				var role = $(option).val();
				if (role == "Admin") {
					var values = [];
					$('#editAirline').multiselect('disable');
					$('#editAirline option').each(function() {
						values.push($(this).val());
					});
					$('#editAirline').multiselect('deselect', values);
				} else if (role == "Customer") {
					var values = [];
					$('#editAirline option').each(function() {
                        values.push($(this).val());
                    });
					$('#editAirline').multiselect('deselect', values);
				}  else {
					$('#editAirline').multiselect('enable');
				}
				$('#editAirline').multiselect('deselect', values);
			}
		});
    });
	var submit = $('#submit');
	submit.click(function () {
		var modal = $('#myModal');
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
			edit: '<a role="button" data-toggle="modal" data-target="#myModal1" data-index=' + i + ' data-uid="' + $scope.users[i][0] + '" data-email="' + $scope.users[i]['email'] + '" data-role="' + $scope.users[i][2] + '" data-airline="' + $scope.users[i][4] + '" data-isactive="' + $scope.users[i][3] + '""><img src="../img/edit.png" title="Edit User">',
			resetPass: '<a role="button" data-toggle="modal" data-target="#myModal2" data-uid="' + $scope.users[i][0] + '"><img src="../img/reset_password.png" style="vertical-align:middle" title="Reset Password" height="16" width="16"></a>',
			lastActive: $scope.users[i]['lastActive']};
			
		}
		$('#table').bootstrapTable({
			
			columns: [{
					field: 'state',
					checkbox: true
				},{
					field: 'userid',
					title: 'User ID',
					sortable: true,
					switchable: true,
					width: '75px',
					align: 'center',
					valign: 'top',
					visible: false
				}, {
					field: 'email',
					title: 'Email',
					sortable: true,
					switchable: false,
					align: 'left',
					width: '125px',
					valign: 'top'
				}, {
					field: 'role',
					title: 'Role',
					sortable: true,
					align: 'center',
					width: '75px',
					valign: 'top'
				}, {
					field: 'airlineid',
					title: 'Airline ID',
					sortable: true,
					align: 'center',
					width: '200px',
					valign: 'top'
				}, {
					field: 'account',
					title: 'Account',
					sortable: true,
					align: 'center',
					width: '75px',
					valign: 'top'
				},{
					field: 'status',
					title: 'Status',
					sortable: true,
					align: 'center',
					width: '75px',
					valign: 'top'
				},{
					field: 'resetPass',
					title: 'Reset Pwd',
					align: 'center',
					width: '75px',
					valign: 'top'
				},{
					field: 'edit',
					title: 'Edit User',
					align: 'center',
					width: '75px',
					valign: 'top'
				},
				{
					field: 'lastActive',
					title: 'Last Activity',
					align: 'center',
					width: '75px',
					valign: 'top',
					visible: false,
					sortable: true
				}],
				checkboxHeader: false,
				checkboxEnable: true,
				striped: true,
				pagination: true,
				pageSize: 50,
				pageList: [25, 50, 100],
				search: true,
				data: userArray,
				showColumns: true
		});
	}
	
	function getUsers() {
		$http.get("../common/getUsers.php")
			.success(function (data) {
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
		if (confirm("Are you sure you want to delete selected users?")) {
			var ids = getIdSelections();
			postData(ids);
		}
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
			$("#successMessage").hide()
		 }, 5000);
}
	
	function handleSuccessAlert(message) {
		$alertDiv = $('#successMessage')
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
				edit: '<a role="button" data-toggle="modal" data-target="#myModal1" data-index="' + numberOfUsers + '" data-uid="' + userid + '" data-email="' + email + '" data-role="' + role + '" data-airline="' + airlinesIdString + '" data-isactive=1><img src="../img/edit.png" title="Edit User">',
				resetPass: '<a role="button" data-toggle="modal" data-target="#myModal2" data-uid="' + userid + '"><img src="../img/reset_password.png" style="vertical-align:middle" title="Reset Password" height="16" width="16"></a>'
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
		var modal = $('#myModal');
		var email = modal.find('.modal-body #email').val();
		var password = modal.find('.modal-body #password').val();
		var confirmPassword = modal.find('.modal-body #confirmpassword').val();
		var role = modal.find('.modal-body #role').val();
		var airline = modal.find('.modal-body #airline').val();
		
		
		if (airline == null && role != "Admin") {
			handleAlerts("Must choose an airline", "myModalLabel");
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
					var values = [];
					$('#airline option').each(function() {
						values.push($(this).val());
					});
					$('#airline').multiselect('deselect', values);	
					var values = [];
					$('#role option').each(function() {
						values.push($(this).val());
					});
					$('#role').multiselect('select', ["None"]);	
					modal.modal('hide');
					handleSuccessAlert(data['message']);
					
				} else if (data['success'] == false) {
					handleAlerts(data['message'], "myModalLabel");
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
			userEmail = row.cells[1].innerHTML;
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
				edit: '<a role="button" data-toggle="modal" data-target="#myModal1" data-index="' + index + '" data-uid="' + userid + '" data-email="' + email + '" data-role="' + role + '" data-airline="' + airlinesIdString + '" data-isactive="' + isactive + '""><img src="../img/edit_user.png" title="Edit User">',
				resetPass: '<a role="button" data-toggle="modal" data-target="#myModal2" data-uid="' + userid + '"><img src="../img/reset_password.png" style="vertical-align:middle" title="Reset Password" height="16" width="16"></a>'
			}
		});
	}
	
	$resetPass.click(function() {
		var modal = $('#myModal2');
		var uid = modal.find('.modal-body #uid').val();
		var password = modal.find('.modal-body #password').val();
		var confirmPassword = modal.find('.modal-body #confirmpassword').val();
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
					handleAlerts(data['message'], "passwordModal");
				}
			});
		
	});
	
	$submitEdit.click(function() {
		var modal = $('#myModal1');
		var uid = modal.find('.modal-body #uid').val();
		var email = modal.find('.modal-body #email').val();
		var role = modal.find('.modal-body #editRole').val();
		var airline = modal.find('.modal-body #editAirline').val();
		var isactive = modal.find('.modal-body #isactive').val();
		if (airline == null && role != "Admin") {
			handleAlerts("Must choose an airline", "myModalLabel1");
			return;
		}
		//console.log(index);
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
				if (dataSuccess) {
					modal.modal('hide');
					editTable(index, uid, email, role, airline, isactive, data['acronyms']);
					handleSuccessAlert(data['message']);
				} else if (data['success'] == false) {
					/*
					var alert = document.createElement("div");
					alert.className += "alert alert-danger";
					var text = document.createTextNode(data['message']);
					alert.appendChild(text);
					var modalTitle = document.getElementById("myModalLabel1");
					modalTitle.appendChild(alert);
					*/
					handleAlerts(data['message'], "myModalLabel1"); 	
				}
			}).error(function (data, status, headers, config) {});
		});
		
		
	
	$('#myModal2').on('show.bs.modal', function (event) {
		var modal = $(this);
		var button = $(event.relatedTarget);
		var uid = button.data('uid');
		modal.find('.modal-body #uid').val(uid);
		var errorDiv = document.getElementById("alert");
		if (errorDiv) {
			$('#alert').remove();
		}
	});
	
	$('#myModal1').on('show.bs.modal', function (event) {
		var values = [];
		$('#editAirline option').each(function() {
			values.push($(this).val());
		});
		$('#editAirline').multiselect('deselect', values);	
		var button = $(event.relatedTarget);
		var index = button.data('index');
		var uid = button.data('uid');
		var email = button.data('email');
		var role = button.data('role');
		var airlines = button.data('airline');
		var status = button.data('isactive');
		var modal = $(this)
		//console.log(airlines);
		modal.find('.modal-title').text('Edit user: ' + email);
		modal.find('.modal-body #index').val(index);
		modal.find('.modal-body #uid').val(uid);
		modal.find('.modal-body #email').val(email);
		modal.find('.modal-body #isactive').val(status);
		//console.log(index);
		var values = [];
		$('#editRole option').each(function() {
			values.push($(this).val());
		});
		$('#editRole').multiselect('deselect', values);	
		$('.modal-body #editRole').multiselect('select', role);		
		if (airlines == "-1" || airlines == "-1, ") {
			$('.modal-body #editAirline').multiselect('selectAll', false);
		} else if (airlines == 0) {
			
		} else {
			airlines = airlines.toString() + ", ";
			airlines = airlines.split(", ");
			$('.modal-body #editAirline').multiselect('select', airlines);
		}
		if (role == "Admin") {
			var values = [];
			$('#editAirline').multiselect('disable');
			$('#editAirline option').each(function() {
				values.push($(this).val());
			});
			$('#editAirline').multiselect('deselect', values);
		} else {
			$('#editAirline').multiselect('enable');
		}
		$('#editAirline').multiselect('deselect', values);
		var errorDiv = document.getElementById("alert");
		if (errorDiv) {
			$('#alert').remove();
		}
	});
	
	$('#myModal').on('show.bs.modal', function (event) {
		var modal = $('#myModal');
		modal.find('.modal-body #email').val('');
		modal.find('.modal-body #password').val('');
		modal.find('.modal-body #confirmpassword').val('');
		var values = [];
		$('#airline option').each(function() {
			values.push($(this).val());
		});
		$('#airline').multiselect('deselect', values);	
		var values = [];
		$('#role option').each(function() {
			values.push($(this).val());
		});
		$('#role').multiselect('deselect', values);
		$('#role').multiselect('select', ["None"]);
		var errorDiv = document.getElementById("alert");
		if (errorDiv) {
			$('#alert').remove();
		}
		setTimeout(function() {
			  $(this).find('#email').focus();
		}, 420);
	
		
	});
	
});