<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/customerFilters.php";
require_once('../engineering/checkEngineeringPermission.php');
require_once '../libs/PHPExcel/PHPExcel.php';
require_once '../libs/PHPExcel/PHPExcel/IOFactory.php';
require_once "../common/seatAnalyticsData.php";

$aircraftId = $_REQUEST['tailsign'];
$sqlDump = $_REQUEST['sqlDump'];
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];
$seat = $_REQUEST['seat'];
$formatType = $_REQUEST['formatType'];

$faultCode = $_REQUEST['faultCode'];
$failureCode = $_REQUEST['failureCode'];
$ImpactedServicesCode = $_REQUEST['ImpactedServicesCode'];
$flightPhasescode = $_REQUEST['flightPhases'];
$resetsCode = $_REQUEST['resetCode'];
$countValue = $_REQUEST['countValue'];
$svduUnit='SVDU'.$seat;
$pcuUnit='PCU'.$seat;
//echo "Ajax call OK for a/c with id $aircraftId and $seat between $startDateTime and $endDateTime<br><br>";

$failuresToRemove = getFailuresToRemove();
$failuresToKeep = getFailuresToKeep();
$faultsToRemove = getFaultsToRemove();
$faultsToKeep = getFaultsToKeep();

$flightPhases = getAllFlightPhases();

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
$svduImpactedServices = array();
$pcuImpactedServices = array();
$svduEvents = array();
$pcuEvents = array();

if( isset($aircraftId) || isset($sqlDump)  ) {

	if(isset($aircraftId)) {
		// Get database name
		$query = "SELECT databaseName from aircrafts WHERE tailsign='".$aircraftId."'";
			
		if( $stmt = $dbConnection->prepare($query) ) {
			//$stmt->bind_param("i", $aircraftId);
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
	
	if(count($flightPhasescode)>0 and $flightPhasescode!=null){
		$flightPhasescode=$flightPhasescode;
	}else{
		$flightPhasescode=$flightPhases;
	}
	
	// Get active failures
	$svduActiveFailures = getActiveFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '3', $failuresToKeep, $failuresToRemove,$flightPhasescode,$failureCode);
	$pcuActiveFailures = getActiveFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '3', $failuresToKeep, $failuresToRemove,$flightPhasescode,$failureCode);
	
	// Get failures
	$svduFailures = getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '1,3', $failuresToKeep, $failuresToRemove,$flightPhasescode,$failureCode);
	$pcuFailures = getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '1,3', $failuresToKeep, $failuresToRemove,$flightPhasescode,$failureCode);
	
	// Get faults
	$svduFaults = getFaults($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU',$flightPhasescode,$countValue,$faultCode);
	$pcuFaults = getFaults($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU',$flightPhasescode,$countValue,$faultCode);
	
	
	// Get resets
	$svduResets = getResets($dbConnection, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU',$flightPhasescode,$resetsCode);
	$pcuResets = getResets($dbConnection, $dbName, $startDateTime, $endDateTime, $seat, 'PCU',$flightPhasescode,$resetsCode);
	
	//Get Impacted Services
	$svduImpactedServices = getImpactedServices($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '1,3', $failuresToKeep, $failuresToRemove,$flightPhasescode,$ImpactedServicesCode,$countValue);
	$pcuImpactedServices = getImpactedServices($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '1,3', $failuresToKeep, $failuresToRemove,$flightPhasescode,$ImpactedServicesCode,$countValue);
	
	//Get App Events
	$svduEvents = getEvents($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'SVDU', '1,3', $failuresToKeep, $failuresToRemove,$flightPhasescode);
	$pcuEvents = getEvents($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, 'PCU', '1,3', $failuresToKeep, $failuresToRemove,$flightPhasescode);
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
	'svduImpactedServices' => $svduImpactedServices,
	'pcuImpactedServices' => $pcuImpactedServices,
	'svduEvents' => $svduEvents,
	'pcuEvents' => $pcuEvents
);

# JSON-encode the response
//echo $json_response = json_encode($data, JSON_NUMERIC_CHECK);


////////////////// UTILITIES FUNCTIONS //////////////
function getResets($dbConnection, $dbName, $startDateTime, $endDateTime, $seat, $lruType,$flightPhasescode,$resetsCode) {
	$resets = array();
	
	//$query = "SELECT eventName, COUNT(*)  as count
	
	$query = "SELECT  eventName, t.lastUpdate, eventInfo,t.idFlightLeg,b.idFlightPhase
			FROM (
				SELECT eventInfo, idFlightLeg, eventName, lastUpdate
				FROM $dbName.BIT_events a
				WHERE (DATE(a.lastUpdate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
				
				if($resetsCode!=''){
				
					$query.="AND eventName IN ($resetsCode) ";
				}
	
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
			AND b.idFlightPhase IN ($flightPhasescode) 
			AND t.lastUpdate BETWEEN b.startTime AND b.endTime
			ORDER BY lastUpdate";
	
	//echo $query; exit;	
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($eventName, $lastUpdate, $eventInfo,$idFlightLeg,$idFlightPhase);
		while ($stmt->fetch()) {
			$eventName = ($eventName == 'CommandedReboot' ) ? 'Commanded' : 'Uncommanded';
			$getflightPhase = getFlightPhaseDesc ( $idFlightPhase );
			$idFlightPhase = $idFlightPhase . " - " . $getflightPhase;
			$resets[] = array(
				'lastUpdate' => $lastUpdate, 
				'eventName' => $eventName, 
				'eventInfo' => $eventInfo,
				'idFlightLeg' => $idFlightLeg,
				'idFlightPhase' => $idFlightPhase
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
	
	return $resets;
}

function getActiveFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove,$flightPhasescode,$failureCode) {
	$failures = array();
	
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
	
	//echo $query; exit;
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($idFlightLeg,$failureCode, $failureDesc, $legFailureCount, $monitorState, $correlationDate, $lastUpdate,$idFlightPhase);
		while ($stmt->fetch()) {
			if($monitorState ==1 ) {
				$duration = dateDifference($correlationDate, $lastUpdate, '%h Hours %i Minute %s Seconds');
			} else {
				$duration = '-';
			}
			$getflightPhase = getFlightPhaseDesc ( $idFlightPhase );
			$idFlightPhase = $idFlightPhase . " - " . $getflightPhase;
			
			$failures[] = array(
				'idFlightLeg' => $idFlightLeg, 
				'failureCode' => $failureCode, 
				'failureDesc' => $failureDesc, 
				'legFailureCount' => $legFailureCount, 
				'monitorState' => getMonitorStateDesc($monitorState),
				'correlationDate' => $correlationDate, 
				'lastUpdate' => $lastUpdate, 
				'duration' => $duration,
				'idFlightPhase' => $idFlightPhase
				);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
	
	return $failures;
}

function getFailures($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove,$flightPhasescode,$failureCode) {
	$failures = array();
	
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
	$monitorState = rtrim($_REQUEST['monitorState'], ",");
	error_log('Monitor State : '.$monitorState);
	
	$query .= ") AS t
			INNER JOIN $dbName.SYS_flightPhase b
			ON t.idFlightLeg = b.idFlightLeg
				AND b.idFlightPhase IN ($flightPhasescode) ";

	if($monitorState=='3'){
		$query .= " AND ((t.monitorState=3 AND t.correlationDate<=b.endTime)) ";
	}else if($monitorState=='1'){
		$query .= " AND (t.monitorState=1 AND ((t.correlationDate BETWEEN b.startTime AND b.endTime) OR (t.lastUpdate BETWEEN b.startTime and b.endTime) OR (t.correlationDate<=b.startTime AND t.lastUpdate>=b.endTime))) ";
	}else {
		$query .= " AND ((t.monitorState=3 AND t.correlationDate<=b.endTime) OR (t.monitorState=1 AND ((t.correlationDate BETWEEN b.startTime AND b.endTime) OR (t.lastUpdate BETWEEN b.startTime and b.endTime) OR (t.correlationDate<=b.startTime AND t.lastUpdate>=b.endTime)))) ";
	}
	
    $query .= " LEFT JOIN $mainDB.sys_failureinfo c
			ON t.failureCode = c.failureCode
			ORDER BY t.correlationDate";
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($idFlightLeg,$failureCode, $failureDesc, $legFailureCount, $monitorState, $correlationDate, $lastUpdate);
		$stmt->store_result();
		while ($stmt->fetch()) {
			if($monitorState ==1 ) {
				$duration = dateDifference($correlationDate, $lastUpdate, '%h Hours %i Minute %s Seconds');
			} else {
				$duration = '-';
			}
			$flytPhase=getFlightPhase($dbConnection, $mainDB, $dbName,$idFlightLeg,$correlationDate,$lastUpdate,$flightPhasescode);
			$getflightPhase = getFlightPhaseDesc ( $flytPhase );
			$flytPhase = $flytPhase . " - " . $getflightPhase;
			$failures[] = array(
							'idFlightLeg' => $idFlightLeg, 
							'failureCode' => $failureCode, 
							'failureDesc' => $failureDesc, 
							'legFailureCount' => $legFailureCount, 
							'monitorState' => getMonitorStateDesc($monitorState),
							'correlationDate' => $correlationDate, 
							'lastUpdate' => $lastUpdate, 
							'duration' => $duration,
							'idFlightPhase' => $flytPhase
						);
			
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
	
	return $failures;
}

function getFlightPhase($dbConnection, $mainDB, $dbName,$idFlightLeg,$correlationDate,$lastUpdate, $flightPhasescode){

	$flytQuery="SELECT idFlightPhase FROM $dbName.SYS_flightPhase b WHERE b.idFlightPhase IN ($flightPhasescode) AND b.idFlightLeg=$idFlightLeg AND ( (STR_TO_DATE('$correlationDate', '%Y-%m-%d %T') BETWEEN b.startTime AND b.endTime) OR (STR_TO_DATE('$lastUpdate', '%Y-%m-%d %T') BETWEEN b.startTime AND b.endTime) ) ORDER BY idFlightPhase LIMIT 0,1";
	
	if( $stmt = $dbConnection->prepare($flytQuery) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($idFlightPhase);
		while ($stmt->fetch()) {
			
			$flightPhase=$idFlightPhase;
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $flytQuery";
		exit;
	}
	
	return $flightPhase;
}

function getFaults($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType,$flightPhasescode,$countValue,$faultCode) {
	$faults = array();

	//$query = "SELECT t.faultCode, c.faultDesc, COUNT(*)  as count
	$query = "SELECT t.faultCode,c.faultDesc, t.reportingHostName, t.monitorState,t.detectionTime,t.clearingTime,t.idFlightLeg FROM (SELECT DISTINCT a.hostName,a.serialNumber,a.reportingHostName,a.faultCode,a.monitorState,a.detectionTime,a.clearingTime,a.idFlightLeg
    FROM $dbName.BIT_fault a  INNER JOIN $dbName.SYS_flightPhase b ON a.idFlightLeg = b.idFlightLeg";
	 
    //$query .= " AND ((a.monitorState=3 AND a.detectionTime<=b.endTime) OR (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime AND b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime)))) ";
    $monitorState = rtrim($_REQUEST['monitorState'], ",");
	error_log('Monitor State : '.$monitorState);
	
	if($monitorState=='3'){
		$query.=" AND ((a.monitorState=3 AND a.detectionTime<=b.endTime)) ";
	}else if($monitorState=='1'){
		$query.=" AND (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime)))";
	}else {
		$query.=" AND ((a.monitorState=3 AND a.detectionTime<=b.endTime) OR (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime))))";
	}
	
	if($lruType == 'SVDU') {
		$query .= "
				AND hostName LIKE  'SVDU$seat'";
	} else if($lruType == 'PCU') {
		$query .= "
				AND ( hostName LIKE  'TPMU$seat' OR hostName LIKE  '%PCU$seat')";
	}
	$query.="AND b.idFlightPhase IN ($flightPhasescode) ";
	// Apply customer filter if any
	if(count($faultCode)>0 and $faultCode!=''){
		$query.=" AND a.faultCode IN ($faultCode)";
	}else{
		if(count($faultsToKeep) > 0) {
			$codes = implode(",", $faultsToKeep);
			$query .= " AND a.faultCode IN ($codes)";
		} else if(count($faultsToRemove) > 0) {
			$codes = implode(",", $faultsToRemove);
			$query .= " AND a.faultCode NOT IN ($codes)";
		}
	}
	
	$query .= "AND (DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE)) )AS t LEFT JOIN $mainDB.sys_faultinfo c ON t.faultCode = c.faultCode ORDER BY t.detectionTime ";
	
	//echo $query; exit;
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($faultCode, $faultDesc, $reportingHostname, $monitorState, $detectionTime, $clearingTime,$idFlightLeg);
		$stmt->store_result();
		while ($stmt->fetch()) {
			if($monitorState ==1 ) {
				$duration = dateDifference($detectionTime, $clearingTime, '%h Hours %i Minute %s Seconds');
			} else {
				$duration = '-';
			}
			$flytPhase=getFlightPhase($dbConnection, $mainDB, $dbName,$idFlightLeg,$detectionTime,$clearingTime,$flightPhasescode);
			$getflightPhase = getFlightPhaseDesc ( $flytPhase );
			$flytPhase = $flytPhase . " - " . $getflightPhase;
			$faults[] = array(
				'faultCode' => $faultCode, 
				'faultDesc' => $faultDesc,
				'reportingHostname' => $reportingHostname,				
				'monitorState' => getMonitorStateDesc($monitorState), 
				'detectionTime' => $detectionTime, 
				'clearingTime' => $clearingTime, 
				'duration' => $duration, 
				'idFlightLeg' => $idFlightLeg, 
				'idFlightPhase' => $flytPhase
			);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}

	return $faults;
}


function getImpactedServices($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove,$flightPhasescode,$ImpactedServicesCode,$countValue) {
	$impactedServices = array();	
	
	$query = "SELECT t.failureCode,c.failureDesc,c.failureImpact,t.correlationDate,t.monitorState,t.accusedHostName,t.lastUpdate,
				t.idFlightLeg,d.name,d.description,t.idFlightPhase FROM (SELECT DISTINCT a.failureCode,a.correlationDate,a.monitorState,a.accusedHostName,a.lastUpdate,a.idFlightLeg ,a.idService,b.idFlightPhase
				FROM $dbName.bit_servicefailure a INNER JOIN $dbName.SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) ";
	$monitorState = rtrim($_REQUEST['monitorState'], ",");
	error_log('Monitor State : '.$monitorState);
	
	if($monitorState=='3'){
		$query.=" AND ((a.monitorState=3 AND a.correlationDate<=b.endTime)) ";
	}else if($monitorState=='1'){
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
	
	if( $stmt = $dbConnection->prepare($query) ) {
		//$stmt->bind_param("s", $flightLegs);
		$stmt->execute();
		$stmt->bind_result($failureCode, $failureDesc, $failureImpact, $correlationDate, $monitorState,$accusedHostName,  $lastUpdate, $idFlightLeg, $name, $description,$idFlightPhase);
			
		while ($stmt->fetch()) {
			$getflightPhase = getFlightPhaseDesc ( $idFlightPhase );
			$idFlightPhase = $idFlightPhase . " - " . $getflightPhase;
			
			if($monitorState ==1 ) {
				$duration = dateDifference($correlationDate, $lastUpdate, '%h Hours %i Minute %s Seconds');
			} else {
				$duration = '-';
			}			
			$impactedServices[] = array(
					'idFailure' => $idFailure, 
					'correlationDate' => $correlationDate, 
					'accusedHostName' => $accusedHostName, 
					'failureCode' => $failureCode, 
					'monitorState' => getMonitorStateDesc($monitorState),  
					'lastUpdate' => $lastUpdate,
					'failureDesc' => $failureDesc,
					'failureImpact' => $failureImpact,
					'name' => $name,
					'description' => $description,
					'duration' => $duration,
					'idFlightLeg' => $idFlightLeg,
					'idFlightPhase' => $idFlightPhase
				);
		}

		$stmt->close();
	} else {
		echo "Error creating statement for $query";
		exit;
	}
	
	return $impactedServices;
}

function getEvents($dbConnection, $mainDB, $dbName, $startDateTime, $endDateTime, $seat, $lruType, $monitorState, $failuresToKeep, $failuresToRemove,$flightPhasescode) {
	$events = array();
	
	$query = " SELECT a.idExtAppEvent, a.hostName, a.reportingHostName, a.faultCode, a.param1, a.param2, a.param3, a.param4, a.detectionTime,a.idFlightLeg, b.idFlightPhase FROM $dbName.BIT_extappevent a INNER JOIN $dbName.SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
	
	if ($lruType == 'SVDU') {
		$query .= "	hostName LIKE  'SVDU$seat'";
	} else if ($lruType == 'PCU') {
		$query .= "	( hostName LIKE  'TPMU$seat' OR hostName LIKE  '%PCU$seat')";
	}
	$query .= "	AND  a.detectionTime BETWEEN b.startTime AND b.endTime AND b.idFlightPhase IN ($flightPhasescode) AND ((DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))) ORDER BY a.detectionTime";	

	
		if( $stmt = $dbConnection->prepare($query) ) {
			//$stmt->bind_param("s", $flightLegs);
			$stmt->execute();
			$stmt->bind_result($idExtAppEvent, $hostName, $reportingHostName, $faultCode, $param1, $param2, $param3, $param4, $detectionTime, $idFlightLeg, $idFlightPhase);
			while ($stmt->fetch()) {
				//$flightPhase = getFlightPhaseDesc($idFlightPhase);
				//$faultDesc = getExtAppEventDesc($faultCode);
				$getflightPhase = getFlightPhaseDesc($idFlightPhase);
				$flightPhase = $idFlightPhase." -".$getflightPhase;
				
				$events[] = array(
					'idExtAppEvent' => $idExtAppEvent, 
					'hostName' => $hostName, 
					'reportingHostName' => $reportingHostName, 
					'faultCode' => $faultCode, 
					'faultDesc' => '-', 
					'param1' => $param1, 
					'param2' => $param2, 
					'param3' => $param3, 
					'param4' => $param4, 
					'detectionTime' => $detectionTime,
					'flightPhase' => $flightPhase,
					'idFlightLeg' => $idFlightLeg
				);
			}

			$stmt->close();
		} else {
			echo "Error creating statement for $query";
			exit;
		}
		return $events;
	}

	function exportData(){

	}	

if(!empty($data)){
	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();

	// Create a first sheet, representing sales data
	$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Seat '.$seat.' - '.$svduUnit)->getStyle('A1:A1')->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));
		$objPHPExcel->getActiveSheet()->setCellValue('A2', 'Summary -SVDU')->getStyle('A2:A2')->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('A3', 'Serial Number :');
		$objPHPExcel->getActiveSheet()->setCellValue('A4', 'LRU Type :');
		$objPHPExcel->getActiveSheet()->setCellValue('A5', 'HW Part Number :');
		$objPHPExcel->getActiveSheet()->setCellValue('A6', 'Revision :');
		$objPHPExcel->getActiveSheet()->setCellValue('A7', 'Mod :');
		$objPHPExcel->getActiveSheet()->setCellValue('A8', 'Mac Address :');
		$objPHPExcel->getActiveSheet()->setCellValue('A9', 'Last Update :');
		
		$objPHPExcel->getActiveSheet()->getStyle('A2')->applyFromArray(
			array(
				'fill' => array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array('rgb' => '04225e')
				)
			)
		);
	
		if(count($data['svduDetails'])>=1){
			$objPHPExcel->getActiveSheet()->setCellValue('B3', $data['svduDetails'][0]['serialNumber']);
			$objPHPExcel->getActiveSheet()->setCellValue('B4', $data['svduDetails'][0]['lruType']);
			$objPHPExcel->getActiveSheet()->setCellValue('B5', $data['svduDetails'][0]['hwPartNumber']);
			$objPHPExcel->getActiveSheet()->setCellValue('B6', $data['svduDetails'][0]['revision']);
			$objPHPExcel->getActiveSheet()->setCellValue('B7', $data['svduDetails'][0]['mod']);
			$objPHPExcel->getActiveSheet()->setCellValue('B8', $data['svduDetails'][0]['macAddress']);
			$objPHPExcel->getActiveSheet()->setCellValue('B9', $data['svduDetails'][0]['lastUpdate']);
		}else{
			$objPHPExcel->getActiveSheet()->setCellValue('B3', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B4', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B5', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B6', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B7', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B8', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B9', '-');
		}		
		
		$objPHPExcel->getActiveSheet()->setCellValue('A11', 'Summary -PCU')->getStyle('A11:A11')->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));		
		$objPHPExcel->getActiveSheet()->setCellValue('A13', 'Serial Number :');
		$objPHPExcel->getActiveSheet()->setCellValue('A14', 'LRU Type :');
		$objPHPExcel->getActiveSheet()->setCellValue('A15', 'HW Part Number :');
		$objPHPExcel->getActiveSheet()->setCellValue('A16', 'Revision :');
		$objPHPExcel->getActiveSheet()->setCellValue('A17', 'Mod :');
		$objPHPExcel->getActiveSheet()->setCellValue('A18', 'Mac Address :');
		$objPHPExcel->getActiveSheet()->setCellValue('A19', 'Last Update :');

	$objPHPExcel->getActiveSheet()->getStyle('A11')->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);		
		
		if(count($data['pcuDetails'])>=1){
			$objPHPExcel->getActiveSheet()->setCellValue('B13', $data['pcuDetails'][0]['serialNumber']);
			$objPHPExcel->getActiveSheet()->setCellValue('B14', $data['pcuDetails'][0]['lruType']);
			$objPHPExcel->getActiveSheet()->setCellValue('B15', $data['pcuDetails'][0]['hwPartNumber']);
			$objPHPExcel->getActiveSheet()->setCellValue('B16', $data['pcuDetails'][0]['revision']);
			$objPHPExcel->getActiveSheet()->setCellValue('B17', $data['pcuDetails'][0]['mod']);
			$objPHPExcel->getActiveSheet()->setCellValue('B18', $data['pcuDetails'][0]['macAddress']);
			$objPHPExcel->getActiveSheet()->setCellValue('B19', $data['pcuDetails'][0]['lastUpdate']);
		}else{
			$objPHPExcel->getActiveSheet()->setCellValue('B13', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B14', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B15', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B16', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B17', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B18', '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B19', '-');
		}
		
		
			
			$objPHPExcel->getActiveSheet()->getStyle('A22:B22')->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);	
		//Seat Information for Resets
		$objPHPExcel->getActiveSheet()->setCellValue('A21', 'Resets - SVDU')->getStyle('A21:A21')->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));		
		$objPHPExcel->getActiveSheet()->setCellValue('A22', 'FlightLeg')->getStyle('A22:E22')->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B22', 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('C22', 'Time');
		$objPHPExcel->getActiveSheet()->setCellValue('D22', 'Type');
		$objPHPExcel->getActiveSheet()->setCellValue('E22', 'Reset Reason');
		
		$objPHPExcel->getActiveSheet()->getStyle('A22:E22')->applyFromArray(
				array(
						'fill' => array(
								'type' => PHPExcel_Style_Fill::FILL_SOLID,
								'color' => array('rgb' => '04225e')
						)
				)
				);
		
		$ctr=22;
		if(count($data['svduResets'])>0){
			foreach ($data['svduResets'] as $Resetvalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $Resetvalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $Resetvalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $Resetvalue['lastUpdate']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $Resetvalue['eventName']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $Resetvalue['eventInfo']);
			}
		}else{
			$ctr++;
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
			$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
			$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
			$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
		}
		$ctr++;
		
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Resets - PCU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));		
		$ctr++;
		//$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Time')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'FlightLeg')->getStyle('A'.$ctr.':E'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'Time');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'Type');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'Reset Reason');
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'E'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		
		if(count($data['pcuResets'])>0){
			foreach ($data['pcuResets'] as $Resetvalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $Resetvalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $Resetvalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $Resetvalue['lastUpdate']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $Resetvalue['eventName']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $Resetvalue['eventInfo']);
			}
		}else{
			$ctr++;
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
			$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
			$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
			$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
			$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
		}
		
		//Seat Information for Active Failures	
		$ctr++;		
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Active Failures - SVDU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));
		
		$ctr++;
		
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'FlightLeg')->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'FailureCode');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'FailureDesc');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'LegFailureCount');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'MonitorState');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'CorrelationDate');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'LastUpdate');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'Duration');
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		
		if(count($data['svduActiveFailures'])>0){
			foreach ($data['svduActiveFailures'] as $activeFailurevalue) {
				$ctr++;				
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $activeFailurevalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $activeFailurevalue['failureCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $activeFailurevalue['failureDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $activeFailurevalue['legFailureCount']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $activeFailurevalue['monitorState']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $activeFailurevalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $activeFailurevalue['correlationDate']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $activeFailurevalue['lastUpdate']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $activeFailurevalue['duration']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
		}
		$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Active Failures - PCU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));	
		
		$ctr++;		
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'FlightLeg')->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'FailureCode');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'FailureDesc');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'LegFailureCount');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'MonitorState');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'CorrelationDate');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'LastUpdate');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'Duration');
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		if(count($data['pcuActiveFailures'])>0){
			foreach ($data['pcuActiveFailures'] as $activeFailurevalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $activeFailurevalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $activeFailurevalue['failureCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $activeFailurevalue['failureDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $activeFailurevalue['legFailureCount']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $activeFailurevalue['monitorState']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $activeFailurevalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $activeFailurevalue['correlationDate']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $activeFailurevalue['lastUpdate']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $activeFailurevalue['duration']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
		}
		
		
	//Seat Information for Failures
		$ctr++;$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Failures - SVDU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));
		
		$ctr++;		
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'FlightLeg')->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'FailureCode');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'FailureDesc');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'LegFailureCount');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'MonitorState');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'CorrelationDate');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'LastUpdate');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'Duration');
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		
		if(count($data['svduFailures'])>0){
			foreach ($data['svduFailures'] as $failurevalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $failurevalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $failurevalue['failureCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $failurevalue['failureDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $failurevalue['legFailureCount']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $failurevalue['monitorState']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $failurevalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $failurevalue['correlationDate']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $failurevalue['lastUpdate']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $failurevalue['duration']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
		}
		$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Failures - PCU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));
	
		$ctr++;		
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'FlightLeg')->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'FailureCode');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'FailureDesc');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'LegFailureCount');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'MonitorState');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'CorrelationDate');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'LastUpdate');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'Duration');
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		if(count($data['pcuFailures'])>0){
			foreach ($data['pcuFailures'] as $failurevalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $failurevalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $failurevalue['failureCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $failurevalue['failureDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $failurevalue['legFailureCount']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $failurevalue['monitorState']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $failurevalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $failurevalue['correlationDate']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $failurevalue['lastUpdate']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $failurevalue['duration']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
		}
		
	//Seat Information for Faults
		$ctr++;$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Faults - SVDU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));	
		
		$ctr++;		
		
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Flightleg')->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'Code');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'Description');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'Reporting Host');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'State');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'Detection Time');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'Clearing Time');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'Duration');
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		
		if(count($data['svduFaults'])>0){
			foreach ($data['svduFaults'] as $faultsvalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $faultsvalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $faultsvalue['faultCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $faultsvalue['faultDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $faultsvalue['reportingHostname']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $faultsvalue['monitorState']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $faultsvalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $faultsvalue['detectionTime']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $faultsvalue['clearingTime']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $faultsvalue['duration']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
		}
		$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Faults - PCU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));
		
		$ctr++;				
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Flightleg')->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'Code');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'Description');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'Reporting Host');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'State');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'Detection Time');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'Clearing Time');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'Duration');
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'I'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		if(count($data['pcuFaults'])>0){
			foreach ($data['pcuFaults'] as $faultsvalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $faultsvalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $faultsvalue['faultCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $faultsvalue['faultDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $faultsvalue['reportingHostname']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $faultsvalue['monitorState']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $faultsvalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $faultsvalue['detectionTime']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $faultsvalue['clearingTime']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $faultsvalue['duration']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
		}
		
		//Seat Information for Impacted Services
		$ctr++;$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Impacted Services - SVDU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));	
		
		$ctr++;	
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Flight Leg')->getStyle('A'.$ctr.':'.'K'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'Correlation Date');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'Flight Phase');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'Failure Code');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'Failure Description');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'Failure Impact');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'Service Name');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'Service Description');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'MonitorState');
		$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, 'LastUpdate');
		$objPHPExcel->getActiveSheet()->setCellValue('K'.$ctr, 'Duration');
		//$objPHPExcel->getActiveSheet()->setCellValue('K'.$ctr, 'CorrectiveAction');
		
		
		
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'K'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		if(count($data['svduImpactedServices'])>0){
			foreach ($data['svduImpactedServices'] as $impactedServicesvalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $impactedServicesvalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $impactedServicesvalue['correlationDate']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $impactedServicesvalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $impactedServicesvalue['failureCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $impactedServicesvalue['failureDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $impactedServicesvalue['failureImpact']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $impactedServicesvalue['name']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $impactedServicesvalue['description']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $impactedServicesvalue['monitorState']);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, $impactedServicesvalue['lastUpdate']);
				$objPHPExcel->getActiveSheet()->setCellValue('K'.$ctr, $impactedServicesvalue['duration']);
				//$objPHPExcel->getActiveSheet()->setCellValue('L'.$ctr, $impactedServicesvalue['correctiveAction']);
				
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('K'.$ctr, '-');
		}
		$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Impacted Services - PCU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));
		
		$ctr++;				
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Flight Leg')->getStyle('A'.$ctr.':'.'K'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'Correlation Date');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'Flight Phase');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'Failure Code');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'Failure Description');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'Failure Impact');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'Service Name');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'Service Description');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'MonitorState');
		$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, 'LastUpdate');
		$objPHPExcel->getActiveSheet()->setCellValue('K'.$ctr, 'Duration');
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'K'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		if(count($data['pcuImpactedServices'])>0){
			foreach ($data['pcuImpactedServices'] as $impactedServicesvalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $impactedServicesvalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $impactedServicesvalue['correlationDate']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $impactedServicesvalue['idFlightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $impactedServicesvalue['failureCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $impactedServicesvalue['failureDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $impactedServicesvalue['failureImpact']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $impactedServicesvalue['name']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $impactedServicesvalue['description']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $impactedServicesvalue['monitorState']);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, $impactedServicesvalue['lastUpdate']);
				$objPHPExcel->getActiveSheet()->setCellValue('K'.$ctr, $impactedServicesvalue['duration']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('K'.$ctr, '-');
		}
		
		//Seat Information for Events
		$ctr++;$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Events - SVDU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));	
		
		$ctr++;		

		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Flight Leg')->getStyle('A'.$ctr.':'.'J'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'DetectionTime');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'ReportingHostName');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'FaultCode');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'FaultDescription');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'Param1');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'Param2');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'Param3');
		$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, 'Param4');
		
		
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'J'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);			
		
		if(count($data['svduEvents'])>0){
			foreach ($data['svduEvents'] as $eventsvalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $eventsvalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $eventsvalue['detectionTime']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $eventsvalue['flightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $eventsvalue['reportingHostName']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $eventsvalue['faultCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $eventsvalue['faultDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $eventsvalue['param1']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $eventsvalue['param2']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $eventsvalue['param3']);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, $eventsvalue['param4']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, '-');
		}
		$ctr++;
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Events - PCU')->getStyle('A'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => '000000'))));
		
		$ctr++;				
		$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, 'Flight Leg')->getStyle('A'.$ctr.':'.'J'.$ctr)->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
		$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, 'DetectionTime');
		$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, 'FlightPhase');
		$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, 'ReportingHostName');
		$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, 'FaultCode');
		$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, 'FaultDescription');
		$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, 'Param1');
		$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, 'Param2');
		$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, 'Param3');
		$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, 'Param4');
		
		$objPHPExcel->getActiveSheet()->getStyle('A'.$ctr.':'.'J'.$ctr)->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
		
		if(count($data['pcuEvents'])>0){
			foreach ($data['pcuEvents'] as $eventsvalue) {
				$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $eventsvalue['idFlightLeg']);
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $eventsvalue['detectionTime']);
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $eventsvalue['flightPhase']);
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $eventsvalue['reportingHostName']);
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $eventsvalue['faultCode']);
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $eventsvalue['faultDesc']);
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $eventsvalue['param1']);
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, $eventsvalue['param2']);
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, $eventsvalue['param3']);
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, $eventsvalue['param4']);
			}
		}else{
			$ctr++;
				$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('H'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('I'.$ctr, '-');
				$objPHPExcel->getActiveSheet()->setCellValue('J'.$ctr, '-');
		}
		
		
	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Seat Information');	
	$objPHPExcel->setActiveSheetIndex(0);
	if($formatType=="xls"){
		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="SeatInformation.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	}else{
		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename="SeatInformation.csv"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
		$objWriter->save('php://output');
	}

	
}

?>