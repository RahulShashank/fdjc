<?php
$menu = 'flightsummary';
//session_start ();
include ("../common/getAircraftCodes.php");
require_once ("../common/validateUser.php");
// $approvedRoles = [
// $roles ["all"]
// ];
// $auth->checkPermission ( $hash, $approvedRoles );
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
require_once "../common/computeFleetStatusData.php";
require_once "../ajax/GetCabinEventsData.php";
require_once "../ajax/GetPassengerServices.php";
require_once "../ajax/GetDigitalStatusServerData.php";
require_once "../ajax/GetSystemEvents.php";
require_once ("checkEngineeringPermission.php");

$aircraftId = $_REQUEST ['aircraftId'];
$sqlDump = $_REQUEST ['sqlDump'];
$flightLegs = $_REQUEST ['flightLegs'];
$mainMenu = $_REQUEST ['mainmenu'];
//$aircraftId = 293;
//$flightLegs = 5612;

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

//BiteTimeline
$showFailures = false;
$showFaults = true;
$showReboots = false;
$showAppEvents = false;
$showImpServices = false;

$showDSU = true;
$showAVCD_LAIC = true;
$showADBG = true;
$showICMT = true;
	//$showCIDS = true;
	//$showCAMERA = true;
	//$showPRINTER= true;
$showSVDU = false;
$showTPMU = false;
$showQSEB_SDB_VCSSDB = true;
$showSPB = true;
	
$hostnameInput = "";

$biteCode = "";
$notBiteCode = "";
$severity = "all";
$monitorState = "1,3";


// Handle single or multiple flight legs
if($flightLegId != '') {
	if(strpos($flightLegId, '-') > 0) {
		$leg1 = $type  = strtok($flightLegId, '-');
		$leg2 = $type  = strtok('-');
		$whereCondition = "WHERE idFlightLeg BETWEEN $leg1 AND $leg2";
	} else if(strpos($flightLegId, ',') > 0) {
		$whereCondition = "WHERE idFlightLeg in ($flightLegId)";
	} else {
		$whereCondition = "WHERE idFlightLeg = $flightLegId";
	}
}


//cabin Events
$cabindataItems = array();
$cabindataItems = getCabinEventData($dbName,$flightLegs,$dbConnection);

//Passenger Events
$passengerDataItems = array();
$passengerDataItems = getPassengerServicesData($dbName,$flightLegs,$dbConnection);

//Digital Server Status
$digitalServerdataItems = array();
$digitalServerdataItems = getDigitalServerStatusData($dbName,$flightLegs,$dbConnection);

//System Events
$SystemEventsdataItems = array();
$SystemEventsdataItems = getSystemEventData($dbName,$flightLegs,$dbConnection);

// File Repository path
$ds = DIRECTORY_SEPARATOR;
$fileRepoPath = dirname(dirname(__FILE__)) . $ds . "wiring_data" . $ds;

?>
<html lang="en" data-ng-app="myApp">
<head>
<link rel="shortcut icon" href="../img/globe-icon.ico">
<title>BITE Analytics</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" type="text/css" id="theme"
	href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!--  <script type="text/javascript" src="../js/plugins/jquery/jquery.min.js"></script> -->
<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<script src="../js/jquery-1.11.2.js"></script>
<link href="../css/vis.css" rel="stylesheet">
<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/angular-route.js"></script>
<script src="../js/angular-cookies.js"></script>
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
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
<link rel="stylesheet" href="../css/alertify/alertify.min.css" />
<link rel="stylesheet" href="../css/alertify/default.min.css" />
<link rel="stylesheet" href="../css/alertify/semantic.min.css" />
<script src="../js/Chart.js"></script>
<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>
<script src="../js/angular-animate.js"></script>
<link href="../css/wiringDiagram/style_horizontal.css" rel="stylesheet">
<style>
body {
	background: #ffffff;
}

.loadFrame html>body {
	background: #ffffff
}

.select.bs-select-hidden,select.selectpicker {
	display: block !important;
}

.bootstrap-table .table.table-no-bordered>thead>tr>th,.bootstrap-table .table.table-no-bordered>tbody>tr>td
	{
	font-size: 12px !important;
}

.uncommandedli b,.commandedli b {
	font-size: 12px;
}

.mailList {
	margin-left: -40px;
}

.commandedul li,.uncommandedul li {
	font-size: 12px;
}

.rebootImg {
	margin-top: -2px !important;
}

li.lli {
	font-size: 12px;
}

.lli img {
	width: 12px;
	height: 12px;
	margin-right: 10px;
	margin-top: -3px;
}

ul.commadedul,ul.uncommadedul {
	margin-left: -45px;
}

.mainBlock {
	height: 135px;
	background-color: #FCFCFC;
	border: 1px solid #E8E8E8;
	padding: 5px;
	margin-bottom: 15px;
	border-radius: 10px;
}

.mailList {
	list-style-type: none;
}

.vis-item.vis-selected {
	background-color: violet !important;
	border-color: purple !important;
}

.vis-item.vis-dot.vis-readonly {
	display: none;
}

.listPadding {
	padding: 5px;
}
.filterBox{
	border: 1px solid #E8E8E8;
    padding: 0px !important;
    padding-top: 4px !important;
    padding-bottom: 4px !important;
    width: 201px;
    margin-top: 4px;
    height: 101px;
    margin-right: 14px;
}
.modal-backdrop.in {
    opacity: .5 !important;
}

</style>
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
						class="fa fa-dedent"></span> </a></li>
				<!-- END TOGGLE NAVIGATION -->

				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span> </a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->
			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li>
				<?php 
					if($mainMenu =='AircraftTimeline'){
						echo '<a href="AircraftTimeline.php?aircraftVisited=true">';
						echo $mainMenu;
						echo ' </a>';
					}else if($mainMenu =='LOPA'){
						echo '<a href="lopa.php?lopaVisited=true">';
						echo $mainMenu;
						echo ' </a>';
					}else if($mainMenu =='FlightScore'){
						echo '<a href="FlightScore.php?flightScoreVisited=true">';
						echo $mainMenu;
						echo ' </a>';
					}else if($mainMenu =='UploadnDownloadOffload'){
						echo '<a href="UploadnDownloadOffload.php?downloadOffloadVisited=true">';
						echo 'Upload Offloads';
						echo ' </a>';
					} else if($mainMenu =='MaintenanceActivities'){
					    $submenu=$_REQUEST['submenu'];
					    echo '<a href="MaintenanceActivities.php?maintenanceActivitiesVisited=true">';
					    echo $mainMenu;
					    echo ' </a>';
					} else if($mainMenu =='DASHBOARD'){
					    echo '<a href="AirlineDashboard.php?dashboardVisited=true">';
					    echo "Dashboard";
					    echo ' </a>';
					}
				?>
				</li>
				<li class="active">Flight Analysis <?php if(isset($aircraftTailsign)) {echo "($aircraftTailsign)";} ?></li>
			</ul>
			<div class="page-title">
				<h2>
					<?php 
						if($mainMenu =='AircraftTimeline'){
							echo '<a href="AircraftTimeline.php?aircraftVisited=true" style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to Aircraft Timeline"></span></a>&nbsp;Flight Analysis';
							if(isset($aircraftTailsign)) {
							    echo " ($aircraftTailsign)";
						    }
						}else if($mainMenu =='LOPA'){
							echo '<a href="lopa.php?lopaVisited=true"  style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to LOPA"></span></a>&nbsp;Flight Analysis';
							if(isset($aircraftTailsign)) {
							    echo " ($aircraftTailsign)";
							}
						}else if($mainMenu =='FlightScore'){
							echo '<a href="FlightScore.php?flightScoreVisited=true"  style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to Flight Score"></span></a>&nbsp;Flight Analysis';
							if(isset($aircraftTailsign)) {
							    echo " ($aircraftTailsign)";
							}
						}else if($mainMenu =='DownloadOffload'){
							echo '<a href="downloadOffload.php?downloadOffloadVisited=true"  style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to DownloadOffload"></span></a>&nbsp;Flight Analysis';
							if(isset($aircraftTailsign)) {
							    echo " ($aircraftTailsign)";
							}
						}else if($mainMenu =='UploadnDownloadOffload'){
						    echo '<a href="UploadnDownloadOffload.php?downloadOffloadVisited=true"  style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to Upload Offloads"></span></a>&nbsp;Flight Analysis';
						    if(isset($aircraftTailsign)) {
						        echo " ($aircraftTailsign)";
						    }
						}else if($mainMenu =='MaintenanceActivities'){
						    echo '<a href="MaintenanceActivities.php?maintenanceActivitiesVisited=true"  style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to MaintenanceActivities"></span></a>&nbsp;Flight Analysis';
						    if(isset($aircraftTailsign)) {
						        echo " ($aircraftTailsign)";
						    }
						} else if($mainMenu =='DASHBOARD'){
						    echo '<a href="AirlineDashboard.php?dashboardVisited=true"  style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to Dashboard"></span></a>&nbsp;Flight Analysis';
						    if(isset($aircraftTailsign)) {
						        echo " ($aircraftTailsign)";
						    }
						}
					?>				
					<!-- <a href="AircraftTimeline.php?aircraftVisited=true" style="color:#3e4a61"><span class="fa fa-arrow-circle-o-left" title="Back to Aircraft Timeline"></span></a>&nbsp;Flight Analysis-->
				</h2>
			</div>
			<!-- END BREADCRUMB -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<!-- Tabular View  -->
				<div class="tab-content" role="tab" data-toggle="tab">
					<div id="tabularData">
						<br /> <br />
						<!-- Nav tabs -->
						<ul class="nav nav-tabs" role="tablist" id="myTabs">
							<li role="presentation" id="summaryPanel" class="active"><a
								href="#summaryTab" aria-controls="summaryTab" role="tab"
								data-toggle="tab"
								style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Summary</a>
							</li>
							<li role="presentation" id="staticsPanel"><a href="#staticsTab"
								aria-controls="resetsTab" role="tab" data-toggle="tab"
								style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Statistics</a>
							</li>
							<li role="presentation" id="biteDataPanel"><a href="#biteDataTab"
								aria-controls="biteDataTab" role="tab" data-toggle="tab"
								style="font-family: 'Open Sans', sans-serif; font-size: 13px;">BITE
									Data</a></li>
							<li role="presentation" id="biteTimelinePanel"><a
								href="#biteTimelineTab" aria-controls="biteTimelineTab"
								role="tab" data-toggle="tab"
								style="font-family: 'Open Sans', sans-serif; font-size: 13px;">BITE
									Timeline</a></li>
							<li role="presentation" id="flightSeatDetailsPanel"><a
								href="#flightSeatDetailsTab"
								aria-controls="flightSeatDetailsTab" role="tab"
								data-toggle="tab"
								style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Flight
									Seat Details</a>
							</li>
							
							<li role="presentation" id="serviceDetailsPanel"><a
								href="#serviceDetailsTab" aria-controls="serviceDetailsTab"
								role="tab" data-toggle="tab"
								style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Service Details</a>
							</li>
							<li role="presentation" id="lruRemovalDetailsPanel"><a
								href="#lruRemovalDetailsTab" aria-controls="lruRemovalDetailsTab"
								role="tab" data-toggle="tab"
								style="font-family: 'Open Sans', sans-serif; font-size: 13px;">LRU Removal Details</a>
							</li>
						</ul>
						<!-- Tab panes -->
						<div class="tab-content">
							<div role="tabpanel" class="tab-pane active" id="summaryTab">
								<br>
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default">
											<div>
												<div class="panel-body">
												<?php include("Summary.php");?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div role="tabpanel" class="tab-pane" id="staticsTab">
								<br>
								<div class="row">
									<div class="col-md-12">
										<?php include("FlightStatus.php");?>										
									</div>
								</div>
							</div>
							<div role="tabpanel" class="tab-pane" id="biteDataTab">
								<br>
								<div class="row">
									<div class="col-md-12">
										<?php include("BiteData.php");?>										
									</div>
								</div>

							</div>
							<div role="tabpanel" class="tab-pane" id="biteTimelineTab">								
								<div class="row">
									<div class="col-md-12">
										<?php include("BiteTimeline.php");?>
									</div>
								</div>

							</div>
							<div role="tabpanel" class="tab-pane" id="flightSeatDetailsTab">
								<br>
								<div class="row">
									<div class="col-md-12">
                                        <?php include("FlightSeatDetails.php");?>
									</div>
								</div>
							</div>
							
							<div role="tabpanel" class="tab-pane" id="serviceDetailsTab">
								<br>
								<div class="row">
									<div class="col-md-12">
										<?php include("ServiceDetails.php");?>
									</div>
								</div>
							</div>
							
							<div role="tabpanel" class="tab-pane" id="lruRemovalDetailsTab">
								<br>
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default">
											<div>												
												<div class="panel-body">
													<?php include("LRURemovalTimeline.php");?>
												</div>
											</div>
										</div>
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

		<!-- Logout page -->
		<?php include("../logout.php"); ?>
		<!-- END Logout page-->

		<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
		<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>
		<script type="text/javascript"
			src="../js/plugins/bootstrap/bootstrap.min.js"></script>
		<script type='text/javascript'
			src='../js/plugins/icheck/icheck.min.js'></script>
		<script type="text/javascript"
			src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
		<script type="text/javascript" src="../js/plugins.js"></script>
		<script type="text/javascript" src="../js/actions.js"></script>
		<script type="text/javascript"
			src="../js/plugins/jquery/jquery-ui.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/bootstrap/bootstrap.min.js"></script>
		<script type="text/javascript"
			src="../js/plugins/bootstrap/bootstrap-select.js"></script>

		<script>
// 	    var thalesApp = angular.module("thalesApp", ['ngRoute', 'ngSanitize', 'ngAnimate', 'ui.bootstrap']);
	    var currentProject = window.location.pathname;
	    var thalesAppSettings = {
	        stub : false,	
	        build: currentProject.indexOf("idct") >= 0 ? "idct" : "adct"
	    };
	    
	    thalesAppSettings.services = {
		    // convertFileToHtmlAndText: '<?=$requestURL ?>/convertFileToHtmlAndText',
		    convertFileToHtmlAndText: 'http://localhost:8080/adct/convertFileToHtmlAndText'
//     		convertFileToHtmlAndText: 'https://isebite.thales-ifec.com:8443/adct/convertFileToHtmlAndText'
	    };

	    var aircraftId = <?= $aircraftId ?>;
	    var fileRepoPath = '<?= $fileRepoPath ?>';
	    var flightLegs = "<?= $flightLegs ?>";

		var issueItems;
		var app = angular.module('myApp', ['ngRoute', 'ngSanitize', 'ngAnimate']);         
		app.controller('flightStatusController', function($scope, $http) {
			init();
			$('#statics').val('svdus');
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
							$scope.statuses = data;
					});	    	            
					}
		});

		    
		
			<?php
				echo "var displayMap = ";
				echo $displayMap ? "true;" : "false;";
			?>
         	if(displayMap) {
         		<?php
					echo "var departureAirportCode = '$departureAirportCode';";
					//echo "var departureAirportName = '$departureAirportName';";
					echo "var departureAirportName = ";
					echo '"'.$departureAirportName.'";';
					if ($departureAirportLat != '')
							echo "var departureAirportLat = $departureAirportLat;";
							if ($departureAirportLong != '')
								echo "var departureAirportLong = $departureAirportLong;";										
								echo "var arrivalAirportCode = '$arrivalAirportCode';";
								echo "var arrivalAirportName = ";
								echo '"'.$arrivalAirportName.'";';
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
		var firstTime=true;
		$(document).ready(function(){
			$('.nav-sidebar li').removeClass('active');
			$("#sideBarSummary").addClass("active");
			$('#systemEventLoading').hide();
			$('#flightSeatLoading').hide();
			$('#statics').val('svdus');
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

			getFailureSeatTimeline(data);
			getFaultSeatTimeline(data);
			getEventSeatTimeline(data);
			getResetSeatTimeline(data); 		

			$("#hostnameInput").blur(function(){
              	console.log('When blured.....');
        		autoSelectLRUs();
			});
        		
			$("a:not(.accordion-toggle)").click(function(){
				localStorage.removeItem("COUNT");
			});	
		});

   		$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        	var target = $(e.target).attr("href") // activated tab        	
         	if(target=='#staticsTab'){
         		loadSVDUsCharts();             	
    			$('#pcus').hide();
    			$('#qsebs').hide();		
    			$('#headend').hide();
    			$('#svdus').show();
    			$('#digitalServerflightTimeline').hide();
            	$('#systemEventsflightTimeline').hide();	
            	$('#cabinflightTimeline').hide();			
				$('#passengerflightTimeline').hide();
           	}else if(target=='#biteDataTab'){
           		$('#biteData').val('failures');
				$('#biteData').selectpicker('refresh');
           		getData("Failures", "failuresTable");
    		    $('#faults').hide();
    			$('#resets').hide();
    			$('#events').hide();
    			$('#services').hide();		
    			$('#failures').show();  
    			$('#digitalServerflightTimeline').hide();
            	$('#systemEventsflightTimeline').hide();	
            	$('#cabinflightTimeline').hide();			
				$('#passengerflightTimeline').hide();       	
            }else if(target=='#biteTimelineTab'){
            	//$('#accOneColTwog').removeClass('panel-body-open');
            	$('#biteTimelineLoading').show();  
				if($("#accOneColTwog").hasClass("panel-body-open")){
					document.getElementById("accOneColTwog").style.display = "none";
				}
				$("#more").show();
				count=null;
			  	if(localStorage.getItem("COUNT")==null){
			    	count = 0;
			    	localStorage.setItem("COUNT", count);
			    }else{
			    	count = Number.parseInt(localStorage.getItem("COUNT"));
			    }
            	getBiteTimelineData();
            	setInterval(function(){ 
                	$('#biteTimelineLoading').hide();
				}, 1000);
            	
            }else if(target=='#flightSeatDetailsTab'){            	
            	$('#flightSeatLoading').show();         
                $('#faultsSeat').hide();
                $('#resetsSeat').hide();
                $('#eventsSeat').hide(); 
                $('#failuresSeat').show();
                setInterval(function(){ 
                	$('#flightSeatLoading').hide();
				}, 1000);
            }else if(target=='#serviceDetailsTab'){
            	$('#CabinEvents').hide();
				$('#DigitalServerStatus').hide();
				$('#SystemEvents').hide();		
				$('#PassengerServices').show();
				$('#serviceLoading').show();
            	$('#passengerflightTimeline').hide();
            	$('#passengerflightTimeline').show();
            	setInterval(function(){ 
                	$('#serviceLoading').hide(); 
            	}, 1000);  	
            	$('#digitalServerflightTimeline').hide();
            	$('#systemEventsflightTimeline').hide();	
            	$('#cabinflightTimeline').hide();
            }
        });
        
		//Summary Page - Timeline View         
		function getTimeLineData(data) {
			$.ajax({
                 type: "POST",
                 dataType: "json",
                 url: "../ajax/getAircraftTimeLineData.php",
                 data: data,
                 success: function(data) {         
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
               props.event.preventDefault();
             });
         }

		//Statistics
		function loadHeadEndCharts() {
			    loadChartData('headEndFailureCodesLoading', 'headEndFailureCodesChart', 'headEndFailureCodes');
			    loadChartData('headEndFailureHostnamesLoading', 'headEndFailureHostnamesChart', 'headEndFailureHostnames');
			    loadChartData('headEndFaultCodesLoading', 'headEndFaultCodesChart', 'headEndFaultCodes');
			    loadChartData('headEndFaultHostnamesLoading', 'headEndFaultHostnamesChart', 'headEndFaultHostnames');
			    loadChartData('headEndExtAppCodesLoading', 'headEndExtAppCodesChart', 'headEndExtAppCodes');
			    loadChartData('headEndExtAppHostnamesLoading', 'headEndExtAppHostnamesChart', 'headEndExtAppHostnames');
			    loadChartData('headEndResetCodesLoading', 'headEndResetCodesChart', 'headEndResetCodes');
			    loadChartData('headEndResetHostnamesLoading', 'headEndResetHostnamesChart', 'headEndResetHostnames');	
		}
			
		function loadSVDUsCharts() {
			    loadChartData('svduFailureCodesLoading', 'svduFailureCodesChart', 'svduFailureCodes');
			    loadChartData('svduFailureHostnamesLoading', 'svduFailureHostnamesChart', 'svduFailureHostnames');
			    loadChartData('svduFaultCodesLoading', 'svduFaultCodesChart', 'svduFaultCodes');
			    loadChartData('svduFaultHostnamesLoading', 'svduFaultHostnamesChart', 'svduFaultHostnames');
			    loadChartData('svduExtAppCodesLoading', 'svduExtAppCodesChart', 'svduExtAppCodes');
			    loadChartData('svduExtAppHostnamesLoading', 'svduExtAppHostnamesChart', 'svduExtAppHostnames');
			    loadChartData('svduResetCodesLoading', 'svduResetCodesChart', 'svduResetCodes');
			    loadChartData('svduResetHostnamesLoading', 'svduResetHostnamesChart', 'svduResetHostnames');
		}
			
		function loadPCUsCharts() {
			    loadChartData('tpmuFailureCodesLoading', 'tpmuFailureCodesChart', 'tpmuFailureCodes');
			    loadChartData('tpmuFailureHostnamesLoading', 'tpmuFailureHostnamesChart', 'tpmuFailureHostnames');
			    loadChartData('tpmuFaultCodesLoading', 'tpmuFaultCodesChart', 'tpmuFaultCodes');
			    loadChartData('tpmuFaultHostnamesLoading', 'tpmuFaultHostnamesChart', 'tpmuFaultHostnames');
			    loadChartData('tpmuExtAppCodesLoading', 'tpmuExtAppCodesChart', 'tpmuExtAppCodes');
			    loadChartData('tpmuExtAppHostnamesLoading', 'tpmuExtAppHostnamesChart', 'tpmuExtAppHostnames');
			    loadChartData('tpmuResetCodesLoading', 'tpmuResetCodesChart', 'tpmuResetCodes');
			    loadChartData('tpmuResetHostnamesLoading', 'tpmuResetHostnamesChart', 'tpmuResetHostnames');
		}
			
		function loadQSEBsCharts() {
			    loadChartData('qsebFailureCodesLoading', 'qsebFailureCodesChart', 'qsebFailureCodes');
			    loadChartData('qsebFailureHostnamesLoading', 'qsebFailureHostnamesChart', 'qsebFailureHostnames');
			    loadChartData('qsebFaultCodesLoading', 'qsebFaultCodesChart', 'qsebFaultCodes');
			    loadChartData('qsebFaultHostnamesLoading', 'qsebFaultHostnamesChart', 'qsebFaultHostnames');
			    loadChartData('qsebExtAppCodesLoading', 'qsebExtAppCodesChart', 'qsebExtAppCodes');
			    loadChartData('qsebExtAppHostnamesLoading', 'qsebExtAppHostnamesChart', 'qsebExtAppHostnames');
			    loadChartData('qsebResetCodesLoading', 'qsebResetCodesChart', 'qsebResetCodes');
			    loadChartData('qsebResetHostnamesLoading', 'qsebResetHostnamesChart', 'qsebResetHostnames');
		}

		function showFlightSeatData(){
    			console.log($('#flightSeatData').val());
    			$("#failuresLopa").bootstrapTable('destroy');
    			$("#faultsLopa").bootstrapTable('destroy');
    			$("#resetsLopa").bootstrapTable('destroy');
    			$("#applicationsLopa").bootstrapTable('destroy'); 
    			if($('#flightSeatData').val()=='failures'){
        			$('#flightSeatLoading').show(); 
        			$('#faultsSeat').hide();
        			$('#resetsSeat').hide();
        			$('#eventsSeat').hide(); 
        			$('#failuresSeat').show(); 
        			setInterval(function(){ 
        				$('#flightSeatLoading').hide();
					}, 2000); 
    			}else if($('#flightSeatData').val()=='faults'){
    				$('#flightSeatLoading').show();  
        			$('#faultsSeat').show();
        			$('#resetsSeat').hide();
        			$('#eventsSeat').hide(); 
        			$('#failuresSeat').hide();
        			setInterval(function(){ 
        				$('#flightSeatLoading').hide();
					}, 2000);
    			}else if($('#flightSeatData').val()=='resets'){
        			$('#flightSeatLoading').show(); 
        			$('#faultsSeat').hide();
        			$('#resetsSeat').show();
        			$('#eventsSeat').hide(); 
        			$('#failuresSeat').hide();
        			setInterval(function(){ 
        				$('#flightSeatLoading').hide();
					}, 2000);
    			}else if($('#flightSeatData').val()=='events'){ 
        			$('#flightSeatLoading').show(); 
        			$('#faultsSeat').hide();
        			$('#resetsSeat').hide();
        			$('#eventsSeat').show(); 
        			$('#failuresSeat').hide();
        			setInterval(function(){ 
        				$('#flightSeatLoading').hide();
					}, 2000);
    			}
		}
			
		function loadChartData(loadingId, chartId, chartType) {
			data = {
			        <?php 
			            if($aircraftId != '')  {
			                echo "aircraftId: $aircraftId";
			            }
			            else {
			                echo "sqlDump: '$sqlDump'";
			            }
			            echo ",
			                flightLegs: '$flightLegs'";
			        ?>,
			        dataChartType: chartType
			    };
			
			$.ajax({
			        type: "POST",
			        dataType: "json",
			        url: "../ajax/getFlightDataChart.php",
			        data: data,
			        success: function(data) {
			            // console.log(data);
			            document.getElementById(loadingId).style.display = 'none';
			            var ctx = document.getElementById(chartId).getContext("2d");
			            
			            if(data.labels.length) {
			                var barChart = new Chart(ctx).Bar(data, {
			                    animation: false,
			                    responsive: true,
								barStrokeWidth : 1
			                });
			            } else {
			                ctx.font = "italic 12px Arial";
			                ctx.fillText("No reported data.", 100, 50);
			            }
			        },
			        error: function (err) {
			            console.log('Error', err);
			        }
			    });
			}
		
		function showStaticsData(){
				if($('#statics').val()=='headend'){
					$('#svdus').hide();
					$('#pcus').hide();
					$('#qsebs').hide();		
					$('#headend').show();			
					loadHeadEndCharts();					
				}else if($('#statics').val()=='svdus'){
					$('#headend').hide();
					$('#pcus').hide();
					$('#qsebs').hide();
					$('#svdus').show();	
					loadSVDUsCharts();	
					svdusLoaded = true;				
				}else if($('#statics').val()=='pcus'){
					$('#svdus').hide();
					$('#headend').hide();
					$('#qsebs').hide();
					$('#pcus').show();
					loadPCUsCharts();	
					pcusLoaded = true;				
				}else if($('#statics').val()=='qsebs'){
					$('#svdus').hide();
					$('#pcus').hide();
					$('#headend').hide();
					$('#qsebs').show();
					loadQSEBsCharts();	
					qsebsLoaded = true;				
				}
		}

		function showServiceData(){
			console.log($('#serviceData').val());
			if($('#serviceData').val()=='PassengerServices'){				
				$('#CabinEvents').hide();
				$('#DigitalServerStatus').hide();
				$('#SystemEvents').hide();		
				$('#PassengerServices').show();
				$('#serviceLoading').show();
            	$('#passengerflightTimeline').hide();
            	$('#passengerflightTimeline').show();
            	setInterval(function(){ 
                	$('#serviceLoading').hide(); 
            	}, 1000);  	
            	$('#digitalServerflightTimeline').hide();
            	$('#systemEventsflightTimeline').hide();	
            	$('#cabinflightTimeline').hide();
            	
			}else if($('#serviceData').val()=='CabinEvents'){
				$('#PassengerServices').hide();
				$('#DigitalServerStatus').hide();
				$('#SystemEvents').hide();		
				$('#CabinEvents').show();

				$('#serviceLoading').show();
            	$('#cabinflightTimeline').hide();	
            	$('#cabinflightTimeline').show();	
            	setInterval(function(){ 
                	$('#serviceLoading').hide(); 
            	}, 1000);  	
            	$('#digitalServerflightTimeline').hide();
            	$('#systemEventsflightTimeline').hide();	            			
				$('#passengerflightTimeline').hide();
				
			}else if($('#serviceData').val()=='DigitalServerStatus'){
				$('#CabinEvents').hide();
				$('#PassengerServices').hide();
				$('#SystemEvents').hide();		
				$('#DigitalServerStatus').show();

				$('#serviceLoading').show();
            	$('#digitalServerflightTimeline').hide();
            	$('#digitalServerflightTimeline').show();
            	setInterval(function(){ 
                	$('#serviceLoading').hide(); 
            	}, 1000);
            	$('#systemEventsflightTimeline').hide();	
            	$('#cabinflightTimeline').hide();			
				$('#passengerflightTimeline').hide();
				
			}else if($('#serviceData').val()=='SystemEvents'){
				$('#CabinEvents').hide();
				$('#DigitalServerStatus').hide();
				$('#PassengerServices').hide();		
				$('#SystemEvents').show();

				$('#serviceLoading').show();
            	$('#systemEventsflightTimeline').hide();
            	setInterval(function(){ 
                	$('#serviceLoading').hide(); 
            	}, 1000); 	
        		$('#systemEventsflightTimeline').show();
            	$('#digitalServerflightTimeline').hide();            		
            	$('#cabinflightTimeline').hide();			
				$('#passengerflightTimeline').hide();
				
			}	
		}

		function showBiteData(){										
				if($('#biteData').val()=='failures'){					
				    getData("Failures", "failuresTable");
				    $('#faults').hide();
					$('#resets').hide();
					$('#events').hide();
					$('#services').hide();		
					$('#failures').show();
				}else if($('#biteData').val()=='faults'){
					getData("Faults", "faultsTable");
					$('#faults').show();
					$('#resets').hide();
					$('#events').hide();
					$('#services').hide();		
					$('#failures').hide();
				}else if($('#biteData').val()=='resets'){
					getData("Resets", "resetsTable");
					$('#faults').hide();
					$('#resets').show();
					$('#events').hide();
					$('#services').hide();		
					$('#failures').hide();
				}else if($('#biteData').val()=='events'){
					getData("Events", "eventsTable");
					$('#faults').hide();
					$('#resets').hide();
					$('#events').show();
					$('#services').hide();		
					$('#failures').hide();
				}else if($('#biteData').val()=='services'){
					getData("ImpactedServices", "servicesTable");
					$('#faults').hide();
					$('#resets').hide();
					$('#events').hide();
					$('#services').show();		
					$('#failures').hide();
				}
		}
		function getData(type, table) {
				$('#biteDataLoading').show();
				$('#'+table).hide();
		        $.ajax({
		            type: "GET",
		            url: "../ajax/get" + type + ".php",
		            data: {
						<?php
							if($aircraftId != '')  {
								echo "aircraftId: $aircraftId";
							}
							else {
								echo "sqlDump: '$sqlDump'";
							}
						?>
		                ,flightLegs: '<?php echo $flightLegs; ?>'
		            },
		            success: function(data) {		               
		                // Need to convert from json string to json object to we can pass it to the table
		                var jsonData = $.parseJSON(data);

		                $('#' + table).bootstrapTable({
		                    data: jsonData,
							exportOptions: {
								fileName: '<?php echo $aircraftTailsign; ?>' + '_' + type + '_' + '<?php echo $flightLegs; ?>'
							}
		                });
		                $('#biteDataLoading').hide();
		                $('#'+table).show();
		            },
		            error: function (err) {
		                console.log('Error', err);
		            }
		        });
		}


		function getFailureSeatTimeline(data) {
				data = {
						<?php 
							if($aircraftId != '')  {
								echo "aircraftId: $aircraftId";
							}
							else {
								echo "sqlDump: '$sqlDump'";
							}
							echo ",
								flightLegs: '$flightLegs'";
						?>,
						failureTimeline: true
					};
				
				$.ajax({
					type: "GET",
					dataType: "json",
					url: "../ajax/getAircraftTimeLineData.php",
					data: data,
					success: function(data) {		 			
						createFailureSeatTimeline(data, 'failureTimeline', 'loadingFaultsTimeline');
					},
					error: function (err) {
						console.log('Error', err);
					}
				});

				data = {
						<?php 
							if($aircraftId != '')  {
								echo "aircraftId: $aircraftId";
							}
							else {
								echo "sqlDump: '$sqlDump'";
							}
							echo ",
								flightLegs: '$flightLegs'";
						?>,
						dataType: 'failures'
					};
				
				$.ajax({
					type: "GET",
					url: "../ajax/getLopaData.php",
					data: data,
					success: function(data) {
						$('#loadingFailuresLopa').hide();
						$("#failuresLopa").append(data);
					},
					error: function (err) {
						console.log('Error', err);
					}
				});
		}

		function getFaultSeatTimeline(data) {
				 data = {
					        <?php 
					            if($aircraftId != '')  {
					                echo "aircraftId: $aircraftId";
					            }
					            else {
					                echo "sqlDump: '$sqlDump'";
					            }
					            echo ",
					                flightLegs: '$flightLegs'";
					        ?>,
					        faultsTimeline: true
					    };
				    
				$.ajax({
			        type: "GET",
			        dataType: "json",
			        url: "../ajax/getAircraftTimeLineData.php",
			        data: data,
			        success: function(data) {
			            createFailureSeatTimeline(data, 'faultsTimeline', 'loadingFaultsTimeline');
			        },
			        error: function (err) {
			            console.log('Error', err);
			        }
			    });

				data = {
				        <?php 
				            if($aircraftId != '')  {
				                echo "aircraftId: $aircraftId";
				            }
				            else {
				                echo "sqlDump: '$sqlDump'";
				            }
				            echo ",
				                flightLegs: '$flightLegs'";
				        ?>,
				        dataType: 'faults'
				    };

			    $.ajax({
			        type: "GET",
			        url: "../ajax/getLopaData.php",
			        data: data,
			        success: function(data) {			            
			            $('#loadingFaultsLopa').hide();
			            $("#faultsLopa").append(data);
			        },
			        error: function (err) {
			            console.log('Error', err);
			        }
			    });
		}

		function getResetSeatTimeline(data) {
				data = {
				        <?php 
				            if($aircraftId != '')  {
				                echo "aircraftId: $aircraftId";
				            }
				            else {
				                echo "sqlDump: '$sqlDump'";
				            }
				            echo ",
				                flightLegs: '$flightLegs'";
				        ?>,
				        resetsTimeline: true
				    };
								
				$.ajax({
			        type: "GET",
			        dataType: "json",
			        url: "../ajax/getAircraftTimeLineData.php",
			        data: data,
			        success: function(data) {
			            createFailureSeatTimeline(data, 'resetsTimeline', 'loadingResetsTimeline');
			        },
			        error: function (err) {
			            console.log('Error', err);
			        }
			    });

				 data = {
					        <?php 
					            if($aircraftId != '')  {
					                echo "aircraftId: $aircraftId";
					            }
					            else {
					                echo "sqlDump: '$sqlDump'";
					            }
					            echo ",
					                flightLegs: '$flightLegs'";
					        ?>,
					        dataType: 'reset'
					    };

			    $.ajax({
			        type: "GET",
			        url: "../ajax/getLopaData.php",
			        data: data,
			        success: function(data) {			            
			            $('#loadingResetsLopa').hide();
			            $("#resetsLopa").append(data);
			        },
			        error: function (err) {
			            console.log('Error', err);
			        }
			    });
		}

		function getEventSeatTimeline(data) {
				data = {
				        <?php 
				            if($aircraftId != '')  {
				                echo "aircraftId: $aircraftId";
				            }
				            else {
				                echo "sqlDump: '$sqlDump'";
				            }
				            echo ",
				                flightLegs: '$flightLegs'";
				        ?>,
				        applicationsTimeline: true
				    };
			    
				$.ajax({
			        type: "GET",
			        dataType: "json",
			        url: "../ajax/getAircraftTimeLineData.php",
			        data: data,
			        success: function(data) {			            
			            createFailureSeatTimeline(data, 'applicationsTimeline', 'loadingApplicationsTimeline');
			        },
			        error: function (err) {
			            console.log('Error', err);
			        }
			    });

				data = {
				        <?php 
				            if($aircraftId != '')  {
				                echo "aircraftId: $aircraftId";
				            }
				            else {
				                echo "sqlDump: '$sqlDump'";
				            }
				            echo ",
				                flightLegs: '$flightLegs'";
				        ?>,
				        dataType: 'applications'
				    };

			    $.ajax({
			        type: "GET",
			        url: "../ajax/getLopaData.php",
			        data: data,
			        success: function(data) {
			            // console.log("lopa: \n\n" + data);
			            $('#loadingApplicationsLopa').hide();
			            $("#applicationsLopa").append(data);
			        },
			        error: function (err) {
			            console.log('Error', err);
			        }
			    });
		}
		function createFailureSeatTimeline(data, timelineId, loadingId) {
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
					var event = properties.items[0];
     				if(event.indexOf("FLG") != 0) {
     					<?php
     						if($aircraftId != '') {
     							$param = "aircraftId=$aircraftId";
     						} else {
     							$param = "db=$db";
     						}
     						$flytIds=$_REQUEST['flightLegs'];
     						echo "var url = 'UnitTimeline.php?$param&id=$flytIds&mainmenu=$mainMenu&event=';";
     					?>
     					window.location = url+event;
     					var urlToOpen = url+event;
     					var win = window.open(urlToOpen, '_self');
     					win.focus();
     				}
				});
				timeline.on('contextmenu', function (props) {
				  //alert('Right click!');
				  props.event.preventDefault();
				});
		}		    

	//Bite Timeline Initialization 
	//count variable for the "Load More" button
	var count;
  	if(localStorage.getItem("COUNT")==null){
    	count = 0;
    	localStorage.setItem("COUNT", count);
    }else{
    	count = Number.parseInt(localStorage.getItem("COUNT"));
    }
    //Container for the timeline object
    var container = document.getElementById('flightDetailedTimeline');

    var groups = [
    			{ id: 'OPP', content: '<i class="fa fa-sign-in fa-fw" aria-hidden="true"></i><br><strong>Open Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
    			{ id: 'CL', content: '<i class="fa fa-sign-out fa-fw" aria-hidden="true"></i><br><strong>Closed Flight Legs</strong>', style: 'font-weight: bold; text-align: center'},
    			{ id: 'FP', content: '<i class="fa fa-plane fa-fw" aria-hidden="true"></i><br><strong>Flight Phases</strong>', subgroupOrder: function (a,b) {return a.subgroupOrder - b.subgroupOrder;}, style: 'font-weight: bold;text-align: center'},
    			{ id: 'DSU', content: '<img src="../img/dsu.png"/><br><strong>DSU</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'AVCD_LAIC', content: '<img src="../img/dsu.png"/><br><strong>AVCD / LAIC</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'ADBG', content: '<img src="../img/adbg.png" width="16px" height="16px"/><br><strong>ADB / ADBG</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'ICMT', content: '<img src="../img/svdu.png" width="16px" height="16px"/><br><strong>ICMT</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'SPB', content: '<img src="../img/adbg.png" width="16px" height="16px"/><br><strong>SPB</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'QSEB_SDB_VCSSDB', content: '<img src="../img/adbg.png" width="16px" height="16px"/><br><strong>QSEB / SDB / VCSSDB</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'SVDU', content: '<img src="../img/svdu.png" width="16px" height="16px"/><br><strong>SVDU</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'TPMU', content: '<img src="../img/tpmu.png"/><br><strong>TMPU</strong>', style: 'font-weight: bold;text-align: center'},  	
    			{ id: 'CAMERA', content: '<i class="fa fa-video-camera fa-fw" aria-hidden="true"></i><br><strong>Camera</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'PRINTER', content: '<i class="fa fa-print fa-fw" aria-hidden="true"></i><br><strong>PRINTER</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'CIDSCSS', content: '<i class="fa fa-puzzle-piece fa-fw" aria-hidden="true"></i><br><strong>CIDS / CSS</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'DVD', content: '<img src="../img/adbg.png" width="16px" height="16px"/><br><strong>DVD</strong>', style: 'font-weight: bold;text-align: center'},  	
    			{ id: 'CWLU', content: '<img src="../img/adbg.png" width="16px" height="16px"/><br><strong>CWLU</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'BTS', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>BTS</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'NCU', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>NCU</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'SDU', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>SDU</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'MODMAN', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>MODMAN</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'KRFU', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>KRFU</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'KANDU', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>KANDU</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'AFDX', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>AFDX</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'DCPS', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>DCPS</strong>', style: 'font-weight: bold;text-align: center'},	
    			{ id: 'SAC', content: '<img src="../img/dsu.png"width="16px" height="16px" /><br><strong>SAC</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'IPM', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>IPM</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'FSA', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>FSA</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'APM', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>APM</strong>', style: 'font-weight: bold;text-align: center'},	
    			{ id: 'NFC', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>NFC</strong>', style: 'font-weight: bold;text-align: center'},		
    			{ id: 'OAE', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>OAE</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'ACARS', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>ACARS</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'A429', content: '<img src="../img/dsu.png" width="16px" height="16px"/><br><strong>A429</strong>', style: 'font-weight: bold;text-align: center'},
    			{ id: 'TU', content: '<img src="../img/dsu.png"width="16px" height="16px" /><br><strong>TU</strong>', style: 'font-weight: bold;text-align: center'}];

		// Configuration for the Timeline
    	var options = {
    			orientation: 'both',
    			clickToUse: true,
    			stack: false
		};

		$("#more").click(loadTimeline);
     	
		function loadTimeline(){
     			var filteredItems;
     			var filteredGroups;
     			var set1 = ["OPP", "CL", "FP", "DSU", "AVCD_LAIC", "ADBG", "ICMT"];
     			var set2 = ["SPB", "QSEB_SDB_VCSSDB", "SVDU"];
     			var set3 = ["TPMU", "CAMERA", "PRINTER", "CIDSCSS", "DVD", "CWLU", "BTS", "NCU", "SDU", "MODMAN", "KRFU", "KANDU", "AFDX", "DCPS", "SAC", "IPM", "FSA", "APM", "NFC", "OAE", "ACARS", "A429", "TU"];
     			if(count==0){
     				var filterSet = set1;
     				count+=1;
     				localStorage.setItem("COUNT", 0);
     			}else if(count==1){
     				var filterSet = set1.concat(set2);
     				count+=1;
     				localStorage.setItem("COUNT", 1);
     			}else{
     				var filterSet = set1.concat(set2).concat(set3);
     				//Complete Timeline is displayed, hide the "Load More" button
     				$("#more").hide();
     				localStorage.setItem("COUNT", 2);
     			}
     			var filteredItems = issueItems.filter(function(obj){
     				return filterSet.indexOf(obj.group) != -1;
     			});
     			var filteredGroups = groups.filter(function(obj){
     				return filterSet.indexOf(obj.id) != -1;
     			});
     			container.innerHTML = "";
     			var itemSet = new vis.DataSet(filteredItems);
     			var groupSet = new vis.DataSet(filteredGroups);
     			var timeline = new vis.Timeline(container, itemSet, groupSet, options);
     			timeline.on('select', function (properties) {
     				var event = properties.items[0];
     				if(event.indexOf("FLG") != 0) {
     					<?php
     						if($aircraftId != '') {
     							$param = "aircraftId=$aircraftId";
     						} else {
     							$param = "db=$db";
     						}
     						$flytIds=$_REQUEST['flightLegs'];
     						echo "var url = 'UnitTimeline.php?$param&id=$flytIds&mainmenu=$mainMenu&event=';";
     					?>
     					window.location = url+event;
     					var urlToOpen = url+event;
     					var win = window.open(urlToOpen, '_self');
     					win.focus();
     				}
     			});
		}
		     	
	    //FlightTimeline	     
	    function getBiteTimelineData(){
		    if(firstTime){
		    	data = {
				        <?php 
				            if($aircraftId != '')  {
				                echo "aircraftId: $aircraftId";
				            }
				            else {
				                echo "sqlDump: '$sqlDump'";
				            }
				            echo ",
				                flightLegs: '$flightLegs'";
				        ?>,
				        'filter': 'false'
				    };
		    	firstTime=false;
			}else{
				data={};
				data = {
				        <?php 
				            if($aircraftId != '')  {
				                echo "aircraftId: $aircraftId";
				            }
				            else {
				                echo "sqlDump: '$sqlDump'";
				            }
				            echo ",
				                flightLegs: '$flightLegs'";
				        ?>,
				        'filter': 'true'			       
				        
				    };

		/*		var showDSU= $("input[name=showDSU]").val();
				var showAVCD_LAIC= $("input[name=showAVCD_LAIC]").val();
				var showICMT= $("input[name=showICMT]").val();
		        var showSVDU= $("input[name=showSVDU]").val();
		        var showTPMU= $("input[name=showTPMU]").val();
		        var showQSEB_SDB_VCSSDB= $("input[name=showQSEB_SDB_VCSSDB]").val();
		        var showADBG= $("input[name=showADBG]").val();
		        var showSPB= $("input[name=showSPB]").val();
		        var otherLruType= $("#otherLruType").val();
		        var hostnameInput= $("input[name=hostnameInput]").val();
		        var showFailures= $("input[name=showFailures]").val();
		        var showFaults= $("input[name=showFaults]").val();
		        var showReboots= $("input[name=showReboots]").val();
		        var showAppEvents= $("input[name=showAppEvents]").val();
		        var showImpServices= $("input[name=showImpServices]").val();
		        var biteCode= $("input[name=biteCode]").val();
		        var notBiteCode= $("input[name=notBiteCode]").val();
		        var severity=$("input[name=severity]").val();
		        var monitorState= $("input[name=monitorState]").val();
		        var min= $("input[name=min]").val();*/

				if($('input[name=showDSU]').prop('checked')) {
					data.showDSU= $("input[name=showDSU]").val();
				}

				if($('input[name=showAVCD_LAIC]').prop('checked')) {
					data.showAVCD_LAIC= $("input[name=showAVCD_LAIC]").val();
				}

				if($('input[name=showICMT]').prop('checked')) {
					data.showICMT= $("input[name=showICMT]").val();
				}

				if($('input[name=showSVDU]').prop('checked')) {
					data.showSVDU= $("input[name=showSVDU]").val();
				}

				if($('input[name=showTPMU]').prop('checked')) {
					data.showTPMU= $("input[name=showTPMU]").val();
				}

				if($('input[name=showTPMU]').prop('checked')) {
					data.showTPMU= $("input[name=showTPMU]").val();
				}

				if($('input[name=showQSEB_SDB_VCSSDB]').prop('checked')) {
					data.showQSEB_SDB_VCSSDB= $("input[name=showQSEB_SDB_VCSSDB]").val();
				}

				if($('input[name=showADBG]').prop('checked')) {
					data.showADBG= $("input[name=showADBG]").val();
				}

				if($('input[name=showSPB]').prop('checked')) {
					data.showSPB= $("input[name=showSPB]").val();
				}

				if($('input[name=showFailures]').prop('checked')) {
					data.showFailures= $("input[name=showFailures]").val();
				}

				if($('input[name=showFaults]').prop('checked')) {
					data.showFaults= $("input[name=showFaults]").val();
				}

				if($('input[name=showReboots]').prop('checked')) {
					data.showReboots= $("input[name=showReboots]").val();
				}
				
				if($('input[name=showImpServices]').prop('checked')) {
					showImpServices= $("input[name=showImpServices]").val();
				}

				if($('input[name=showAppEvents]').prop('checked')) {
					data.showAppEvents= $("input[name=showAppEvents]").val();
				}

				if($('#allSeverity').prop('checked')) {
					data.severity= $("#allSeverity").val();
				}else if($('#criticalSeverity').prop('checked')) {
					data.severity= $("#criticalSeverity").val();
				}else if($('#not_criticalSeverity').prop('checked')) {
					data.severity= $("#not_criticalSeverity").val();
				}

				if($('#allmonitorState').prop('checked')) {
					data.monitorState= $("#allmonitorState").val();
				}
				if($('#activeMonitorState').prop('checked')) {
					data.monitorState= $("#activeMonitorState").val();
				}
				if($('#inactmonitorState').prop('checked')) {
					data.monitorState= $("#inactmonitorState").val();
				}

			//	if($('input[name=monitorState]').prop('checked')) {
			//		data.monitorState= $("input[name=monitorState]").val();
			//	}

				if($("input[name=hostnameInput]").val()!=null && $("input[name=hostnameInput]").val()!=''){
					data.hostnameInput=$("input[name=hostnameInput]").val();
				}
				
				if($("input[name=biteCode]").val()!=null && $("input[name=biteCode]").val()!=''){
					data.biteCode=$("input[name=biteCode]").val();
				}
				
				if($("input[name=notBiteCode]").val()!=null && $("input[name=notBiteCode]").val()!=''){
					data.notBiteCode=$("input[name=notBiteCode]").val();
				}
				
				if($("input[name=otherLruType]").val()!=null && $("input[name=otherLruType]").val()!=''){
					data.otherLruType=$("input[name=otherLruType]").val();
				}
			}    	
		    
			$.ajax({
		        type: "GET",
		        url: "../ajax/GetBiteTimelineData.php",
		        data: data,
		        success: function(data) {
			       // console.log(JSON.stringify(JSON.parse(data)));			            
			        //console.log(data);
			        var array = JSON.parse("[" + data + "]");
		            //createFailureSeatTimeline(data, 'applicationsTimeline', 'loadingApplicationsTimeline');
		        	issueItems=array; 
		        	loadTimeline();
		        },
		        error: function (err) {
		            console.log('Error', err);
		        }
		    });
		}

	    $('#filter').click(function(){ 
	    	//$('#accOneColTwog').removeClass('panel-body-open'); 
	    	$('#biteTimelineLoading').show();
	    	if($("#accOneColTwog").hasClass("panel-body-open")){
				document.getElementById("accOneColTwog").style.display = "none";
			}
	    	if(document.getElementById("accOneColTwog").style.display=='block'){
				document.getElementById("accOneColTwog").style.display = "none";
			}
	    	$("#more").show();
	    	count=null;
	      	if(localStorage.getItem("COUNT")==null){
	        	count = 0;
	        	localStorage.setItem("COUNT", count);
	        }else{
	        	count = Number.parseInt(localStorage.getItem("COUNT"));
	        }
	  		getBiteTimelineData();
	  		setInterval(function(){ 
            	$('#biteTimelineLoading').hide();
			}, 1000);
	  	});

	  	function eraseBiteCode() {
	  		document.getElementById("biteCode").value = "";
	  	}

	  	function eraseNotBiteCode() {
	  		document.getElementById("notBiteCode").value = "";
	  	}

	  	function eraseHostname() {
	  		document.getElementById("hostnameInput").value = "";
	  		autoSelectLRUs();
	  	}
	  	
	  	//this function automatically select the 
	  	//filter checkboxes based on the HostName entered
	  	var matched = [];
	  	function autoSelectLRUs(){
	  		var hostname = $("#hostnameInput").val().toLowerCase();
	  		var checkboxes = {"dsu":"dsu", "avcd":"avcd_laic", "laic":"avcd_laic", "icmt":"icmt", "svdu":"svdu", "tpmu":"tpmu", "qseb":"qseb_sdb_vcssdb", "sdb":"qseb_sdb_vcssdb", "vcssdb":"qseb_sdb_vcssdb", "adb":"adb_adbg", "adbg":"adb_adbg", "spb":"spb"};
	  		var otherlrus = {"a429":"A429","acars":"ACARS","apm":"APM","afdx":"AFDX","bts":"BTS","camera":"Camera","css":"CIDSCSS","cids":"CIDSCSS","cwlu":"CWLU","dcps":"DCPS","dvd":"DVD","fsa":"FSA","kandu":"KANDU","krfu":"KRFU","ipm":"IPM","modman":"MODMAN","ncu":"NCU","nfc":"NFC","oae":"OAE","printer":"PRINTER","sdu":"SDU","sac":"SAC","tu":"TU"};
	  		var otherlrus_keys = Object.keys(otherlrus);
	  		//reset modified filters to default
	  		for(var lru in checkboxes){
	  			if(lru=="svdu" || lru=="tpmu"){
	  				$('#' + checkboxes[lru] + '_checkbox').prop('checked', false);
	  			}else{
	  				$('#' + checkboxes[lru] + '_checkbox').prop('checked', true);
	  			}
	  		}
	  		if(matched){
	  			matched.forEach(function(lru){
	  				if(hostname.indexOf(lru)==-1){
	  					$("#otherLruType option[value='" + otherlrus[lru] + "']").removeAttr("selected");
	  				}
	  			});
	  			matched=[];
	  		}
	  		//set the lrus present in hostname
	  		for(var lru in checkboxes){
	  			if(hostname.indexOf(lru)!=-1){
	  				$('#' + checkboxes[lru] + '_checkbox').prop('checked', true);
	  			}
	  		}
	  		for(var lru in otherlrus){
	  			if(hostname.indexOf(lru)!=-1){
	  				$("#otherLruType option[value='" + otherlrus[lru] + "']").attr("selected", "selected");
	  				matched.push(lru);
	  			}
	  		}
	  	}

	  	$('#rebootHeading').click(function(){ 
	  		if(document.getElementById("accOneColTwof").style.display=='block'){
				document.getElementById("accOneColTwof").style.display = "none";
			}else{
				document.getElementById("accOneColTwof").style.display = "block";
			}
	  		if(document.getElementById("accOneColTwog").style.display=='block'){
				document.getElementById("accOneColTwog").style.display = "none";
			}
		});

	  	$('#filterHeading').click(function(){ 
	  		if(document.getElementById("accOneColTwog").style.display=='block'){
				document.getElementById("accOneColTwog").style.display = "none";				
			}else{
				document.getElementById("accOneColTwog").style.display = "block";
			}
	  		if(document.getElementById("accOneColTwof").style.display=='block'){
				document.getElementById("accOneColTwof").style.display = "none";
			}
		});
		
	 // Toggle plus minus icon on show hide of collapse element
	    $(".collapse").on('show.bs.collapse', function(){
	    	$(this).parent().find(".glyphicon").removeClass("glyphicon-chevron-up").addClass("glyphicon-chevron-down");
	    	
	    }).on('hide.bs.collapse', function(){
	    	$(this).parent().find(".glyphicon").removeClass("glyphicon-chevron-down").addClass("glyphicon-chevron-up");
	    	
	    });

		function flightseatToUnitTimeline(url){			
			var urlToOpen = url+'&mainmenu='+'<?php echo $mainMenu; ?>';
			var win = window.open(urlToOpen, '_self');
			win.focus();
		}

		$("#filterIconHref").on('click', function() {
			if ($("#filterIcon").hasClass("glyphicon glyphicon-chevron-right")) {
				$("#filterIcon").removeClass("glyphicon glyphicon-chevron-right");
				$("#filterIcon").addClass("glyphicon glyphicon-chevron-down");
			}else{
				$("#filterIcon").removeClass("glyphicon glyphicon-chevron-down");
				$("#filterIcon").addClass("glyphicon glyphicon-chevron-right");				
			}
		});
	</script>
	<script src="../js/angular-animate.js"></script>
	<script src="../js/angular-route.js"></script>
	<script src="../js/angular-sanitize.js"></script>
	<script src="../js/wiringData/utility.js"></script>
	<script src="../js/wiringData/controller/dbfunctions.js"></script>	  	  
	<script src="../js/wiringData/model/dbfunctions.js"></script>		
	<script src="../js/wiringData/controller/seatconnection.js"></script>
	<script src="../js/wiringData/controller/enhancedseat.js"></script>
	<script src="../js/wiringData/controller/lrumap.js"></script>
	<script src="../controllers/LRURemovalTimelineController.js"></script>

</body>
</html>
