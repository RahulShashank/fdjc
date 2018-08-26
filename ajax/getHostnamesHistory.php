<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
//require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];

$flightPhases = getFlightPhases();

// Change format
$starDateTime = strtotime($starDateTime);
$starDateTime = date("Y-m-d", $starDateTime);

// Change format
$endDateTime = strtotime($endDateTime);
$endDateTime = date("Y-m-d", $endDateTime); 

$removals = array();

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
$query = "SELECT DISTINCT(a.idFlightLeg), a.flightNumber, a.departureAirportCode, a.arrivalAirportCode, DATE_FORMAT( a.createDate,  '%m/%d' ) AS createDate 
	FROM $dbName.SYS_flight a
	INNER JOIN $dbName.SYS_flightPhase b
        ON a.idFlightLeg = b.idFlightLeg
        AND b.idFlightPhase IN ($flightPhases)
	AND a.createDate BETWEEN '$startDateTime' AND '$endDateTime'
	AND flightLeg LIKE 'OPP%'";
//echo $query; exit;
$result = mysqli_query($dbConnection, $query);

$header1 = array();
$header2 = array();
$data = array();

while ($row = mysqli_fetch_array($result)) {
	$flightLegId = $row['idFlightLeg'];
	$date = $row['createDate'];
	$flightNumber = $row['flightNumber'];
	$departureAirportCode = $row['departureAirportCode'];
	$arrivalAirportCode = $row['arrivalAirportCode'];

	$content = $flightNumber; //." / ".$departureAirportCode." - ".$arrivalAirportCode;

	$header1[] = array(
		'date' => $date,
		);
	
	$header2[] = array(
		'id' => $flightLegId,
		'flight' => $content,
		);
	
	$query2 = "SELECT DISTINCT(hostName)
				FROM $dbName.BIT_lru
				WHERE 
					(hostName LIKE 'DSU%') OR (hostName LIKE 'LAIC%') OR (hostName LIKE 'AVCD%') OR (hostName LIKE 'ADB%') OR 
					(hostName LIKE 'SDB%') OR (hostName LIKE 'QSEB%') OR 
					(hostName LIKE 'SVDU%') OR (hostName LIKE '%PCU%') OR (hostName LIKE 'TPMU%')
				ORDER BY
					CASE 
						WHEN hostName LIKE 'DSU%' THEN 1
						WHEN hostName LIKE 'LAIC%' THEN 2
						WHEN hostName LIKE 'AVCD%' THEN 2
						WHEN hostName LIKE 'ADBG%' THEN 3
						WHEN hostName LIKE 'SDB%' THEN 4
						WHEN hostName LIKE 'QSEB%' THEN 4
						WHEN hostName LIKE 'SVDU%' THEN 5
						WHEN hostName LIKE '%PCU%' THEN 6
						WHEN hostName LIKE 'TPMU%' THEN 6						
						ELSE 7
					END,
					LENGTH(hostName),
					hostName";
	
	$result2 = mysqli_query($dbConnection, $query2);
	
	while ($row2 = mysqli_fetch_array($result2)) {
		$hostname = $row2['hostName'];
		
		$query3 = "SELECT count(*) as faultCount
				FROM $dbName.BIT_fault a
				INNER JOIN $dbName.SYS_flightPhase b
				ON a.idFlightLeg = b.idFlightLeg
				AND b.idFlightPhase IN ($flightPhases)
				AND a.idFlightLeg = $flightLegId
				AND hostName = '$hostname'";
		//echo $query3; exit;
		$result3 = mysqli_query($dbConnection, $query3);
		
		while ($row3 = mysqli_fetch_array($result3)) {
		
			$faultCount = $row3['faultCount'];

			$data[$hostname] = array(
					'faultCount' => $faultCount
				);
			
				
			//echo "$hostname - $faultCount<br>";
		}
	}
	
	// exit after first day for testing
	break;
}

// echo json_encode($event, JSON_NUMERIC_CHECK );
echo json_encode($header1);
echo "<br><br>";
echo json_encode($header2);
echo "<br><br>";
echo json_encode($data);
?>
