app.controller('loginCtrl', function($scope, $http, $window) {
    init();
	$submit = $('#submit');
	console.log("Hello World!");
	function init() {
	}
	
	function validateEmail(email) {
		var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
		return re.test(email);
	}
	
	function handleAlerts(message) {
		var errorDiv = document.getElementById("alert");
		console.log(errorDiv);
		if (errorDiv) {
			errorDiv.innerHTML = message;
			return;
		} else {
			var alert = document.createElement("div");
			alert.className += "alert alert-danger";
			alert.id = "alert";
			var text = document.createTextNode(message);
			alert.appendChild(text);
			var modalTitle = document.getElementById("containerBox");
			modalTitle.appendChild(alert);
		}
	}
	
	function loginUser(email, password, remember) {
		console.log('inside loginUser in loginCtrl');
		$http({
			url: "common/login_user.php",
			method: "POST",
			data: {'email': email,
				   'password': password,
				   'remember': remember},
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			}).success(function (data, status, headers) {
				console.log(data);
				var success = Boolean(data['success']);
				var message = data['message'];
				if (success) {
					window.location.replace(data['homepage']);
				} else {
				 handleAlerts(message);
				}
				
			}).error(function (data, status, headers) {
				console.log('Error: ' + status);
				console.log('Error data: ' + data);
			});
	}
	
	function loginHandler() {
		console.log('inside loginHandler');
		var loginBox = $('#loginBox');
		//var email = loginBox.find('.input_field #email').val();
		//var password = loginBox.find('.input_field #password').val();
		var email = loginBox.find('#email').val();
		var password = loginBox.find('#password').val();
		var remember = document.getElementById('checkmark').checked;
		var emailOkay = false;
		var passwordOkay = false;
		if (email.length > 0) {
			if (validateEmail(email)) {
				emailOkay = true;
			} else {
				handleAlerts("Enter a valid email address.");
			}
		} else {
			handleAlerts("Enter an email address.");
		}
		if (password.length > 0) {
			passwordOkay = true;
		} else{
			if (email.length == 0) handleAlerts("Enter email address and password.");
			else handleAlerts("Enter your password.");
		}
		if (emailOkay && passwordOkay) loginUser(email, password, remember);
	}
	$submit.click(function() {
	/*
		var loginBox = $('#loginBox');
		var email = loginBox.find('.input_field #email').val();
		var password = loginBox.find('.input_field #password').val();
		var remember = document.getElementById('checkmark').checked;
		var emailOkay = false;
		var passwordOkay = false;
		if (email.length > 0) {
			if (validateEmail(email)) {
				emailOkay = true;
			} else {
				handleAlerts("Enter a valid email address.");
			}
		} else {
			handleAlerts("Enter an email address.");
		}
		if (password.length > 0) {
			passwordOkay = true;
		} else{
			if (email.length == 0) handleAlerts("Enter email address and password.");
			else handleAlerts("Enter your password.");
		}
		if (emailOkay && passwordOkay) loginUser(email, password, remember);
	*/
		loginHandler();
	});
	
	$(document).keypress(function(e) {
    if(e.which == 13) {
        console.log('You pressed enter!');
		loginHandler();
    }
});
	
});