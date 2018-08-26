<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");


require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');
require_once "../common/datesConfiguration.php";


$flightsColors = "151,187,205";
$failuresColor = "247,70,74";
$resetsColor = "253, 180, 92";


$dataChartType = $_REQUEST['dataChartType'];
$airlineId = $_REQUEST['airlineId'];
$platform = $_REQUEST['platform'];
$actype = $_REQUEST['actype'];
$configType = $_REQUEST['configType'];
$software = $_REQUEST['software'];
$startDate = $_REQUEST['startDate'];
$endDate = $_REQUEST['endDate'];
$startDate .= " 00:00:00";
$endDate .= " 23:59:59";
$period = $fleetStatusPeriod;


$criticalFailures = getCriticalFailures();
$flightPhases = getFlightPhases();


// Get aircrafts databases for the airline
$query = "SELECT databaseName, tailsign
FROM aircrafts 
WHERE airlineId = $airlineId";
if(is_array($platform)) {
    $query .= " and platform in(";
    foreach ($platform as $p) {
        $query .= "'" . $p . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if($platform != '') {
    $query .= " and platform='$platform'";
}

if(is_array($configType)) {
    $query .= " and Ac_Configuration in(";
    foreach ($configType as $c) {
        $query .= "'" . $c . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if($configType!= '') {
    $query .= " and Ac_Configuration='$configType'";
}

if(is_array($software)) {
    $query .= " and software in(";
    foreach ($software as $s) {
        $query .= "'" . $s . "',";
    }
    $query = rtrim($query, ",") . ")";
} else if($software != '') {
    $query .= " and software='$software'";
}

$query .= " ORDER BY tailsign";

$result = mysqli_query ($dbConnection, $query );

$databaseNames = array ();
if($result){
	while ( $row = mysqli_fetch_assoc ( $result ) ) {
		$dbName = $row ['databaseName'];
		$tailsign = $row ['tailsign'];
		$databaseNames[$tailsign] = $dbName;
	}
}else{
	echo "Error $query :" . mysqli_error($dbConnection);
}

if(count($databaseNames) < 1) {
    echo json_encode(null, JSON_NUMERIC_CHECK );
    exit;
}

if($dataChartType == 'flights') {
    // Get Flights per day
    $dataQuery = array();

    $query = "SELECT DATE_FORMAT(createDate, '%m-%d-%Y') AS 'createDate', count(idFlightLeg) AS 'count' FROM ( ";
    $i = 0;
    foreach ($databaseNames as $tailsign => $dbName) {
        $db_selected = mysqli_select_db($dbConnection, $dbName);
        if ($db_selected) {
            if ($i > 0) {
                $query .= " UNION ";
            }
            $query .=
            "SELECT DISTINCT(a.idFlightLeg), a.createDate
            FROM $dbName.SYS_flight a, $dbName.SYS_flightPhase b
            WHERE a.idFlightLeg = b.idFlightLeg
            AND b.idFLightPhase IN (".$GLOBALS['flightPhases'].")
            AND a.createDate BETWEEN \"$startDate\" AND \"$endDate\"";
            //         AND a.createDate BETWEEN CURDATE()-INTERVAL $period DAY AND CURDATE()";
            $i++;
        }
        
    }
    $query .= ") AS T GROUP BY date(createDate)";

    $result = mysqli_query ($dbConnection, $query );
	if($result){
	
		while ( $row = mysqli_fetch_assoc ( $result ) ) {
			$date = $row ['createDate'];
			$count = $row ['count'];
			$dataQuery[$date] = $count;
		}
	}/* else{
		echo "Error $query :" . mysqli_error($dbConnection);
	} */

    $color = $flightsColors;
} else if($dataChartType == 'failures') {
    $dataQuery = getFailureRankings($dbConnection, $databaseNames, "BIT_failure", "correlationDate", "accusedHostName", $startDate, $endDate, $criticalFailures, "failureCode");
    $color = $failuresColor;
} else if($dataChartType == 'resets') {
    $dataQuery = getRankings($dbConnection, $databaseNames, "BIT_events", "lastUpdate", "eventData", $startDate, $endDate);
    $color = $resetsColor;
}

$labels = array();
$data = array();

if($dataChartType == 'flights') {
	foreach ($dataQuery as $label => $value) {
		$labels[] = $label;
		$data[] = $value;
	}
} else {
	// limit to 10 results for failures and faults
	$i = 0;
	foreach ($dataQuery as $label => $value) {
		if($i >= 10) {
			break;
		}
		$labels[] = $label;
		$data[] = $value;
		$i++;
	}
}

if($dataChartType == 'flights') {
    $dataChart = array(
        'labels' => $labels,
        'datasets' => [[
            'label' => "My First dataset",
            'fillColor' => "rgba($color,0)",
            'strokeColor' => "rgba($color,1)",
            'pointColor' => "rgba($color,1)",
            'pointStrokeColor' => "#fff",
            'pointHighlightFill' => "#fff",
            'pointHighlightStroke' => "rgba($color,1)",
            'data' => $data
        ]]
    );
} else {
    $dataChart = array(
        'labels' => $labels,
        'datasets' => [[
            'label' => "My First dataset",
            'fillColor' => "rgba($color,0.5)",
            'strokeColor' => "rgba($color,0.8)",
            'highlightFill' => "rgba($color,0.75)",
            'highlightStroke' => "rgba($color,1)",
            'data' => $data
        ]]
    );
}


echo json_encode($dataChart, JSON_NUMERIC_CHECK );



function getFailureRankings($dbConnection, $databaseNames, $table, $dateColumn, $hostNameColumn, $startDate, $endDate, $criticalCodes = array(), $codeColumn = '') {
    $rankings = array();

    foreach ($databaseNames as $tailsign => $dbName) {
        $db_selected = mysqli_select_db($dbConnection, $dbName);
        if ($db_selected) {
            $showLru = false;
            
            $nbOfFLights = getNumberOfFlights($dbConnection, $dbName, $startDate, $endDate);
            
            $query = "SELECT SUM(legFailureCount) AS \"count\"
        FROM $dbName.$table a
        INNER JOIN $dbName.SYS_flightPhase b
        ON a.idFlightLeg = b.idFlightLeg
        AND b.idFlightPhase IN (".$GLOBALS['flightPhases'].")
        AND a.$dateColumn BETWEEN b.startTime AND b.endTime
        AND a.$dateColumn BETWEEN \"$startDate\" AND \"$endDate\"
        AND ( $hostNameColumn LIKE 'DSU%' OR $hostNameColumn LIKE 'LAIC%' OR $hostNameColumn LIKE 'AVCD%' OR $hostNameColumn LIKE 'ADB%' OR $hostNameColumn LIKE 'SVDU%' OR $hostNameColumn LIKE 'TPMU%' OR $hostNameColumn LIKE '%PCU%' )";
            
            if(count($criticalCodes) > 0) {
                //$query .= "AND a.$codeColumn  IN (".implode(",", $criticalCodes).")";
            }
            
            $result = mysqli_query ($dbConnection, $query );
            if($result){
                
                while ( $row = mysqli_fetch_assoc ( $result ) ) {
                    $count = $row ['count'];
                    if($count != '') {
                        if($nbOfFLights != 0) {
                            $rankings[$tailsign] = round($count / $nbOfFLights);
                        }
                    }
                }
            }/* else{
                echo "Error $query : ". mysqli_error($dbConnection);
            } */
        }
    }

    arsort($rankings);

    return $rankings;
}


function getRankings($dbConnection, $databaseNames, $table, $dateColumn, $hostNameColumn, $startDate, $endDate, $criticalCodes = array(), $codeColumn = '') {
    $rankings = array();

    foreach ($databaseNames as $tailsign => $dbName) {
        $db_selected = mysqli_select_db($dbConnection, $dbName);
        if ($db_selected) {
            $showLru = false;
            
            $nbOfFLights = getNumberOfFlights($dbConnection, $dbName, $startDate, $endDate);
            
            $query = "SELECT COUNT(*) AS \"count\"
            FROM $dbName.$table a
            INNER JOIN $dbName.SYS_flightPhase b
            ON a.idFlightLeg = b.idFlightLeg
            AND b.idFlightPhase IN (".$GLOBALS['flightPhases'].")
            AND a.$dateColumn BETWEEN b.startTime AND b.endTime
            AND a.$dateColumn BETWEEN \"$startDate\" AND \"$endDate\"
            AND ( $hostNameColumn LIKE 'DSU%' OR $hostNameColumn LIKE 'LAIC%' OR $hostNameColumn LIKE 'AVCD%' OR $hostNameColumn LIKE 'ADB%' OR $hostNameColumn LIKE 'SVDU%' OR $hostNameColumn LIKE 'TPMU%' OR $hostNameColumn LIKE '%PCU%' )";
            
            if(count($criticalCodes) > 0) {
                //$query .= "AND a.$codeColumn  IN (".implode(",", $criticalCodes).")";
            }
            
            $result = mysqli_query ($dbConnection, $query );
            if($result){
                while ( $row = mysqli_fetch_assoc ( $result ) ) {
                    $count = $row ['count'];
                    if($count != '') {
                        if($nbOfFLights != 0) {
                            $rankings[$tailsign] = round($count / $nbOfFLights);
                        }
                    }
                }
            }/* else{
                echo "Error : $query ". mysqli_error($dbConnection);
            } */
        }
    }

    arsort($rankings);

    return $rankings;
}

function getNumberOfFlights($dbConnection, $dbName, $startDate, $endDate) {
    // Get number of flighs for the period
    $query = "SELECT count(DISTINCT(g.idFlightLeg)) AS 'count' 
            FROM $dbName.SYS_flight g, $dbName.SYS_flightPhase h 
            WHERE g.idFlightLeg = h.idFlightLeg AND h.idFLightPhase IN (".$GLOBALS['flightPhases'].")
            AND g.createDate BETWEEN \"$startDate\" AND \"$endDate\"";
//             AND g.createDate BETWEEN CURDATE()-INTERVAL $period DAY AND CURDATE()";
    $result = mysqli_query ($dbConnection, $query );
	if($result){
		while ( $row = mysqli_fetch_assoc ( $result ) ) {
			$nbOfFLights = $row ['count'];
		}
	}else{
		echo "Error : $query ". mysqli_error($dbConnection);
	}

    return $nbOfFLights;
}
?>
