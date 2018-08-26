<?php
	ini_set('max_execution_time', 300);
    date_default_timezone_set("GMT");

	require_once "../database/connecti_database.php";
	require_once('../engineering/checkEngineeringPermission.php');

	include("getSeatAvailabilityData.php");
	
	$postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
	$aircraftDB = $request->aircraftDB;

	// Check connection
	if (mysqli_connect_errno())
	{
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
	
	$startTime = date("H:i:s", time());
	$returnArray = [];
	$availArray = array_fill(0, 12, 0);
	$dbConnection = connectToDB($dbConnection, $aircraftDB);
	if (mysqli_connect_errno())
	{
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}

	$getSeatAvailability = getSeatAvailability($dbConnection);
	$availArray = $getSeatAvailability["seatAvailabilities"];
	//$datesArray = $getSeatAvailability["startDates"];
	$zeroCount = 0;
	for ($i = 0; $i < 12; $i++) {
		$availArray[$i] = round($availArray[$i], 2);
		if ($availArray[$i] == 0) {
			$zeroCount++;
		}
		
	}
	mysqli_close($dbConnection);
	$availArray = array_slice($availArray, 0, 12);
	$getSeatAvailability["seatAvailabilities"] = $availArray;
	$averageValue = 0;
	if ($zeroCount != 12) {
		$averageValue = round((array_sum($availArray) / (count($availArray) - $zeroCount)), 2);
	}
	$averageArray = array_fill(0, 12, $averageValue);
	$getSeatAvailability["average"] = $averageArray;
	$endTime = date("H:i:s", time());
	$elapsedTime = strtotime($endTime) - strtotime($startTime);
	$elapsedTime = date("i:s", $elapsedTime);
	$getSeatAvailability["elapsedTime"] = $elapsedTime;
	$getSeatAvailability["success"] = true;
	$getSeatAvailability["aircraft"] = $aircraftDB;
	echo json_encode($getSeatAvailability);
	
?>
