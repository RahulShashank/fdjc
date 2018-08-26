<?php
session_start();
error_log(basename(__FILE__) . " entered");
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');
require_once "../common/datesConfiguration.php";
require_once "../common/computeFleetStatusData.php";

$itemStyle = "font-family: Helvetica; font-size: 10px; text-align: left";
$flightPhases = getFlightPhases();

$airlineId = $_REQUEST['airlineId'];
$platform = $_REQUEST['platform'];
// $actype = $_REQUEST['actype'];
$configType = $_REQUEST['configType'];
$software = $_REQUEST['software'];
$startDateTime = $_REQUEST['startDate'];
$endDateTime = $_REQUEST['endDate'];

$_SESSION['airlineId'] = $_REQUEST['airlineId'];
$_SESSION['platform'] =  $_REQUEST['platform'];
$_SESSION['configType'] =  $_REQUEST['configType'];
$_SESSION['software'] =  $_REQUEST['software'];
$_SESSION['startDate'] =  $_REQUEST['startDate'];
$_SESSION['endDate'] =  $_REQUEST['endDate'];

$startDateTime .= " 00:00:00";
$endDateTime .= " 23:59:59";

$timelineStartDateTime = date('Y-m-d H:i:s', strtotime($startDateTime . ' -1 day'));
$timelineEndDateTime = date('Y-m-d H:i:s', strtotime($endDateTime . ' +1 day'));
// error_log("Timeline Start Date : " . $timelineStartDateTime);
// error_log("Timeline End Date : " . $timelineEndDateTime);

// Options data - start and end time values are for the date picker fields
$options = array(
    'start' => "$timelineStartDateTime",
    'end' => "$timelineEndDateTime",
    'min' => "$timelineStartDateTime",
    'max' => "$timelineEndDateTime"
);

// Get aircrafts
$query = "SELECT id, tailsign, platform, databaseName, noseNumber
			FROM aircrafts
			WHERE airlineId = $airlineId";
if(is_array($platform)) {
    $query .= " and platform in(";
    foreach ($platform as $p) {
        $query .= "'" . $p . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if($platform != '') {
    $query .= " and platform='$platform'";
}

if(is_array($configType)) {
    $query .= " and Ac_Configuration in(";
    foreach ($configType as $c) {
        $query .= "'" . $c . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if($configType!= '') {
    $query .= " and Ac_Configuration='$configType'";
}

if(is_array($software)) {
    $query .= " and software in(";
    foreach ($software as $s) {
        $query .= "'" . $s . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if($software != '') {
    $query .= " and software='$software'";
}

// if($platform != '') {
//     $query .= " AND platform = '$platform'";
// }
// if($actype != '') {
//     $query .= " AND type = '$actype'";
// }
$query .= " ORDER BY tailsign";
$result = mysqli_query ($dbConnection, $query );

// error_log("Aircraft Query: " . $query);

$groups = array ();
$dataItems = array();

while ( $row = mysqli_fetch_assoc ( $result ) ) {
    $dbName = $row ['databaseName'];
    $tailsign = $row ['tailsign'];
    $aircraftId = $row ['id'];
    $platform = $row ['platform'];
	$noseNumber = $row ['noseNumber'];
	
	if(!empty($noseNumber)){
		$tailsign= $tailsign." (" .$noseNumber. ")";
	}

    // Add tailsign as group
    //$content = "<img src=\"../img/plane.png\" style=\"vertical-align:middle\"/> <strong><a href=\"aircraftDashboard.php?aircraftId=$aircraftId\" target=\"_blank\">$tailsign</a></strong>";
// 	$content = "<strong><a href=\"aircraftDashboard.php?aircraftId=$aircraftId\" target=\"_blank\"><i class=\"fa fa-plane fa-fw\" aria-hidden=\"true\"></i>&nbsp;$tailsign</a></strong>";
	$content = "<strong><i class=\"fa fa-plane fa-fw\" aria-hidden=\"true\"></i>&nbsp;$tailsign</strong>";
	$groups[] = array(
    	'id' => $tailsign, 
    	'content' => $content
    );

	$db_selected = mysqli_select_db($dbConnection, $dbName);
	if (!$db_selected) {
	    continue;
	}
	
//     error_log("Groups: " . print_r($groups, TRUE));
	// Note: need to use DISTINCT in below query otherwise will have a return per flight phase per flight leg, probably due to the join
	$query2 = "SELECT DISTINCT(a.idFlightLeg), a.flightNumber, a.departureAirportCode, a.arrivalAirportCode, a.createDate, a.lastUpdate, a.analyzed 
		FROM $dbName.SYS_flight a
		INNER JOIN $dbName.SYS_flightPhase b
        ON a.idFlightLeg = b.idFlightLeg
        AND b.idFlightPhase IN ($flightPhases)
        AND (
	          ( '$startDateTime' <= a.createDate AND '$endDateTime' >= a.lastUpdate)
	      OR
	          ( '$startDateTime' <= a.lastUpdate AND '$endDateTime' >= a.createDate)
	    )";
	
// 	error_log("Query : " . $query2);
        
	$result2 = mysqli_query($dbConnection, $query2);

	while ($row2 = mysqli_fetch_array($result2)) {

		$flightLegId = $row2['idFlightLeg'];
		$content = $flightLegId." - ".$row2['flightNumber']." / ".$row2['departureAirportCode']." - ".$row2['arrivalAirportCode'];
		$start = $row2['createDate'];
		$end = $row2['lastUpdate'];
		$duration = dateDifference($start, $end, '%h Hours %i Minute %s Seconds');
		$title = $flightLegId." - ".$row2['flightNumber']." / ".$row2['departureAirportCode']." -> ".$row2['arrivalAirportCode']." / $start -> $end / $duration";
		$analyzed = $row2['analyzed'];

		$status = getFlightStatus($dbName, $flightLegId);
		if($status == 0) {
			if($analyzed) {
				$class = 'statusOKAnalyzed';
			} else {
				$class = 'statusOK';
			}
		} else if($status == 1) {
			if($analyzed) {
				$class = 'statusWarningAnalyzed';
			} else {
				$class = 'statusWarning';
			}
		} else if($status > 1) {
			if($analyzed) {
				$class = 'statusAlertAnalyzed';
			} else {
				$class = 'statusAlert';
			}
		}
		
		$dataItems[] = array(
		    'className' => "$class",
		    'id' => "ACID/$aircraftId/FLID/$flightLegId/ST/$status",
		    'group' => "$tailsign",
		    'content' => "$content",
		    'title' => "$title",
		    'start' => "$start",
		    'end' => "$end",
		    'style' => "$itemStyle"
		);
	}
}


$fleetStatusData = array(
	'groups' => $groups,
	'items' => $dataItems,
    'options' => $options
); 

// echo var_dump($fleetStatusData);
echo json_encode($fleetStatusData, JSON_NUMERIC_CHECK );

?>
