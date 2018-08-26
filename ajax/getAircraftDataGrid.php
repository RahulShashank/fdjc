<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/datesConfiguration.php";

require_once('../engineering/checkEngineeringPermission.php');

$postdata = file_get_contents ( "php://input" );
$request = json_decode ( $postdata, true );

$aircraftId = $request['aircraftId'];
$sqlDump = $request['sqlDump'];	//TODO : Is this required ?
$period = $aircraftStatusPeriod;

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

//No. of records shown per page.
$recordsPerPage = 15000;

//Initial Values
$faultCount = 0;
$currentFaultPage =1;
$faultInformation = array();
$failureCount=0;
$currentFailurePage=1;
$failureInformation = array();
$resetCount=0;
$currentResetPage=1;
$resetsInformation=array();

if($request['pageType'] == 'all' || $request['pageType'] == 'faults')
{
	/* Fault Statistics retrieval */

	$faultCodes = array();
	$faultCodesStringArray = $request['faultCodes'];
	if(is_array($faultCodesStringArray)){
		foreach($faultCodesStringArray as $faultCodeString){
			$fault = explode(':', $faultCodeString);
			array_push($faultCodes, $fault[0]);	//FaultCode pattern = "FaultCode:FaultDescription"
		}
	}
	
	$flightPhases = array();
	$flightPhasesStringArray = $request['flightPhase'];
	if(is_array($flightPhasesStringArray)){
		foreach($flightPhasesStringArray as $flightPhaseString){
			$flightPhase = explode(':', $flightPhaseString);
			array_push($flightPhases, $flightPhase[0]);	//FlightPhase pattern = "FlightPhase:FlightPhaseDescription"
		}
	}

	$multipleCondition = false;
	$query = "SELECT * FROM $dbName.BIT_fault a INNER JOIN $dbName.SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
	$countQuery = "SELECT COUNT(*) as count FROM $dbName.BIT_fault a INNER JOIN $dbName.SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
	if(isset($faultCodes) && is_array($faultCodes) && count($faultCodes) > 0){
		$faultCodesInput = implode( ',' , $faultCodes );
		$query = $query . " a.faultCode IN ($faultCodesInput) ";
		$countQuery = $countQuery . " a.faultCode IN ($faultCodesInput) ";
		$multipleCondition = true;
	}

	$lrus = $request['lrus'];
	$lrusLike = $request['lrusLike'];
	$isLrusSet = false;
	$isLrusLikeSet = false;
	
	if(isset($lrus) && !empty($lrus) && !is_null($lrus)){
		$lrus = mysqli_real_escape_string($dbConnection, $lrus);
		$isLrusSet = true;
	}
	
	if(isset($lrusLike) && !empty($lrusLike) && !is_null($lrusLike)){
		$lrusLike = mysqli_real_escape_string($dbConnection, $lrusLike);
		$isLrusLikeSet = true;
	}
	
	//If both LRU's and (LRU's Like) is set
	if($isLrusSet && $isLrusLikeSet){
		$lrusArray = explode(',', $lrus);

		foreach($lrusArray as &$lru){	//Passing by reference &$lru to change it in loop
			$lru = "'$lru'";
		}
		$lruString = implode(',', $lrusArray);

		if($multipleCondition){
			$query = $query . " AND ( a.hostName IN ($lruString) OR a.hostName LIKE '$lrusLike' ) ";
			$countQuery = $countQuery . " AND ( a.hostName IN ($lruString)  OR a.hostName LIKE '$lrusLike' ) ";			
		}else{
			$query = $query . " ( a.hostName IN ($lruString) OR a.hostName LIKE '$lrusLike' ) ";
			$countQuery = $countQuery . " ( a.hostName IN ($lruString) OR a.hostName LIKE '$lrusLike' ) ";
		}
		$multipleCondition = true;
	}
	
	//If Only LRU's is set
	if($isLrusSet && !$isLrusLikeSet){
		$lrusArray = explode(',', $lrus);

		foreach($lrusArray as &$lru){	//Passing by reference &$lru to change it in loop
			$lru = "'$lru'";
		}
		$lruString = implode(',', $lrusArray);

		if($multipleCondition){
			$query = $query . " AND a.hostName IN ($lruString) ";
			$countQuery = $countQuery . " AND a.hostName IN ($lruString) ";			
		}else{
			$query = $query . " a.hostName IN ($lruString) ";
			$countQuery = $countQuery . " a.hostName IN ($lruString) ";
		}
		$multipleCondition = true;
	}
	
	//If Only LRU's Like is set
	if(!$isLrusSet && $isLrusLikeSet){
		if($multipleCondition){
			$query = $query . " AND  a.hostName LIKE '$lrusLike' ";
			$countQuery = $countQuery . " AND a.hostName LIKE '$lrusLike' ";				
		}else{
			$query = $query . " a.hostName LIKE '$lrusLike' ";
			$countQuery = $countQuery . " a.hostName LIKE '$lrusLike' ";
		}
		$multipleCondition = true;
	}
	
	if(isset($flightPhases) && is_array($flightPhases) && count($flightPhases) > 0){
		$flightPhasesString = implode( ',', $flightPhases);
		if($multipleCondition){
			$query = $query . " AND b.idFlightPhase IN ($flightPhasesString) ";
			$query = $query . " AND a.detectionTime BETWEEN b.startTime AND b.endTime  ";
			
			$countQuery = $countQuery . " AND b.idFlightPhase IN ($flightPhasesString) ";		
			$countQuery = $countQuery . " AND a.detectionTime BETWEEN b.startTime AND b.endTime  ";
		}else{
			$query = $query . " b.idFlightPhase IN ($flightPhasesString) ";
			$query = $query . " AND a.detectionTime BETWEEN b.startTime AND b.endTime  ";
			
			$countQuery = $countQuery . " b.idFlightPhase IN ($flightPhasesString) ";
			$countQuery = $countQuery . " AND a.detectionTime BETWEEN b.startTime AND b.endTime  ";
		}
		$multipleCondition = true;
	}

	$startDateTime = $request['startDateTime'];
	$endDateTime = $request['endDateTime'];
	if(isset($startDateTime) && isset($endDateTime)){
		if($multipleCondition){
			$query = $query . " AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime'  ";
			$countQuery = $countQuery . " AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime'  ";
		}else{
			$query = $query . " a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime' ";
			$countQuery = $countQuery . " a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime' ";
		}
		$multipleCondition = true;
	}


	//Apply Limit
	if(1){
		$startAtRow = ((int) $request['page'] - 1) * $recordsPerPage;
		$query = $query . " LIMIT $startAtRow , $recordsPerPage ";
		$currentFaultPage=1;
	}
	//echo $query;
	//echo "<br/>$countQuery"; exit;

	$countResult = mysqli_query($dbConnection, $countQuery);
	if($countResult){
		$row=mysqli_fetch_array($countResult);
		$faultCount = $row['count'];
	}else{
		echo "Error in $countQuery : " . mysqli_error($dbConnection);
	}

	$result = mysqli_query($dbConnection, $query);
	$faultInformation=array();
	if($result){
		while($row=mysqli_fetch_assoc($result)){
			array_push($faultInformation, $row);
		}
	}else{
		echo "Error in $query : " . mysqli_error($dbConnection);
	}
}

if($request['pageType'] == 'all' || $request['pageType'] == 'failures')
{
	/* Failure Statistics retrieval */
	$failureCodes = array();
	$failureCodesStringArray = $request['failureCodes'];
	if(is_array($failureCodesStringArray)){
		foreach($failureCodesStringArray as $failureCodeString){
			$failure = explode(':', $failureCodeString);
			array_push($failureCodes, trim($failure[0]));	//FailureCode pattern = "FailureCode:FailureDescription"
		}
	}
	
	$flightPhases = array();
	$flightPhasesStringArray = $request['flightPhase'];
	if(is_array($flightPhasesStringArray)){
		foreach($flightPhasesStringArray as $flightPhaseString){
			$flightPhase = explode(':', $flightPhaseString);
			array_push($flightPhases, $flightPhase[0]);	//FlightPhase pattern = "FlightPhase:FlightPhaseDescription"
		}
	}
	
	
	//$faultCodesArray = explode( $faultCodes, ',');
	$multipleCondition = false;
	$query = "SELECT * FROM $dbName.BIT_failure a INNER JOIN $dbName.SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
	$countQuery = "SELECT COUNT(*) as count FROM $dbName.BIT_failure a INNER JOIN $dbName.SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
	if(isset($failureCodes) && is_array($failureCodes) && count($failureCodes) > 0){
		$failureCodesInput = implode( ',' , $failureCodes );
		$query = $query . " a.failureCode IN ($failureCodesInput) ";
		$countQuery = $countQuery . " a.failureCode IN ($failureCodesInput) ";
		$multipleCondition = true;
	}
	
	
	$lrus = $request['lrus'];
	$lrusLike = $request['lrusLike'];
	$isLrusSet = false;
	$isLrusLikeSet = false;
	
	if(isset($lrus) && !empty($lrus) && !is_null($lrus)){
		$lrus = mysqli_real_escape_string($dbConnection, $lrus);
		$isLrusSet = true;
	}
	
	if(isset($lrusLike) && !empty($lrusLike) && !is_null($lrusLike)){
		$lrusLike = mysqli_real_escape_string($dbConnection, $lrusLike);
		$isLrusLikeSet = true;
	}
	
	//If both LRU's and (LRU's Like) is set
	if($isLrusSet && $isLrusLikeSet){
		$lrusArray = explode(',', $lrus);

		foreach($lrusArray as &$lru){	//Passing by reference &$lru to change it in loop
			$lru = "'$lru'";
		}
		$lruString = implode(',', $lrusArray);

		if($multipleCondition){
			$query = $query . " AND ( a.accusedHostName IN ($lruString) OR a.accusedHostName LIKE '$lrusLike' ) ";
			$countQuery = $countQuery . " AND ( a.accusedHostName IN ($lruString)  OR a.accusedHostName LIKE '$lrusLike' ) ";	
		}else{
			$query = $query . " ( a.accusedHostName IN ($lruString) OR a.accusedHostName LIKE '$lrusLike' ) ";
			$countQuery = $countQuery . " ( a.accusedHostName IN ($lruString) OR a.accusedHostName LIKE '$lrusLike' ) ";
		}
		$multipleCondition = true;
	}
	
	//If Only LRU's is set
	if($isLrusSet && !$isLrusLikeSet){
		$lrusArray = explode(',', $lrus);

		foreach($lrusArray as &$lru){	//Passing by reference &$lru to change it in loop
			$lru = "'$lru'";
		}
		$lruString = implode(',', $lrusArray);

		if($multipleCondition){
			$query = $query . " AND a.accusedHostName IN ($lruString) ";
			$countQuery = $countQuery . " AND a.accusedHostName IN ($lruString) ";			
		}else{
			$query = $query . " a.accusedHostName IN ($lruString) ";
			$countQuery = $countQuery . " a.accusedHostName IN ($lruString) ";
		}
		$multipleCondition = true;
	}
	
	//If Only LRU's Like is set
	if(!$isLrusSet && $isLrusLikeSet){
		if($multipleCondition){
			$query = $query . " AND  a.accusedHostName LIKE '$lrusLike' ";
			$countQuery = $countQuery . " AND a.accusedHostName LIKE '$lrusLike' ";				
		}else{
			$query = $query . " a.accusedHostName LIKE '$lrusLike' ";
			$countQuery = $countQuery . " a.accusedHostName LIKE '$lrusLike' ";
		}
		$multipleCondition = true;
	}
	

	if(isset($flightPhases) && is_array($flightPhases) && count($flightPhases) > 0){
		$flightPhasesString = implode( ',', $flightPhases);
		if($multipleCondition){
			$query = $query . " AND b.idFlightPhase IN ($flightPhasesString) ";
			$query = $query . " AND a.correlationDate BETWEEN b.startTime AND b.endTime  ";
			
			$countQuery = $countQuery . " AND b.idFlightPhase IN ($flightPhasesString) ";
			$countQuery = $countQuery . " AND a.correlationDate BETWEEN b.startTime AND b.endTime  ";
		}else{
			$query = $query . " b.idFlightPhase IN ($flightPhasesString) ";
			$query = $query . " AND a.correlationDate BETWEEN b.startTime AND b.endTime  ";
			
			$countQuery = $countQuery . " b.idFlightPhase IN ($flightPhasesString) ";
			$countQuery = $countQuery . " AND a.correlationDate BETWEEN b.startTime AND b.endTime  ";
		}
		$multipleCondition = true;
	}

	$startDateTime = $request['startDateTime'];
	$endDateTime = $request['endDateTime'];
	if(isset($startDateTime) && isset($endDateTime)){
		if($multipleCondition){
			$query = $query . " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'  ";
			$countQuery = $countQuery . " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'  ";
		}else{
			$query = $query . " a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime' ";
			$countQuery = $countQuery . " a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime' ";
		}
		$multipleCondition = true;
	}

	//Apply Limit
	if(1){
		$startAtRow = ((int) $request['page'] - 1) * $recordsPerPage;
		$query = $query . " LIMIT $startAtRow , $recordsPerPage ";
		$currentFailurePage=1;
	}
	//echo $query;

	$countResult = mysqli_query($dbConnection, $countQuery);
	if($countResult){
		$row=mysqli_fetch_array($countResult);
		$failureCount = $row['count'];
	}

	$result = mysqli_query($dbConnection, $query);
	$failureInformation=array();
	if($result){
		while($row=mysqli_fetch_array($result)){
			array_push($failureInformation, $row);
		}
	}
}

if($request['pageType'] == 'all' || $request['pageType'] == 'resets')
{
	/* Resets Statistics retrieval */
	$commandedResets = $request['commandedResets'];
	$unCommandedResets = $request['unCommandedResets'];
	
	$flightPhases = array();
	$flightPhasesStringArray = $request['flightPhase'];
	if(is_array($flightPhasesStringArray)){
		foreach($flightPhasesStringArray as $flightPhaseString){
			$flightPhase = explode(':', $flightPhaseString);
			array_push($flightPhases, $flightPhase[0]);	//FlightPhase pattern = "FlightPhase:FlightPhaseDescription"
		}
	}
	
	// Only Find Resets if commandedResets/UnCommandedResets have been selected.
	if($commandedResets == 'on' || $unCommandedResets == 'on'){
		$multipleCondition = false;
		$query = "SELECT a.eventName, a.eventData, a.idFlightLeg, a.lastUpdate FROM $dbName.BIT_events a INNER JOIN $dbName.SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
		$countQuery = "SELECT COUNT(*) as count FROM $dbName.BIT_events a INNER JOIN $dbName.SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
		if($commandedResets == 'on' && $unCommandedResets == 'on'){
			//Enclose the 'OR' condition in ()
			$query = $query . " ( a.eventName = 'CommandedReboot' OR a.eventName = 'UnCommandedReboot' ) ";
			$countQuery = $countQuery . " ( a.eventName = 'CommandedReboot' OR a.eventName = 'UnCommandedReboot' ) ";
			$multipleCondition = true;
		}else if($commandedResets == 'on' && $unCommandedResets == 'off'){
			$query = $query . " a.eventName = 'CommandedReboot' ";
			$countQuery = $countQuery . " a.eventName = 'CommandedReboot' ";
			$multipleCondition = true;
		}else if($commandedResets == 'off' && $unCommandedResets == 'on'){
			$query = $query . " a.eventName = 'UnCommandedReboot' ";
			$countQuery = $countQuery . " a.eventName = 'UnCommandedReboot' ";
			$multipleCondition = true;
		}

		$lrus = $request['lrus'];
		$lrusLike = $request['lrusLike'];
		$isLrusSet = false;
		$isLrusLikeSet = false;
		
		if(isset($lrus) && !empty($lrus) && !is_null($lrus)){
			$lrus = mysqli_real_escape_string($dbConnection, $lrus);
			$isLrusSet = true;
		}
		
		if(isset($lrusLike) && !empty($lrusLike) && !is_null($lrusLike)){
			$lrusLike = mysqli_real_escape_string($dbConnection, $lrusLike);
			$isLrusLikeSet = true;
		}
		
		//If both LRU's and (LRU's Like) is set
		if($isLrusSet && $isLrusLikeSet){
			$lrusArray = explode(',', $lrus);

			foreach($lrusArray as &$lru){	//Passing by reference &$lru to change it in loop
				$lru = "'$lru'";
			}
			$lruString = implode(',', $lrusArray);

			if($multipleCondition){
				$query = $query . " AND ( a.eventData IN ($lruString) OR a.eventData LIKE '$lrusLike' ) ";
				$countQuery = $countQuery . " AND ( a.eventData IN ($lruString)  OR a.eventData LIKE '$lrusLike' ) ";	
			}else{
				$query = $query . " ( a.eventData IN ($lruString) OR a.eventData LIKE '$lrusLike' ) ";
				$countQuery = $countQuery . " ( a.eventData IN ($lruString) OR a.eventData LIKE '$lrusLike' ) ";
			}
			$multipleCondition = true;
		}
		
		//If Only LRU's is set
		if($isLrusSet && !$isLrusLikeSet){
			$lrusArray = explode(',', $lrus);

			foreach($lrusArray as &$lru){	//Passing by reference &$lru to change it in loop
				$lru = "'$lru'";
			}
			$lruString = implode(',', $lrusArray);

			if($multipleCondition){
				$query = $query . " AND a.eventData IN ($lruString) ";
				$countQuery = $countQuery . " AND a.eventData IN ($lruString) ";			
			}else{
				$query = $query . " a.eventData IN ($lruString) ";
				$countQuery = $countQuery . " a.eventData IN ($lruString) ";
			}
			$multipleCondition = true;
		}
		
		//If Only LRU's Like is set
		if(!$isLrusSet && $isLrusLikeSet){
			if($multipleCondition){
				$query = $query . " AND  a.eventData LIKE '$lrusLike' ";
				$countQuery = $countQuery . " AND a.eventData LIKE '$lrusLike' ";				
			}else{
				$query = $query . " a.eventData LIKE '$lrusLike' ";
				$countQuery = $countQuery . " a.eventData LIKE '$lrusLike' ";
			}
			$multipleCondition = true;
		}
		

		if(isset($flightPhases) && is_array($flightPhases) && count($flightPhases) > 0){
			$flightPhasesString = implode( ',', $flightPhases);
			if($multipleCondition){
				$query = $query . " AND b.idFlightPhase IN ($flightPhasesString) ";
				$query = $query . " AND a.lastUpdate BETWEEN b.startTime AND b.endTime  ";
				
				$countQuery = $countQuery . " AND b.idFlightPhase IN ($flightPhasesString) ";
				$countQuery = $countQuery . " AND a.lastUpdate BETWEEN b.startTime AND b.endTime  ";
			}else{
				$query = $query . " b.idFlightPhase IN ($flightPhasesString) ";
				$query = $query . " AND a.lastUpdate BETWEEN b.startTime AND b.endTime  ";
				
				$countQuery = $countQuery . " b.idFlightPhase IN ($flightPhasesString) ";
				$countQuery = $countQuery . " AND a.lastUpdate BETWEEN b.startTime AND b.endTime  ";
			}
			$multipleCondition = true;
		}

		$startDateTime = $request['startDateTime'];
		$endDateTime = $request['endDateTime'];
		if(isset($startDateTime) && isset($endDateTime)){
			if($multipleCondition){
				$query = $query . " AND a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime'  ";
				$countQuery = $countQuery . " AND a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime'  ";
			}else{
				$query = $query . " a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime' ";
				$countQuery = $countQuery . " a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime' ";
			}
			$multipleCondition = true;
		}

		//Apply Limit
		if(1){
			$startAtRow = ((int) $request['page'] - 1) * $recordsPerPage;
			$query = $query . " LIMIT $startAtRow , $recordsPerPage ";
			$currentResetPage=1;
		}
		//echo $countQuery; exit;
		
		$countResult = mysqli_query($dbConnection, $countQuery);
		if($countResult){
			$row=mysqli_fetch_array($countResult);
			$resetCount = $row['count'];
		}

		$result = mysqli_query($dbConnection, $query);
		$resetsInformation=array();
		if($result){
			while($row=mysqli_fetch_array($result)){
				array_push($resetsInformation, $row);
			}
		}
	}else{
		$resetsInformation = array();
	}
}

$data = array('faultCount' => $faultCount, 'currentFaultPage' => $currentFaultPage, 'faultData' => $faultInformation, 'failureCount' => $failureCount, 'currentFailurePage' => $currentFailurePage, 'failureData' => $failureInformation, 'resetCount' => $resetCount, 'currentResetPage' => $currentResetPage, 'resetsData' => $resetsInformation );
echo json_encode($data);


?>
