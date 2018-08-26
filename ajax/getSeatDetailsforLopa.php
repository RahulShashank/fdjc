<?php
ini_set ( 'memory_limit', '-1' );
ini_set ( 'max_execution_time', 300 );

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/customerFilters.php";
require_once ('../engineering/checkEngineeringPermission.php');
require_once "../common/seatAnalyticsData.php";

$aircraftId = $_REQUEST ['aircraftId'];
$sqlDump = $_REQUEST ['sqlDump'];
$startDateTime = $_REQUEST ['startDateTime'];
$endDateTime = $_REQUEST ['endDateTime'];
$seat = $_REQUEST ['seat'];

$faultCode = $_REQUEST ['faultCode'];
$failureCode = $_REQUEST ['failureCode'];
$ImpactedServicesCode = $_REQUEST ['ImpactedServicesCode'];
$flightPhasescode = $_REQUEST ['flightPhases'];
$resetsCode = $_REQUEST ['resetCode'];
$countValue = $_REQUEST ['countValue'];

// echo "Ajax call OK for a/c with id $aircraftId and $seat between $startDateTime and $endDateTime<br><br>";

$failuresToRemove = getFailuresToRemove ();
$failuresToKeep = getFailuresToKeep ();
$faultsToRemove = getFaultsToRemove ();
$faultsToKeep = getFaultsToKeep ();

$flightPhases = getAllFlightPhases ();

$svduDetails = array ();
$pcuDetails = array ();
$svduActiveFailures = array ();
$pcuActiveFailures = array ();
$svduFailures = array ();
$pcuFailures = array ();
$svduFaults = array ();
$pcuFaults = array ();
$svduResets = array ();
$pcuResets = array ();
$svduImpactedServices = array ();
$pcuImpactedServices = array ();
$svduEvents = array ();
$pcuEvents = array ();

if (isset ( $aircraftId ) || isset ( $sqlDump )) {
	
	if (isset ( $aircraftId )) {
		// Get database name
		$query = "SELECT databaseName from aircrafts WHERE tailsign='" . $aircraftId . "'";
		
		if ($stmt = $dbConnection->prepare ( $query )) {
			// $stmt->bind_param("i", $aircraftId);
			$stmt->execute ();
			$stmt->bind_result ( $dbName );
			$stmt->fetch ();
			$stmt->close ();
		} else {
			echo "Error creating statement";
			exit ();
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
	
	if ($stmt = $dbConnection->prepare ( $query )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $serialNumber, $lruType, $lruSubType, $hwPartNumber, $revision, $model, $macAddress, $lastUpdate );
		while ( $stmt->fetch () ) {
			$lruName = getLruName ( $lruType, $lruSubType );
			
			$svduDetails [] = array (
					'serialNumber' => $serialNumber,
					'lruType' => $lruName,
					'hwPartNumber' => $hwPartNumber,
					'revision' => $revision,
					'mod' => getModVal ( $model ),
					'macAddress' => $macAddress,
					'lastUpdate' => date ( 'Y-m-d', strtotime ( $lastUpdate ) ) 
			);
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $query";
		exit ();
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
	
	if ($stmt = $dbConnection->prepare ( $query )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $serialNumber, $lruType, $lruSubType, $hwPartNumber, $revision, $model, $macAddress, $lastUpdate );
		while ( $stmt->fetch () ) {
			$lruName = getLruName ( $lruType, $lruSubType );
			
			$pcuDetails [] = array (
					'serialNumber' => $serialNumber,
					'lruType' => $lruName,
					'hwPartNumber' => $hwPartNumber,
					'revision' => $revision,
					'mod' => getModVal ( $model ),
					'macAddress' => $macAddress,
					'lastUpdate' => date ( 'Y-m-d', strtotime ( $lastUpdate ) ) 
			);
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $query";
		exit ();
	}
	
	if (count ( $flightPhasescode ) > 0 and $flightPhasescode != null) {
		$flightPhasescode = $flightPhasescode;
	} else {
		$flightPhasescode = $flightPhases;
	}
	
	// Get active failures
	$svduActiveFailures = getActiveFailures ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '3', $failuresToKeep, $failuresToRemove, $flightPhasescode, $failureCode );
	$pcuActiveFailures = getActiveFailures ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '3', $failuresToKeep, $failuresToRemove, $flightPhasescode, $failureCode );
	
	// Get failures
	$svduFailures = getFailures ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '1,3', $failuresToKeep, $failuresToRemove, $flightPhasescode, $failureCode );
	$pcuFailures = getFailures ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '1,3', $failuresToKeep, $failuresToRemove, $flightPhasescode, $failureCode );
	
	// Get faults
	$svduFaults = getFaults ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', $flightPhasescode, $countValue, $faultCode );
	$pcuFaults = getFaults ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', $flightPhasescode, $countValue, $faultCode );
	
	// Get resets
	$svduResets = getResets ( $dbConnection, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', $flightPhasescode, $resetsCode );
	$pcuResets = getResets ( $dbConnection, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', $flightPhasescode, $resetsCode );
	
	// Get Impacted Services
	$svduImpactedServices = getImpactedServices ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '1,3', $failuresToKeep, $failuresToRemove, $flightPhasescode, $ImpactedServicesCode, $countValue );
	$pcuImpactedServices = getImpactedServices ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '1,3', $failuresToKeep, $failuresToRemove, $flightPhasescode, $ImpactedServicesCode, $countValue );
	
	// Get App Events
	$svduEvents = getEvents ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '1,3', $failuresToKeep, $failuresToRemove, $flightPhasescode );
	$pcuEvents = getEvents ( $dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '1,3', $failuresToKeep, $failuresToRemove, $flightPhasescode );
}

$data = array (
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
		'svduImpactedServices' => $svduImpactedServices,
		'pcuImpactedServices' => $pcuImpactedServices,
		'svduEvents' => $svduEvents,
		'pcuEvents' => $pcuEvents 
);

// JSON-encode the response
echo $json_response = json_encode ( $data, JSON_NUMERIC_CHECK );

// //////////////// UTILITIES FUNCTIONS //////////////
function getResets($dbConnection, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $flightPhasescode, $resetsCode) {
	$resets = array ();
	
	// $query = "SELECT eventName, COUNT(*) as count
	
	$query = "SELECT  eventName, t.lastUpdate, eventInfo,t.idFlightLeg,b.idFlightPhase
			FROM (
				SELECT eventInfo, idFlightLeg, eventName, lastUpdate
				FROM $dbName.BIT_events a
				WHERE (DATE(a.lastUpdate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
	
	if ($resetsCode != '') {
		
		$query .= "AND eventName IN ($resetsCode) ";
	}
	
	if ($lruType == 'SVDU') {
		$query .= "
				AND eventData LIKE  'SVDU$seat'";
	} else if ($lruType == 'PCU') {
		$query .= "
				AND ( eventData LIKE  'TPMU$seat' OR eventData LIKE  '%PCU$seat')";
	}
	
	$query .= "
			) AS t
			INNER JOIN $dbName.SYS_flightPhase b
			ON t.idFlightLeg = b.idFlightLeg
			AND b.idFlightPhase IN ($flightPhasescode) 
			AND t.lastUpdate BETWEEN b.startTime AND b.endTime
			ORDER BY lastUpdate";
	
	// echo $query; exit;
	
	if ($stmt = $dbConnection->prepare ( $query )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $eventName, $lastUpdate, $eventInfo, $idFlightLeg, $idFlightPhase );
		$stmt->store_result ();
		while ( $stmt->fetch () ) {
			$eventName = ($eventName == 'CommandedReboot') ? 'Commanded' : 'Uncommanded';
			$getflightPhase = getFlightPhaseDesc ( $idFlightPhase );
			$flightPhase = $idFlightPhase . " - " . $getflightPhase;
			$resets [] = array (
					'lastUpdate' => $lastUpdate,
					'eventName' => $eventName,
					'eventInfo' => $eventInfo,
					'idFlightLeg' => $idFlightLeg,
					'idFlightPhase' => $flightPhase 
			);
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $query";
		exit ();
	}
	
	return $resets;
}
function getActiveFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove, $flightPhasescode, $failureCode) {
	$failures = array ();
	
	// $query = "SELECT t.failureCode, c.failureDesc, COUNT(*) as count
	$query = "SELECT t.idFlightLeg,t.failureCode, c.failureDesc, t.legFailureCount, t.monitorState, t.correlationDate, t.lastUpdate,t.idFlightPhase FROM (SELECT a.idFlightLeg, a.idFailure, a.correlationDate, a.accusedHostName, a.failureCode, a.param1, a.monitorState, a.legFailureCount, a.lastUpdate,b.idFlightPhase FROM $dbName.BIT_failure a INNER JOIN $dbName.SYS_flightPhase b ON a.idFlightLeg = b.idFlightLeg 	AND b.idFlightPhase IN ($flightPhasescode)  AND a.correlationDate >= b.startTime AND a.correlationDate <= b.endTime";
	
	if ($lruType == 'SVDU') {
		$query .= "
				AND accusedHostName LIKE  'SVDU$seat'";
	} else if ($lruType == 'PCU') {
		$query .= "
				AND ( accusedHostName LIKE  'TPMU$seat' OR accusedHostName LIKE  '%PCU$seat')";
	}
	$query .= " AND monitorState = 3 WHERE (DATE(a.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
	// Apply customer filter if any
	if (count ( $failureCode ) > 0 and $failureCode != '') {
		$query .= " AND a.failureCode IN ($failureCode)  ";
	} else {
		if (count ( $failuresToKeep ) > 0) {
			$codes = implode ( ",", $failuresToKeep );
			$query .= " AND a.failureCode IN ($codes)";
		} else if (count ( $failuresToRemove ) > 0) {
			$codes = implode ( ",", $failuresToRemove );
			$query .= " AND a.failureCode NOT IN ($codes)";
		}
	}
	
	$query .= ") AS t LEFT JOIN $mainDB.sys_failureinfo c ON t.failureCode = c.failureCode ORDER BY t.correlationDate";
	
	// echo $query; exit;
	
	if ($stmt = $dbConnection->prepare ( $query )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $idFlightLeg, $failureCode, $failureDesc, $legFailureCount, $monitorState, $correlationDate, $lastUpdate, $idFlightPhase );
		$stmt->store_result ();
		while ( $stmt->fetch () ) {
			if ($monitorState == 1) {
				$duration = dateDifference ( $correlationDate, $lastUpdate, '%h Hours %i Minute %s Seconds' );
			} else {
				$duration = '-';
			}
			$getflightPhase = getFlightPhaseDesc ( $idFlightPhase );
			$flightPhase = $idFlightPhase . " - " . $getflightPhase;
			$failures [] = array (
					'idFlightLeg' => $idFlightLeg,
					'failureCode' => $failureCode,
					'failureDesc' => $failureDesc,
					'legFailureCount' => $legFailureCount,
					'monitorState' => getMonitorStateDesc ( $monitorState ),
					'correlationDate' => $correlationDate,
					'lastUpdate' => $lastUpdate,
					'duration' => $duration,
					'idFlightPhase' => $flightPhase 
			);
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $query";
		exit ();
	}
	
	return $failures;
}

function getMonitorStateValue(){
	foreach ($_REQUEST['monitorState'] as $ts){
		$monitorStateValue.=  $ts . ",";	
	}
	$monitorStateValue = rtrim($monitorStateValue, ",");
	return $monitorStateValue;
}
function getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove, $flightPhasescode, $failureCode) {
	$failures = array ();
	$monitorStateValue=getMonitorStateValue();
	error_log('Failures monitorStateValue :'.$monitorStateValue);
	// $query = "SELECT t.failureCode, c.failureDesc, COUNT(*) as count
	$query = "SELECT DISTINCT t.idFlightLeg, t.failureCode, c.failureDesc, t.legFailureCount, t.monitorState, t.correlationDate, t.lastUpdate
			FROM (
				SELECT a.idFlightLeg, a.idFailure, a.correlationDate, a.accusedHostName, a.failureCode, a.param1, a.monitorState, a.legFailureCount, a.lastUpdate
				FROM $dbName.BIT_failure a
				WHERE (DATE(a.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))
				AND monitorState IN ($monitorState)";
	
	if ($lruType == 'SVDU') {
		$query .= "
				AND accusedHostName LIKE  'SVDU$seat'";
	} else if ($lruType == 'PCU') {
		$query .= "
				AND ( accusedHostName LIKE  'TPMU$seat' OR accusedHostName LIKE  '%PCU$seat')";
	}
	
	// Apply customer filter if any
	if (count ( $failureCode ) > 0 and $failureCode != '') {
		$query .= " AND a.failureCode IN ($failureCode)  ";
	} else {
		if (count ( $failuresToKeep ) > 0) {
			$codes = implode ( ",", $failuresToKeep );
			$query .= " AND a.failureCode IN ($codes)";
		} else if (count ( $failuresToRemove ) > 0) {
			$codes = implode ( ",", $failuresToRemove );
			$query .= " AND a.failureCode NOT IN ($codes)";
		}
	}
	
	$query .= ") AS t
			INNER JOIN $dbName.SYS_flightPhase b
			ON t.idFlightLeg = b.idFlightLeg
			AND b.idFlightPhase IN ( $flightPhasescode ) ";
			//AND ((t.monitorState=3 AND t.correlationDate<=b.endTime) OR (t.monitorState=1 AND ((t.correlationDate BETWEEN b.startTime AND b.endTime) OR (t.lastUpdate BETWEEN b.startTime and b.endTime) OR (t.correlationDate<=b.startTime AND t.lastUpdate>=b.endTime))))";
	if($monitorStateValue=='3'){
		$query .= " AND ((t.monitorState=3 AND t.correlationDate<=b.endTime)) ";
	}else if($monitorStateValue=='1'){
		$query .= " AND (t.monitorState=1 AND ((t.correlationDate BETWEEN b.startTime AND b.endTime) OR (t.lastUpdate BETWEEN b.startTime and b.endTime) OR (t.correlationDate<=b.startTime AND t.lastUpdate>=b.endTime))) ";
	}else {
		$query .= " AND ((t.monitorState=3 AND t.correlationDate<=b.endTime) OR (t.monitorState=1 AND ((t.correlationDate BETWEEN b.startTime AND b.endTime) OR (t.lastUpdate BETWEEN b.startTime and b.endTime) OR (t.correlationDate<=b.startTime AND t.lastUpdate>=b.endTime)))) ";
	}
	
	$query .="	LEFT JOIN $mainDB.sys_failureinfo c
			ON t.failureCode = c.failureCode
			ORDER BY t.correlationDate";
	
	if ($stmt = $dbConnection->prepare ( $query )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $idFlightLeg, $failureCode, $failureDesc, $legFailureCount, $monitorState, $correlationDate, $lastUpdate );
		$stmt->store_result ();
		while ( $stmt->fetch () ) {
			if ($monitorState == 1) {
				$duration = dateDifference ( $correlationDate, $lastUpdate, '%h Hours %i Minute %s Seconds' );
			} else {
				$duration = '-';
			}
			$flytPhase = getFlightPhase ( $dbConnection, $mainDB, $dbName, $idFlightLeg, $correlationDate, $lastUpdate, $flightPhasescode );
			$getflightPhase = getFlightPhaseDesc ( $flytPhase );
			$flightPhase = $flytPhase . " - " . $getflightPhase;
			$failures [] = array (
					'idFlightLeg' => $idFlightLeg,
					'failureCode' => $failureCode,
					'failureDesc' => $failureDesc,
					'legFailureCount' => $legFailureCount,
					'monitorState' => getMonitorStateDesc ( $monitorState ),
					'correlationDate' => $correlationDate,
					'lastUpdate' => $lastUpdate,
					'duration' => $duration,
					'idFlightPhase' => $flightPhase 
			);
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $query";
		exit ();
	}
	
	return $failures;
}
function getFlightPhase($dbConnection, $mainDB, $dbName, $idFlightLeg, $correlationDate, $lastUpdate, $flightPhasescode) {
	$flytQuery = "SELECT idFlightPhase FROM $dbName.SYS_flightPhase b WHERE b.idFlightPhase IN ($flightPhasescode) AND b.idFlightLeg=$idFlightLeg AND ( (STR_TO_DATE('$correlationDate', '%Y-%m-%d %T') BETWEEN b.startTime AND b.endTime) OR (STR_TO_DATE('$lastUpdate', '%Y-%m-%d %T') BETWEEN b.startTime AND b.endTime) ) ORDER BY idFlightPhase LIMIT 0,1";
	
	if ($stmt = $dbConnection->prepare ( $flytQuery )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $idFlightPhase );
		while ( $stmt->fetch () ) {
			
			$flightPhase = $idFlightPhase;
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $flytQuery";
		exit ();
	}
	
	return $flightPhase;
}
function getFaults($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $flightPhasescode, $countValue, $faultCode) {
	$faults = array ();
	$monitorStateValue=getMonitorStateValue();
	// $query = "SELECT t.faultCode, c.faultDesc, COUNT(*) as count
	$query = "SELECT t.faultCode,c.faultDesc, t.reportingHostName, t.monitorState,t.detectionTime,t.clearingTime,t.idFlightLeg FROM (SELECT DISTINCT a.hostName,a.serialNumber,a.reportingHostName,a.faultCode,a.monitorState,a.detectionTime,a.clearingTime,a.idFlightLeg
    FROM $dbName.BIT_fault a  INNER JOIN $dbName.SYS_flightPhase b ON a.idFlightLeg = b.idFlightLeg"; 
    
	if($monitorStateValue=='3'){
		$query.=" AND ((a.monitorState=3 AND a.detectionTime<=b.endTime)) ";
	}else if($monitorStateValue=='1'){
		$query.=" AND (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime)))";
	}else {
		$query.=" AND ((a.monitorState=3 AND a.detectionTime<=b.endTime) OR (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime))))";
	}
	
	if ($lruType == 'SVDU') {
		$query .= "
				AND hostName LIKE  'SVDU$seat'";
	} else if ($lruType == 'PCU') {
		$query .= "
				AND ( hostName LIKE  'TPMU$seat' OR hostName LIKE  '%PCU$seat')";
	}
	$query .= "AND b.idFlightPhase IN ($flightPhasescode) ";
	// Apply customer filter if any
	if (count ( $faultCode ) > 0 and $faultCode != '') {
		$query .= " AND a.faultCode IN ($faultCode)";
	} else {
		if (count ( $faultsToKeep ) > 0) {
			$codes = implode ( ",", $faultsToKeep );
			$query .= " AND a.faultCode IN ($codes)";
		} else if (count ( $faultsToRemove ) > 0) {
			$codes = implode ( ",", $faultsToRemove );
			$query .= " AND a.faultCode NOT IN ($codes)";
		}
	}
	
	$query .= "AND (DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE)) )AS t LEFT JOIN $mainDB.sys_faultinfo c ON t.faultCode = c.faultCode ORDER BY t.detectionTime ";	
	
	if ($stmt = $dbConnection->prepare ( $query )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $faultCode, $faultDesc, $reportingHostname, $monitorState, $detectionTime, $clearingTime, $idFlightLeg );
		$stmt->store_result ();
		while ( $stmt->fetch () ) {
			if ($monitorState == 1) {
				$duration = dateDifference ( $detectionTime, $clearingTime, '%h Hours %i Minute %s Seconds' );
			} else {
				$duration = '-';
			}
			$flytPhase = getFlightPhase ( $dbConnection, $mainDB, $dbName, $idFlightLeg, $detectionTime, $clearingTime, $flightPhasescode );
			$getflightPhase = getFlightPhaseDesc ( $flytPhase );
			$flightPhase = $flytPhase . " - " . $getflightPhase;
			$faults [] = array (
					'faultCode' => $faultCode,
					'faultDesc' => $faultDesc,
					'reportingHostname' => $reportingHostname,
					'monitorState' => getMonitorStateDesc ( $monitorState ),
					'detectionTime' => $detectionTime,
					'clearingTime' => $clearingTime,
					'duration' => $duration,
					'idFlightLeg' => $idFlightLeg,
					'idFlightPhase' => $flightPhase 
			);
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $query";
		exit ();
	}
	
	return $faults;
}
function getImpactedServices($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove, $flightPhasescode, $ImpactedServicesCode, $countValue) {
	$impactedServices = array ();
	$monitorStateValue=getMonitorStateValue();
	$query = "SELECT t.failureCode,c.failureDesc,c.failureImpact,t.correlationDate,t.monitorState,t.accusedHostName,t.lastUpdate,
				t.idFlightLeg,d.name,d.description,t.idFlightPhase FROM (SELECT DISTINCT a.failureCode,a.correlationDate,a.monitorState,a.accusedHostName,a.lastUpdate,a.idFlightLeg ,a.idService,b.idFlightPhase
				FROM $dbName.bit_servicefailure a INNER JOIN $dbName.SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) ";	
	if($monitorStateValue=='3'){
		$query.=" AND ((a.monitorState=3 AND a.correlationDate<=b.endTime)) ";
	}else if($monitorStateValue=='1'){
		$query.=" AND (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))) ";
	}else {
		$query.=" AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime)))) ";
	}
	
	if($lruType == 'SVDU') {
		$query .= "
				AND accusedHostName LIKE  'SVDU$seat'";
	} else if($lruType == 'PCU') {
		$query .= "
				AND ( accusedHostName LIKE  'TPMU$seat' OR accusedHostName LIKE  '%PCU$seat')";
	}
	
	// Apply customer filter if any
	if(count($ImpactedServicesCode)>0 and $ImpactedServicesCode!=''){
		$query.=" AND a.failureCode IN ($ImpactedServicesCode)  ";
	}
	
	$query .= ") AS t LEFT JOIN $mainDB.sys_serviceFailureInfo c ON t.failureCode = c.failureCode 
		LEFT JOIN $mainDB.sys_services d ON t.idService = d.idService WHERE (DATE(t.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE)) ORDER BY t.correlationDate";
	error_log('impact services...'.$query);
	if ($stmt = $dbConnection->prepare ( $query )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $failureCode, $failureDesc, $failureImpact, $correlationDate, $monitorState, $accusedHostName, $lastUpdate, $idFlightLeg, $name, $description, $flightPhase );
		$stmt->store_result ();
		while ( $stmt->fetch () ) {
			// $flytPhase = getFlightPhase ( $dbConnection, $mainDB, $dbName, $idFlightLeg, $correlationDate, $lastUpdate, $flightPhasescode );
			$getflightPhase = getFlightPhaseDesc ( $flightPhase );
			$flightPhase = $flightPhase . " - " . $getflightPhase;			
			
			if ($monitorState == 1) {
				$duration = dateDifference ( $correlationDate, $lastUpdate, '%h Hours %i Minute %s Seconds' );
			} else {
				$duration = '-';
			}
			$impactedServices [] = array (
					'idFailure' => $idFailure,
					'correlationDate' => $correlationDate,
					'accusedHostName' => $accusedHostName,
					'failureCode' => $failureCode,
					'monitorState' => getMonitorStateDesc ( $monitorState ),
					'lastUpdate' => $lastUpdate,
					'failureDesc' => $failureDesc,
					'failureImpact' => $failureImpact,
					'name' => $name,
					'description' => $description,
					'duration' => $duration,
					'idFlightLeg' => $idFlightLeg,
					'idFlightPhase' => $flightPhase 
			);
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $query";
		exit ();
	}
	
	return $impactedServices;
}
function getEvents($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove, $flightPhasescode) {
	$events = array ();
	
	$query = " SELECT a.idExtAppEvent, a.hostName, a.reportingHostName, a.faultCode, a.param1, a.param2, a.param3, a.param4, a.detectionTime,a.idFlightLeg, b.idFlightPhase FROM $dbName.BIT_extappevent a INNER JOIN $dbName.SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
	
	if ($lruType == 'SVDU') {
		$query .= "	hostName LIKE  'SVDU$seat'";
	} else if ($lruType == 'PCU') {
		$query .= "	( hostName LIKE  'TPMU$seat' OR hostName LIKE  '%PCU$seat')";
	}
	$query .= "	AND  a.detectionTime BETWEEN b.startTime AND b.endTime AND b.idFlightPhase IN ($flightPhasescode) AND ((DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))) ORDER BY a.detectionTime";
	// Apply customer filter if any
	/*
	 * if(count($faultsToKeep) > 0) {
	 * $codes = implode(",", $faultsToKeep);
	 * $query .= " AND a.faultCode IN ($codes)";
	 * } else if(count($faultsToRemove) > 0) {
	 * $codes = implode(",", $faultsToRemove);
	 * $query .= " AND a.faultCode NOT IN ($codes)";
	 * }
	 *
	 * if (count ( $faultCode ) > 0 and $faultCode != '') {
	 * $query .= " AND a.faultCode IN ($faultCode) ";
	 * }
	 */
	
	/*
	 * $query .= "AND ((DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE)))) AS t
	 * LEFT JOIN $mainDB.sys_faultinfo c
	 * ON t.faultCode = c.faultCode
	 *
	 * LEFT JOIN $dbName.SYS_flightPhase b
	 * ON t.idFlightLeg = b.idFlightLeg AND b.idFlightPhase IN ($flightPhasescode)
	 * AND t.detectionTime BETWEEN b.startTime AND b.endTime ORDER BY t.detectionTime";
	 */
	
	if ($stmt = $dbConnection->prepare ( $query )) {
		// $stmt->bind_param("s", $flightLegs);
		$stmt->execute ();
		$stmt->bind_result ( $idExtAppEvent, $hostName, $reportingHostName, $faultCode, $param1, $param2, $param3, $param4, $detectionTime, $idFlightLeg, $idFlightPhase );
		while ( $stmt->fetch () ) {
			// $flightPhase = getFlightPhaseDesc($idFlightPhase);
			// $faultDesc = getExtAppEventDesc($faultCode);
			$getflightPhase = getFlightPhaseDesc ( $idFlightPhase );
			$flightPhase = $idFlightPhase . " - " . $getflightPhase;
			
			$events [] = array (
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
					'flightPhase' => $flightPhase,
					'idFlightLeg' => $idFlightLeg 
			);
		}
		
		$stmt->close ();
	} else {
		echo "Error creating statement for $query";
		exit ();
	}
	return $events;
}

?>
