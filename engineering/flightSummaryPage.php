<!DOCTYPE html>
<?php
$menu = 'flightsummary';
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
$userArray=$user['email'];
$userArray=explode("@",$userArray);
$usr=explode(".",$userArray[0]);
$str = str_replace(".", " ", $userArray[0]);
$str = ucwords($str);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
require_once "../map/airports.php";

require_once ("checkEngineeringPermission.php");

$aircraftId = $_REQUEST ['aircraftId'];
$sqlDump = $_REQUEST ['sqlDump'];
$flightLegs = $_REQUEST ['flightLegs'];

if (isset ( $aircraftId )) {
	checkAircraftPermission ( $dbConnection, $aircraftId );
}

if ($aircraftId != '') {
	// Get information to display in header
	$query = "SELECT a.tailsign, b.id, b.name, a.databaseName, a.isp FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
	$result = mysqli_query ( $dbConnection, $query );
	
	if ($result && mysqli_num_rows ( $result ) > 0) {
		$row = mysqli_fetch_array ( $result );
		$aircraftTailsign = $row ['tailsign'];
		$airlineId = $row ['id'];
		$airlineName = $row ['name'];
		$dbName = $row ['databaseName'];
		$aircraftIsp = $row ['isp'];
	} else {
		echo "error: " . mysqli_error ( $error );
	}
} else if ($sqlDump != '') {
	$dbName = $sqlDump;
} else {
	echo "Error - no aircraftId nor sqlDump";
	exit ();
}

$selected = mysqli_select_db ( $dbConnection, $dbName ) or die ( "Could not select " . $dbName );

// data for map
$displayMap = false;

$flightLegsArray = explode ( ",", $flightLegs );
$flightLegsCount = count ( $flightLegsArray );

if ($flightLegsCount == 1) {
	$flightLegId = $flightLegsArray [0];
	
	$query = "SELECT departureAirportCode, arrivalAirportCode FROM SYS_flight WHERE idFlightLeg = $flightLegId";
	$result = mysqli_query ( $dbConnection, $query );
	if ($result) {
		$row = mysqli_fetch_array ( $result );
		$departureAirportCode = trim ( $row ['departureAirportCode'] );
		$arrivalAirportCode = trim ( $row ['arrivalAirportCode'] );
		
		// echo "*$departureAirportCode* - *$arrivalAirportCode*";
		
		if ($departureAirportCode != "" && $arrivalAirportCode != "" && strpos ( $$departureAirportCode, '-' ) === false && strpos ( $arrivalAirportCode, '-' ) === false) {
			$departureAirportInfo = getAirportInfo ( $departureAirportCode );
			if ($departureAirportInfo != "") {
				$departureAirportLat = $departureAirportInfo ['lat'];
				$departureAirportLong = $departureAirportInfo ['long'];
				$departureAirportName = $departureAirportInfo ['name'];
				
				$arrivalAirportInfo = getAirportInfo ( $arrivalAirportCode );
				if ($arrivalAirportInfo != "") {
					$arrivalAirportLat = $arrivalAirportInfo ['lat'];
					$arrivalAirportLong = $arrivalAirportInfo ['long'];
					$arrivalAirportName = $arrivalAirportInfo ['name'];
					
					// Need to do some workaround to have flights crossing the Pacific Ocean and not the entire world
					if ($departureAirportLong >= 90 && $arrivalAirportLong <= - 20) {
						$arrivalAirportLong += 360;
					} else if ($departureAirportLong <= - 20 && $arrivalAirportLong >= 90) {
						$departureAirportLong += 360;
					}
					
					$displayMap = true;
				}
			}
		}
	} else {
		echo "Problem with query $query";
		exit ();
	}
}
// echo $displayMap; exit;
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
				<li class="active">FlightSummary</li>
			</ul>
			<!-- END BREADCRUMB -->
			<!-- PAGE TITLE -->
			<div class="page-title">
				<h2>
					<!-- <span class="fa fa-arrow-circle-o-left"></span>-->
					Summary
				</h2>
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body" style="height: 318px; padding: 8px;">                 
                 				 <?php
																						if ($displayMap) {
																							echo "<div id=\"map\" style=\"height: 300px\"></div>";
																							echo "<br><br>";
																						}
																						?>
                     </div>
						</div>
					</div>
				</div>
				<div class="row" ng-controller="flightStatusController">
					<div class="col-md-4" data-ng-repeat="status in statuses">
						<div class="card"
							ng-class="{'cardDanger': (status.value == 2), 'cardWarning': (status.value == 1), 'cardOK': (status.value == 0)}"}>
							<div class="cardStatus">{{ status.name }}</div>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<div id="flightTimeline" class="flightTimeline"></div>
								<div id="loadingTimeline">
									<img src="../img/ajaxLoading.gif"> Loading Timeline...
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
         <?php
									echo "var displayMap = ";
									echo $displayMap ? "true;" : "false;";
									?>
         if(displayMap) {
         	<?php
										echo "var departureAirportCode = '$departureAirportCode';";
										echo "var departureAirportName = '$departureAirportName';";
										if ($departureAirportLat != '')
											echo "var departureAirportLat = $departureAirportLat;";
										if ($departureAirportLong != '')
											echo "var departureAirportLong = $departureAirportLong;";
										
										echo "var arrivalAirportCode = '$arrivalAirportCode';";
										echo "var arrivalAirportName = '$arrivalAirportName';";
										if ($arrivalAirportLat != '')
											echo "var arrivalAirportLat = $arrivalAirportLat;";
										if ($arrivalAirportLong != '')
											echo "var arrivalAirportLong = $arrivalAirportLong;";
										
										?>
         
         	var map = L.map('map').fitBounds(
         								[
         									[departureAirportLat, departureAirportLong],
         									[arrivalAirportLat, arrivalAirportLong]
         								],
         								{padding: [20,20]}
         							);
         
         	// var url = 'http://otile4.mqcdn.com/tiles/1.0.0./sat/{z}/{x}/{y}.png';
         	var url = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';
         
         	L.tileLayer(url, {
         		maxZoom: 18,
         		id: 'mapbox.streets'
         	}).addTo(map);
         
         
         	var departureIcon = L.icon({
         		iconUrl: '../img/departure.png',
         		shadowUrl: '../img/marker-shadow.png',
         		iconAnchor: [15,50],
         		popupAnchor:  [2, -40]
         	});
         
         	var arrivalIcon = L.icon({
         		iconUrl: '../img/arrival.png',
         		shadowUrl: '../img/marker-shadow.png',
         		iconAnchor: [15,50],
         		popupAnchor:  [2, -40]
         	});
         	
         	L.marker(
         			[
         				departureAirportLat, 
         				departureAirportLong
         			],
         			{icon: departureIcon}
         		)
         		.addTo(map)
         		.bindPopup(
         			"<b>Airport Name</b>: " + departureAirportName + "<br>" +
         			"<b>Code</b>: " + departureAirportCode +
         			"<br><br>This the <b>DEPARTURE</b> airport."
         		);
         		
         	L.marker(
         			[
         				arrivalAirportLat, 
         				arrivalAirportLong
         			]
         			,
         			{icon: arrivalIcon}
         		).addTo(map)
         		.bindPopup(
         			"<b>Airport Name</b>: " + arrivalAirportName + "<br>" +
         			"<b>Code</b>: " + arrivalAirportCode +
         			"<br><br>This the <b>ARRIVAL</b> airport."
         		);
         	
         	var polyline = L.polyline(
         		[
         			[departureAirportLat, departureAirportLong],
         			[arrivalAirportLat, arrivalAirportLong]
         		], 
         		{color: 'red', weight: 3, opacity: 0.9, dashArray: "10,5"}
         	).addTo(map);
         }
      </script>
	<script>
         $(document).ready(function(){
             $('.nav-sidebar li').removeClass('active');
             $("#sideBarSummary").addClass("active");
         	
             data = {
                 <?php
																	if ($aircraftId != '') {
																		echo "aircraftId: $aircraftId";
																	} else {
																		echo "sqlDump: '$sqlDump'";
																	}
																	echo ",
                    flightLegs: '$flightLegs'";
																	?>
             };
             getTimeLineData(data);
			console.log('reload');
			/*$("#pagecontent").removeClass("page-content");
			$('#pagecontent').css({
				  'min-height': '100%',
				  'margin-left': '220px',
				  'background': '#f5f5f5 url("../img/bg.png") left top repeat',
				  'position': 'relative',
				  'zoom': '1'
			});*/

         });
         
         function getTimeLineData(data) {
             $.ajax({
                 type: "POST",
                 dataType: "json",
                 url: "../ajax/getAircraftTimeLineData.php",
                 data: data,
                 success: function(data) {
                     //console.log(data);
         
                     $('#startDateTimePicker').datetimepicker({
                         value: data.options.start,
                         step:15,
                         weeks:true
                     });
         
                     $('#endDateTimePicker').datetimepicker({
                         value: data.options.end,
                         step:15,
                         weeks:true
                     });
         
                     createTimeline(data, 'flightTimeline', 'loadingTimeline');
                 },
                 error: function (err) {
                     console.log('Error', err);
                 }
             });
         }
         
         
         
         function createTimeline(data, timelineId, loadingId) {
             $('#'+loadingId).hide();
         
             var container = document.getElementById(timelineId);
         
             var groups = new vis.DataSet(
                 data.groups
                 );
         
             var items = new vis.DataSet(
                 data.items
                 );
         
             var options = {
                 orientation: 'both',
                 start: data.options.start,
                 end: data.options.end,
                 min: data.options.min,
                 max: data.options.max,
                 clickToUse: true,
                 stack: false,
                 multiselect: true
             };
         
             timeline = new vis.Timeline(container, items,  groups, options);
             timeline.on('select', function (properties) {
                 var selectedItems = properties.items;
                 flightLegs = ''; // reset flight Legs
         
                 if(selectedItems != null) {
                     for (i = 0; i < selectedItems.length; i++) { 
                         var selectedItem = selectedItems[i];
                         var res = selectedItem.split("/");
                         if(res[0] == 'FLI') {
                             if(flightLegs != '') {
                                 flightLegs += ',';
                             }
                             flightLegs += res[1];
                         }
                     }
                 }
             });
             timeline.on('contextmenu', function (props) {
               //alert('Right click!');
               props.event.preventDefault();
             });
         }
      </script>
	<script>
         var app = angular.module('myApp', []);
         app.controller('flightStatusController', function($scope, $http) {
                 init();
         
                 function init() {
                     getFlightStatuses();
                 }
         
                 function getFlightStatuses() {
                     <?php
							if ($aircraftId != '') {
									$param = "aircraftId=$aircraftId";
							} else {
									$param = "sqlDump=$sqlDump";
							}
							$param .= "&flightLegs=$flightLegs";
						?>
         
                     $http.get("../ajax/getFlightStatusData.php?<?php echo $param; ?>")
                         .success(function (data) {
                             //console.log(data);
                             $scope.statuses = data;
                         });
                 }
         });
      </script>
      <script src="../controllers/sessionExpires.js"></script>
</body>
</html>
