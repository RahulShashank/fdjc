<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];

$startDate = '2016-07-01';
$endDate = '2016-07-31';

$faults = array();

if( ( isset($aircraftId) || isset($sqlDump) ) ) {

	if(isset($aircraftId)) {
		// Get database name
		$query = "SELECT databaseName from aircrafts WHERE id=?";
		
		if( $stmt = $dbConnection->prepare($query) ) {
			$stmt->bind_param("i", $aircraftId);
			$stmt->execute();
			$stmt->bind_result($dbName);
			$stmt->fetch();

			$stmt->close();
		} else {
			echo "Error creating statement";
			exit;
		}
	} else {
		$dbName = $sqlDump;
	}
	
	// Get faults
	/*
	$query = "SELECT idFault, a.hostName, a.reportingHostName, a.faultCode, a.param1, a.param2, a.param3, a.param4, a.detectionTime, a.monitorState, a.insertionTime, a.clearingTime, a.lastUpdate, b.idFlightPhase, c.faultDesc 
				FROM $dbName.BIT_fault a
				LEFT JOIN $mainDB.sys_faultinfo c
				ON a.faultCode = c.faultCode
				INNER JOIN $dbName.SYS_flightPhase b
				ON a.idFlightLeg = b.idFlightLeg
				AND a.idFlightLeg IN ($flightLegs)
				AND a.detectionTime BETWEEN b.startTime AND b.endTime
				ORDER BY a.detectionTime";
	*/
	$query = "SELECT a.faultCode, c.faultDesc, COUNT(*) as count 
				FROM $dbName.BIT_fault a
				LEFT JOIN $mainDB.sys_faultinfo c
				ON a.faultCode = c.faultCode
				AND a.detectionTime BETWEEN '$startDate' AND '$endDate'
				GROUP BY a.faultCode
				ORDER BY count
				LIMIT 10)";
	
	echo $query;exit;
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($idFault, $hostName, $reportingHostName, $faultCode, $param1, $param2, $param3, $param4, $detectionTime, $monitorState, $insertionTime, $clearingTime, $lastUpdate, $idFlightPhase, $faultDesc);
		while ($stmt->fetch()) {
			$flighPhase = getFlightPhaseDesc($idFlightPhase);

			$faults[] = array(
				'idFault' => $idFault, 
				'hostName' => $hostName, 
				'reportingHostName' => $reportingHostName, 
				'faultCode' => $faultCode, 
				'param1' => $param1,
				'param2' => $param2, 
				'param3' => $param3, 
				'param4' => $param4, 
				'detectionTime' => $detectionTime, 
				'monitorState' => getMonitorStateDesc($monitorState), 
				'insertionTime' => $insertionTime, 
				'clearingTime' => $clearingTime, 				
				'lastUpdate' => $lastUpdate,
				'flightPhase' => $flighPhase,
				'faultDesc' => $faultDesc
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
}

# JSON-encode the response
echo $json_response = json_encode($faults);

?>
