<?php
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];

$faults = array();

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

	// Get faults
	$query = "SELECT idFault, a.hostName, a.reportingHostName, a.faultCode, a.param1, a.param2, a.param3, a.param4, a.detectionTime, a.monitorState, a.insertionTime, a.clearingTime, a.lastUpdate, b.idFlightPhase, c.faultDesc 
				FROM $dbName.BIT_fault a
				LEFT JOIN $mainDB.sys_faultinfo c
				ON a.faultCode = c.faultCode
				LEFT JOIN $dbName.SYS_flightPhase b
				ON a.idFlightLeg = b.idFlightLeg
				WHERE a.idFlightLeg IN ($flightLegs)
				AND (a.detectionTime BETWEEN b.startTime AND b.endTime)
				ORDER BY a.detectionTime";
	
	//echo $query;exit;
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($idFault, $hostName, $reportingHostName, $faultCode, $param1, $param2, $param3, $param4, $detectionTime, $monitorState, $insertionTime, $clearingTime, $lastUpdate, $idFlightPhase, $faultDesc);
		while ($stmt->fetch()) {
			//$flighPhase = getFlightPhaseDesc($idFlightPhase);
			
			$getflightPhase = getFlightPhaseDesc($idFlightPhase);
			$flightPhase = $idFlightPhase." -".$getflightPhase;
			
			//$monitorStateDesc = getMonitorStateDesc($monitorState);
			
			if($monitorState == 1){
				$duration = dateDifference($detectionTime, $lastUpdate, '%h Hours %i Minute %s Seconds');
			}else{
				$duration = '-';
			}
			
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
				'flightPhase' => $flightPhase,
				'faultDesc' => $faultDesc,
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
echo $json_response = json_encode($faults);

?>
