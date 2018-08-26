<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];

$failures = array();


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
	$query = "SELECT a.idFailure, a.correlationDate, a.accusedHostName, a.failureCode, a.monitorState, a.lastUpdate, b.idFlightPhase, c.failureDesc, c.failureImpact, d.name, d.description
			FROM $dbName.BIT_serviceFailure a
			LEFT JOIN $mainDB.sys_serviceFailureInfo c
			ON a.failureCode = c.failureCode
			LEFT JOIN $mainDB.sys_services d 
			ON a.idService = d.idService 
			LEFT JOIN $dbName.SYS_flightPhase b
			ON a.idFlightLeg = b.idFlightLeg
			WHERE a.idFlightLeg IN ($flightLegs)
			AND (a.correlationDate BETWEEN b.startTime AND b.endTime)
			ORDER BY a.correlationDate";
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($idFailure, $correlationDate, $accusedHostName, $failureCode, $monitorState, $lastUpdate, $idFlightPhase, $failureDesc, $failureImpact, $name, $description);
		
		
		while ($stmt->fetch()) {
			$getflightPhase = getFlightPhaseDesc($idFlightPhase);
			$flightPhase = $idFlightPhase." -".$getflightPhase;
			
			if($monitorState == 1){
				$duration = dateDifference($correlationDate, $lastUpdate, '%h Hours %i Minute %s Seconds');
			}else{
				$duration = '-';
			}
			
			$failures[] = array(
				'idFailure' => $idFailure, 
				'correlationDate' => $correlationDate, 
				'accusedHostName' => $accusedHostName, 
				'failureCode' => $failureCode, 
				'monitorState' => getMonitorStateDesc($monitorState),  
				'lastUpdate' => $lastUpdate,
				'correctiveAction' => "<img src=\"../img/maintenance.png\">",
				'flightPhase' => $flightPhase,
				'failureDesc' => $failureDesc,
				'failureImpact' => $failureImpact,
				'name' => $name,
				'description' => $description,
				'duration' => $duration
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
}

# JSON-encode the response
echo $json_response = json_encode($failures);

?>
