<?php

	session_start();
	require_once("../common/validateUser.php");
	require_once "../database/connecti_database.php";
	require_once "../common/functions.php";
	require_once "../common/checkPermission.php";
	
	// Performance parameters & timezone set below
	ini_set('memory_limit', '-1');
	ini_set('max_execution_time', 300);
	
	$approvedRoles = [$roles["admin"]];
	$auth->checkPermission($hash, $approvedRoles);	
	
	$postdata = file_get_contents ( "php://input" );
	$request = json_decode ( $postdata, true );

	$deletionStatus = array('status' => 1, 'message' => "Flight Data Deleted Successfully");
	
	$aircraftId = $request['aircraftId'];
	// Find out the aircraft database to be selected or sqlDump if provided.
	if($aircraftId != '') {
		// Get information to display in header
		$query = "SELECT a.databaseName FROM aircrafts a WHERE a.id = $aircraftId ";
		$result = mysqli_query($dbConnection, $query );

		if ($result && mysqli_num_rows ( $result ) > 0) {
			$row = mysqli_fetch_array ( $result );
			$db = $row['databaseName'];
		}
	}else{
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Aircraft Id is invalid";
		echo json_encode($deletionStatus);
		exit;
	}
	
	$selected = mysqli_select_db($dbConnection, $db);
	if(!$selected){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't select Aircraft DB";
		echo json_encode($deletionStatus);
		exit;
	}
	
	//echo "selected db $db";
	
	$flightLegIdInput = trim($request['flightLegIds']);
	$eventFlightLegIdInput = trim($request['eventFlightLegIds']);
	
	$patternForRange = '/^\d+-\d+$/';
	$patternForCommaSeparatedNumbers = '/^\d+(,\d+)*$/';
	
	if(preg_match($patternForRange, $flightLegIdInput)){
		$flightLegIdRangeValues = explode('-',$flightLegIdInput);
		if($flightLegIdRangeValues[0] > $flightLegIdRangeValues[1]){
			$deletionStatus['status'] = -1;
			$deletionStatus['message'] = "InValid Range specified for Flight Leg Id's";
			echo json_encode($deletionStatus);
			exit;
		}
		$flightLegIdsArray = array();
		for($i = $flightLegIdRangeValues[0]; $i <= $flightLegIdRangeValues[1]; $i++){
			array_push($flightLegIdsArray, $i);
		}
	}else if(preg_match($patternForCommaSeparatedNumbers, $flightLegIdInput)){
		$flightLegIdsArray = explode(',', $flightLegIdInput);
	}else{
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "InValid input for Flight Leg Id's";
		echo json_encode($deletionStatus);
		exit;
	}
	
	/* //Required for Event data deletiion based on EventFlightLegId.
	
	if(preg_match($patternForRange, $eventFlightLegIdInput)){
		$eventFlightLegIdRangeValues = explode('-',$eventFlightLegIdInput);
		if($eventFlightLegIdRangeValues[0] > $eventFlightLegIdRangeValues[1]){
			$deletionStatus['status'] = -1;
			$deletionStatus['message'] = "InValid Range specified for Event Flight Leg Id's";
			echo json_encode($deletionStatus);
			exit;
		}
		$eventFlightLegIdsArray = array();
		for($i = $eventFlightLegIdRangeValues[0]; $i <= $eventFlightLegIdRangeValues[1]; $i++){
			array_push($eventFlightLegIdsArray, $i);
		}
	}else if(preg_match($patternForCommaSeparatedNumbers, $eventFlightLegIdInput)){
		$eventFlightLegIdsArray = explode(',', $eventFlightLegIdInput);
	}else{
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "InValid input for Event Flight Leg Id's";
		echo json_encode($deletionStatus);
		exit;
	}
	*/
	
	//Begin deletion for all the tables in the selected Aircraft DB

	$flightLegIdsForQuery = implode(',',$flightLegIdsArray);
	

	//$eventFlightLegIdsForQuery = implode(',',$eventFlightLegIdsArray);
	
	$query = "DELETE FROM BIT_lru WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from BIT_lru ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM BIT_confSw WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from BIT_confSw ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM BIT_events WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from BIT_events ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM BIT_failure WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from BIT_failure ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM BIT_fault WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from BIT_fault ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM BIT_extAppEvent WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from BIT_extAppEvent ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM BIT_removedLru WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from BIT_removedLru ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM flightstatus WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from flightstatus ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	/*Alternate logic to delete Event data corresponding to flightLeg */
	$query = " SELECT a.createDate, a.lastUpdate FROM SYS_flight a WHERE a.idFlightLeg IN ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if($result){
		while($row = mysqli_fetch_array($result)){
			$deleteServiceEventsQuery = " DELETE FROM services_events WHERE eventTime BETWEEN '" .$row['createDate']. "' AND '" .$row['lastUpdate']. "' ";
			$deleteServiceEventResult = mysqli_query($dbConnection, $deleteServiceEventsQuery);
			if(!$deleteServiceEventResult)
				$deletionStatus['message'] = $deletionStatus['message'] . "Mysql Error : " . mysqli_error($dbConnection);

			$deleteEventOffloadsQuery = " DELETE FROM offloads WHERE offloadDate BETWEEN '" .$row['createDate']. "' AND '" .$row['lastUpdate']. "' ";
			$deleteServiceEventResult = mysqli_query($dbConnection, $deleteEventOffloadsQuery);
			if(!$deleteServiceEventResult)
				$deletionStatus['message'] = $deletionStatus['message'] . "Mysql Error : " . mysqli_error($dbConnection);
		}
	}
	
	$query = "DELETE FROM SYS_flightPhase WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from SYS_flightPhase ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM SYS_flight WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from SYS_flight ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM offloads WHERE idFlightLeg in ($flightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from offloads ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
		
	/*
	$query = "DELETE FROM services_events WHERE idEventFlightLeg IN ($eventFlightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from services_events ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	
	$query = "DELETE FROM offloads WHERE idEventFlightLeg IN ($eventFlightLegIdsForQuery) ";
	$result = mysqli_query($dbConnection, $query);
	if(!$result){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Couldn't Delete from offloads ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	}
	*/
	

	
	if(!mysqli_commit($dbConnection)){
		$deletionStatus['status'] = -1;
		$deletionStatus['message'] = "Error in Commiting to DB ".mysqli_error($dbConnection);
		echo json_encode($deletionStatus);
		exit;
	};
		
	echo json_encode($deletionStatus);
?>
