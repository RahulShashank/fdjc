<?php
	ini_set('max_execution_time', 600);
	date_default_timezone_set("GMT");

	require_once "../database/connecti_database.php";
	require_once('../engineering/checkEngineeringPermission.php');

	include("getSeatAvailabilityData.php");
	
	$postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
	$airlineId = $request->airlineId;


	function getAvantAirlineTypes($dbConnection, $airlineId) {
		$airlineTypes = [];
		$query = "SELECT DISTINCT type FROM `aircrafts` WHERE airlineId=$airlineId AND type NOT IN ('A319', 'A320' ,'A321')";
		// $query = "SELECT DISTINCT type FROM `aircrafts` WHERE airlineId=$airlineId AND type IN ('A320')";
		$result = mysqli_query ($dbConnection, $query);
		while($row = mysqli_fetch_array($result)) {
			array_push($airlineTypes, $row[0]);
		} 
		return $airlineTypes;
	}
	
	function getAircraftDatabases($dbConnection, $type, $airlineId) {
		$airlineDatabases = [];
		$query = "SELECT DISTINCT databaseName FROM `aircrafts` WHERE airlineId=". $airlineId ." AND type='". $type ."'";
		$result = mysqli_query ($dbConnection, $query);
		if ($result) {
			while($row = mysqli_fetch_array($result)) {
				$aircraft = $row[0];
				$aircraftDB = connectToDB($dbConnection, $aircraft);
				if (getSeats($aircraftDB) > 0) {
					array_push($airlineDatabases, $row[0]);
				}
			}
		}
		return $airlineDatabases;
	}
	
	$startTime = date("H:i:s", time());
	$types = getAvantAirlineTypes($dbConnection, $airlineId);
	$mainDB = "banalytics";
	$airlineDatabaseJSON = [];
	foreach ($types as $type) {
		$airlineDatabaseJSON[$type] = getAircraftDatabases($dbConnection, $type, $airlineId);
		$dbConnection = connectToDB($dbConnection, $mainDB);
	}
	$returnArray = [];
	$returnArray["success"] = false;
	$keys = array_keys($airlineDatabaseJSON);
	foreach ($keys as $key) {
		$aircrafts = count($airlineDatabaseJSON[$key]);
		$aircraftArray = $airlineDatabaseJSON[$key];
		$total = array_fill(0, 12, 0);
		$hasData = array_fill(0, 12, 0);
		foreach ($aircraftArray as $aircraft) {
			connectToDB($dbConnection, $aircraft);
			if (mysqli_connect_errno())
			{
				echo "Failed to connect to MySQL: " . mysqli_connect_error();
			}
			// Set autocommit to off
			// mysqli_autocommit($dbconnection,FALSE);
			$getSeatAvailability = getSeatAvailability($dbConnection);
			$availArray = $getSeatAvailability["seatAvailabilities"];
			$datesArray = $getSeatAvailability["startDates"];
			for ($i = 0; $i < 12; $i++) {
				if($availArray[$i] > 0) {
					$total[$i] += $availArray[$i];
					$hasData[$i] += 1;
				}
			}
			// mysqli_close($dbconnection);
		}
		//var_dump($total);
		//echo $aircrafts;
		for ($i = 0; $i < 12; $i++) {
			if ($aircrafts > 0) {
				if ($hasData[$i] > 0) {
					$total[$i] = round(($total[$i] / $hasData[$i]), 2);
				}
			}		
		}
		if ($datesArray = [0]) {
			$datesArray = getDates();
		}
		$returnArray[$key] = $total;
		$returnArray["dates"] = $datesArray;
		$returnArray["success"] = true;
	}
	$endTime = date("H:i:s", time());
	$elapsedTime = strtotime($endTime) - strtotime($startTime);
	$elapsedTime = date("i:s", $elapsedTime);
	$returnArray["elapsedTime"] = $elapsedTime;
	$returnArray["keys"] = $keys;
	echo json_encode($returnArray);
	
?>
