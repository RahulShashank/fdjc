<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/customerFilters.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];
$seat = $_REQUEST['seat'];

//echo "Ajax call OK for a/c with id $aircraftId and $seat between $startDateTime and $endDateTime<br><br>";

$failuresToRemove = getFailuresToRemove();
$failuresToKeep = getFailuresToKeep();
$faultsToRemove = getFaultsToRemove();
$faultsToKeep = getFaultsToKeep();

$flightPhases = getFlightPhases();

$svduDetails = array();
$pcuDetails = array();
$svduActiveFailures = array();
$pcuActiveFailures = array();
$svduFailures = array();
$pcuFailures = array();
$svduFaults = array();
$pcuFaults = array();
$svduResets = array();
$pcuResets = array();

if( isset($aircraftId) || isset($sqlDump)  ) {

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
	
	// Get SVDU details
	$query = "SELECT serialNumber, lruType, lruSubType, hwPartNumber, revision, model, macAddress, lastUpdate
				FROM $dbName.BIT_lru a
				WHERE a.hostName = 'SVDU$seat'
				AND a.lastUpdate = (
					SELECT MAX(b.lastUpdate) AS max
					FROM $dbName.BIT_lru b
					WHERE a.hostName = b.hostName
				)";
				
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($serialNumber, $lruType, $lruSubType, $hwPartNumber, $revision, $model, $macAddress, $lastUpdate);
		while ($stmt->fetch()) {
			$lruName = getLruName($lruType, $lruSubType);
			
			$svduDetails[] = array(
				'serialNumber' => $serialNumber, 
				'lruType' => $lruName,
				'hwPartNumber' => $hwPartNumber,
				'revision' => $revision,
				'mod' => getModVal($model),
				'macAddress' => $macAddress,
				'lastUpdate' => date('Y-m-d', strtotime($lastUpdate)),
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
	
	// Get PCU details
	$query = "SELECT serialNumber, lruType, lruSubType, hwPartNumber, revision, model, macAddress, lastUpdate
				FROM $dbName.BIT_lru a
				WHERE (a.hostName = 'TPMU$seat' OR a.hostName = '%PCU$seat')
				AND a.lastUpdate = (
					SELECT MAX(b.lastUpdate) AS max
					FROM $dbName.BIT_lru b
					WHERE a.hostName = b.hostName
				)";
				
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($serialNumber, $lruType, $lruSubType, $hwPartNumber, $revision, $model, $macAddress, $lastUpdate);
		while ($stmt->fetch()) {
			$lruName = getLruName($lruType, $lruSubType);
			
			$pcuDetails[] = array(
				'serialNumber' => $serialNumber, 
				'lruType' => $lruName,
				'hwPartNumber' => $hwPartNumber,
				'revision' => $revision,
				'mod' => getModVal($model),
				'macAddress' => $macAddress,
				'lastUpdate' => date('Y-m-d', strtotime($lastUpdate)),
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
	
	// Get active failures
	$svduActiveFailures = getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '3', $failuresToKeep, $failuresToRemove);
	$pcuActiveFailures = getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '3', $failuresToKeep, $failuresToRemove);
	
	// Get failures
	$svduFailures = getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '1,3', $failuresToKeep, $failuresToRemove);
	$pcuFailures = getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '1,3', $failuresToKeep, $failuresToRemove);
	
	// Get faults
	$svduFaults = getFaults($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU');
	$pcuFaults = getFaults($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU');
	
	// Get resets
	$svduResets = getResets($dbConnection, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU');
	$pcuResets = getResets($dbConnection, $dbName, $startDateTime, $endDateTime, $seat, 'PCU');
}

$data = array(
	'svduDetails' => $svduDetails,
	'pcuDetails' => $pcuDetails,
	'svduResets' => $svduResets,
	'pcuResets' => $pcuResets,
	'svduActiveFailures' => $svduActiveFailures,
	'pcuActiveFailures' => $pcuActiveFailures,
	'svduFailures' => $svduFailures,
	'pcuFailures' => $pcuFailures,
	'svduFaults' => $svduFaults,
	'pcuFaults' => $pcuFaults,
);

# JSON-encode the response
echo $json_response = json_encode($data, JSON_NUMERIC_CHECK);


////////////////// UTILITIES FUNCTIONS //////////////

function getResets($dbConnection, $dbName, $startDateTime, $endDateTime, $seat, $lruType) {
	$resets = array();
	
	//$query = "SELECT eventName, COUNT(*)  as count
	$query = "SELECT eventName, t.lastUpdate
			FROM (
				SELECT idFlightLeg, eventName, lastUpdate
				FROM $dbName.BIT_events a
				WHERE a.lastUpdate BETWEEN  '$startDateTime' AND  '$endDateTime'";
	
	if($lruType == 'SVDU') {
		$query .= "
				AND eventData LIKE  'SVDU$seat'";
	} else if($lruType == 'PCU') {
		$query .= "
				AND ( eventData LIKE  'TPMU$seat' OR eventData LIKE  '%PCU$seat')";
	}
	
	$query .= "
			) AS t
			INNER JOIN $dbName.SYS_flightPhase b
			ON t.idFlightLeg = b.idFlightLeg
			AND b.idFlightPhase IN ( 4, 5 ) 
			AND t.lastUpdate BETWEEN b.startTime AND b.endTime
			ORDER BY lastUpdate";
	
	//echo $query; exit;
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($eventName, $lastUpdate);
		while ($stmt->fetch()) {
			$eventName = ($eventName == 'CommandedReboot' ) ? 'Commanded' : 'Uncommanded';
			$resets[] = array(
				'lastUpdate' => $lastUpdate, 
				'eventName' => $eventName, 
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
	
	return $resets;
}

function getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove) {
	$failures = array();
	
	//$query = "SELECT t.failureCode, c.failureDesc, COUNT(*)  as count
	$query = "SELECT t.failureCode, c.failureDesc, t.legFailureCount, t.monitorState, t.correlationDate, t.lastUpdate
			FROM (
				SELECT a.idFlightLeg, a.idFailure, a.correlationDate, a.accusedHostName, a.failureCode, a.param1, a.monitorState, a.legFailureCount, a.lastUpdate
				FROM $dbName.BIT_failure a
				WHERE a.correlationDate BETWEEN  '$startDateTime' AND  '$endDateTime'
				AND monitorState IN ($monitorState)";
	
	if($lruType == 'SVDU') {
		$query .= "
				AND accusedHostName LIKE  'SVDU$seat'";
	} else if($lruType == 'PCU') {
		$query .= "
				AND ( accusedHostName LIKE  'TPMU$seat' OR accusedHostName LIKE  '%PCU$seat')";
	}
	
	// Apply customer filter if any
	if(count($failuresToKeep) > 0) {
		$codes = implode(",", $failuresToKeep);
		$query .= " AND a.failureCode IN ($codes)";
	} else if(count($failuresToRemove) > 0) {
		$codes = implode(",", $failuresToRemove);
		$query .= " AND a.failureCode NOT IN ($codes)";
	}
	
	$query .= ") AS t
			INNER JOIN $dbName.SYS_flightPhase b
			ON t.idFlightLeg = b.idFlightLeg
			AND b.idFlightPhase IN ( 4, 5 ) 
			AND t.correlationDate BETWEEN b.startTime AND b.endTime
			LEFT JOIN $mainDB.sys_failureinfo c
			ON t.failureCode = c.failureCode
			ORDER BY t.correlationDate";
	
	//echo $query; exit;
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($failureCode, $failureDesc, $legFailureCount, $monitorState, $correlationDate, $lastUpdate);
		while ($stmt->fetch()) {
			if($monitorState ==1 ) {
				$duration = dateDifference($correlationDate, $lastUpdate, '%h Hours %i Minute %s Seconds');
			} else {
				$duration = '-';
			}
			
			$failures[] = array(
				'failureCode' => $failureCode, 
				'failureDesc' => $failureDesc, 
				'legFailureCount' => $legFailureCount, 
				'monitorState' => getMonitorStateDesc($monitorState),
				'correlationDate' => $correlationDate, 
				'lastUpdate' => $lastUpdate, 
				'duration' => $duration
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
	
	return $failures;
}

function getFaults($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType) {
	$faults = array();

	//$query = "SELECT t.faultCode, c.faultDesc, COUNT(*)  as count
	$query = "SELECT t.faultCode, c.faultDesc, t.reportingHostname, t.monitorState, t.detectionTime, t.clearingTime
			FROM (
				SELECT idFault, a.idFlightLeg, a.hostName, a.reportingHostName, a.faultCode, a.param1, a.param2, a.param3, a.param4, a.detectionTime, a.monitorState, a.insertionTime, a.clearingTime, a.lastUpdate
				FROM $dbName.BIT_fault a
				WHERE a.detectionTime BETWEEN  '$startDateTime' AND  '$endDateTime'";
	
	if($lruType == 'SVDU') {
		$query .= "
				AND hostName LIKE  'SVDU$seat'";
	} else if($lruType == 'PCU') {
		$query .= "
				AND ( hostName LIKE  'TPMU$seat' OR hostName LIKE  '%PCU$seat')";
	}
	
	// Apply customer filter if any
    if(count($faultsToKeep) > 0) {
        $codes = implode(",", $faultsToKeep);
        $query .= " AND a.faultCode IN ($codes)";
    } else if(count($faultsToRemove) > 0) {
        $codes = implode(",", $faultsToRemove);
        $query .= " AND a.faultCode NOT IN ($codes)";
    }
	
	$query .= ") AS t
			INNER JOIN $dbName.SYS_flightPhase b
			ON t.idFlightLeg = b.idFlightLeg
			AND b.idFlightPhase IN ( 4, 5 ) 
			AND t.detectionTime BETWEEN b.startTime AND b.endTime
			LEFT JOIN $mainDB.sys_faultinfo c
			ON t.faultCode = c.faultCode
			ORDER BY t.detectionTime";
	
	//echo $query; exit;
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($faultCode, $faultDesc, $reportingHostname, $monitorState, $detectionTime, $clearingTime);
		while ($stmt->fetch()) {
			if($monitorState ==1 ) {
				$duration = dateDifference($detectionTime, $clearingTime, '%h Hours %i Minute %s Seconds');
			} else {
				$duration = '-';
			}
			
			$faults[] = array(
				'faultCode' => $faultCode, 
				'faultDesc' => $faultDesc,
				'reportingHostname' => $reportingHostname,				
				'monitorState' => getMonitorStateDesc($monitorState), 
				'detectionTime' => $detectionTime, 
				'clearingTime' => $clearingTime, 
				'duration' => $duration, 
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}

	return $faults;
}

?>
