var IDLE_TIMEOUT = 1800; // seconds
var _idleSecondsCounter = 0;
document.onclick = function() {
	_idleSecondsCounter = 0;
};
document.onmousemove = function() {
	_idleSecondsCounter = 0;
};
document.onkeypress = function() {
	_idleSecondsCounter = 0;
};
//window.setInterval(CheckIdleTime, 1000);
var id = window.setInterval(CheckIdleTime, 1800);
function CheckIdleTime() {
	_idleSecondsCounter++;
	var oPanel = document.getElementById("SecondsUntilExpire");
	if (oPanel)
		oPanel.innerHTML = (IDLE_TIMEOUT - _idleSecondsCounter) + "";
	if (_idleSecondsCounter >= IDLE_TIMEOUT) {
		window.clearInterval(id);
		alertify
				.alert(
						'Your Session has expired.\nSessions automatically ends after 30 minutes of inactivity.\nClick Ok for Log in to start a new Session.',
						function() {
							window.location.href = '../common/logout_user.php';
						}).setHeader('Session Expires '); 
	}
}
