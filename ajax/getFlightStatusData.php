<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

require_once "../common/computeFleetStatusData.php";

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];

if($aircraftId != '') {
	$query = "SELECT a.databaseName, a.platform FROM aircrafts a WHERE a.id = $aircraftId" ;
	$result = mysqli_query($dbConnection,$query);

	$lrus = array();
	if($result && mysqli_num_rows($result) > 0 ) {
		$row = mysqli_fetch_array($result);
		$dbName = $row['databaseName'];
		$platform = $row['platform'];
	}
} else if($sqlDump != '') {
	$dbName = $sqlDump;
} else {
	echo "no acid nor sqlDump";
	exit;
}

$data = array();
// $data = getFlightStatusData($dbName, $flightLegs, $platform);
/*
$data[] = array("name" => "System Reset", "value" => getFlightDetailedStatus($dbName, $flightLegs, "systemResetStatus"));
$data[] = array("name" => "Head-End", "value" => getFlightDetailedStatus($dbName, $flightLegs, "headEndStatus"));
$data[] = array("name" => "First Class", "value" => getFlightDetailedStatus($dbName, $flightLegs, "firstClassStatus"));
$data[] = array("name" => "Business Class", "value" => getFlightDetailedStatus($dbName, $flightLegs, "businessClassStatus"));
$data[] = array("name" => "Economy Class", "value" => getFlightDetailedStatus($dbName, $flightLegs, "economyClassStatus"));
$data[] = array("name" => "Connectivity Status", "value" => getFlightDetailedStatus($dbName, $flightLegs, "connectivityStatus"));
*/
// New Api to get Flight Status for (SR, HE, FC, BC, EC, Connectivity) at once for flightlegs input
$flightStatus = getFlightDetailedStatusForFlightlegs($dbName, $flightLegs);
$data[] = array("name" => "System Reset", "value" => $flightStatus['systemResetStatus']);
$data[] = array("name" => "Head-End", "value" => $flightStatus['headEndStatus']);
$data[] = array("name" => "Connectivity", "value" => $flightStatus['connectivityStatus']);
$data[] = array("name" => "First Class", "value" => $flightStatus['firstClassStatus']);
$data[] = array("name" => "Business Class", "value" => $flightStatus['businessClassStatus']);
$data[] = array("name" => "Economy Class", "value" => $flightStatus['economyClassStatus']);

# JSON-encode the response
echo $json_response = json_encode($data);
?>
