<?php
date_default_timezone_set("GMT");

include("../database/connecti_database.php");
include("../common/functions.php");

require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];


if($aircraftId != '') {
    // Get aircraft database
    $query = "SELECT databaseName 
    			FROM aircrafts 
    			WHERE id = $aircraftId";
    $result = mysqli_query($dbConnection, $query);

    if($result && mysqli_num_rows($result) > 0) {
    	$row = mysqli_fetch_array($result);
        $dbName = $row['databaseName'];
    } else {
        echo "error: ".mysqli_error($dbConnection). " / query: $query";
    }
} else if($sqlDump != '') {
    $dbName = $sqlDump;
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}


// Select aircraft database
$selected = mysqli_select_db($dbConnection, $dbName)
    or die("Could not select ".$dbName);


$query = "SELECT lastUpdate FROM SYS_flight ORDER BY lastUpdate DESC LIMIT 1";
$result = mysqli_query($dbConnection, $query);
if($result != null) {
    $row = mysqli_fetch_array($result);
    $endDateTimeOffload = $row['lastUpdate'];
    $defaultStartDateTimeOffload = strtotime("-5 day", strtotime($endDateTimeOffload));
    $defaultStartDateTimeOffload = date("Y/m/d H:i", $defaultStartDateTimeOffload);
}


$startDateTime = $defaultStartDateTimeOffload;
$endDateTime = date("Y/m/d H:i", strtotime($endDateTimeOffload));


$jsonData = array(
	'start' => "$startDateTime",
	'end' => "$endDateTime",
);

echo json_encode($jsonData, JSON_NUMERIC_CHECK);
?>
