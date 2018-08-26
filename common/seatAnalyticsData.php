<?php
require_once "../database/connecti_database.php";

function getCruiseTime($dbConnection,$FlightLeg,$dbName) {
	//to get count of repeating cruise phase
	$query="SELECT COUNT(*) AS CountCruisePhase FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5)";
	$result = mysqli_query ($dbConnection, $query);
	$row = mysqli_fetch_array ( $result );
	$CruisePhaseCount = $row ['CountCruisePhase'];
																																
	//For multiple cruise phase		
	if($CruisePhaseCount != 1){
		$qry2="SELECT startTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5) ORDER BY id ASC LIMIT 1";
		$reslt2 = mysqli_query ($dbConnection, $qry2);
		$row = mysqli_fetch_array ( $reslt2 );
		$startTime = $row ['startTime'];
																	
		$qry3="SELECT endTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5)ORDER BY id DESC LIMIT 1";
		$reslt3 = mysqli_query ($dbConnection, $qry3);
		$row = mysqli_fetch_array ( $reslt3 );
		$endTime = $row ['endTime'];
																	
	}else{
		$qry4="SELECT startTime,endTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5) ";
		$reslt4 = mysqli_query ($dbConnection, $qry4);
		$row = mysqli_fetch_array ( $reslt4 );
		$startTime = $row ['startTime'];
		$endTime = $row ['endTime'];
	}
	
	$cruiseTime = round((strtotime($endTime) - strtotime($startTime))/3600, 2);
	
	return $cruiseTime;
	
}

function getStartTime($dbConnection,$FlightLeg,$dbName) {
	//to get count of repeating cruise phase
	$query="SELECT COUNT(*) AS CountCruisePhase FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5)";
	$result = mysqli_query ($dbConnection, $query);
	$row = mysqli_fetch_array ( $result );
	$CruisePhaseCount = $row ['CountCruisePhase'];
																																
	//For multiple cruise phase		
	if($CruisePhaseCount != 1){
		$qry2="SELECT startTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5) ORDER BY id ASC LIMIT 1";
		$reslt2 = mysqli_query ($dbConnection, $qry2);
		$row = mysqli_fetch_array ( $reslt2 );
		$startTime = $row ['startTime'];
																	
		$qry3="SELECT endTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5) ORDER BY id DESC LIMIT 1";
		$reslt3 = mysqli_query ($dbConnection, $qry3);
		$row = mysqli_fetch_array ( $reslt3 );
		$endTime = $row ['endTime'];
																	
	}else{
		$qry4="SELECT startTime,endTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5) ";
		$reslt4 = mysqli_query ($dbConnection, $qry4);
		$row = mysqli_fetch_array ( $reslt4 );
		$startTime = $row ['startTime'];
		$endTime = $row ['endTime'];
	}
	
	return $startTime;
	
}

function getEndTime($dbConnection,$FlightLeg,$dbName) {
	//to get count of repeating cruise phase
	$query="SELECT COUNT(*) AS CountCruisePhase FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5)";
	$result = mysqli_query ($dbConnection, $query);
	$row = mysqli_fetch_array ( $result );
	$CruisePhaseCount = $row ['CountCruisePhase'];
																																
	//For multiple cruise phase		
	if($CruisePhaseCount != 1){
		$qry2="SELECT startTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5) ORDER BY id ASC LIMIT 1";
		$reslt2 = mysqli_query ($dbConnection, $qry2);
		$row = mysqli_fetch_array ( $reslt2 );
		$startTime = $row ['startTime'];
																	
		$qry3="SELECT endTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5) ORDER BY id DESC LIMIT 1";
		$reslt3 = mysqli_query ($dbConnection, $qry3);
		$row = mysqli_fetch_array ( $reslt3 );
		$endTime = $row ['endTime'];
																	
	}else{
		$qry4="SELECT startTime,endTime FROM $dbName.SYS_flightPhase WHERE idFlightLeg=$FlightLeg AND idFlightPhase IN (4,5) ";
		$reslt4 = mysqli_query ($dbConnection, $qry4);
		$row = mysqli_fetch_array ( $reslt4 );
		$startTime = $row ['startTime'];
		$endTime = $row ['endTime'];
	}
	
	return $endTime;
	
}

?>