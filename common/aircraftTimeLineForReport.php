<?php
ini_set ( 'memory_limit', '-1' );
ini_set ( 'max_execution_time', 300 );
date_default_timezone_set ( "GMT" );

// Start the session
session_start ();
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
//require_once("validateUser.php");
//$approvedRoles = [$roles["all"]];
//$auth->checkPermission($hash, $approvedRoles);

require_once "../common/flightLegFiltersComputation.php";
require_once "../common/computeFleetStatusData.php";

$itemStyle = "style: 'font-family: Helvetica; font-size: 10px; text-align: left'";

// Get database information
$aircraftId = $_REQUEST ['aircraftId'];
$sqlDump = $_REQUEST ['db'];

$db = $mainDB;
if ($db == '' && $aircraftId == '') { // Modified $db to $dbName
	header ( 'Location: ../login.php' );
}

if ($aircraftId != '') {
	$selected = mysqli_select_db ( $dbConnection, $mainDB ) or die ( "Could not select " . $mainDB );
} else { // Modified code for $sqlDump
	$selected = mysqli_select_db ( $dbConnection, $sqlDump ) or die ( "Could not select " . $sqlDump );
}

// Get information to display in header
if ($aircraftId != '') {
	$query = "SELECT a.tailsign, a.databaseName, a.platform, b.id, b.name FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
	$result = mysqli_query ( $dbConnection, $query );
	if ($result && mysqli_num_rows ( $result ) > 0) {
		$row = mysqli_fetch_array ( $result );
		$airlineId = $row ['id'];
		$airline = $row ['name'];
		$aircraft = $row ['tailsign'];
		$dbName = $row ['databaseName'];
		$pageTitle = "<a href=\"aircrafts.php?airlineId=$airlineId\">Flights timeline for <b>$airline</b></a> / <b><a href=\"aircraftTimeLine.php?aircraftId=$aircraftId\">$aircraft</a></b>";
		$pageIcon = "timeline.png";
		$platform = $row['platform'];
	} else {
		echo "<br>error: " . mysqli_error ( $dbConnection );
	}
} else {
	$dbName = $sqlDump;
	$pageTitle = "<a href=\"aircraftTimeLineForReport.php?db=$dbName\">" . trimDBName ( $dbName ) . "</a>"; // Modified $db to $dbName
	$pageIcon = "timeline.png";
}
//echo "DBName:----".$dbName;
// Connect to aircraft database. We don't need to select a particular database in case of a dump.
// if($aircraftId != '') {
$selected = mysqli_select_db ( $dbConnection, $dbName ) or die ( "Could not select " . $dbName );
// }

/**
 * This section for Capture screenshot.
 * I Commented for PhantomJS, because it's not require for it.
 */
$startDateTimeForScreenCapture = $_REQUEST ['startDateTimeForScreenShot'];
$endDateTimeForScreenCapture = $_REQUEST ['endDateTimeForScreenShot'];

//echo "-->>StartDateTimeForTimeLine:$startDateTimeForScreenCapture--->>>EndDateTimeForTimeLine:-$endDateTimeForScreenCapture";
//$dateTimeForStartTimeLine = strtotime ( $startDateTimeForScreenCapture );
//$startDateTimeForTimeLine = date ( "Y-m-d H:i:s", $dateTimeForStartTimeLine );

$dateTimeForEndTimeLine = strtotime ( $endDateTimeForScreenCapture );
$endDateTimeForTimeLine = date ( "Y-m-d H:i:s", $dateTimeForEndTimeLine );

$dailyStartTime = strtotime ( '-2 day', $dateTimeForEndTimeLine );
$startDateTimeForTimeLine = date ( "Y-m-d H:i:s", $dailyStartTime );
 //echo "-->>StartDateTimeForTimeLine11:$startDateTimeForTimeLine--->>>EndDateTimeForTimeLine11:-$endDateTimeForTimeLine";
// Get date range for the aircraft or database
$minDateTime = getMinDateTime ();
$maxDateTime = getMaxDateTime ( $airline );

/* To Capture 24hr data at TimeLine I changed Method to getStartDateTimeForReport, Newly added it at flightLegFiltersComputation.php */
 /* $startDateTime = getStartDateTimeForReport ( $airlineId, $startDateTimeForScreenCapture, $minDateTime, $maxDateTime );
$endDateTime = getEndDateTime ( $airlineId, $endDateTimeForScreenCapture, $maxDateTime );  */
//echo "-->>startDateTime22:$startDateTime--->>>endDateTime22:-$endDateTime";

// Get flight leg filter input
$flightLegIdInput = $_REQUEST ['flightLegIdInput'];
//echo "FlightLegIdInput--->>>>+$flightLegIdInput ";
$flightLegIdCondition = getFlightLegIdCondition ( $flightLegIdInput );

// Get durations conditions
/* $minDurationInput = $_REQUEST ['minDurationInput'];
$maxDurationInput = $_REQUEST ['maxDurationInput']; */

/* $durationCondition = getDurationCondition ( $minDurationInput, $maxDurationInput ); */

// execute the SQL query and return records
// added flight phase 4 to account for i5000 as well since there is no cruise phase in i5000
$query = "SELECT DISTINCT a.idFlightLeg,a.flightNumber,a.flightLeg,a.departureAirportCode,a.arrivalAirportCode,a.createDate,a.lastUpdate FROM SYS_flight a INNER JOIN SYS_flightPhase b ON a.idFlightLeg = b.idFlightLeg AND (b.idFlightPhase = 5 OR b.idFlightPhase = 4) AND (( a.createDate >= '$startDateTimeForTimeLine' AND a.lastUpdate <= '$endDateTimeForTimeLine') OR ( '$startDateTimeForTimeLine' <= a.lastUpdate AND '$endDateTimeForTimeLine' >= a.createDate)) ";
$query .= " ORDER BY a.createDate";

//echo "--->>>FlightLegs --> $query--->>>>";
error_log('Timeline Aircraft Id '.$aircraftId);
error_log('Timeline StartDate '.$startDateTimeForTimeLine);
error_log('Timeline End Date '.$endDateTimeForTimeLine);
error_log('Timeline Query '.$query);
$result = mysqli_query ( $dbConnection, $query );
// $myfile = fopen("screenshotQuery.txt", "w");
// fwrite($myfile, dirname( __FILE__ )."\n");
// fwrite($myfile, basename(dirname(__DIR__))."\n");
// fwrite($myfile, $query);
// fclose($myfile);
if ($result) {
	if (mysqli_num_rows ( $result ) > 0) {
		$dataItems = array ();
		$i = 0;
		$keepLooping = true;
		$row = mysqli_fetch_array ( $result ); // get first flight leg
		
		while ( $keepLooping ) {
			// $flightLegName = $row{'flightLeg'};
			// if(strpos($flightLegName, 'OP') === 0) { // We need only open Flight.
			
			$id = $row ['idFlightLeg'];
			
			$flightNumber = $row ['flightNumber'];
			$departureAirportCode = $row ['departureAirportCode'];
			$arrivalAirportCode = $row ['arrivalAirportCode'];
			$content = "$id - $flightNumber - $departureAirportCode - $arrivalAirportCode";
			$start = $row ['createDate'];
			//$end = $row ['lastUpdate'];
			 if ($aircraftId != '') {
				$end = $row ['lastUpdate'];
			} else {
				// for db, we need to get the createDate of the next flight leg in order to get the end date
				$nextRow = mysqli_fetch_array ( $result );
				if ($nextRow) {
					$end = $nextRow ['createDate'];
				} else {
					// we have reached the last flight leg
					// there are two cases: the
					$query = "SELECT createDate FROM SYS_flight WHERE idFlightLeg = $id+1";
					//echo $query; exit;
					$result = mysqli_query ( $dbConnection, $query );
					if ($result != null && mysqli_num_rows ( $result ) > 0) {
						$Resultrow = mysqli_fetch_array ( $result );
						$end = $Resultrow ['createDate'];
					} else {
						// it is the very last flight leg of the database
						$end = $maxDateTime;
					}
					
					$keepLooping = false;
				}
			} 
			$flightLegName = $row ['flightLeg'];
			//echo ">>>>$flightNumber-----<<<<<flightLegName-->>>>$flightLegName";
			$duration = dateDifference ( $start, $end, '%h Hours %i Minute %s Seconds' );
			$title = "$id - $flightLegName - $flightNumber - $departureAirportCode - $arrivalAirportCode / $start --> $end / $duration";
			
			if (strpos ( $flightLegName, 'CL' ) === 0) {
				$group = 1;
				$class = 'closed';
			} else {
				$group = 0;
				$class = 'open';
			}
			
			if($sqlDump == ''){ //Don't compute flightStatus for sqldumps
				if($aircraftId != '') {
					$status = getFlightStatus($dbName, $id, $platform);
				} else {
					$status = 0;
				}
				
				if($status == 0) {
					$class = 'statusOK';
				} else if($status == 1) {
					$class = 'statusWarning';
				} else if($status > 1) {
					$class = 'statusAlert';
				}
			}
			
			$dataItems [$i ++] = "{className: '$class', id: 'FLI/$id', group: '$group', subgroup:'$subgroup', content: '$content', title: '$title', 
					start: '$start', end: '$end', $itemStyle}";
			
			// get corresponding flight phases
			$query2 = "SELECT * FROM $dbName.SYS_flightPhase WHERE idFlightLeg = $id ORDER BY startTime";
			$result2 = mysqli_query ( $dbConnection, $query2 );
			
			if ($result2) { // not every dump has the SYS_flightPhase table
				while ( $row2 = mysqli_fetch_array ( $result2 ) ) {
					$id = $row2 {'idFlightPhase'};
					$content = getFlightPhaseDesc ( $id ) . " [$id]";
					$start = $row2 {'startTime'};
					$end = $row2 {'endTime'};
					$subgroupOrder = getFlightPhaseOrder ( $id );
					$duration = dateDifference ( $start, $end, '%h Hours %i Minute %s Seconds' );
					$title = "$content / $start --> $end / $duration";
					
					$dataItems [$i] = "{group: '2', subgroup:'$subgroupOrder',
							content: '$content', title: '$title', start: '$start', end: '$end', $itemStyle}";
					
					$i ++;
				}
			}
			
			if ($nextRow) {
				$row = $nextRow;
			} else {
				$row = mysqli_fetch_array ( $result );
			}
			
			if (! $row) {
				$keepLooping = false;
			}
		}
		
		// Get the number of SVDUs
		$query = "SELECT count(*) AS 'count' 
						FROM $dbName.BIT_lru  a
						WHERE hostName LIKE 'SVDU%'
						AND a.lastUpdate = (
							SELECT MAX(b.lastUpdate)
							FROM $dbName.BIT_lru b
							WHERE a.hostName = b.hostName
						)";
		$result = mysqli_query ( $dbConnection, $query );
		if ($result) {
			$row = mysqli_fetch_array ( $result );
			$nbLru = $row ['count'];
		//	echo ">>>$nbLru>>>";
		}
		

		// Create system restarts (computation is done for an interval of 5 minutes / 300 seconds)
		// Use filter inputs for this request in the where close
		// But it looks putting the flight leg condition on this query will reduce the number of count
		// So I don't filter on the flight leg id but after in the results
		
		$query_1 = " SELECT idFlightLeg, lastUpdate, count(*) AS 'count'
		FROM (
		SELECT idFlightLeg, eventData, lastUpdate
		FROM $dbName.BIT_events
		WHERE lastUpdate >= '$startDateTimeForTimeLine' AND lastUpdate <= '$endDateTimeForTimeLine'
		) AS t
		WHERE eventData LIKE 'SVDU%'
		GROUP BY UNIX_TIMESTAMP(lastUpdate) DIV 300 ORDER BY lastUpdate";
		//echo ">>>>>>>>----->>>>>.$query_1";
		$result = mysqli_query ( $dbConnection, $query_1 );
		if($result) {
			$thresholdHigh = $nbLru * 30 / 100;
			while ($row = mysqli_fetch_array ( $result ) ) {
				$count = $row['count'];
				
				// echo "1<br>";
				if($count >= $thresholdHigh) {
					$idFlightLeg = $row['idFlightLeg'];
					
					// check if flight id match the flight id filter
					if(count($flightLegIds) > 0) {
						$found = in_array($idFlightLeg, $flightLegIds);
						
					}
					if ($flightLegIdInput == '' || $found) {
						$img = "<img src=\"../img/power.png\" style=\"width: 12px; height: 12px;\">";
						$content = $img;
						$start = $row['lastUpdate'];
						$title = "System start at $start / Flight leg: $idFlightLeg";
							
						// Check if system restart happened during cruise
						if($platform=="i5000"){
							$query2 = "SELECT idFlightPhase
							FROM SYS_flightPhase
							WHERE '$start' >= startTime AND '$start' <= endTime
							AND idFlightPhase IN (4,5)";
							// echo $query2;
						}
						else{
							$query2 = "SELECT idFlightPhase
							FROM SYS_flightPhase
							WHERE '$start' >= startTime AND '$start' <= endTime
							AND idFlightPhase = 5";
						}
						$result2 = mysqli_query ( $dbConnection, $query2 );
						if($result2) {
							if(mysqli_num_rows ( $result2 ) > 0) {
								$dataItems[$i] = "{className: 'appdown', group: '4', content: '$content', title: '$title', start: '$start', $itemStyle}";
								$i++;
								
								//echo "$idFlightLeg - $nbLru - $threshold - $count<br>";
							}
						}
							
						/* if($className != '' || $count > $thresholdGround ) {
							
							$dataItems[$i] = "{className: '$className', group: '4', content: '$content', title: '$title', start: '$start', $itemStyle}";
							$i++;
						} */
					}
				}
			}
		}
		
		// Get redundancy events
		$query = " SELECT param2, eventTime
					FROM $dbName.services_events a 
					WHERE a.eventSource='DSS DSU'
					AND eventName = 'DSS Redundancy'
					AND param1 = 'DOWN' 
					AND eventTime >= '$startDateTimeForTimeLine' AND eventTime <= '$endDateTimeForTimeLine'
					ORDER BY eventTime";
		$result = mysqli_query ( $dbConnection, $query );
		
		if ($result) {
			while ( $row = mysqli_fetch_array ( $result ) ) {
				$dsu = $row ['param2'];
				$time = $row ['eventTime'];
				// Check if system restart happened during cruise
				if($platform=="i5000"){
					$query2 = "SELECT idFlightPhase
								FROM SYS_flightPhase
								WHERE '$time' >= startTime AND '$time' <= endTime
								AND idFlightPhase IN (4,5)";
					// echo $query2;
				}else{
					$query2 = "SELECT idFlightPhase
								FROM SYS_flightPhase
								WHERE '$time' >= startTime AND '$time' <= endTime
								AND idFlightPhase = 5";
				}
				$result2 = mysqli_query ( $dbConnection, $query2 );
				if ($result2) {
					if (mysqli_num_rows ( $result2 ) > 0) {
						$dataItems [$i ++] = "{className: 'appdown', group: '3', subgroup:'$dsu', content: '$dsu', title: '$time', start: '$time', $itemStyle}";
					}
				}
			}
		}
	} else {
		//$msg = "<img src=\"img/warning.png\" style=\"vertical-align:bottom\">&nbsp;<b>The filters didn't return any result. Please verify the value(s).</b><br><br>";
		$msg = "The filters didn't return any result.TimeLine Data is missing. Please verify the values.</b><br><br>";
		echo $msg;
	}
} else {
	echo "error: " . mysqli_error ( $dbConnection );
}
?>
<!DOCTYPE HTML>
<html>
<head>
<!--  <title>BITE Analytics</title> -->
<script src="../js/jquery-1.11.2.js"></script>

<!-- <script src="scripts/datetimepicker/jquery.datetimepicker.js"></script>-->

<!--  <link rel="stylesheet" type="text/css" href="scripts/datetimepicker/jquery.datetimepicker.css" />-->
<!-- Newly Addd lib For PhantomJS  -->
<style type="text/css">
body,html {
	font-family: sans-serif;
	font-size: 12px;
	background-color: #FFF;
}
</style>

<!-- load ES5 shim needed to run on old browsers IE8 and PhantomJS -->
<script src="../js/es5-shim.min.js"></script>

<!-- load vis.js -->
<script src="../js/vis.min.js"></script>
<link href="../css/vis.css" rel="stylesheet" type="text/css" />
<!--  <link   href="../js/vis.min.css" rel="stylesheet" type="text/css" /> -->
</head>
<body>
	<?php
	// include("navigationPanelAircraft.php");
	?>
	<div class="main" align="center">

		<div id="visualization">
			<script type="text/javascript">
				  // DOM element where the Timeline will be attached
				  var container = document.getElementById('visualization');

				   var groups = new vis.DataSet([
				      { id: 0, content: '' },
				      { id: 1, content: '' },
				      { id: 2, content: '' },
					  { id: 3, content: '' },
				      { id: 4, content: '' }
				       ]);  	                 
				 
				  // Create a DataSet (allows two way data-binding)
				  var items = new vis.DataSet([
				  	  <?php
									foreach ( $dataItems as $dataItem ) {
										echo $dataItem;
										echo ",";
									}
									?>
				  ]);

				  // Configuration for the Timeline
				  var options = {	
						'showCurrentTime': false,		  		
				  		orientation: 'both',
						clickToUse: true,
				  		stack: false
				  };

				  // Create a Timeline
				  var timeline = new vis.Timeline(container, items, groups,  options);				  
			</script>
		</div>

		<div style="width: 100%; clear: both; display: block"></div>
		<br> <br>

	</div>

</body>
</html>
