<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];

$events = array();

if( ( isset($aircraftId) || isset($sqlDump) ) && isset($flightLegs) ) {

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

	// Get failures
	$query = "SELECT a.idExtAppEvent, a.hostName, a.reportingHostName, a.faultCode, a.param1, a.param2, a.param3, a.param4, a.detectionTime, b.idFlightPhase
				FROM $dbName.BIT_extAppEvent a
				LEFT JOIN $dbName.SYS_flightPhase b
				ON a.idFlightLeg = b.idFlightLeg
				WHERE a.idFlightLeg IN ($flightLegs)
				AND (a.detectionTime BETWEEN b.startTime AND b.endTime)
				ORDER BY a.detectionTime";
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($idExtAppEvent, $hostName, $reportingHostName, $faultCode, $param1, $param2, $param3, $param4, $detectionTime, $idFlightPhase);
		while ($stmt->fetch()) {
			//$flightPhase = getFlightPhaseDesc($idFlightPhase);
			$faultDesc = getExtAppEventDesc($faultCode);
			$getflightPhase = getFlightPhaseDesc($idFlightPhase);
			$flightPhase = $idFlightPhase." -".$getflightPhase;
			
			$events[] = array(
				'idExtAppEvent' => $idExtAppEvent, 
				'hostName' => $hostName, 
				'reportingHostName' => $reportingHostName, 
				'faultCode' => $faultCode, 
				'faultDesc' => $faultDesc, 
				'param1' => $param1, 
				'param2' => $param2, 
				'param3' => $param3, 
				'param4' => $param4, 
				'detectionTime' => $detectionTime,
				'flightPhase' => $flightPhase
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
}

# JSON-encode the response
echo $json_response = json_encode($events);

?>
