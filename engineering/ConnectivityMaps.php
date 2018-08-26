<!DOCTYPE html>
<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/checkPermission.php";
require_once "../database/connecti_mongoDB.php";
require_once("../map/airports.php");

require_once("checkEngineeringPermission.php");

$aircraftId = $_REQUEST['aircraftId'];
$flightLegs = $_REQUEST['flightLegs'];


if(isset($aircraftId)) {
    checkAircraftPermission($dbConnection, $aircraftId);
}

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

// STO working for only one flight leg... Need to see how to do it for multiple flight legs...
$query = "SELECT departureAirportCode, arrivalAirportCode, createDate, lastUpdate FROM $dbName.SYS_flight WHERE idFlightLeg IN ($flightLegs) LIMIT 1";
$result = mysqli_query($dbConnection, $query);
if($result) {
	$row = mysqli_fetch_array ( $result );
	$start = $row['createDate'];
	$end = $row['lastUpdate'];
	$departureAirportCode = $row['departureAirportCode'];
	$arrivalAirportCode = $row['arrivalAirportCode'];
	
	$collection = $db->connectivityEvents;
	$flightCursor = $collection->find(
		array("startTime" => array('$gte' => $start), "endTime" => array('$lte' => $end), "tailSign" => $aircraftTailsign),
		array("wifiAvailabilityEvents" => 1, "omtsAvailabilityEvents" =>1)
		);
	
	$wifiOffEvents = array();
	$omtsOffEvents = array();
	
	if($flightCursor->count() > 0) {
		$flight = $flightCursor->getNext(); // only one object to retrieve
	
		$wifiAvailabilityEvents = $flight['wifiAvailabilityEvents'];		
		foreach($wifiAvailabilityEvents as $event) {
			//var_dump($event) . "<br><br><br>";
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
		//var_dump($wifiOffEvents); exit;
		
		$omtsAvailabilityEvents = $flight['omtsAvailabilityEvents'];		
		foreach($omtsAvailabilityEvents as $event) {
			//var_dump($event) . "<br><br><br>";
			if($event['description'] == "OMTS-OFF") {
				$omtsOffEvents[] = array(
					'service' => "OMTS",
					'start' => $event['startTime'],
					'end' => $event['endTime'],
					'startLatLong' => $event['location']['coordinates'][0],
					'endLatLong' => $event['location']['coordinates'][1],
					'rootCause' => $event['computedFailure']
				);
			}
		}
		//var_dump($omtsOffEvents); exit;
		
		
		////////// ADD TAILSIGN IN QUERY !!!!!!!!!!!!!!!!!!!!!!!!
		$collection = $db->connectivityActivity;
		$activities = $collection->find(
			array("timestamp" => array('$gte' => $start, '$lte' => $end), "TGS_FLIGHT.flightPhase" => array('$ne' => "unknown"), "tailSign" => $aircraftTailsign),
			array("timestamp" => 1, "TGS_FLIGHT" => 1)
			)->sort(array("timestamp" => 1));

		$trajectory = array();
			
		$i = 0;
		foreach($activities as $activity) {
			// Debug
			$flightPhase = $activity['TGS_FLIGHT']['flightPhase'];
			if($flightPhase == "unknown" || $flightPhase == "ko") {
				echo "Flight phase should not be retrieved => $flightPhase"; exit;
			}
			
			if($i == 0) {
				// Get the first coordinates which will help building the lines
				$previousLatitude = $activity['TGS_FLIGHT']['latitude'];
				$previousLongitude = $activity['TGS_FLIGHT']['longitude'];				
				
				// Used to set the view of the map if we don't have departure/arrival airports
				$firstLatitude = $previousLatitude;
				$firstLongitude = $previousLongitude;
				
				// Get departure and arrival airports from connectivity if we don't have them from BITE data (i.e. the values are empty or contains numeric values such as SVA...)				
				if( ($departureAirportCode == '') || ($arrivalAirportCode == '') || preg_match('#[0-9]#',$departureAirportCode) || preg_match('#[0-9]#',$arrivalAirportCode) ) {
					$cityPair = $activity['TGS_FLIGHT']['cityPair'];
					$cityPairArray = explode("-", $cityPair);
					
					$departureAirportCode = $cityPairArray[0];
					$arrivalAirportCode = $cityPairArray[1];					
				}
				
				$i++;
				continue;
			}
			
			$timestamp = $activity['timestamp'];
			
			$latitude = $activity['TGS_FLIGHT']['latitude'];
			$longitude = $activity['TGS_FLIGHT']['longitude'];
			
			//echo "$latitude / $longitude<br><br>";
			
			$altitude = $activity['TGS_FLIGHT']['altitude'];

			if($altitude < 10000) {
				$omtsStatus = "DISABLED";
				$wifiStatus = "DISABLED";
			} else {
				
				$omtsStatus = "ON";
				$wifiStatus = "ON";
				
				$omtsStartOff = $timestamp;
				$omtsEndOff = $timestamp;
				$wifiStartOff = $timestamp;
				$wifiEndOff = $timestamp;
				
				// The following 2 loops are not really bringing good performances... Need to find a better way
				// These 2 loops are used to find the events corresponding to the timestamp we are looking at.
				foreach($omtsOffEvents as $omtsOffEvent) {
					if( ($timestamp >= $omtsOffEvent['start']) && ($timestamp <= $omtsOffEvent['end']) ) {
						$omtsStatus = "OFF";
						$omtsRootCause = $omtsOffEvent['rootCause'];
						$omtsStartOff = $omtsOffEvent['start'];
						$omtsEndOff = $omtsOffEvent['end'];
						$omtsDurationOff = dateDifference($omtsStartOff, $omtsEndOff, '%hh %I\' %S\'\'');
						break;
					}
				}
				
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
							array("service" => "OMTS", "status" => $omtsStatus, "rootCause" => $omtsRootCause, "start" => $omtsStartOff, "end" => $omtsEndOff, "duration" => $omtsDurationOff),
							array("service" => "WIFI", "status" => $wifiStatus, "rootCause" => $wifiRootCause, "start" => $wifiStartOff, "end" => $wifiEndOff, "duration" => $wifiDurationOff)
						)
					),
					"geometry" => array(
						"type" => "LineString", 
						"coordinates" => array(
							//array($previousLatitude,$previousLongitude),
							//array($latitude,$longitude)
							array($previousLongitude, $previousLatitude),
							array($longitude, $latitude)
						)
					)
				);
			
			$previousLatitude = $latitude;
			$previousLongitude = $longitude;
		}
	} else {
		$displayNoDataAlert = true;
	}
	
	// Used to set the view of the map if we don't have departure/arrival airports
	$lastLatitude = $previousLatitude;
	$lastLongitude = $previousLongitude;
	
	//var_dump($trajectory); exit;
} else {
	echo "Error with query: $query"; exit;
}

//echo "$departureAirportCode / $arrivalAirportCode"; exit;

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

// echo "$departureAirportLat / $departureAirportLong<br>";
// echo "$arrivalAirportLat / $arrivalAirportLong";

if($departureAirportLong >= 90 && $arrivalAirportLong <= -20) {
	$arrivalAirportLong += 360;
} else if($departureAirportLong <= -20 && $arrivalAirportLong >= 90) {
	$departureAirportLong += 360;

}

$trajectory = json_encode($trajectory);


?>
<html lang="en" >
<head>
<!-- META SECTION -->
<link rel="shortcut icon" href="../img/globe-icon.ico">
<title>BITE Analytics</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/theme-white.css" />
<link rel="stylesheet" type="text/css" id="theme" href="../css/app.css" />
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
<script src="../js/bootstrap-multiselect.js"></script>
<script src="../js/bootstrap-table.js"></script>
<link href="../css/bootstrap-table.css" rel="stylesheet" />
<script src="../js/Chart.HeatMap.S.js"></script>
<script src="../js/tableExport.js"></script>
<script src="../js/bootstrap-table-export.js"></script>
<link rel="stylesheet" href="../css/dataTables/datatables.min.css">	
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
</style>
<body >
	<!-- START PAGE CONTAINER -->
	<div class="page-container">
		<!-- START PAGE SIDEBAR -->
        <?php include("SideNavBar.php"); ?>
        <!-- END PAGE SIDEBAR -->
		<!-- PAGE CONTENT -->
		<div id="here" class="page-content" style="height: 100% !important;">			
			<ul class="x-navigation x-navigation-horizontal x-navigation-panel">				
				<li class="xn-icon-button"><a href="#" class="x-navigation-minimize"><span
						class="fa fa-dedent"></span></a></li>			
				<li class="xn-icon-button pull-right"><a href="#" class="mb-control"
					data-box="#mb-signout"><span class="fa fa-sign-out"></span></a></li>				
			</ul>
			<ul class="breadcrumb">
				<li><a href="#">Home</a></li>
				<li class="active">LOPA</li>
			</ul>
			<div class="page-title" style="padding-right: 12px;">
				<h2>					
					LOPA
				</h2>				
			</div>
			<!-- END PAGE TITLE -->
			<!-- PAGE CONTENT WRAPPER -->
			<div class="page-content-wrap">
				<div class="row">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-body">
								<?php
					if($displayNoDataAlert) {
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
					console.log("firstLat: " + firstLat);
					console.log("firstLong: " + firstLong);
					console.log("lastLat: " + lastLat);
					console.log("lastLong: " + lastLong);
										
					var map = L.map('map').fitBounds(
												[
													[firstLat, firstLong],
													[lastLat, lastLong]
												],
												{padding: [20,20]}
											);
					console.log('map created');
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

					console.log('before tragectory style');
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

					console.log('after tragectory creation');
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
					
					<?php
						createMarkers($wifiOffEvents, $aircraftId, $flightLegs);
						createMarkers($omtsOffEvents, $aircraftId, $flightLegs);
						
						function createMarkers($events, $aircraftId, $flightLegs) {
							foreach($events as $event) {
								//var_dump($event); exit;
								$service = $event['service'];							
								
								// Create the start marker
								$start = $event['start'];
								$end = $event['end'];
								$rootCause = $event['rootCause'];
								$latitude = $event['startLatLong'][0];
								$longitude = $event['startLatLong'][1];
								
								echo "var marker =
									L.marker(
										[$longitude,$latitude],
										{icon: eventServiceOff}
									)									
									.bindPopup(
										\"<br><img src='../img/" . $service . "_OFF.png' style='vertical-align:bottom'/>&nbsp;&nbsp;<span style='color:#BE3E14'><b>$service is OFF</b></span><br><br><b>Time:</b> $start<br><br><b>Root Cause:</b> <span style='color:#BE3E14'>$rootCause</span><br><br><a class='serviceLink' href='connectivityActivityView.php?aircraftId=$aircraftId&flightLegs=$flightLegs&start=$start&end=$end' target='_blank'>>> See logs</a>\"
									);\n";
								
								echo "eventsLayer.addLayer(marker);";
								
								// Create the end marker
								$latitude = $event['endLatLong'][0];
								$longitude = $event['endLatLong'][1];
								
								echo "var marker =
									L.marker(
										[$longitude,$latitude],
										{icon: eventServiceOn}
									)									
									.bindPopup(
										\"<br><img src='../img/" . $service . "_ON.png' style='vertical-align:bottom'/>&nbsp;&nbsp;<span style='color:#6BB658'><b>$service is ON</b></span><br><br><b>Time:</b> $end\"
									);\n";
									
								echo "eventsLayer.addLayer(marker);";
							}			
						}
					?>					
					
					eventsLayer.addTo(map);
					
					console.log('here 1');
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
					console.log('here 2');
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
					console.log('here 3');
					// Bring trajectory layer and events layer to the top as the areas layer have been added after.
					trajectoryLayer.bringToFront();
					eventsLayer.bringToFront();
					console.log('here 4');
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

					console.log('here 5');


					// Popup to give coordinates
				    /*  	
					var popup = L.popup();

					function onMapClick(e) {
						popup
							.setLatLng(e.latlng)
							.setContent("You clicked the map at " + e.latlng.toString())
							.openOn(map);
					}

					map.on('click', onMapClick);
					*/


					// Utilities
					function convertFeetToMeters(feetValue) {
						return 0.3048 * feetValue;
					}

					function convertMetersToFeet(metersValue) {
						return 3.2808 * metersValue;
					}

					// http://stackoverflow.com/questions/5786025/decimal-degrees-to-degrees-minutes-and-seconds-in-javascript
					function degToDms (deg, lng) {
						var values = {
							dir : deg<0?lng?'W':'S':lng?'E':'N',
							deg : 0|(deg<0?deg=-deg:deg),
							min : 0|deg%1*60,
							sec :(0|deg*60%1*6000)/100
						};

						return ("" + values['deg'] + "°" + values['min'] + "'" + values['sec'] + "\" " + values['dir']);
					}
					console.log('final');
				</script>
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

<script type='text/javascript'	src='../js/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js'></script>
<script type='text/javascript' src='../js/plugins/jvectormap/jquery-jvectormap-world-mill-en.js'></script>
<script type='text/javascript' src='../js/plugins/jvectormap/jquery-jvectormap-europe-mill-en.js'></script>
<script type='text/javascript' src='../js/plugins/jvectormap/jquery-jvectormap-us-aea-en.js'></script>
<script type='text/javascript' src='../js/plugins/icheck/icheck.min.js'></script>
<script type="text/javascript" src="../js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>
<script type="text/javascript" src="../js/plugins.js"></script>
<script type="text/javascript" src="../js/actions.js"></script>
	
</body>
</html>
