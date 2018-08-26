<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];

$resets = array();

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
	$query = "SELECT a.idEvent, a.eventName, a.eventData, a.eventInfo, a.lastUpdate, b.idFlightPhase
				FROM $dbName.BIT_events a
				LEFT JOIN $dbName.SYS_flightPhase b
				ON a.idFlightLeg = b.idFlightLeg
				WHERE a.idFlightLeg IN ($flightLegs)
				AND (a.lastUpdate BETWEEN b.startTime AND b.endTime)
				ORDER BY a.lastUpdate";
				
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($idEvent, $eventName, $eventData, $eventInfo, $lastUpdate, $idFlightPhase);
		while ($stmt->fetch()) {
			//$flightPhase = getFlightPhaseDesc($idFlightPhase);
			$getflightPhase = getFlightPhaseDesc($idFlightPhase);
			$flightPhase = $idFlightPhase." -".$getflightPhase;

			$resets[] = array(
				'idEvent' => $idEvent, 
				'eventName' => $eventName, 
				'eventData' => $eventData, 
				'eventInfo' => $eventInfo,
				'lastUpdate' => $lastUpdate, 
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
echo $json_response = json_encode($resets);

?>
