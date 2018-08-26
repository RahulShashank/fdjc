<?php

	require_once "../database/connecti_database.php";
	require_once("validateUser.php");
	$approvedRoles = [$roles["admin"], $roles["engineer"]];
	$auth->checkPermission($hash, $approvedRoles);
	
	function aircraftTypesArray() {
	
		$dbConnection  = $GLOBALS['dbConnection'];

		$aircraftTypes = [];
		$query = "SELECT `type` FROM `aircraft_types` ORDER BY `type`";
		$result = mysqli_query ($dbConnection, $query);
		if($result){
			while($row = mysqli_fetch_array($result)) {
				array_push($aircraftTypes, $row['type']);
			}
		}
		return $aircraftTypes;
	}
	
	function aircraftConfigTypesArray() {
	
		$dbConnection  = $GLOBALS['dbConnection'];

		$aircraftConfigTypes = array();
		$query = "SELECT `id`, `configurationName` FROM `aircraft_seatinfo` ORDER BY `configurationName`";
		$result = mysqli_query ($dbConnection, $query);
		if($result){
			while($row = mysqli_fetch_array($result)) {
				array_push($aircraftConfigTypes, array('id'=>$row['id'], 'configurationName'=>$row['configurationName']));
			}
		}
		return $aircraftConfigTypes;
	}
	
?>
