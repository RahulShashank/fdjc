<?php

	require_once "../database/connecti_database.php";
	require_once("validateUser.php");
	$approvedRoles = [$roles["admin"], $roles["engineer"]];
	$auth->checkPermission($hash, $approvedRoles);
	
	function aircraftPlatformsArray() {
	
		$dbConnection  = $GLOBALS['dbConnection'];

		$aircraftPlatforms = [];
		$query = "SELECT `name` FROM `aircraft_platforms` ORDER BY `name`";
		$result = mysqli_query ($dbConnection, $query);
		while($row = mysqli_fetch_array($result)) {
			array_push($aircraftPlatforms, $row['name']);
		}
		return $aircraftPlatforms;
	}
	

	
?>
