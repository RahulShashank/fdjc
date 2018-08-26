<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");

require_once "../database/connecti_database.php";
require_once('../engineering/checkEngineeringPermission.php');
require_once "../common/functions.php";
require_once "../common/datesConfiguration.php";

$flightsColors = "151,187,205";
$failuresColor = "247,70,74";
// $failuresColor = "253, 180, 92";
$resetsColor = "253, 180, 92";

$flightPhases = getFlightPhases();


$dataChartType = $_REQUEST['dataChartType'];
$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
// $period = 8;
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];

$isTimePeriodInput = false;
if(isset($startDateTime) && !is_null($startDateTime) && !empty($startDateTime) && isset($endDateTime) && !is_null($endDateTime) && !empty($endDateTime)){
	$isTimePeriodInput = true;
}

if($isTimePeriodInput){
	$datetime1 = date_create($startDateTime);
	$datetime2 = date_create($endDateTime);
	$interval = date_diff($datetime1, $datetime2);
	$period = $interval->format('%a');
}else{
	$period = $aircraftStatusPeriod;	//Default aircraftStatusPeriod
}


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

if(!$isTimePeriodInput){
	/* Query to find out the last flightLeg endtime */
	$query = "SELECT lastUpdate 
				FROM $dbName.SYS_flight 
				ORDER BY lastUpdate 
				DESC LIMIT 1";
	$result = mysqli_query($dbConnection, $query);
	if($result) { // i5000 doesn't have this table -> to be checked with XML
		$row = mysqli_fetch_array($result);
		$maxDateTime = $row['lastUpdate'];
	}else{
		echo "Error : ". mysqli_error($dbConnection);
	}

	$lastFlightLegDateTime = date('Y-m-d', strtotime($maxDateTime));
	//Increment by 1 day.
	$maxDateTime = date('Y-m-d H:i:s', strtotime("+1 days", strtotime($maxDateTime)));
}else{
	//If timeperiod is input by user.
	$lastFlightLegDateTime = date('Y-m-d', strtotime($endDateTime));
	$maxDateTime = date('Y-m-d H:i:s', strtotime("+1 days", strtotime($endDateTime)));
}
		
//Initialization		
$labels = array();
$flightsPerDay = array();
$failuresPerDay = array();
$resetsPerDay = array();

if($dataChartType == 'stats') {
	// Get dates for the period
	
	$range = $period;
	
	while($range >= 0) {
	
		//$date = date('Y-m-d', strtotime("-$range days"));
		$date = date('Y-m-d', strtotime("-$range days", strtotime($lastFlightLegDateTime)));
		$startDate = date('Y-m-d 00:00:00', strtotime("-$range days", strtotime($lastFlightLegDateTime)));
		$endDate = date('Y-m-d 23:59:59', strtotime("-$range days", strtotime($lastFlightLegDateTime)));

		if($endDate > $maxDateTime ){
			break;	//Stop Data collection if Last FlightLeg in DB is reached.
		}
		
		$labels[] = $date;
		$failuresDateConditons = array();
		$resetsDateConditons = array();
		$flightsForDate = 0;
		$failuresForDate = 0;
		$resetsForDate = 0;

		$query = "SELECT GROUP_CONCAT(a.idFlightLeg) AS flightLegs, COUNT(id) as count
					FROM $dbName.SYS_flight a, $dbName.SYS_flightPhase b
			    	WHERE a.idFlightLeg = b.idFlightLeg AND b.idFlightPhase IN (3)
					AND createDate BETWEEN '$startDate' AND '$endDate'";
		 //echo "$query\n\n";
		$result = mysqli_query($dbConnection, $query);
		if($result) {
			$row = mysqli_fetch_assoc ( $result );
			$flightsForDate = $row ['count'];
		    $flightLegs = $row['flightLegs'];
		    // echo "$date : $count / $flightLegs\n\n";

		    if($flightsForDate > 0) {
		    	$flightLegs = explode(",", $flightLegs);
			    $previousFlightLeg = -1;
			    $seenFlightLeg = 0;
			    foreach ($flightLegs as $flightLeg) {
			    	if($flightLeg != $previousFlightLeg) {
			    		$seenFlightLeg = 1;
			    	}

			    	// get start time of take off
			    	$query2 = "SELECT startTime
			    				FROM $dbName.SYS_flightPhase
			    				WHERE idFlightPhase IN (3)
			    				AND idFlightLeg = $flightLeg";
			    	// echo "$query2\n\n";
			    	$result2 = mysqli_query($dbConnection, $query2);

			    	$i=0;
			    	while($i < $seenFlightLeg) {
			    		// We could have several take offs in one flight leg.
			    		// This loop while get the correct take off time depending how many times we have already done a request on that particular flight leg
						$row2 = mysqli_fetch_assoc ( $result2 );
						$i++;			    		
			    	}
			    	
			    	$takeOffTime = $row2 ['startTime'];

			    	// echo "take off: $takeOffTime\n\n";

			    	// get end time of landing/ weight of weel
			    	// we are getting the flightPhaseId 7 which is following our take off time
					$query2 = "SELECT endTime
	    				FROM $dbName.SYS_flightPhase
	    				WHERE idFlightPhase IN (7)
	    				AND endTime > '$takeOffTime'
	    				LIMIT 1";
	    			$result2 = mysqli_query($dbConnection, $query2);
	    			$row2 = mysqli_fetch_assoc ( $result2 );
	    			$landingTime = $row2 ['endTime'];

	    			// echo "landing: $landingTime\n\n";

	    			$failuresDateConditons[] = " ( correlationDate BETWEEN '$takeOffTime' AND '$landingTime' ) ";
	    			$resetsDateConditons[] = " ( lastUpdate BETWEEN '$takeOffTime' AND '$landingTime' ) ";

			    	$previousFlightLeg = $flightLeg;
			    	$seenFlightLeg++;
			    }

			    // Get failures for the flights of that date
			    $query2 = "SELECT SUM(legFailureCount) AS count	FROM $dbName.BIT_failure WHERE ( accusedHostname LIKE 'DSU%' OR accusedHostname LIKE 'LAIC%' OR accusedHostname LIKE 'AVCD%' OR accusedHostname LIKE 'ADB%' OR accusedHostname LIKE 'SVDU%' OR accusedHostname LIKE 'TPMU%' OR accusedHostname LIKE '%PCU%' ) AND ";
			    for ($i=0; $i < count($failuresDateConditons); $i++) { 
			    	if($i > 0) {
			    		$query2 .= " OR ";
			    	}
			    	$query2 .= $failuresDateConditons[$i];
			    }
			    
			    $result2 = mysqli_query($dbConnection, $query2);
	    		$row2 = mysqli_fetch_assoc ( $result2 );
	    		$failuresForDate += $row2 ['count'];

			    // Get resets for the flights of that date
			    $query2 = "SELECT COUNT(*) AS count	FROM $dbName.BIT_events	WHERE ( eventData LIKE 'DSU%' OR eventData LIKE 'LAIC%' OR eventData LIKE 'AVCD%' OR eventData LIKE 'ADB%' OR eventData LIKE 'SVDU%' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%' )
			    			AND ";
			    for ($i=0; $i < count($resetsDateConditons); $i++) { 
			    	if($i > 0) {
			    		$query2 .= " OR ";
			    	}
			    	$query2 .= $resetsDateConditons[$i];
			    }
			    
			    // echo "$query2\n\n";
			    $result2 = mysqli_query($dbConnection, $query2);
	    		$row2 = mysqli_fetch_assoc ( $result2 );
	    		$resetsForDate += $row2 ['count'];
		    }
		}else{
			echo "Error ". mysqli_error($dbConnection);
		}

		$flightsPerDay[] = $flightsForDate;
		if($flightsForDate > 0 ) {
			$failuresForDate = round($failuresForDate / $flightsForDate);
			$resetsForDate = round($resetsForDate / $flightsForDate);
		}
		$failuresPerDay[] = $failuresForDate;
		$resetsPerDay[] = $resetsForDate;

		$range--;
	}
}


$color = $flightsColors;
$dataFlights = [
        'label' => "Flights",
        'fillColor' => "rgba($color,0)",
        'strokeColor' => "rgba($color,1)",
        'pointColor' => "rgba($color,1)",
        'pointStrokeColor' => "#fff",
        'pointHighlightFill' => "#fff",
        'pointHighlightStroke' => "rgba($color,1)",
        'data' => $flightsPerDay
    ];

$color = $failuresColor;
$dataFailures = [
        'label' => "Failures",
        'fillColor' => "rgba($color,0)",
        'strokeColor' => "rgba($color,1)",
        'pointColor' => "rgba($color,1)",
        'pointStrokeColor' => "#fff",
        'pointHighlightFill' => "#fff",
        'pointHighlightStroke' => "rgba($color,1)",
        'data' => $failuresPerDay
    ];

$color = $resetsColor;
$dataResets = [
        'label' => "Resets",
        'fillColor' => "rgba($color,0)",
        'strokeColor' => "rgba($color,1)",
        'pointColor' => "rgba($color,1)",
        'pointStrokeColor' => "#fff",
        'pointHighlightFill' => "#fff",
        'pointHighlightStroke' => "rgba($color,1)",
        'data' => $resetsPerDay
    ];

$dataChart = array(
    'labels' => $labels,
    'datasets' => [$dataFlights, $dataFailures, $dataResets]
);

echo json_encode($dataChart, JSON_NUMERIC_CHECK );



function getFlightsPerDay($dbName, $period) {
	$query = "SELECT Date, COALESCE(count,0) AS count
			FROM
			(SELECT CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY AS Date
				FROM
				(SELECT 0 AS a
					UNION ALL SELECT 1
					UNION ALL SELECT 2
					UNION ALL SELECT 3
					UNION ALL SELECT 4
					UNION ALL SELECT 5
					UNION ALL SELECT 6
					UNION ALL SELECT 7
					UNION ALL SELECT 8
					UNION ALL SELECT 9) AS a CROSS
			JOIN
			(SELECT 0 AS a
				UNION ALL SELECT 1
				UNION ALL SELECT 2
				UNION ALL SELECT 3
				UNION ALL SELECT 4
				UNION ALL SELECT 5
				UNION ALL SELECT 6
				UNION ALL SELECT 7
				UNION ALL SELECT 8
				UNION ALL SELECT 9) AS b CROSS
			JOIN
			(SELECT 0 AS a
				UNION ALL SELECT 1
				UNION ALL SELECT 2
				UNION ALL SELECT 3
				UNION ALL SELECT 4
				UNION ALL SELECT 5
				UNION ALL SELECT 6
				UNION ALL SELECT 7
				UNION ALL SELECT 8
				UNION ALL SELECT 9) AS c
			) a
			LEFT JOIN (
				SELECT count(DISTINCT(h.id)) AS 'count', startTime
			    FROM $dbName.SYS_flight g, $dbName.SYS_flightPhase h 
			    WHERE g.idFlightLeg = h.idFlightLeg AND h.idFLightPhase IN (4)
			    AND g.createDate BETWEEN CURDATE()-INTERVAL $period WEEK AND CURDATE() 
			    GROUP BY date(g.createDate)
			) f
			ON a.Date = DATE_FORMAT(f.startTime, '%Y-%m-%d')
			WHERE a.Date > CURDATE()-INTERVAL $period WEEK
			GROUP BY a.Date";

    $result = mysqli_query ($GLOBALS['dbConnection'], $query );
    while ( $row = mysqli_fetch_assoc ( $result ) ) {
        $date = $row ['Date'];
        $count = $row ['count'];
        $dataQuery[$date] = $count;
    }

    return $dataQuery;
}

function getResetsPerDay($dbName, $period) {
	$query = "SELECT Date, count(idEvent) AS 'count'
		FROM
		(SELECT CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY AS Date
			FROM
			(SELECT 0 AS a
				UNION ALL SELECT 1
				UNION ALL SELECT 2
				UNION ALL SELECT 3
				UNION ALL SELECT 4
				UNION ALL SELECT 5
				UNION ALL SELECT 6
				UNION ALL SELECT 7
				UNION ALL SELECT 8
				UNION ALL SELECT 9) AS a CROSS
		JOIN
		(SELECT 0 AS a
			UNION ALL SELECT 1
			UNION ALL SELECT 2
			UNION ALL SELECT 3
			UNION ALL SELECT 4
			UNION ALL SELECT 5
			UNION ALL SELECT 6
			UNION ALL SELECT 7
			UNION ALL SELECT 8
			UNION ALL SELECT 9) AS b CROSS
		JOIN
		(SELECT 0 AS a
			UNION ALL SELECT 1
			UNION ALL SELECT 2
			UNION ALL SELECT 3
			UNION ALL SELECT 4
			UNION ALL SELECT 5
			UNION ALL SELECT 6
			UNION ALL SELECT 7
			UNION ALL SELECT 8
			UNION ALL SELECT 9) AS c
		) a
		LEFT JOIN (
			SELECT idEvent, a.lastUpdate, eventData, startTime
			FROM $dbName.BIT_events a
			INNER JOIN $dbName.SYS_flightPhase b
			ON a.idFlightLeg = b.idFlightLeg
			AND b.idFlightPhase IN (".$GLOBALS['flightPhases'].")
			AND a.lastUpdate BETWEEN b.startTime AND b.endTime
			AND ( eventData LIKE 'DSU%' OR eventData LIKE 'LAIC%' OR eventData LIKE 'AVCD%' OR eventData LIKE 'ADB%' OR eventData LIKE 'SVDU%' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%' )
		) f
		ON a.Date = DATE_FORMAT(f.startTime, '%Y-%m-%d')
		WHERE a.Date > CURDATE()-INTERVAL $period WEEK
		GROUP BY a.Date";

    $result = mysqli_query ($GLOBALS['dbConnection'], $query );
    while ( $row = mysqli_fetch_assoc ( $result ) ) {
        $date = $row ['Date'];
        $count = $row ['count'];
        $dataQuery[$date] = $count;
    }

    return $dataQuery;
}

function getFailuresPerDay($dbName, $period) {
	$query = "SELECT a.Date, SUM(legFailureCount) AS 'count'
		FROM
		(SELECT CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY AS Date
			FROM
			(SELECT 0 AS a
				UNION ALL SELECT 1
				UNION ALL SELECT 2
				UNION ALL SELECT 3
				UNION ALL SELECT 4
				UNION ALL SELECT 5
				UNION ALL SELECT 6
				UNION ALL SELECT 7
				UNION ALL SELECT 8
				UNION ALL SELECT 9) AS a CROSS
		JOIN
		(SELECT 0 AS a
			UNION ALL SELECT 1
			UNION ALL SELECT 2
			UNION ALL SELECT 3
			UNION ALL SELECT 4
			UNION ALL SELECT 5
			UNION ALL SELECT 6
			UNION ALL SELECT 7
			UNION ALL SELECT 8
			UNION ALL SELECT 9) AS b CROSS
		JOIN
		(SELECT 0 AS a
			UNION ALL SELECT 1
			UNION ALL SELECT 2
			UNION ALL SELECT 3
			UNION ALL SELECT 4
			UNION ALL SELECT 5
			UNION ALL SELECT 6
			UNION ALL SELECT 7
			UNION ALL SELECT 8
			UNION ALL SELECT 9) AS c
		) a
		LEFT JOIN (
			SELECT idFailure, correlationDate, legFailureCount, startTime
			FROM $dbName.BIT_failure a
			INNER JOIN $dbName.SYS_flightPhase b
			ON a.idFlightLeg = b.idFlightLeg
			AND b.idFlightPhase IN (".$GLOBALS['flightPhases'].") 
			AND a.correlationDate BETWEEN b.startTime AND b.endTime
			AND ( accusedHostname LIKE 'DSU%' OR accusedHostname LIKE 'LAIC%' OR accusedHostname LIKE 'AVCD%' OR accusedHostname LIKE 'ADB%' OR accusedHostname LIKE 'SVDU%' OR accusedHostname LIKE 'TPMU%' OR accusedHostname LIKE '%PCU%' )
		) f
		ON a.Date = DATE_FORMAT(f.startTime, '%Y-%m-%d')
		WHERE a.Date > CURDATE()-INTERVAL $period WEEK
		GROUP BY a.Date";

    $result = mysqli_query ($GLOBALS['dbConnection'], $query );
    while ( $row = mysqli_fetch_assoc ( $result ) ) {
        $date = $row ['Date'];
        $count = $row ['count'];
        $dataQuery[$date] = $count;
    }

    return $dataQuery;
}
?>
