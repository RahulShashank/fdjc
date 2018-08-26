<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/customerFilters.php";
require_once('../engineering/checkEngineeringPermission.php');

$aircraftId = $_REQUEST['tailsign'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];
$dataType = $_REQUEST['dataType'];

$failuresToRemove = getFailuresToRemove();
$failuresToKeep = getFailuresToKeep();
$faultsToRemove = getFaultsToRemove();
$faultsToKeep = getFaultsToKeep();

$flightPhases = getFlightPhases();

if(isset($flightLegs)) {
	if(strpos($flightLegs, '-') > 0) {
		$leg1 = $type  = strtok($flightLegs, '-');
		$leg2 = $type  = strtok('-');
		$flightLegsCondition = " a.idFlightLeg BETWEEN $leg1 AND $leg2";
	} else if(strpos($flightLegs, ',') > 0) {
		$flightLegsCondition = " a.idFlightLeg in ($flightLegs)";
	} else {
		$flightLegsCondition = " a.idFlightLeg = $flightLegs";
	}
} else {
    $startDateTime = $_REQUEST['startDateTime'];
    $endDateTime = $_REQUEST['endDateTime'];
}

if($aircraftId != '') {
	$query = "SELECT databaseName, platform FROM aircrafts a WHERE a.tailsign = '$aircraftId'";
	$result = mysqli_query($dbConnection, $query);
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $dbName = $row['databaseName'];
        $platform = $row['platform'];

        $selected = mysqli_select_db($dbConnection, $dbName)
			or die("Could not select ".$dbName);
    } else {
         echo "<br>error: ".mysql_error($dbhandle);
    }
} else {
	$selected = mysqli_select_db($dbConnection, $sqlDump)
			or die("Could not select ".$sqlDump);
}

$svdus = array();

if($dataType == 'activeFailures') {
    $query = "SELECT accusedHostName, COUNT(*) AS count
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhases) 
                AND a.correlationDate >= b.startTime AND a.correlationDate <= b.endTime
                AND (
						accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___' 
					)
                AND monitorState = 3";

    // Apply customer filter if any
    if(count($failuresToKeep) > 0) {
        $codes = implode(",", $failuresToKeep);
        $query .= " AND a.failureCode IN ($codes)";
    } else if(count($failuresToRemove) > 0) {
        $codes = implode(",", $failuresToRemove);
        $query .= " AND a.failureCode NOT IN ($codes)";
    }
    
    if(isset($flightLegsCondition)) {
        $query .= "AND $flightLegsCondition";
    } else {
        $query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    }

    $query .= " GROUP BY accusedHostName";

    $result = mysqli_query($dbConnection, $query);

    if($result) {
        while ($row = mysqli_fetch_array($result)) {
            $hostname = $row['accusedHostName'];
            $count = $row['count'];
            $svdus[$hostname] = $count;
        }
    }

    $darkThreshold = 5;
	$dangerThreshold = 3;
    $warningThreshold = 1;
} else if($dataType == 'failures') {
    $query = "SELECT accusedHostName, COUNT(*) AS count
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhases) 
                AND a.correlationDate >= b.startTime AND a.correlationDate <= b.endTime
                AND (
						accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___' 
					)";

    // Apply customer filter if any
    if(count($failuresToKeep) > 0) {
        $codes = implode(",", $failuresToKeep);
        $query .= " AND a.failureCode IN ($codes)";
    } else if(count($failuresToRemove) > 0) {
        $codes = implode(",", $failuresToRemove);
        $query .= " AND a.failureCode NOT IN ($codes)";
    }
    
    if(isset($flightLegsCondition)) {
        $query .= "AND $flightLegsCondition";
    } else {
        $query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    }

    $query .= " GROUP BY accusedHostName";

    $result = mysqli_query($dbConnection, $query);

    if($result) {
        while ($row = mysqli_fetch_array($result)) {
            $hostname = $row['accusedHostName'];
            $count = $row['count'];
            $svdus[$hostname] = $count;
        }
    }

    $darkThreshold = 20;
	$dangerThreshold = 10;
    $warningThreshold = 5;
} else if($dataType == 'faults') {
    $query = "SELECT hostName, COUNT(DISTINCT(idFault)) AS count
                FROM BIT_fault a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
                AND a.detectionTime >= b.startTime AND a.detectionTime <= b.endTime
                AND (
						hostName LIKE 'SVDU__' OR hostName LIKE 'SVDU___' 
					) 
				AND b.idFlightPhase IN ($flightPhases) ";
				// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
    
    // Apply customer filter if any
    if(count($faultsToKeep) > 0) {
        $codes = implode(",", $faultsToKeep);
        $query .= " AND a.faultCode IN ($codes)";
    } else if(count($faultsToRemove) > 0) {
        $codes = implode(",", $faultsToRemove);
        $query .= " AND a.faultCode NOT IN ($codes)";
    }

    if(isset($flightLegsCondition)) {
        $query .= "AND $flightLegsCondition";
    } else {
        $query .= " AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime'";
    }

    $query .= " GROUP BY hostName";
	//echo $query;exit;
    $result = mysqli_query($dbConnection, $query);

    if($result) {
        while ($row = mysqli_fetch_array($result)) {
            $hostname = $row['hostName'];
            $count = $row['count'];
            $svdus[$hostname] = $count;
        }
    }

	$darkThreshold = 10;
    $dangerThreshold = 5;
    $warningThreshold = 3;
} else if($dataType == 'reset') {
	$query = "SELECT eventData, COUNT(DISTINCT(idEvent)) AS count
				FROM BIT_events a
				INNER JOIN SYS_flightPhase b
	            ON a.idFlightLeg = b.idFlightLeg  
	            AND a.lastUpdate >= b.startTime AND a.lastUpdate <= b.endTime
				AND (
						eventData LIKE 'SVDU__' OR eventData LIKE 'SVDU___' 
					) 
				AND b.idFlightPhase IN ($flightPhases) ";
				// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
				error_log('reset query :'.$query);
	if(isset($flightLegsCondition)) {
		$query .= "AND $flightLegsCondition";
	} else {
        $query .= " AND a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime'";
    }

	$query .= " GROUP BY eventData";
	//echo $query; exit;
	$result = mysqli_query($dbConnection, $query);

	if($result) {
		while ($row = mysqli_fetch_array($result)) {
			$hostname = $row['eventData'];
			$count = $row['count'];
			$svdus[$hostname] = $count;
		}
	}

	$darkThreshold = 10;
    $dangerThreshold = 5;
    $warningThreshold = 3;
} else if($dataType == 'applications') {
    $query = "SELECT hostName, COUNT(*) AS count
                FROM BIT_extAppEvent a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg
                AND b.idFlightPhase IN ($flightPhases) 
                AND a.detectionTime >= b.startTime AND a.detectionTime <= b.endTime
                AND (
						hostName LIKE 'SVDU__' OR hostName LIKE 'SVDU___'
					)";
                // AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
    
    if(isset($flightLegsCondition)) {
        $query .= "AND $flightLegsCondition";
    }
    $query .= " GROUP BY hostName";

    $result = mysqli_query($dbConnection, $query);

    if($result) {
        while ($row = mysqli_fetch_array($result)) {
            $hostname = $row['hostName'];
            $count = $row['count'];
            $svdus[$hostname] = $count;
        }
    }

	$darkThreshold = 100;
    $dangerThreshold = 50;
    $warningThreshold = 20;
}

// Display LOPA
$ids = array();
$maxId;
foreach(range('L','A') as $i) {

    $query = "SELECT DISTINCT hostName 
    FROM BIT_lru 
    WHERE hostName LIKE 'SVDU%$i' AND (hostName LIKE 'SVDU__' OR hostName LIKE 'SVDU___')
    ORDER BY LENGTH(hostName), hostName";
    $result = mysqli_query($dbConnection, $query);
    if($result) {
        if(mysqli_num_rows($result) > 0) {
            echo "<tr style=\"padding:0px; font-size:10px\">";
            echo "<td style=\"vertical-align: middle\"><b>$i&nbsp;&nbsp;&nbsp;</b></td>";
            $j = 1;
            while($row = mysqli_fetch_array($result)) {
                $hostName = $row['hostName'];

                // get seat number
                $length = strlen($hostName) - 5; // 4 for 'SVDU' + 1 for letter
                $id = substr($hostName, 4, $length);
                $index = findFirstDigit($hostName);
                $seat = substr($hostName, $index);

                $count = '&nbsp;';
                $countTooltip = '0';
                $buttonType = "btn-info";
                if(array_key_exists($hostName, $svdus)) {
                	$count = $svdus[$hostName];
                    $countTooltip = $count;
                	if($count >= $darkThreshold) {
                		$buttonType = "btn-inverse";
					} else if ($count >= $dangerThreshold) {
                		$buttonType = "btn-danger";
                	} else if($count >= $warningThreshold) {
                		$buttonType = "btn-warning";
                	}
                } 
                if(!in_array($id, $ids)) {
                    $ids[] = $id;
                    if($maxId < $id) {
                        $maxId = $id;
                    }
                }
                while ($j < $id) {
                    echo "<td>&nbsp;&nbsp;</td>";
                    $j++;
                }
                echo "<td style=\"text-align: center;padding: 2px\">";
                if(isset($flightLegsCondition)) {
                    echo    "<a href=\"unitTimeline.php?aircraftId=$aircraftId&id=$flightLegs&event=SVDU$seat\" target=\"_blank\" role=\"button\" id=\"$seat\" class=\"btn $buttonType btn-xs seat\" style=\"width:30px\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Seat $seat - $countTooltip\" data-poload=\"/test.php\">$count</a>";
                } else {
                    echo    "<span data-toggle=\"tab\" class=\"tabView\" data-target=\"#seatModal\" data-seat=\"$seat\">
                                <a role=\"button\" id=\"$seat\" class=\"btn $buttonType btn-xs seat\" style=\"width:30px\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Seat $seat - $countTooltip\" data-poload=\"/test.php\" onclick=\"seatSelected('$seat');\">$count</a>
                            </span>";
                }
                echo "</td>";
                $j++;
            }

            echo "</tr>";
        }
    }
}

echo "<tr style=\"text-align: center\"><td></td>";
for ($i=1; $i <= $maxId; $i++) { 
    echo "<td style=\"font-size:10px\">";
    if(in_array($i, $ids)) {
        echo "<b>$i</b>";
    }
    echo "</td>";
}
echo "</tr>";

// Additional empty row for the scrollbar so it is not displayed on over the row numbers
// echo "<tr><td>&nbsp;</td></tr>";

?>
