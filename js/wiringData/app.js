// Define the `thalesApp` module
// also include ngRoute for all our routing needs
//var thalesApp = angular.module("thalesApp", ['ngSanitize','ngAnimate', 'ui.bootstrap']);
var thalesApp = angular.module("thalesApp", ['ngRoute', 'ngSanitize', 'ngAnimate', 'ui.bootstrap']);
//				angular.module('myApp', ['ngAnimate', 'ui.bootstrap']);

var currentProject = window.location.pathname;
var thalesAppSettings = {
	stub : false,	
	build: currentProject.indexOf("idct") >= 0 ? "idct" : "adct"
};

thalesAppSettings.services = {
	convertFileToHtmlAndText :"/convertFileToHtmlAndText",		
};
