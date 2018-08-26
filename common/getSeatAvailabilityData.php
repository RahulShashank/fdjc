<?php
	require_once "../database/connecti_database.php";
	require_once('../engineering/checkEngineeringPermission.php');
	/*
	$postdata = file_get_contents("php://input");
    $request = json_decode($postdata);
	$dbname = $request->databaseName;
	set_time_limit ( 300 );
	*/
	
	function getSeatAvailability($dbConnection) {
		$seats = getSeats($dbConnection);
		$seatAvailabilities = array_fill(0, 12, 0);
		$startingDates = [];
		for ($i = 0; $i < 12; $i++) 
		{
			$weeksBack = $i;
			$end =  date("Y-m-d H:i", time());
			$end = strtotime("-". $weeksBack ." weeks", strtotime($end));
			$end = date("Y-m-d H:i", $end);
			$start = strtotime("-1 weeks", strtotime($end));
			$start = date("Y-m-d H:i", $start);
			array_unshift($startingDates, date("m-d-Y", strtotime($start)));
			$flightLegs = getFlightLegs($dbConnection, $start, $end);
			if ($flightLegs) {
				$events = getEventsTotal($dbConnection, $flightLegs);
				if (count($events) > 0)  {
					// $average = (array_sum($events) / count($flightLegs) ) / count($events);
					$average = (array_sum($events) / count($flightLegs) );
				} else {
					$average = 0;
				}
				array_unshift($seatAvailabilities, (100 * (1 - ($average/$seats))));
			} else {
				array_unshift($seatAvailabilities, 0);
			}
		}
		$jsonArray = array(
			"seatAvailabilities" => $seatAvailabilities,
			"startDates" => $startingDates
		);
		return $jsonArray;
	}
	
	function getDates() {
		$startingDates = [];
		for ($i = 0; $i < 12; $i++) 
		{
			$weeksBack = $i;
			$end =  date("Y-m-d H:i", time());
			$end = strtotime("-". $weeksBack ." weeks", strtotime($end));
			$end = date("Y-m-d H:i", $end);
			$start = strtotime("-1 weeks", strtotime($end));
			$start = date("Y-m-d H:i", $start);
			array_unshift($startingDates, date("m-d-Y", strtotime($start)));
		}
		return $startingDates;
	}
	//echo $json_response = json_encode($jsonArray);
	
	// Check connection
	/*
	if (mysqli_connect_errno())
	{
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
	// Set autocommit to off
	mysqli_autocommit($dbConnection,FALSE);
	*/
	function connectToDB($dbConnection, $dbname) {
		// $username = "root";
		// $password = "";
		// $hostname = "10.76.108.177:3306"; 
		// //$mainDB = "qtr_a7_ala"; 
		// $mainDB = $dbname;
		// //connection to mysql Server
		// $dbConnection = mysqli_connect($hostname, $username, $password, $mainDB)
		//   or die("Unable to connect to MySQL");
		mysqli_select_db($dbConnection,$dbname);
		  
		return $dbConnection;
	}
	
	function getSeats($dbConnection) {
		$seats = 0;
		$query = "SELECT COUNT( DISTINCT (hostName) ) as count FROM  BIT_lru WHERE hostName LIKE  'SVDU__' OR hostName LIKE 'SVDU___'";
		$result = mysqli_query ($dbConnection, $query);
		while ($result) {
			$row = mysqli_fetch_array ( $result );
			if (!$row) break;
			$seats = $row['count'];
			
		}
		return $seats;
	}
	
	function getFlightLegs($dbConnection, $start, $end) {
		$flightLegs = [];
		$query = "SELECT DISTINCT a.idFlightLeg,a.flightNumber, a.departureAirportCode, a.arrivalAirportCode, a.createDate, a.lastUpdate  AS 'endFlightLeg'
				  FROM SYS_flight a
				  INNER JOIN SYS_flightPhase b
				  ON a.idFlightLeg = b.idFlightLeg
				  AND b.idFlightPhase = 5
				  AND (
					  ( '". $start ."' <= a.createDate AND '". $end ."' >= a.lastUpdate)
				  OR
					  ( '". $end ."' <= a.lastUpdate AND '". $start ."' >= a.createDate)
				  )
				 ORDER BY a.createDate";
			
		$result = mysqli_query ($dbConnection, $query);

		while ($result) {
			$row = mysqli_fetch_array ( $result );
			if (!$row) break;
			array_push($flightLegs, $row['idFlightLeg']);
			
		}		
		return $flightLegs;
	}
	
	function arrayPrint($array) {
		foreach($array as $a) {
			echo $a . ', ';
		}
		
		echo "<br>";
	}
	
	function getEventsTotal($dbConnection, $flightLegs) {
		$events = [];
		$flightsString = '';
		foreach($flightLegs as $flightLeg) {
			$flightsString = $flightsString . $flightLeg . ", ";
		}
		$flightsString = substr($flightsString, 0, -2);
		$query = "  SELECT COUNT(*) as count
						FROM (
						SELECT a.idFlightLeg, eventData, COUNT(*) 
						FROM `BIT_events` a
						INNER JOIN SYS_flightPhase b
						ON a.idFlightLeg = b.idFlightLeg
						AND b.idFlightPhase = 5
						AND a.idFlightLeg IN (" . $flightsString . ")
						AND (a.eventData LIKE 'SVDU__' OR a.eventData LIKE 'SVDU___')
						AND a.lastUpdate >= b.startTime
						AND a.lastUpdate <= b.endTime
						AND eventName = 'CommandedReboot'
						GROUP BY a.idFlightLeg, a.eventData
						) AS t
					GROUP BY idFlightLeg";
		
		
		$result = mysqli_query ($dbConnection, $query);
		if ($result) {
			while ($row = mysqli_fetch_array($result)) {
				array_unshift($events, $row[0]);
			}
		}
		return $events;
	}
	
?>
