app.controller('userInformationCtrl', function($scope, $http, $window) {
    init();
	var $resetPass = $('#resetPass');
	function init() {
	}
	
	function handleAlerts(message, success) {
		var errorDiv = document.getElementById("alert");
		//console.log(errorDiv);
		if (errorDiv) {
			if (success) {
				errorDiv.className = "alert alert-success";
			} else {
				errorDiv.className = "alert alert-danger";
			}
			errorDiv.innerHTML = message;
			return;
		} else {
			var alert = document.createElement("div");
			if (success) alert.className += "alert alert-success";
			else alert.className += "alert alert-danger";
			alert.id = "alert";
			var text = document.createTextNode(message);
			alert.appendChild(text);
			var modalTitle = document.getElementById("errorOffset");
			modalTitle.appendChild(alert);
		}
	}
	
	$resetPass.click(function() {
		var input = $("#passwordInputs");
		var uid = input.find('#uid').val();
		var currentPassword = input.find('#currPass').val();
		var newPassword = input.find('#newPass').val();
		var confirmPassword = input.find('#confirmPass').val();
		$http({
			url: "../common/changePassword.php",
			method: "POST",
			data: { 'uid': uid,
					'currentPassword': currentPassword,
					'password': newPassword,
					'confirmPassword': confirmPassword,
					'isAdmin': 0
				},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers, config) {
				//console.log(data);
				var dataSuccess = Boolean(data['success']);
				if (dataSuccess) {
					handleAlerts(data['message'], data['success']);
					input.find('#currPass').val('');
					input.find('#newPass').val('');
					input.find('#confirmPass').val('');
				} else if (data['success'] == false) {
					handleAlerts(data['message'], data['success']);
				}
			});
		
	});
	
});