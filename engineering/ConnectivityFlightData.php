<?php 

session_start ();
$menu = 'ConnectivityFlightData';
require_once "../database/connecti_database.php";
include ("checkEngineeringPermission.php");
require_once "../database/connecti_mongoDB.php";
require_once "../common/functions.php";
require_once("../map/airports.php");

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
$start = $_REQUEST['start'];
$end = $_REQUEST['end'];
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


if($aircraftIsp!="KaNoVAR"){
	//SB:Get data for logsView
	require_once "../database/connecti_mongoDB.php";
	$cursorHeader = $collectionActivity->find(array("tailSign" => $aircraftTailsign, 'timestamp' => array('$gt' => $start, '$lte' => $end)))->limit(1);
	$cursorBody = $collectionActivity->find(array("tailSign" => $aircraftTailsign, 'timestamp' => array('$gt' => $start, '$lte' => $end)));
	
	if($cursorBody->count() == 0) {
		$displayNoDataAlert = true;
	}
	
	
	//data with dates only for altitude graph
		
		$connectivityActivity = $db->connectivityActivity;
		$where=array('$and' => array(	array("timestamp" => array('$gte' => $start, '$lte' => $end)),
										array("tailSign" => $aircraftTailsign)));
										
		$cursor = $connectivityActivity->find(
			$where,
			array("timestamp" => 1, "TGS_FLIGHT.flightPhase" => 1, "TGS_FLIGHT.altitude" => 1)
		)->sort(array("timestamp" => 1));
		
		//Smita
		$cursor->timeout(-1);
		
		$data = array();
		if($cursor->count() > 0) {		
			foreach($cursor as $document) {
				$flightPhase = $document['TGS_FLIGHT']['flightPhase'];
				
				switch($flightPhase) {
					case "preflight":
					case "postflightground";
						$flightPhase = "Ground";
						break;
					case "taxiout";
					case "taxiin";
						$flightPhase = "Taxi";
						break;
					case "climb":
					case "descentapproach":
						$flightPhase = "Climb/Descent";
						break;
					case "cruise":
						$flightPhase = "Cruise";
						break;
					default:
						$flightPhase = "";
				}		
				
				$newData = array(
					'time' => $document['timestamp'],
					'flightPhase' => $flightPhase,
					'altitude' => $document['TGS_FLIGHT']['altitude']
				);		
				
				//var_dump($newData); echo "<br><br>";
				$data[]	= $newData;				
			}
			//var_dump($data); exit;
		} else {
			$displayNoDataAlert = true;
		}
	
	
	
	//get the wifi and Omts availability data
	function getWifiOmtsAvailabilityData($dbConnection,$collection,$start,$end, $aircraftTailsign)
	{
		$totalWifiOmtsAvailbilityPercentage = array();
		
		$where=array('$and' => array(	array("startTime" => array('$eq' => $start)),array("endTime" => array('$eq' => $end)),
										array("tailSign" => $aircraftTailsign)));
	
		
		$fields = array('wifiAvailability.totalOnPercentage',
						'wifiAvailability.manualPercentageOn',
						'omtsAvailability.totalOnPercentage',
						'omtsAvailability.manualPercentageOn',					
						'startTime',
						'endTime');
		
		$cursor = $collection->find($where,$fields);
		//Smita
		$cursor->timeout(-1);
	
		foreach ($cursor as $doc) 
		{
		
			if(($doc['wifiAvailability']['totalOnPercentage'] !== NULL))
			{
				$totalWifiOmtsAvailbilityPercentage['computedWifiPercentage'] = round($doc['wifiAvailability']['totalOnPercentage'],2);
			}
			if(($doc['wifiAvailability']['manualPercentageOn'] != NULL))
			{
				$totalWifiOmtsAvailbilityPercentage['overriddenWifiPercentage'] = round($doc['wifiAvailability']['manualPercentageOn'],2);
			}		
			
			if(($doc['omtsAvailability']['totalOnPercentage'] !== NULL))
			{
				$totalWifiOmtsAvailbilityPercentage['computedOmtsPercentage'] = round($doc['omtsAvailability']['totalOnPercentage'],2);
			}
			if($doc['omtsAvailability']['manualPercentageOn'] != NULL )
			{
				$totalWifiOmtsAvailbilityPercentage['overriddenOmtsPercentage'] = round($doc['omtsAvailability']['manualPercentageOn'],2);
			}		
	
		}	
	
	 return $totalWifiOmtsAvailbilityPercentage;		
	}
	
	//get the Root cause Wifi for the Flight leg
	function getWifiRootCauseForFlightLeg($dbConnection,$collection,$start,$end, $aircraftTailsign)
	{
		$where=array('$and' => array(	array("startTime" => array('$eq' => $start)),array("endTime" => array('$eq' => $end)),
										array("tailSign" => $aircraftTailsign)));
	
		$cursor = $collection->find($where);
	
		//Smita
		$cursor->timeout(-1);
		
		foreach ($cursor as $doc) 
		{
			if(is_array($doc['wifiAvailabilityEvents']) && count($doc['wifiAvailabilityEvents']) > 0)
					{
						foreach($doc['wifiAvailabilityEvents']  as $temp)
						{	
							if($temp['computedFailure'] != ''){
								if($temp['manualFailureEntry'] != ''){
									$rootCauseStringWifi = $rootCauseStringWifi . $temp['manualFailureEntry'] . "\n";
								}else{
									$rootCauseStringWifi = $rootCauseStringWifi . $temp['computedFailure'] . "\n";
								}
								
							}
							if($temp['computedFailure'] == ''){
								if($temp['manualFailureEntry'] != ''){
									$rootCauseStringWifi = $rootCauseStringWifi . $temp['manualFailureEntry'] . "\n";
								}
							}
						}
	
					}
					
		}	
		
		return $rootCauseStringWifi;	
	}
	
	//get the Root Cause OMTS for the Flight leg
	function getOmtsRootCauseForFlightLeg($dbConnection,$collection,$start,$end, $aircraftTailsign)
	{
							
		$where=array('$and' => array(	array("startTime" => array('$eq' => $start)),array("endTime" => array('$eq' => $end)),
										array("tailSign" => $aircraftTailsign)));
	
		$cursor = $collection->find($where);
	//Smita
		$cursor->timeout(-1);
		foreach ($cursor as $doc) 
		{		
	
			if(is_array($doc['omtsAvailabilityEvents']) && count($doc['omtsAvailabilityEvents']) > 0)
					{
					
						foreach($doc['omtsAvailabilityEvents']  as $temp)
						{	
						
							if($temp['computedFailure'] != ''){		
	
								if($temp['manualFailureEntry'] != ''){
									$rootCauseStringOmts = $rootCauseStringOmts . $temp['manualFailureEntry'] . "\n";
								}else{
									$rootCauseStringOmts = $rootCauseStringOmts . $temp['computedFailure'] . "\n";
								}	
	
							}
							if($temp['computedFailure'] == ''){		
								if($temp['manualFailureEntry'] != ''){
									$rootCauseStringOmts = $rootCauseStringOmts . $temp['manualFailureEntry'] . "\n";
								}
										
							}	
										
						}
					}							
		}	
		
		return $rootCauseStringOmts;	
	}
	
	//$flightLegsArray = getFlightInArray($flightLegs);
	
	//remove duplicates in wifi RC
	$rootCauseStringWifi = getWifiRootCauseForFlightLeg($dbConnection,$collection,$start,$end, $aircraftTailsign);
	$rootCauseStringWifiArray = explode("\n",$rootCauseStringWifi);
	$rootCauseStringWifiUnique = array_unique($rootCauseStringWifiArray);
	// Remove restricted area fault from list
	$index = array_search('Restricted Area ',$rootCauseStringWifiUnique);
	if($index !== FALSE){
	    unset($rootCauseStringWifiUnique[$index]);
	}
	
	//remove duplicates in omts RC
	$rootCauseStringOmts = getOmtsRootCauseForFlightLeg($dbConnection,$collection,$start,$end, $aircraftTailsign);
	$rootCauseStringOmtsArray = explode("\n",$rootCauseStringOmts);
	$rootCauseStringOmtsUnique = array_unique($rootCauseStringOmtsArray);
	// Remove restricted area fault from list
	$index = array_search('Restricted Area ',$rootCauseStringOmtsUnique);
	if($index !== FALSE){
	    unset($rootCauseStringOmtsUnique[$index]);
	}
	
	function str_replace_json($search, $replace, $subject) 
	{
	    return json_decode(str_replace($search, $replace, json_encode($subject)), true);
	}
	
	$rootCauseStringOmtsUnique = str_replace_json('Restricted Area ', '', $rootCauseStringOmtsUnique);
	//var_dump($rootCauseStringOmtsUnique);
	
	$computedPercentage = array();
	$computedPercentage = getWifiOmtsAvailabilityData($dbConnection,$collection,$start,$end, $aircraftTailsign);
	$flightLegsCount = count($flightLegsArray);
	
	if($flightLegs!="") {
		// STO working for only one flight leg... Need to see how to do it for multiple flight legs...
		$query1 = "SELECT departureAirportCode, arrivalAirportCode, createDate, lastUpdate FROM $dbName.SYS_flight WHERE idFlightLeg IN ($flightLegs) LIMIT 1";
		error_log("Query to get start and end date: " . $query1);
		$result1 = mysqli_query($dbConnection, $query1);
		if($result1) {
			$row = mysqli_fetch_array ( $result1 );
			$start = $row['createDate'];
			$end = $row['lastUpdate'];
			$departureAirportCode = $row['departureAirportCode'];
			$arrivalAirportCode = $row['arrivalAirportCode'];
			
			error_log("start date: " . $start);
			error_log("end date: " . $end);
			error_log("Tailsign: " . $aircraftTailsign);
			
			$collection = $db->connectivityEvents;
			$flightCursor = $collection->find(
				array("startTime" => array('$gte' => $start), "endTime" => array('$lte' => $end), "tailSign" => $aircraftTailsign),
				array("wifiAvailabilityEvents" => 1, "omtsAvailabilityEvents" =>1)
				);
			
			$wifiOffEvents = array();
			$omtsOffEvents = array();
			
			if($flightCursor->count() > 0) {error_log('inside if ###############');
				
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
			} else {			error_log('inside else ###############');
				$displayNoMapDataAlert = true;
				
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
			
	}
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
								<li role="presentation" class="active">
									<a href="#flightConnectivity" data-target='#flightConnectivity' aria-controls="flightConnectivity" role="tab" data-toggle="tab" style="font-family: 'Open Sans', sans-serif; font-size: 13px;">TimeLineData</a>
								</li>
								<li role="presentation" >
									<a href="#logs" aria-controls="logs" role="tab" data-toggle="tab" style="font-family: 'Open Sans', sans-serif; font-size: 13px;">Logs</a>
								</li>
								<?php 
									if($flightLegs!="") {
										echo "<li role=\"presentation\" >";
										echo "	<a href=\"#maps\" aria-controls=\"maps\" role=\"tab\" data-toggle=\"tab\" style=\"font-family: 'Open Sans', sans-serif; font-size: 13px;\">Maps</a>";
										echo "</li>";
									}
								?>											
							</ul>
							<!-- Tab panes -->
							<div class="tab-content">
								<div role="tabpanel" class="tab-pane active" id="flightConnectivity">
									<div class="row">
										<div class="col-md-12">
											<div class="panel panel-default">
												<div class="panel-body">
													<?php include("FlightConnectivityNew.php");?>
												</div>
											</div>
										</div>
									</div>
								</div>
								
								<div role="tabpanel" class="tab-pane" id="maps">
									
								</div>
										
								<div role="tabpanel" class="tab-pane" id="logs">						
									<div class="row">
										<div class="col-md-12">
											<div class="panel panel-default">
												<div class="panel-body text-center">
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
														if($aircraftIsp!="KaNoVAR"){										
															if($isDataAvailable){
																include("connectivityLogTable.php");
															}else{
																echo "<label class=\"noData-label\"> No data available for the selected duration or selected filters </label>";
															}
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
											if($flightLegs!="") {
												include("FlightConnectivityMap.php");
											}
										?>
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
</script>

<script>

$(document).ready(function(){
	$('#connectivityDataTable').bootstrapTable({			
		exportOptions: {
			fileName: 'ConnectivityLogs'
		}});
	
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
		        createTimeline(data, 'connectivityTimeline', 'loadingConnectivityTimeline');
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

		$.ajax({
        type: "GET",
        dataType: "json",
        url: "../ajax/getConnectivityAvailability.php",
        data: dataForAvailability,
        success: function(data) {
			console.log(data);
            updateChart(data,'NoDataWifiAvailable','NoDataOmtsAvailable');
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
				//var aircraftId =  //echo $aircraftId; ?>;
				//var start = res[2];
				//var end = res[3];
		
				//var url = "connectivityActivityView.php?aircraftId=" + aircraftId + "&start=" + start + "&end=" + end + "&flightLegs= //echo $flightLegs ?>";	
				//var win = window.open(url, '_blank');
				//win.focus();
			}			
		}		
		
		
        
    });

}

</script>
<script>
	// Inspiration:
	// http://bl.ocks.org/d3noob/e34791a32a54e015f57d
	// http://stackoverflow.com/questions/15471224/how-to-format-time-on-xaxis-use-d3-js
	// http://tributary.io/inlet/5186053
		
	var data = <?php echo json_encode($data); ?>;

    var profileWidth = document.getElementById("flightProfile").offsetWidth;
	
    var margin = {top: 30, right: 100, bottom: 30, left: 60};
    var width = profileWidth - margin.left - margin.right;
	//var width = window.innerWidth - margin.left - margin.right;	
    var height = 250 - margin.top - margin.bottom;
    var parseDate = d3.time.format("%Y-%m-%d %H:%M:%S").parse;
    var x = d3.time.scale()
        .range([0, width]);

    var y = d3.scale.linear()
        .range([height, 0]);
		
	var z = d3.scale.ordinal()
		.domain(["Ground","Taxi","Climb/Descent","Cruise"])
        .rangePoints([height, 0]);

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom")
		.ticks(15)		
		.tickFormat(d3.time.format("%H:%M"));

    var yAxis = d3.svg.axis()
        .scale(y)
        .orient("left");
		
	var zAxis = d3.svg.axis()
        .scale(z)		
        .orient("right")
		.tickValues(["Ground","Taxi","Climb/Descent","Cruise"]);

    var altitudeLine = d3.svg.line()
        .x(function(d) { return x(d.time); })
        .y(function(d) { return y(d.altitude); });

	var flightPhaseLine = d3.svg.line()
        .x(function(d) { return x(d.time); })
        .y(function(d) { return z(d.flightPhase); });
 
    var svg = d3.select("#flightProfile").append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
      .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    data.forEach(function(d) {
        d.time = parseDate(d.time);
        d.altitude = +d.altitude;
    });

    x.domain(d3.extent(data, function(d) { return d.time; }));
    y.domain(d3.extent(data, function(d) { return d.altitude;}));
	//z.domain(d3.extent(data, function(d) { return d.flightPhase;}));

	svg.append("g")         
        .attr("class", "grid")
        .attr("transform", "translate(0," + height + ")")
        .call(make_x_axis()
            .tickSize(-height, 0, 0)
            .tickFormat("")
        )

    svg.append("g")         
        .attr("class", "grid")
        .call(make_y_axis()
            .tickSize(-width, 0, 0)
            .tickFormat("")
        )
	
    svg.append("g")
          .attr("class", "x axis")
          .attr("transform", "translate(0," + height + ")")
          .call(xAxis);

    svg.append("g")
          .attr("class", "y axis")
          .call(yAxis)
          .append("text")
          //.attr("transform", "rotate(-90)")
          .attr("y", -25)
          .attr("dy", ".71em")
          .style("text-anchor", "end")
          .text("Altitude")		  
		  .style("fill", "#1f77b4")
		  .style("font-family", "Arial")
		  .style("font-size", "12px")
		  .style("font-weight", "bold");	  
		  
	svg.append("g")
          .attr("class", "y axis")
		  .attr("transform", "translate(" + width + ",0)")
          .call(zAxis)
          .append("text")
          //.attr("transform", "rotate(-90)")		  
          .attr("x", 75)
		  .attr("y", -25)
          .attr("dy", ".71em")
          .style("text-anchor", "end")
          .text("Flight Phase")
		  .style("font-family", "Arial")
		  .style("font-size", "12px")
		  .style("font-weight", "bold");
	
	// Draw flight phase line
    svg.append("path")
		.datum(data)
		.attr("class", "line")
		.style("fill", "none")
		.style("stroke", "555")
		.attr("d", flightPhaseLine);
		
	// Draw altitude line
    svg.append("path")
		.datum(data)
		.attr("class", "line")
		.style("fill", "none")
		.style("stroke", "#1f77b4")
		.style("stroke-width", "3")
		.attr("d", altitudeLine);
		
	function make_x_axis() {        
		return d3.svg.axis()
			.scale(x)
			.orient("bottom")
			.ticks(20)
	}

	function make_y_axis() {        
		return d3.svg.axis()
			.scale(y)
			.orient("left")
			.ticks(10)
	}
</script>
<script type="text/javascript">

	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var target = $(e.target).attr("href") // activated tab        	
	 	if(target=='#flightConnectivity'){		 	
		 	$('#mapView').hide();
			 // To remove the white space below the page content
			page_content_onresize();
		}else if(target=='#logs'){			
			$('#mapView').hide();
			// To remove the white space below the page content
			page_content_onresize();
		}else if(target=='#maps'){			
			$('#mapView').show();
			// To remove the white space below the page content
			page_content_onresize();
		}
	});
	
</script>
</html>






