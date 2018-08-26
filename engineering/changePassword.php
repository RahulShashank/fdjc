<!DOCTYPE html>
<?php
session_start ();
include ("../common/getAircraftCodes.php");
require_once ("../common/validateUser.php");
$approvedRoles = [ 
		$roles ["all"] 
];
$auth->checkPermission ( $hash, $approvedRoles );
$airlinesCodesArray = aircraftCodesArray ();
$uid = $auth->getSessionUID ( $hash );
$user = $auth->getUser ( $uid );
?>
<html lang="en" data-ng-app="myApp">
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/globe-icon.ico">
<title>BITE Analytics</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- <link rel="icon" href="favicon.ico" type="image/x-icon" />
         END META SECTION -->
<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme"
	href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->
<script src="../js/jquery-1.11.2.js"></script>
<link href="../css/jquery.datetimepicker.css" rel="stylesheet"
	type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/angular-route.js"></script>
<script src="../js/angular-cookies.js"></script>
<!--<script src="../js/plugins/jquery/jquery.min.js"></script>-->
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<link rel="stylesheet"
	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
<script src="../js/bootstrap-multiselect.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<script src="../js/Chart.HeatMap.S.js"></script>
<script src="../js/leaflet/leaflet.js"></script>

<script src="../js/vis.min.js"></script>
<link rel="stylesheet" href="../css/leaflet/leaflet.css">
<link rel="stylesheet" href="../css/chosen/chosen.min.css">
<link href="../css/vis.css" rel="stylesheet">
<script type="text/javascript" src="../js/alertify/alertify.js"></script>
<script type="text/javascript" src="../js/alertify/alertify.min.js"></script>
<link rel="stylesheet" href="../css/alertify/alertify.min.css"/>
<!-- Default theme -->
<link rel="stylesheet" href="../css/alertify/default.min.css"/>
<!-- Semantic UI theme -->
<link rel="stylesheet" href="../css/alertify/semantic.min.css"/>
<style></style>
</head>
<body>
	<!-- START PAGE CONTAINER -->
	<div id="container" class="page-container">

		<!-- START PAGE SIDEBAR -->
        <?php include("SideNavBar.php"); ?>
        <!-- END PAGE SIDEBAR -->

		<!-- PAGE CONTENT -->
		<div class="page-content">
			<!-- START X-NAVIGATION VERTICAL -->
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel">
				<!-- TOGGLE NAVIGATION -->
				<li class="xn-icon-button"><a href="#" class="x-navigation-minimize"><span
						class="fa fa-dedent"></span></a></li>
				<!-- END TOGGLE NAVIGATION -->
				<!-- SEARCH 
                  <li class="xn-search">
                  	<form role="form">
                  		<input type="text" name="search" placeholder="Search..." />
                  	</form>
                  </li>-->
				<!-- END SEARCH -->
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span></a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->
			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">Change Password</li>
			</ul>
			<!-- END BREADCRUMB -->
			<!-- PAGE TITLE -->
			<div class="page-title">
				<h2>
					<!-- <span class="fa fa-arrow-circle-o-left"></span>-->
					Reset Password
				</h2>
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div ng-controller="userInformationCtrl">
								<br>
								<div class="panel-body">
									<div id="passwordInputs">
										<input type="hidden" name="uid" id="uid"
											value=<?php echo $uid; ?>>
										<div class="form-group">
											<label for="currPass">Current Password: </label> <input
												type="password" class="form-control" id="currPass">
										</div>
										<div class="form-group">
											<label for="newPass">New Password: </label> <input
												type="password" class="form-control" id="newPass">
										</div>
										<div class="form-group">
											<label for="confirmPass">Confirm Password: </label> <input
												type="password" class="form-control" id="confirmPass">
										</div>
										<button id="resetPass" type="button" class="btn btn-primary">Change
											Password</button>
									</div>
									<br />
									<div id="errorOffset"></div>
								</div>


							</div>
						</div>
					</div>
				</div>


			</div>
			<!-- PAGE CONTENT WRAPPER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->
	<!-- MESSAGE BOX-->
	<div class="message-box animated fadeIn" data-sound="alert"
		id="mb-signout">
		<?php include("logout.php"); ?>
	</div>
	<!-- END MESSAGE BOX-->
	<!-- START PRELOADS -->
	<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
	<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>
	<!-- END PRELOADS -->
	<!-- START SCRIPTS -->
	<!-- START PLUGINS 
         <script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script>
         <script type="text/javascript" src="../js/plugins/jquery/jquery-ui.min.js"></script>
         <script type="text/javascript" src="../js/plugins/bootstrap/bootstrap.min.js"></script>  -->
	<!-- END PLUGINS -->
	<!-- START THIS PAGE PLUGINS-->
	<script type='text/javascript'
		src='../js/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js'></script>
	<script type='text/javascript'
		src='../js/plugins/jvectormap/jquery-jvectormap-world-mill-en.js'></script>
	<script type='text/javascript'
		src='../js/plugins/jvectormap/jquery-jvectormap-europe-mill-en.js'></script>
	<script type='text/javascript'
		src='../js/plugins/jvectormap/jquery-jvectormap-us-aea-en.js'></script>
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript"
		src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
	<!-- END THIS PAGE PLUGINS-->
	<!-- START TEMPLATE -->
	<!-- <script type="text/javascript" src="../js/settings.js"></script>-->
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
	<!-- END TEMPLATE -->

	<script>
$(document).ready(function(){
$('.navbar-nav li').removeClass('active');
$("#homeSideBarUserProfile").addClass("active");
});
</script>
<script>
		var app = angular.module('myApp', []);
		
</script>
	<script src="../controllers/userInformationCtrl.js"></script>
	<script src="../controllers/sessionExpires.js"></script>
</body>
</html>