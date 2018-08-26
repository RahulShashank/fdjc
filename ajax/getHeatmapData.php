<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 600);
date_default_timezone_set("GMT");


require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$flightsColors = "151,187,205";
$failuresColor = "247,70,74";
$resetsColor = "253, 180, 92";


$dataChartType = $_REQUEST['dataType'];
$aircraftId = $_REQUEST['aircraftId'];

if($aircraftId != '') {
    // Get information to display in header
    $query = "SELECT a.tailsign, b.id, b.name, a.databaseName FROM aircrafts a, airlines b WHERE a.id = $aircraftId AND a.airlineId= b.id";
    $result = mysqli_query($dbConnection, $query );

    if ($result && mysqli_num_rows ( $result ) > 0) {
      $row = mysqli_fetch_array ( $result );
      $aircraftTailsign = $row ['tailsign'];
      $airlineId = $row['id'];
      $airlineName = $row['name'];
      $dbName = $row['databaseName'];
    } else {
      echo "error: " . mysqli_error ( $error );
    }
} else if($sqlDump != '') {
    $dbName = $sqlDump;
} else {
    echo "Error - no aircraftId nor sqlDump";
    exit;
}

// get max flight leg, we only display data for the last 64 flight legs
$query = "SELECT idFlightLeg
            FROM $dbName.SYS_flight
            ORDER BY idFlightLeg DESC
            LIMIT 1";
$result = mysqli_query($dbConnection, $query);
$row = mysqli_fetch_array($result);
$maxFlightLegId = $row['idFlightLeg'];
$flightLegRange = 50;
$flightPhases = getFlightPhases();

if($dataChartType == 'failures') {
    $result = getFailures($dbConnection, $dbName, $flightPhases, $maxFlightLegId, $flightLegRange);
    echo "failures";
} else if($dataChartType == 'lossOfCom') {
    $result = getLossOfCommunication($dbConnection, $dbName, $flightPhases, $maxFlightLegId, $flightLegRange);
    echo "lossOfCom";
} else if($dataChartType == 'resets') {
    $result = getResets($dbConnection, $dbName, $flightPhases, $maxFlightLegId, $flightLegRange);
    echo "resets";
} else if($dataChartType == 'extApp') {
    $result = getExtAppEvents($dbConnection, $dbName, $flightPhases, $maxFlightLegId, $flightLegRange);
    echo "extApp";
}

// Note: we don't need to return anything in that script as the results are stored in json files.


function getFailures($dbConnection, $dbName, $flightPhases, $maxFlightLegId, $flightLegRange) {
    $query = "SELECT DISTINCT hostName 
    FROM $dbName.BIT_lru a
    WHERE (hostName LIKE 'DSU%' OR hostName LIKE 'AVCD%' OR hostName LIKE 'LAIC%' OR hostName LIKE 'ADB%' OR hostName LIKE 'SVDU%' OR hostName LIKE 'TPMU%' OR hostName LIKE '%PCU%' )
    AND a.lastUpdate = (
            SELECT MAX(b.lastUpdate) AS 'max'
            FROM $dbName.BIT_lru b
            WHERE a.hostName = b.hostName
        )
    ORDER BY 
        CASE 
            WHEN hostName LIKE 'DSU%' THEN 1 
            WHEN (hostName LIKE 'AVCD%' OR hostName LIKE 'LAIC%') THEN 2
            WHEN hostName LIKE 'ADB%' THEN 3
            WHEN hostName LIKE 'SVDU%' THEN 4
            ELSE 5
        END, LENGTH(hostName), hostName ";

    // Failures computation
    $result = mysqli_query($dbConnection, $query);

    $text .= "[";
    $start = 100000;
    $end = 0;
    $nbOfUnits = 0;
    $totalCount = 0;
    $totalCountDisplayed = 0;

    while ($row = mysqli_fetch_array($result)) {
        $hostname = $row['hostName'];

        // get counts for each flight legt for that lru
        $query2 = "SELECT a.idFlightLeg, accusedHostName,  SUM(legFailureCount) AS count
                    FROM $dbName.BIT_failure a
                    INNER JOIN $dbName.SYS_flightPhase b
                    ON a.idFlightLeg = b.idFlightLeg
                    AND b.idFlightPhase IN ($flightPhases)
                    WHERE accusedHostName = '$hostname'
                    AND a.idFlightLeg BETWEEN ($maxFlightLegId - $flightLegRange) AND $maxFlightLegId
                    AND a.correlationDate BETWEEN b.startTime AND b.endTime
                    GROUP BY a.idFlightLeg";
        $result2 = mysqli_query($dbConnection, $query2);
        if(!$result2) {
            echo "Error for query: $query2<br><br>";
            echo("Error description: " . mysqli_error($dbConnection));
            die;
        }

        $lruText = "{\"hostnameData\": [";
        $total = 0;
        $j = 0;

        while ($row2 = mysqli_fetch_array($result2)) {
            $idFlightLeg = $row2['idFlightLeg'];
            if($idFlightLeg < $start) {
                $start = $idFlightLeg;
            }
            if($idFlightLeg > $end) {
                $end = $idFlightLeg;
            }
            $failures = $row2['count'];
            $total += $failures;

            if($j > 0 ) $lruText.= ",";
            $lruText .= "[$idFlightLeg,$failures,\"$hostname\"]";
            $j++;
        }

        $lruText .= "], \"total\": $total";
        $lruText .= ", \"hostname\": \"$hostname ($total)\"}";

        $totalCount += $total;

        if($total >= $minTotalInput) {
            if($nbOfUnits > 0 ) {
                $text.= ",";
            }
            $text .= $lruText;
            $totalCountDisplayed += $total;
            $nbOfUnits++;
        }
    }

    $text .= "]";

    $myfile = fopen("../json/failuresHeatMap.json", "w") or die("Unable to open file!");
    fwrite($myfile, $text);
    fclose($myfile);

    return array($nbOfUnits, $start, $end);
}

function getLossOfCommunication($dbConnection, $dbName, $flightPhases, $maxFlightLegId, $flightLegRange) {
    $query = "SELECT DISTINCT hostName 
    FROM $dbName.BIT_lru a
    WHERE (hostName LIKE 'DSU%' OR hostName LIKE 'AVCD%' OR hostName LIKE 'LAIC%' OR hostName LIKE 'ADB%' OR hostName LIKE 'SVDU%' OR hostName LIKE 'TPMU%' OR hostName LIKE '%PCU%' )
    AND a.lastUpdate = (
            SELECT MAX(b.lastUpdate) AS 'max'
            FROM $dbName.BIT_lru b
            WHERE a.hostName = b.hostName
        )
    ORDER BY 
        CASE 
            WHEN hostName LIKE 'DSU%' THEN 1 
            WHEN (hostName LIKE 'AVCD%' OR hostName LIKE 'LAIC%') THEN 2
            WHEN hostName LIKE 'ADB%' THEN 3
            WHEN hostName LIKE 'SVDU%' THEN 4
            ELSE 5
        END, LENGTH(hostName), hostName ";

    // Failures computation
    $result = mysqli_query($dbConnection, $query);

    $text .= "[";
    $start = 100000;
    $end = 0;
    $nbOfUnits = 0;
    $totalCount = 0;
    $totalCountDisplayed = 0;

    while ($row = mysqli_fetch_array($result)) {
        $hostname = $row['hostName'];

        // get counts for each flight legt for that lru
        $query2 = "SELECT a.idFlightLeg, hostName,  COUNT(idFault) AS count
                    FROM $dbName.BIT_fault a
                    INNER JOIN $dbName.SYS_flightPhase b
                    ON a.idFlightLeg = b.idFlightLeg
                    AND b.idFlightPhase IN ($flightPhases)
                    WHERE hostName = '$hostname'
                    AND a.idFlightLeg BETWEEN ($maxFlightLegId - $flightLegRange) AND $maxFlightLegId
                    AND a.detectionTime BETWEEN b.startTime AND b.endTime
                    AND faultCode = 400
                    GROUP BY a.idFlightLeg";
        $result2 = mysqli_query($dbConnection, $query2);
        if(!$result2) {
            echo "Error for query: $query2<br><br>";
            echo("Error description: " . mysqli_error($dbConnection));
            die;
        }

        $lruText = "{\"hostnameData\": [";
        $total = 0;
        $j = 0;

        while ($row2 = mysqli_fetch_array($result2)) {
            $idFlightLeg = $row2['idFlightLeg'];
            if($idFlightLeg < $start) {
                $start = $idFlightLeg;
            }
            if($idFlightLeg > $end) {
                $end = $idFlightLeg;
            }
            $failures = $row2['count'];
            $total += $failures;

            if($j > 0 ) $lruText.= ",";
            $lruText .= "[$idFlightLeg,$failures,\"$hostname\"]";
            $j++;
        }

        $lruText .= "], \"total\": $total";
        $lruText .= ", \"hostname\": \"$hostname ($total)\"}";

        $totalCount += $total;

        if($total >= $minTotalInput) {
            if($nbOfUnits > 0 ) {
                $text.= ",";
            }
            $text .= $lruText;
            $totalCountDisplayed += $total;
            $nbOfUnits++;
        }
    }

    $text .= "]";

    $myfile = fopen("../json/lossOfCommunicationHeatMap.json", "w") or die("Unable to open file!");
    fwrite($myfile, $text);
    fclose($myfile);

    return array($nbOfUnits, $start, $end);
}

function getResets($dbConnection, $dbName, $flightPhases, $maxFlightLegId, $flightLegRange) {
    // Resets computation
    $query = "SELECT DISTINCT hostName 
    FROM $dbName.BIT_lru a
    WHERE (hostName LIKE 'DSU%' OR hostName LIKE 'AVCD%' OR hostName LIKE 'LAIC%' OR hostName LIKE 'ADB%' OR hostName LIKE 'SVDU%' OR hostName LIKE 'TPMU%' OR hostName LIKE '%PCU%' )
    AND a.lastUpdate = (
            SELECT MAX(b.lastUpdate) AS 'max'
            FROM $dbName.BIT_lru b
            WHERE a.hostName = b.hostName
        )
    ORDER BY 
        CASE 
            WHEN hostName LIKE 'DSU%' THEN 1 
            WHEN (hostName LIKE 'AVCD%' OR hostName LIKE 'LAIC%') THEN 2
            WHEN hostName LIKE 'ADB%' THEN 3
            WHEN hostName LIKE 'SVDU%' THEN 4
            ELSE 5
        END, LENGTH(hostName), hostName ";

    $result = mysqli_query($dbConnection, $query);

    $text .= "[";
    $start = 100000;
    $end = 0;
    $nbOfUnits = 0;
    $totalCount = 0;
    $totalCountDisplayed = 0;

    while ($row = mysqli_fetch_array($result)) {
        $hostname = $row['hostName'];

        // get counts for each flight legt for that lru
        $query2 = "SELECT a.idFlightLeg, eventData,  COUNT(*) AS count
                    FROM $dbName.BIT_events a
                    INNER JOIN $dbName.SYS_flightPhase b
                    ON a.idFlightLeg = b.idFlightLeg
                    AND b.idFlightPhase IN ($flightPhases)
                    WHERE eventData = '$hostname'
                    AND a.idFlightLeg BETWEEN ($maxFlightLegId - $flightLegRange) AND $maxFlightLegId
                    AND a.lastUpdate BETWEEN b.startTime AND b.endTime
                    GROUP BY a.idFlightLeg";

        $result2 = mysqli_query($dbConnection, $query2);
        if(!$result2) {
            echo "Error for query: $query2<br><br>";
            echo("Error description: " . mysqli_error($dbConnection));
            die;
        }

        $lruText = "{\"hostnameData\": [";
        $total = 0;
        $j = 0;

        while ($row2 = mysqli_fetch_array($result2)) {
            $idFlightLeg = $row2['idFlightLeg'];
            if($idFlightLeg < $start) {
                $start = $idFlightLeg;
            }
            if($idFlightLeg > $end) {
                $end = $idFlightLeg;
            }
            $failures = $row2['count'];
            $total += $failures;

            if($j > 0 ) $lruText.= ",";
            $lruText .= "[$idFlightLeg,$failures,\"$hostname\"]";
            $j++;
        }

        $lruText .= "], \"total\": $total";
        $lruText .= ", \"hostname\": \"$hostname ($total)\"}";

        $totalCount += $total;

        if($total >= $minTotalInput) {
            if($nbOfUnits > 0 ) {
                $text.= ",";
            }
            $text .= $lruText;
            $totalCountDisplayed += $total;
            $nbOfUnits++;
        }
    }

    $text .= "]";

    $myfile = fopen("../json/resetsHeatMap.json", "w") or die("Unable to open file!");
    fwrite($myfile, $text);
    fclose($myfile);

    return array($nbOfUnits, $start, $end);
}

function getExtAppEvents($dbConnection, $dbName, $flightPhases, $maxFlightLegId, $flightLegRange) {
    $query = "SELECT DISTINCT hostName 
    FROM $dbName.BIT_lru a
    WHERE (hostName LIKE 'DSU%' OR hostName LIKE 'SVDU%')
    AND a.lastUpdate = (
            SELECT MAX(b.lastUpdate) AS 'max'
            FROM $dbName.BIT_lru b
            WHERE a.hostName = b.hostName
        )
    ORDER BY 
        CASE 
            WHEN hostName LIKE 'DSU%' THEN 1 
            ELSE 2
        END, LENGTH(hostName), hostName ";

    $result = mysqli_query($dbConnection, $query);

    $text .= "[";
    $start = 100000;
    $end = 0;
    $nbOfUnits = 0;
    $totalCount = 0;
    $totalCountDisplayed = 0;

    while ($row = mysqli_fetch_array($result)) {
        $hostname = $row['hostName'];

        // get counts for each flight legt for that lru
        $query2 = "SELECT a.idFlightLeg, hostName,  COUNT(*) AS count
                    FROM $dbName.BIT_extAppEvent a
                    INNER JOIN $dbName.SYS_flightPhase b
                    ON a.idFlightLeg = b.idFlightLeg
                    AND b.idFlightPhase IN ($flightPhases)
                    WHERE hostName = '$hostname'
                    AND a.idFlightLeg BETWEEN ($maxFlightLegId - $flightLegRange) AND $maxFlightLegId
                    AND a.detectionTime BETWEEN b.startTime AND b.endTime
                    GROUP BY a.idFlightLeg";

        $result2 = mysqli_query($dbConnection, $query2);
        if(!$result2) {
            echo "Error for query: $query2<br><br>";
            echo("Error description: " . mysqli_error($dbConnection));
            die;
        }

        $lruText = "{\"hostnameData\": [";
        $total = 0;
        $j = 0;

        while ($row2 = mysqli_fetch_array($result2)) {
            $idFlightLeg = $row2['idFlightLeg'];
            if($idFlightLeg < $start) {
                $start = $idFlightLeg;
            }
            if($idFlightLeg > $end) {
                $end = $idFlightLeg;
            }
            $failures = $row2['count'];
            $total += $failures;

            if($j > 0 ) $lruText.= ",";
            $lruText .= "[$idFlightLeg,$failures,\"$hostname\"]";
            $j++;
        }

        $lruText .= "], \"total\": $total";
        $lruText .= ", \"hostname\": \"$hostname ($total)\"}";

        $totalCount += $total;

        if($total >= $minTotalInput) {
            if($nbOfUnits > 0 ) {
                $text.= ",";
            }
            $text .= $lruText;
            $totalCountDisplayed += $total;
            $nbOfUnits++;
        }
    }

    $text .= "]";

    $myfile = fopen("../json/extAppHeatMap.json", "w") or die("Unable to open file!");
    fwrite($myfile, $text);
    fclose($myfile);

    return array($nbOfUnits, $start, $end);
}
?>
