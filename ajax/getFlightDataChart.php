<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
date_default_timezone_set("GMT");
session_start();

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once('../engineering/checkEngineeringPermission.php');

$flightsColors = "151,187,205";
//$failuresColor = "247,70,74";
//$failuresColor = "253, 180, 92";
//$failuresColor = "151,187,205";
//$failuresColor = "220,220,220";0,206,209

//$failuresColor = "0,206,209";
//$failuresColor = "135,206,250";
$failuresColor = "70,130,180";

//$resetsColor = "253, 180, 92";
//$resetsColor = "151,187,205";
$resetsColor = "220,220,220";

$dataChartType = $_REQUEST['dataChartType'];
$aircraftId = $_REQUEST['aircraftId'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];
// $showCritical = $_SESSION['showCritical'];


if($aircraftId != '') {
    $query = "SELECT databaseName FROM aircrafts a WHERE a.id = $aircraftId";
    $result = mysqli_query($dbConnection, $query);
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $dbName = $row['databaseName'];

        $selected = mysqli_select_db($dbConnection, $dbName)
            or die("Could not select ".$dbName);
    } else {
         echo "<br>error: ".mysql_error($dbhandle);
    }
} else {
    $selected = mysqli_select_db($dbConnection, $sqlDump)
            or die("Could not select ".$sqlDump);
}

$flightPhases = getFlightPhases();
$criticalFailures = getCriticalFailures();
$criticalFaults = getCriticalFaults();


$headEndFailureTypes = "accusedHostname LIKE 'DSU%' OR accusedHostname LIKE 'LAIC%' OR accusedHostname LIKE 'AVCD%' OR accusedHostname LIKE 'ADB%'";
$svduFailureTypes = "accusedHostname LIKE 'SVDU%'";
$tpmuFailureTypes = "accusedHostname LIKE 'TPMU%' OR accusedHostname LIKE '%PCU%'";
$qsebFailureTypes = "accusedHostname LIKE 'QSEB%' OR accusedHostname LIKE 'SDB%'";

$headEndFaultTypes = "hostName LIKE 'DSU%' OR hostName LIKE 'LAIC%' OR hostName LIKE 'AVCD%' OR hostName LIKE 'ADBG%'";
$svduFaultTypes = "hostName LIKE 'SVDU%'";
$tpmuFaultTypes = "hostName LIKE 'TPMU%' OR hostName LIKE '%PCU%'";
$qsebFaultTypes = "hostName LIKE 'QSEB%' OR hostName LIKE 'SDB%'";

$headEndResetTypes = "eventData LIKE 'DSU%' OR eventData LIKE 'LAIC%' OR eventData LIKE 'AVCD%' OR eventData LIKE 'ADBG%'";
$svduResetTypes = "eventData LIKE 'SVDU%'";
$tpmuResetTypes = "eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%'";
$qsebResetTypes = "eventData LIKE 'QSEB%' OR eventData LIKE 'SDB%'";

switch ($dataChartType) {
    case 'headEndFailureCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_failure', 'failureCode', $flightPhases, $flightLegs, $headEndFailureTypes, $showCritical, $criticalFailures);
        break;

    case 'headEndFailureHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_failure', 'accusedHostname', $flightPhases, $flightLegs, $headEndFailureTypes, $showCritical, $criticalFailures);
        break;

    case 'headEndFaultCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_fault', 'faultCode', $flightPhases, $flightLegs, $headEndFaultTypes, $showCritical, $criticalFaults);
        break;
    
    case 'headEndFaultHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_fault', 'hostName', $flightPhases, $flightLegs, $headEndFaultTypes, $showCritical, $criticalFaults);
        break;

    case 'headEndExtAppCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_extAppEvent', 'faultCode', $flightPhases, $flightLegs, $headEndFaultTypes);
        break;

    case 'headEndExtAppHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_extAppEvent', 'hostName', $flightPhases, $flightLegs, $headEndFaultTypes);
        break;

    case 'headEndResetCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_events', 'eventName', $flightPhases, $flightLegs, $headEndResetTypes);
        break;

    case 'headEndResetHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_events', 'eventData', $flightPhases, $flightLegs, $headEndResetTypes);
        break;
    
    case 'svduFailureCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_failure', 'failureCode', $flightPhases, $flightLegs, $svduFailureTypes, $showCritical, $criticalFailures);
        break;

    case 'svduFailureHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_failure', 'accusedHostname', $flightPhases, $flightLegs, $svduFailureTypes, $showCritical, $criticalFailures);
        break;

    case 'svduFaultCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_fault', 'faultCode', $flightPhases, $flightLegs, $svduFaultTypes, $showCritical, $criticalFaults);
        break;
    
    case 'svduFaultHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_fault', 'hostName', $flightPhases, $flightLegs, $svduFaultTypes, $showCritical, $criticalFaults);
        break;

    case 'svduExtAppCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_extAppEvent', 'faultCode', $flightPhases, $flightLegs, $svduFaultTypes);
        break;

    case 'svduExtAppHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_extAppEvent', 'hostName', $flightPhases, $flightLegs, $svduFaultTypes);
        break;

    case 'svduResetCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_events', 'eventName', $flightPhases, $flightLegs, $svduResetTypes);
        break;

    case 'svduResetHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_events', 'eventData', $flightPhases, $flightLegs, $svduResetTypes);
        break;

    case 'tpmuFailureCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_failure', 'failureCode', $flightPhases, $flightLegs, $tpmuFailureTypes, $showCritical, $criticalFailures);
        break;

    case 'tpmuFailureHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_failure', 'accusedHostname', $flightPhases, $flightLegs, $tpmuFailureTypes, $showCritical, $criticalFailures);
        break;

    case 'tpmuFaultCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_fault', 'faultCode', $flightPhases, $flightLegs, $tpmuFaultTypes, $showCritical, $criticalFaults);
        break;
    
    case 'tpmuFaultHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_fault', 'hostName', $flightPhases, $flightLegs, $tpmuFaultTypes, $showCritical, $criticalFaults);
        break;

    case 'tpmuExtAppCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_extAppEvent', 'faultCode', $flightPhases, $flightLegs, $tpmuFaultTypes);
        break;

    case 'tpmuExtAppHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_extAppEvent', 'hostName', $flightPhases, $flightLegs, $tpmuFaultTypes);
        break;

    case 'tpmuResetHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_events', 'eventData', $flightPhases, $flightLegs, $tpmuResetTypes);
        break;

    case 'tpmuResetCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_events', 'eventName', $flightPhases, $flightLegs, $tpmuResetTypes);
        break;
	
	case 'qsebFailureCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_failure', 'failureCode', $flightPhases, $flightLegs, $qsebFailureTypes, $showCritical, $criticalFailures);
        break;

    case 'qsebFailureHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_failure', 'accusedHostname', $flightPhases, $flightLegs, $qsebFailureTypes, $showCritical, $criticalFailures);
        break;

    case 'qsebFaultCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_fault', 'faultCode', $flightPhases, $flightLegs, $qsebFaultTypes, $showCritical, $criticalFaults);
        break;
    
    case 'qsebFaultHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_fault', 'hostName', $flightPhases, $flightLegs, $qsebFaultTypes, $showCritical, $criticalFaults);
        break;

    case 'qsebExtAppCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_extAppEvent', 'faultCode', $flightPhases, $flightLegs, $qsebFaultTypes);
        break;

    case 'qsebExtAppHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_extAppEvent', 'hostName', $flightPhases, $flightLegs, $qsebFaultTypes);
        break;

    case 'qsebResetHostnames':
        $dataQuery = getDataChart($dbConnection, 'BIT_events', 'eventData', $flightPhases, $flightLegs, $qsebResetTypes);
        break;

    case 'qsebResetCodes':
        $dataQuery = getDataChart($dbConnection, 'BIT_events', 'eventName', $flightPhases, $flightLegs, $qsebResetTypes);
        break;
		
    default:
        $dataQuery = array();
        break;
}

$labels = array();
$data = array();

foreach ($dataQuery as $label => $value) {
    $labels[] = $label;
    $data[] = $value;
}

$color = $failuresColor;

$dataChart = array(
    'labels' => $labels,
    'datasets' => [[
        'label' => "Failure Codes",
        'fillColor' => "rgba($color,0.5)",
        'strokeColor' => "rgba($color,0.8)",
        'highlightFill' => "rgba($color,0.75)",
        'highlightStroke' => "rgba($color,1)",
        'data' => $data
    ]]
);

echo json_encode($dataChart, JSON_NUMERIC_CHECK );


function getDataChart($dbConnection, $table, $data, $flightPhases, $flightLegs, $unitTypes, $showCritical = false, $criticalValues = '') {
    $dataQuery = array();

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
    }

	if( ($table == 'BIT_fault') || ($table == 'BIT_extAppEvent') ) {
		$column = 'detectionTime';
	} else if($table == 'BIT_failure') {
		$column = 'correlationDate';
	} else if($table == 'BIT_events') {
		$column = 'lastUpdate';
	}
	
    $query = "SELECT $data, COUNT(*) AS \"count\"
            FROM $table a
            INNER JOIN SYS_flightPhase b
            ON a.idFlightLeg = b.idFlightLeg
            AND b.idFlightPhase IN ($flightPhases) 
			AND a.$column BETWEEN b.startTime AND b.endTime
            AND $flightLegsCondition
            AND ($unitTypes)";
    if($showCritical) {
        $query .= " AND a.failureCode  IN (".implode(",", $criticalValues).") ";
    }
    $query .= " 
        GROUP BY $data
        ORDER BY count DESC
        LIMIT 5";
    
    $result = mysqli_query ($dbConnection, $query );
    if($result) {
        if(mysqli_num_rows($result)) {
            while ( $row = mysqli_fetch_assoc ( $result ) ) {
                $code = $row [$data];
                if($code == 'UncommandedReboot') {
                    $code = 'Uncommanded';
                } else if($code == 'CommandedReboot') {
                    $code = 'Commanded';
                }
                $count = $row ['count'];
                $dataQuery[$code] = $count;
            }
        }
    }

    return $dataQuery;
}

?>
