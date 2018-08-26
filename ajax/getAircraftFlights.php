<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

require_once "../common/computeFleetStatusData.php";

$flightPhases = getFlightPhases();

$postdata = file_get_contents("php://input");
$request = json_decode($postdata);
$aircraftId = $request->aircraftId;
$startDate = $request->startDate;
$endDate = $request->endDate;

if($aircraftId != '') {
	$query = "SELECT a.databaseName FROM aircrafts a WHERE a.id = $aircraftId" ;
	$result = mysqli_query($dbConnection,$query);

	$lrus = array();
	if($result && mysqli_num_rows($result) > 0 ) {
		$row = mysqli_fetch_array($result);
		$dbName = $row['databaseName'];
	}
} else if($sqlDump != '') {
	$dbName = $sqlDump;
} else {
	echo "no acid nor sqlDump";
	exit;
}

// Note: need to use DISTINCT in below query otherwise will have a return per flight phase per flight leg, probably due to the join
$query = "SELECT DISTINCT(a.idFlightLeg), a.flightNumber, a.departureAirportCode, a.arrivalAirportCode, a.createDate, a.lastUpdate 
	FROM $dbName.SYS_flight a
	INNER JOIN $dbName.SYS_flightPhase b
        ON a.idFlightLeg = b.idFlightLeg
        AND b.idFlightPhase IN ($flightPhases)
	AND a.createDate BETWEEN '$startDate' AND '$endDate'
	AND flightLeg LIKE 'OPP%'";
$result = mysqli_query($dbConnection, $query);

$events = array();

while ($row = mysqli_fetch_array($result)) {
	$flightLegId = $row['idFlightLeg'];
	$start = $row['createDate'];
	$end = $row['lastUpdate'];
	$flightNumber = $row['flightNumber'];
	$departureAirportCode = $row['departureAirportCode'];
	$arrivalAirportCode = $row['arrivalAirportCode'];
	$status = getFlightStatus($dbName, $flightLegId);

	$content = $flightNumber." / ".$departureAirportCode." - ".$arrivalAirportCode;

	$events[] = array(
		'id' => $flightLegId,
		'title' => $content,
		'start' => $start,
		'end' => $end,
		'status' => $status
		);
}

// echo json_encode($event, JSON_NUMERIC_CHECK );
echo json_encode($events);

?>
