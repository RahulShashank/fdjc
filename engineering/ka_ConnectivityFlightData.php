<?php 

session_start ();
$menu = 'ConnectivityFlightData';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");
require_once "../database/connecti_mongoDB.php";
require_once "../common/functions.php";
require_once("../map/airports.php");
require_once "../database/connecti_mongoDB_Ka.php";

$airlineIds = $_SESSION ['airlineIds'];
$airlineIds = rtrim ( implode ( ",", $_SESSION ['airlineIds'] ), "," );
$airlineIds = rtrim ( $airlineIds, "," );
error_log('airline Ids..'.$airlineIds);
// get current date
$endDateTime = date ( 'Y-m-d' );
$startDate = date_create ( "$endDateTime" );
date_sub ( $startDate, date_interval_create_from_date_string ( "30 days" ) );
$startDateTime = date_format ( $startDate, "Y-m-d" );

$aircraftId = $_REQUEST['aircraftId'];
$start = $_REQUEST['startDate'];
$end = $_REQUEST['endDate'];
$flightLegs = $_REQUEST['flightLegs'];

//session variables
$airlineId = $_REQUEST['airlineId'];
$tailsign = $_REQUEST['tailsign'];
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];
$conStatusVisited = $_REQUEST['conStatusVisited'];
$firstTime=false;
$url="ConnectivityStatus.php?aircraftId=$aircraftId&startDate=$startDate&endDate=$endDate&firstTime=$firstTime&tailsign=$tailsign&airlineId=$airlineId";

if($aircraftId != '') {
    // Get information to display in header
    $query = "SELECT a.tailsign, b.id, b.name, a.databaseName, a.isp FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $aircraftTailsign = $row ['tailsign'];
      $airlineId = $row['id'];
      $airlineName = $row['name'];
      $dbName = $row['databaseName'];
	  $aircraftIsp = $row['isp'];
    } else {
      echo "error: " . mysqli_error ( $error );
    }
} else if($sqlDump != '') {
    $airlineName = $row['name'];
    $dbName = $row['databaseName'];
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}


if($aircraftIsp=="KaNoVAR"){
	if( ($start == '') && ($end == '') ) {
		// Get flight leg start time and end time - work for only one flight leg...
		$query = "SELECT createDate, lastUpdate FROM $dbName.SYS_flight WHERE idFlightLeg IN ($flightLegs) LIMIT 1";	
		$result = mysqli_query($dbConnection, $query);
		
		if($result) {
			$row = mysqli_fetch_array($result);
			$start = $row['createDate'];
			$end = $row['lastUpdate'];		
		} else {
			echo "error with query $query"; exit;
		}
	}
	
	$collection = $db->Ka_connectivityActivity;
	$cursorHeader = $collection->find(array("tailSign" => $aircraftTailsign, 'timestamp' => array('$gt' => $start, '$lte' => $end)))->limit(1);
	$cursorBody = $collection->find(array("tailSign" => $aircraftTailsign, 'timestamp' => array('$gt' => $start, '$lte' => $end)));
	
	if($cursorBody->count() == 0) {
		$displayNoDataAlert = true;
	}
	
	$collection = $db->Ka_connectivityEvents;

	$flightCursor = $collection->find(
		array("startTime" => array('$gte' => $start), "endTime" => array('$lte' => $end), "tailSign" => $aircraftTailsign),
		array("wifiAvailabilityEvents" => 1, "cityPair" => 1)
		);
	
	$wifiOffEvents = array();
	
	if($flightCursor->count() > 0) {
		$flight = $flightCursor->getNext(); // only one object to retrieve
		$cityPair = $flight['cityPair'];
		$cityPairArray = explode("-", $cityPair);
						
		$departureAirportCode = $cityPairArray[0];
		$arrivalAirportCode = $cityPairArray[1];	
	
		$wifiAvailabilityEvents = $flight['wifiAvailabilityEvents'];		
			
		foreach($wifiAvailabilityEvents as $event) {		
			if($event['description'] == "WIFI-OFF") {
				$wifiOffEvents[] = array(
					'service' => "WIFI",
					'start' => $event['startTime'],
					'end' => $event['endTime'],
					'startLatLong' => $event['location']['coordinates'][0],
					'endLatLong' => $event['location']['coordinates'][1],
					'rootCause' => $event['computedFailure']
				);
			}
		}
		////////// ADD TAILSIGN IN QUERY !!!!!!!!!!!!!!!!!!!!!!!!
		$collection = $db->Ka_connectivityActivity;
		$activities = $collection->find(
				array("timestamp" => array('$gte' => $start, '$lte' => $end), "tailSign" => $aircraftTailsign),
				array("timestamp" => 1, "ASC_SHARED" => 1)
				)->sort(array("timestamp" => 1));
	
		$trajectory = array();
				
		$i = 0;
		foreach($activities as $activity) {
			// Debug
			$flightPhase = $activity['ASC_SHARED']['Flight Phase'];
			if($flightPhase == "unknown" || $flightPhase == "ko") {
				echo "Flight phase should not be retrieved => $flightPhase"; exit;
			}
				
			if($i == 0) {
				// Get the first coordinates which will help building the lines
				$previousLatitude = $activity['ASC_SHARED']['Latitude'];
				$previousLongitude = $activity['ASC_SHARED']['Longitude'];				
					
				// Used to set the view of the map if we don't have departure/arrival airports
				$firstLatitude = $previousLatitude;
				$firstLongitude = $previousLongitude;
					
				$i++;
				continue;
			}
				
			$timestamp = $activity['timestamp'];
				
			$latitude = $activity['ASC_SHARED']['Latitude'];
			$longitude = $activity['ASC_SHARED']['Longitude'];
			$altitude = $activity['ASC_SHARED']['Standard Pres Alti'];
	
			if($altitude < 10000) {
				$wifiStatus = "DISABLED";
			} else {
					
				$wifiStatus = "ON";
				$wifiStartOff = $timestamp;
				$wifiEndOff = $timestamp;
					
				foreach($wifiOffEvents as $wifiOffEvent) {
					if( ($timestamp >= $wifiOffEvent['start']) && ($timestamp <= $wifiOffEvent['end']) ) {
						$wifiStatus = "OFF";
						$wifiRootCause = $wifiOffEvent['rootCause'];
						$wifiStartOff = $wifiOffEvent['start'];
						$wifiEndOff = $wifiOffEvent['end'];
						$wifiDurationOff = dateDifference($wifiStartOff, $wifiEndOff, '%hh %I\' %S\'\'');
						break;
					}
				}
			}
				
			$trajectory[] = array(	"type" => "Feature",
					"properties" => array(
						"services" => array(
							array("service" => "WIFI", "status" => $wifiStatus, "rootCause" => $wifiRootCause, "start" => $wifiStartOff, "end" => $wifiEndOff, "duration" => $wifiDurationOff)
						)
					),
					"geometry" => array(
						"type" => "LineString", 
						"coordinates" => array(
							array($previousLongitude, $previousLatitude),
							array($longitude, $latitude)
						)
					)
				);
				
			$previousLatitude = $latitude;
			$previousLongitude = $longitude;
		}
	} else {
		$displayNoMapDataAlert = true;
	}
	
	// Used to set the view of the map if we don't have departure/arrival airports
	$lastLatitude = $previousLatitude;
	$lastLongitude = $previousLongitude;
	$departureAirportInfo = getAirportInfo($departureAirportCode);
	$departureAirportLat = $departureAirportInfo['lat'];
	$departureAirportLong = $departureAirportInfo['long'];
	$departureAirportName = $departureAirportInfo['name'];
	$departureAirportCity = $departureAirportInfo['municipality'];
	$departureAirportElevation = $departureAirportInfo['elevation'];
	
	$arrivalAirportInfo = getAirportInfo($arrivalAirportCode);
	$arrivalAirportLat = $arrivalAirportInfo['lat'];
	$arrivalAirportLong = $arrivalAirportInfo['long'];
	$arrivalAirportName = $arrivalAirportInfo['name'];
	$arrivalAirportCity = $arrivalAirportInfo['municipality'];
	$arrivalAirportElevation = $arrivalAirportInfo['elevation'];
	if($departureAirportLong >= 90 && $arrivalAirportLong <= -20) {
		$arrivalAirportLong += 360;
	} else if($departureAirportLong <= -20 && $arrivalAirportLong >= 90) {
		$departureAirportLong += 360;
	
	}
	
	$trajectory = json_encode($trajectory);
}
?>
<!DOCTYPE html>
<html lang="en" >
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/icon.ico">
<title>BITE Analytics</title>
<meta charset="utf-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- CSS INCLUDE -->
<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
<!-- EOF CSS INCLUDE -->
<script src="../js/jquery-1.11.2.js"></script>
<link href="../css/jquery.datetimepicker.css" rel="stylesheet" type="text/css" />
<script src="../js/jquery.datetimepicker.js"></script>
<script src="../js/jquery.blockUI.js"></script>
<script src="../js/angular.js"></script>
<script src="../js/angular-route.js"></script>
<script src="../js/angular-cookies.js"></script>
<!--<script src="../js/plugins/jquery/jquery.min.js"></script>-->
<script src="../js/plugins/jquery/jquery-ui.min.js"></script>
<script src="../js/plugins/bootstrap/bootstrap.min.js"></script>
<link rel="stylesheet"	href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/plugins/bootstrap/bootstrap-select.js"></script>
<link rel="stylesheet" href="../css/bootstrap-multiselect.css">
<script src="../js/bootstrap-multiselect.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<script src="../js/Chart.HeatMap.S.js"></script>
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>

<link rel="stylesheet" href="../css/bootstrap-select/bootstrap-select.min.css">
<script src="../js/bootstrap-select/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="../css/bootstrap-slider/bootstrap-slider.min.css">
<script src="../js/bootstrap-slider/bootstrap-slider.min.js"></script>
<link rel="stylesheet" href="../css/dataTables/datatables.min.css">	
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedcolumns/3.2.2/css/fixedColumns.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedheader/3.1.2/css/fixedHeader.dataTables.min.css"/>
<link rel="stylesheet" href="../css/leaflet/leaflet.css">
<script src="../js/dataTables/datatables.min.js"></script>
<script src="../js/moment/moment.min.js"></script>
<link href="../css/vis.css" rel="stylesheet">
<link href="../css/chartist.min.css" rel="stylesheet">		
<script src="../js/vis.min.js"></script>
<script src="../js/Chart.js"></script>
<script src="../js/chartist.min.js"></script>	
<script src="../js/d3.min.js"></script>	
<script src="../js/leaflet/leaflet.js"></script>
<link rel="stylesheet" href="../css/leaflet/leaflet.css">
<script src="../js/map/onAirWifiAreas-geojson.js" type="text/javascript"></script>
<script src="../js/map/onAirOmtsAreas-geojson.js" type="text/javascript"></script>
</head>
<style>
		.ct-series-a .ct-slice-donut 	{
											/* give the donut slice a custom colour */
											stroke: #78AB46;
										}
		
		.ct-series-b .ct-slice-donut 	{
											/* give the donut slice a custom colour */
											stroke: #ff3333;
										}
		
		.ct-label {fill:rgba(255,255,255,1);color:rgba(0,0,0,.4);font-size:1.5rem;font-family:"Bookman Old Style";line-height:1}		
		.axis path,.axis line {
			fill: none;
			stroke:#b6b6b6;
			shape-rendering: crispEdges;
		}		
		.tick text {
			font-family: Arial, sans-serif;
			font-size:12px;
			fill:#888;
		}
		
		.grid .tick {
			stroke: lightgrey;
			opacity: 0.7;
		}
		.grid path {
			  stroke-width: 0;
		}
</style>
<!-- Additional Styles -->
<style type="text/css">
		.leaflet-popup-content-wrapper {
			background: #FDFDFD;
			width: 245px;
		}
</style>

<style type="text/css">
		td {
			padding: 5px;
			vertical-align: middle;
		}
		
		a.serviceLink {
			color: grey;
		}
		a.serviceLink:link,
		a.serviceLink:visited,
		a.serviceLink:hover,
		a.serviceLink:active {
			text-decoration: none;
			border-bottom: 1px dotted;
		}
	/* style for fixed header and scrollable body table */
    .fixed-table-body {
        overflow-x: auto;
        overflow-y: auto;
        height: 100% !important;
    }
    
    .fixed-table-header {
        margin-right: 15px;
    }
</style>
<body >
	<!-- START PAGE CONTAINER -->
	<div class="page-container">

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
				<!-- SIGN OUT -->
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span></a></li>
				<!-- END SIGN OUT -->
			</ul>
			<!-- END X-NAVIGATION VERTICAL -->

			<!-- START BREADCRUMB -->
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li><a href="<?php echo $url;?>">Connectivity Status</a></li>
				<li class="active">Connectivity Flight Data</li>
			</ul>
			<!-- END BREADCRUMB -->

			<div class="page-title">
				<h2>
					<a href="<?php echo $url;?>" style="color:#3e4a61"> <span class="fa fa-arrow-circle-o-left" title="Back to Connectivity Status"></span></a>
					Connectivity Flight Data
				</h2>
			</div>

			<!-- PAGE CONTENT WRAPPER -->
			<div id="ctrldiv" class="page-content-wrap">
				<div class="tab-content" role="tab" data-toggle="tab">
						<div id="tabularData">
							<br /> <br />
							<!-- Nav tabs -->
							<ul class="nav nav-tabs" role="tablist" id="myTabs" style="padding:0px 10px;">								
								<li role="presentation" class="active" >
									<a href="#logs" aria-controls="logs" role="tab" data-toggle="tab" style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Logs</a>
								</li>
								<li role="presentation" >
									<a href="#maps" aria-controls="maps" role="tab" data-toggle="tab" style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Maps</a>
								</li>				
							</ul>
							<!-- Tab panes -->
							<div class="tab-content">						
								
								<div role="tabpanel" class="tab-pane" id="maps">
									
								</div>										
								<div role="tabpanel" class="tab-pane active" id="logs">						
									<div class="row">
										<div class="col-md-12">
											<div class="panel panel-default">
												<div class="panel-body  text-center">
													<?php
														$isDataAvailable=false;
														if (isset ( $cursorBody )) {
															foreach($cursorBody as $key => $value) {
																if(is_array($value) && $value != '-'){
																	$isDataAvailable=true;
																	break;
																}
															}
														}
														
															if($isDataAvailable){
																include("kaConnectivityLogTable.php");
															}else{
																echo "<label class=\"noData-label\"> No data available for the selected duration or selected filters </label>";
																// To remove the white space below the page content
																echo "<script type=\"text/javascript\">page_content_onresize();</script>";
															}
														
													?>								
												</div>
											</div>
										</div>
									</div>
								</div>									
							</div>
							</div>
						</div>
					
						<div id="mapView" class="row">
							<div class="col-md-12">
								<div class="panel panel-default">
									<div class="panel-body">
										<?php
										if($displayNoMapDataAlert) {
											echo "<div class=\"alert alert-warning\" role=\"alert\">
												  <span class=\"glyphicon glyphicon-exclamation-sign\" aria-hidden=\"true\"></span>
												  <span class=\"sr-only\">Error:</span>
												  No connectivity data has been uploaded for this flight.
												</div>
												<br>";
										} else {
											echo "<div id=\"map\" style=\"height: 800px\"></div>";
										}
									?>			
										<script>
											//var map = L.map('map').setView([51.505, -0.09], 13);
											<?php
												echo "var departureAirportCode = '$departureAirportCode';\n";
												echo "var departureAirportName = '$departureAirportName';\n";
												echo "var departureAirportCity = '$departureAirportCity';\n";
												echo "var departureAirportLat = '$departureAirportLat';\n";
												echo "var departureAirportLong = '$departureAirportLong';\n";
												echo "var departureAirportElevation = '$departureAirportElevation';\n";
						
												echo "var arrivalAirportCode = '$arrivalAirportCode';";
												echo "var arrivalAirportName = '$arrivalAirportName';\n";
												echo "var arrivalAirportCity = '$arrivalAirportCity';\n";
												echo "var arrivalAirportLat = '$arrivalAirportLat';\n";
												echo "var arrivalAirportLong = '$arrivalAirportLong';\n";
												echo "var arrivalAirportElevation = '$arrivalAirportElevation';\n";
												
												echo "var firstLat = '$firstLatitude';\n";
												echo "var firstLong = '$firstLongitude';\n";
												echo "var lastLat = '$lastLatitude';\n";
												echo "var lastLong = '$lastLongitude';\n";
											?>
																
											var map = L.map('map').fitBounds(
																		[
																			[firstLat, firstLong],
																			[lastLat, lastLong]
																		],
																		{padding: [20,20]}
																	);
						
											// var mapUrl = 'https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6IjZjNmRjNzk3ZmE2MTcwOTEwMGY0MzU3YjUzOWFmNWZhIn0.Y8bhBaUMqFiPrDRW9hieoQ';
											//var mapUrl = 'http://otile4.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.png';
											var mapUrl = 'http://{s}.tile.osm.org/{z}/{x}/{y}.png';
						
											L.tileLayer(mapUrl, {
												maxZoom: 18,
												id: 'mapbox.streets'
											}).addTo(map);
						
											// Google style icons
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
						
											if( (departureAirportLat != '') && (departureAirportLong != '') ) {
												L.marker(
														[
															departureAirportLat, 
															departureAirportLong
														],
														{icon: departureIcon}
													)
													.addTo(map)
													.bindPopup(
														"<b>Code</b>: " + departureAirportCode + "<br>" +
														"<b>City</b>: " + departureAirportCity + "<br>" +
														"<b>Airport Name</b>: " + departureAirportName + "<br>" +
														
														"<br><b>Latitude</b>: " + departureAirportLat + " / " + degToDms(departureAirportLat) + "<br>" +
														"<b>longitude</b>: " + departureAirportLong + " / " + degToDms(departureAirportLong, true) + "<br>" +
														"<b>Elevation</b>: " + departureAirportElevation + "ft / " + Math.round(convertFeetToMeters(departureAirportElevation)) + "m<br>" +
						
														"<br>This the <u>DEPARTURE</u> airport."
													);
											}
											
											if( (arrivalAirportLat != '') && (arrivalAirportLong != '') ) {
												L.marker(
														[
															arrivalAirportLat, 
															arrivalAirportLong
														]
														,
														{icon: arrivalIcon}
													).addTo(map)
													.bindPopup(
														"<b>Code</b>: " + arrivalAirportCode + "<br>" +
														"<b>City</b>: " + arrivalAirportCity + "<br>" +
														"<b>Airport Name</b>: " + arrivalAirportName + "<br>" +
														
														"<br><b>Latitude</b>: " + arrivalAirportLat + " / " + degToDms(arrivalAirportLat) + "<br>" +
														"<b>longitude</b>: " + arrivalAirportLong + " / " + degToDms(arrivalAirportLong, true) + "<br>" +
														"<b>Elevation</b>: " + arrivalAirportElevation + "ft / " + Math.round(convertFeetToMeters(arrivalAirportElevation)) + "m<br>" +
						
														"<br>This the <u>ARRIVAL</u> airport."
													);
											}
						
											
						
						
											// Trajectory
						
											var trajectory = <?php echo $trajectory; ?>;
						
											function onEachTrajectoryFeature(feature, layer) {
												// console.log(onEachTrajectoryFeature);
												// does this feature have a property named popupContent?
												if (feature.properties && feature.properties.services) {
													var htmlCode = "<table>";
						
													var services = feature.properties.services;
													var previousServiceStatus= "";
													for (var i = 0; i < services.length; i++) {
														var service = services[i];
														// console.log(service.service + " - " + service.status);
														var serviceName = service.service;
														var serviceStatus = service.status;
						
														var fontColor;
						
														start = service.start;
														end = service.end;
															
														rootCause = "";
														if(serviceStatus == "ON") {
															fontColor = "#6BB658";
															time = "<tr><td><b>Time:</b></td><td>" + start + "</td></tr>";
															connectivityLink = "";
														} else if(serviceStatus == "OFF") {
															fontColor = "#BE3E14";
															rootCause = "<tr><td><b>Root Cause:</b></td><td><span style='color:#BE3E14'>" + service.rootCause  + "</span></td></tr>";									
															duration = service.duration;
															time = "<tr><td><b>Start:</b></td><td>" + start + "</td></tr><tr><td><b>End:</b></td><td>" + end + "</td></tr><tr><td><b>Total:</b></td><td>" + duration + "</td>";
															connectivityLink = "<tr><td colspan='2'><a class='serviceLink' href='connectivityActivityView.php?aircraftId=<?php echo $aircraftId; ?>&flightLegs=<?php echo $flightLegs; ?>&start=" + start + "&end=" + end +  "' target='_blank'>>> See logs</a></td>"
														} else {
															fontColor = "Grey";
															time = "<tr><td><b>Time:</b></td><td>" + start + "</td></tr>";
															connectivityLink = "";
														}
														
														// Pure cosmetic code
														if(htmlCode != '' && previousServiceStatus == "OFF") {
															htmlCode += "<tr><td colspan='2'><hr></td></tr>";
														}
														
														
														htmlCode += "<tr><td colspan='2' style=\"color:" + fontColor + "\"><img src=\"../img/"+ serviceName + "_" + serviceStatus + ".png\" style='vertical-align:bottom'/>&nbsp;&nbsp;&nbsp;<b>" + serviceName + " is " + serviceStatus + "</b></td></tr>" + rootCause + time + connectivityLink;
														
														previousServiceStatus = serviceStatus;
													}
						
													htmlCode += "</table>";
						
													layer.bindPopup(htmlCode);
												}
											}
						
						
											var trajectoryShadowLayerStyle = {
												"color": "#202020",
												"weight": 5,
												"opacity": 0.75
											};
						
											var trajectoryShadowLayer = L.geoJson(trajectory, {
												style: trajectoryShadowLayerStyle,
												onEachFeature: onEachTrajectoryFeature
											}).addTo(map);
						
						
											var trajectoryLayer = L.geoJson(trajectory, {
												style: function(feature) {
														if (feature.properties) {
															if(feature.properties.services) {
																var services = feature.properties.services;
																var totalServices = services.length;
																var servicesDisabledCount = 0;
																var servicesOnCount = 0;
																var servicesOffCount = 0;
						
																for (var i = 0; i < services.length; i++) {
																	var service = services[i];
																	var serviceStatus = service.status;
						
																	switch(serviceStatus) {
																		case 'DISABLED':
																			servicesDisabledCount++;
																			break;
																		case 'ON':
																			servicesOnCount++;
																			break;
																		case 'OFF':
																			servicesOffCount++;
																			break;
																		default:
																			break;
																	}
																}
						
																if(servicesOnCount == totalServices) {
																	return {color: "LawnGreen", opacity: "0.95", weight:3};
																} else if (servicesOffCount == totalServices) {
																	return {color: "Red", opacity: "0.95", weight:3};
																} else if (servicesOffCount == totalServices / 2) {
																	return {color: "DarkOrange", opacity: "0.95", weight:3};
																} else {
																	return {color: "#FEFEFE", opacity: "0.95", weight:3};
																}
															}
														}
												},
												onEachFeature: onEachTrajectoryFeature
											}).addTo(map);
						
											
											// Event objects
											var eventServiceOn = L.icon({
												iconUrl: '../img/service_on.png',
												iconAnchor: [8,8],
												popupAnchor:  [2, -8]
											});
											
											var eventServiceOff = L.icon({
												iconUrl: '../img/service_off.png',
												iconAnchor: [8,8],
												popupAnchor:  [2, -8]
											});
						
											var eventsLayer = new L.featureGroup();
											
											eventsLayer.addTo(map);
											
											
											function onEachAreaFeature(feature, layer) {
												if (feature.properties) {
													layer.bindPopup("<br><b>Country:</b> " + feature.properties.name + "<br><br><b>Service:</b> " + feature.properties.service +  "<br><b>Status:</b> " + feature.properties.status);
												}
											}
						
											// OMTS Authorized Areas
											var omtsAuthorizedAreaStyle = {
												"color": "#33CC33",
												"weight": 1,
												"opacity": 0.75
											};
						
											var omtsAuthorizedAreaLayer = L.geoJson(omtsAuthorizedArea, {
													style: omtsAuthorizedAreaStyle,
													onEachFeature: onEachAreaFeature
												}
												).addTo(map);
						
											// OMTS Restricted Areas
											var omtsRestricatedAreaStyle = {
												"color": "red",
												"weight": 1,
												"opacity": 0.75
											};
						
											var omtsRestrictedAreaLayer = L.geoJson( omtsRestrictedArea, {
													style: omtsRestricatedAreaStyle,
													onEachFeature: onEachAreaFeature
												}
											).addTo(map);
						
											// Wifi Authorized Areas
											var wifiAuthorizedAreaStyle = {
												"color": "#33CC33",
												"weight": 1,
												"opacity": 0.75
											};
						
											var wifiAuthorizedAreaLayer = L.geoJson(wifiAuthorizedArea, {
													style: wifiAuthorizedAreaStyle,
													onEachFeature: onEachAreaFeature
												}
												).addTo(map);
						
											// Wifi Restricted Areas
											var wifiRestricatedAreaStyle = {
												"color": "red",
												"weight": 1,
												"opacity": 0.75
											};
						
											var wifiRestrictedAreaLayer = L.geoJson( wifiRestrictedArea, {
													style: wifiRestricatedAreaStyle,
													onEachFeature: onEachAreaFeature
												}
											).addTo(map);
						
											// Create layers controls
						
											// http://stackoverflow.com/questions/19545422/leaflet-geojason-layer-control-not-work-in-this-script
											var overlayMaps = {
												"&nbsp;<img src=\"../img/trajectory.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">Trajectory</span>": trajectoryLayer,
												"&nbsp;<img src=\"../img/warning.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">Connectivity Events</span>": eventsLayer,
												"&nbsp;<img src=\"../img/OMTS_ON.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">OMTS Auth. Area</span>": omtsAuthorizedAreaLayer,
												"&nbsp;<img src=\"../img/OMTS_OFF.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">OMTS Rest. Area</span>": omtsRestrictedAreaLayer,
												"&nbsp;<img src=\"../img/WIFI_ON.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">WIFI Auth. Area</span>": wifiAuthorizedAreaLayer,
												"&nbsp;<img src=\"../img/WIFI_OFF.png\" style=\"vertical-align: bottom;\">&nbsp;&nbsp;<span style=\"color: #777\">WIFI Rest. Area</span>": wifiRestrictedAreaLayer						
											};
						
											L.control.layers(null, overlayMaps, {position: 'topleft'}).addTo(map);
						
											// Bring trajectory layer and events layer to the top as the areas layer have been added after.
											trajectoryLayer.bringToFront();
											eventsLayer.bringToFront();
						
											// Way to keep the layers ordered (especially to keep trajectory on top of all other layers)
											// We have to do the following otherwise there is an error when calling bringToFront() on layer which is currently not displayed
											map.on('overlayadd',function(layer){ 
												if(map.hasLayer(omtsAuthorizedAreaLayer)) {
													omtsAuthorizedAreaLayer.bringToFront();
												}
												if(map.hasLayer(omtsRestrictedAreaLayer)) {
													omtsRestrictedAreaLayer.bringToFront();
												}
												if(map.hasLayer(wifiAuthorizedAreaLayer)) {
													wifiAuthorizedAreaLayer.bringToFront();
												}
												if(map.hasLayer(wifiRestrictedAreaLayer)) {
													wifiRestrictedAreaLayer.bringToFront();
												}
												if(map.hasLayer(trajectoryLayer)) {
													// Show the shadow when trajectory is added to display
													map.addLayer(trajectoryShadowLayer);
													trajectoryShadowLayer.bringToFront();
													trajectoryLayer.bringToFront();
												}
												if(map.hasLayer(eventsLayer)) {
													//eventsLayer.bringToFront();
												}
											});
						
											map.on('overlayremove',function(layer){ 
												if(layer.name.indexOf("Trajectory") > 0) {
													// Hide the shadow when trajectory is removed from display
													map.removeLayer(trajectoryShadowLayer);
												}
											});
						
											// Utilities
											function convertFeetToMeters(feetValue) {
												return 0.3048 * feetValue;
											}
						
											function convertMetersToFeet(metersValue) {
												return 3.2808 * metersValue;
											}
											
											function degToDms (deg, lng) {
												var values = {
													dir : deg<0?lng?'W':'S':lng?'E':'N',
													deg : 0|(deg<0?deg=-deg:deg),
													min : 0|deg%1*60,
													sec :(0|deg*60%1*6000)/100
												};
						
												return ("" + values['deg'] + "°" + values['min'] + "'" + values['sec'] + "\" " + values['dir']);
											}
										</script>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- END PAGE CONTENT WRAPPER -->
		</div>
		<!-- END PAGE CONTENT -->
	</div>
	<!-- END PAGE CONTAINER -->
	
	<!-- Logout page -->
	<?php include("../logout.php"); ?>
	<!-- END Logout page-->

	<audio id="audio-alert" src="../audio/alert.mp3" preload="auto"></audio>
	<audio id="audio-fail" src="../audio/fail.mp3" preload="auto"></audio>	
	<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
	<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>	
	<script type="text/javascript" src="../js/plugins.js"></script>
	<script type="text/javascript" src="../js/actions.js"></script>
</body>

<script src="../js/FileSaver.min.js"></script>
<script src="../js/canvas-toBlob.js"></script>
<script>
$(document).ready(function(){
	$('#connectivityDataTable').bootstrapTable({			
		exportOptions: {
			fileName: 'ConnectivityLogs'
		}});
	// To remove the white space below the page content
	page_content_onresize();
	$('#mapView').hide();
	$('#incfont').click(function(){    
        increaseFont();
    });  
	
	$('#decfont').click(function(){    
        decreaseFont();
    });
	
	function increaseFont() {
		curSize= parseInt($('#connectivityDataTable').css('font-size')) + 2;
		if(curSize<=20) {
			$('#connectivityDataTable').css('font-size', curSize);
			$('.header').css('font-size', curSize);
		}
	}
	
	function decreaseFont() {
		curSize= parseInt($('#connectivityDataTable').css('font-size')) - 2;
		if(curSize == 0) {
			curSize = 1;
		}
		if(curSize>=1) {
			$('#connectivityDataTable').css('font-size', curSize);
			$('.header').css('font-size', curSize);
		}
	}
	
		// Ctrl + scroll to zoom
	var isCtrl = false;

    function ctrlCheck(e) {
        if (e.which === 17) {
            isCtrl = e.type === 'keydown' ? true : false;
        }
    }

    function wheelCheck(e, delta) {
        // `delta` will be the distance that the page would have scrolled;
        // might be useful for increasing the SVG size, might not
        if (isCtrl) {
            e.preventDefault();
			console.log("ctrl + scroll");
			console.log(e);
			if(e.deltaY > 0) {
				increaseFont();
			} else if (e.deltaY < 0) {
				decreaseFont();
			}            
        }
    }


});

$('#connectivityDataTable').bootstrapTable({
    onLoadSuccess: function() {
    	// To remove the white space below the page content
    	page_content_onresize();
    }    
 });
</script>

<script>

$(document).ready(function(){	
	var startTime= "<?php echo "$start";?>";
	var endTime= "<?php echo "$end";?>"; 
	var flightLegs= "<?php echo "$flightLegs";?>";
	 
    console.log('loading connectivity');
    if(flightLegs!=""){
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
		    connectivityTimeline: true
		};


		console.log('loading connectivity');
		$.ajax({
		    type: "GET",
		    dataType: "json",
		    url: "../ajax/getAircraftTimeLineData.php",
		    data: data,
		    success: function(data) {
		        console.log(data);
		       // createTimeline(data, 'connectivityTimeline', 'loadingConnectivityTimeline');
		    },
		    error: function (err) {
		        console.log('Error', err);
		    }
		});
    }    
	
	dataForAvailability = {
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
			connectivityTimeline: true,
			startTime:startTime,
			endTime:endTime
		};
		
		console.log(dataForAvailability);
		page_content_onresize();
		setInterval(function(){ 
			page_content_onresize();
		}, 1000);
		//$('#connectivityDataTable').bootstrapTable('load', page_content_onresize());
		$.ajax({
        type: "GET",
        dataType: "json",
        url: "../ajax/getConnectivityAvailability.php",
        data: dataForAvailability,
        success: function(data) {
			console.log(data);
           // updateChart(data,'NoDataWifiAvailable','NoDataOmtsAvailable');
			page_content_onresize();
        },
        error: function (err) {
            console.log('Error', err);
        }
    });
	
});

function updateChart(data,noDataWifiAvailable,noDataOmtsAvailable){
		if (data.WifiOnAvailability != null && data.WifiOnAvailability != -1)
		{
			$('#'+noDataWifiAvailable).hide();
			new Chartist.Pie('.ct-chart', {
				  series: [data.WifiOnAvailability, data.WifiOffAvailability]
				}, {
				  donut: true,
				  donutWidth: 50,
				  startAngle: 270,
				  total: 200,
				  showLabel: true
				});	
		}
		else
		{			
			$("#"+noDataWifiAvailable).html("<em>No availability</em>");
		}
		
		if (data.OmtsOnAvailability != null && data.OmtsOnAvailability != -1)
		{
			$('#'+noDataOmtsAvailable).hide();
			new Chartist.Pie('.ct-chartOmtsOff', {
				  series: [data.OmtsOnAvailability, data.OmtsOffAvailability]
				}, {
				  donut: true,
				  donutWidth: 50,
				  startAngle: 270,
				  total: 200,
				  showLabel: true
				});	
		}
		else
		{
			$("#"+noDataOmtsAvailable).html("<em>No availability</em>");
		}
		
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
	
	//Root Cause Manual Entry
	timeline.on('contextmenu', function (props) {
	
		for(i in props){
			if(i == 'item'){
				//alert(i + "=" +props[i]); // debuging usage
				if(props[i] != ""){
					if(props[i].length > 0) {
						var event = props[i];
						var res = event.split("/")
						if(res[0] == "CON") {
							var connectivityObjectType = res[1]; 
							var aircraftId = <?php echo $aircraftId; ?>;
							var start = res[2];
							var end = res[3];
							var rootCauseManualEntry = window.prompt("Enter The manual Root Cause for the Failure");	
							data = {
								rootCause : rootCauseManualEntry,
								rootCauseStartTime : start,
								rootCauseEndTime	: end,
								rootCauseDataType : connectivityObjectType,
								rootCauseUpdated: true
							};
							
							$.ajax({
								type: "GET",
								dataType: "json",
								url: "../ajax/writeDataOnToDatabase.php",
								data: data,
								success: function(data) {
									console.log(data.rootCauseUpdated);
									location.reload();
								},
								error: function (err) {
									console.log('Error', err);
								}
							});
						}			
					}	
				}					
			}
		}
		props.event.preventDefault();
    });

	//view connectivity activity view page with mondo db logs for the event
    timeline.on('select', function (properties) {
		if(properties.items.length > 0) {
			var event = properties.items[0];
			var res = event.split("/")
			if(res[0] == "CON") {
				//var connectivityObjectType = res[1]; // not used
				var aircraftId = <?php echo $aircraftId; ?>;
				var start = res[2];
				var end = res[3];
		
				var url = "connectivityActivityView.php?aircraftId=" + aircraftId + "&start=" + start + "&end=" + end + "&flightLegs=<?php echo $flightLegs ?>";	
				var win = window.open(url, '_blank');
				win.focus();
			}			
		}		
		
		
        
    });

}

</script>
<script type="text/javascript">
$('#connectivityDataTable').bootstrapTable({
    onLoadSuccess: function() {
    	// To remove the white space below the page content
    	page_content_onresize();
    }    
 });
	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var target = $(e.target).attr("href") // activated tab        	
	 	if(target=='#logs'){			
			$('#mapView').hide();
			// To remove the white space below the page content
			page_content_onresize();
		}else if(target=='#maps'){			
			$('#mapView').show();
			// To remove the white space below the page content
			page_content_onresize();
		}
	});
	$('#connectivityDataTable').bootstrapTable({   
		onAll: function (name, args) {
            console.log('Event: onAll, data: ', args);
            page_content_onresize();
        },     
		onLoadSuccess: function (name, args) {
            console.log('Event: onLoadSucess, data: ', args);
            page_content_onresize();
        }        
    }).on('all.bs.table', function (e, name, args) {
        console.log('Event:', name, ', data:', args);
        page_content_onresize();
    })
    .on('load-success.bs.table', function (e, name, args) {
        console.log('onLoadSucess:', name, ', data:', args);
        page_content_onresize();
    });
</script>
</html>






