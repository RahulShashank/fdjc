<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 5000);
session_start ();
require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/customerFilters.php";
require_once('../engineering/checkEngineeringPermission.php');
require_once "../common/seatAnalyticsData.php";

$aircraftId = $_REQUEST['tailsign'];
$sqlDump = $_REQUEST['sqlDump'];
$flightLegs = $_REQUEST['flightLegs'];
$dataType = $_REQUEST['dataType'];

$faultCode = $_REQUEST['faultCode'];
$failureCode = $_REQUEST['failureCode'];
$ImpactedServicesCode = $_REQUEST['ImpactedServicesCode'];
$flightPhasescode = $_REQUEST['flightPhases'];
$resetsCode = $_REQUEST['resetCode'];

$_SESSION['airline'] =  $_REQUEST['airline'];
$_SESSION['platform'] =  $_REQUEST['platform'];
$_SESSION['configType'] =  $_REQUEST['configType'];
$_SESSION['software'] =  $_REQUEST['software'];
$_SESSION['tailsign'] =  $_REQUEST['aircraftId'];
$_SESSION['startDate'] =  $_REQUEST['startDateTime'];
$_SESSION['endDate'] =  $_REQUEST['endDateTime'];
$_SESSION['faultCode'] =  $_REQUEST['faultCode'];
$_SESSION['failureCode'] =  $_REQUEST['failureCode'];
$_SESSION['ImpactedServicesCode'] =  $_REQUEST['ImpactedServicesCode'];
$_SESSION['flightPhases'] =  $_REQUEST['flightPhases'];
$_SESSION['resetCode'] =  $_REQUEST['resetCode'];
$_SESSION['monitorState'] =  $_REQUEST['monitorState'];

foreach ($_REQUEST['monitorState'] as $ts){
	$monitorState.=  $ts . ",";	
}
$monitorState = rtrim($monitorState, ",");
            
error_log('Monitor State : '.$monitorState);

$failuresToRemove = getFailuresToRemove();
$failuresToKeep = getFailuresToKeep();
$faultsToRemove = getFaultsToRemove();
$faultsToKeep = getFaultsToKeep();

$flightPhases = getAllFlightPhases();

if(count($flightPhasescode)>0 and $flightPhasescode!=null){
	$flightPhasescode=$flightPhasescode;
}else{
	$flightPhasescode=$flightPhases;	
}

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
			//or die(echo "<td id="errortd">Error</td>";.$dbName);
    } else {
         echo "<br>error: ".mysql_error($dbhandle);
    }
} else {
	$selected = mysqli_select_db($dbConnection, $sqlDump)
			or die("Could not select ".$sqlDump);
			//or die(echo "<td id="errortd">Error</td>";.$sqlDump);
			
}

$svdus = array();

if($dataType == 'activeFailures') {
    $query = "SELECT accusedHostName, COUNT(*) AS count
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND a.correlationDate >= b.startTime AND a.correlationDate <= b.endTime
                AND (
						accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___' 
					)
                AND monitorState = 3";

    // Apply customer filter if any
    if(count($failureCode)>0 and $failureCode!=''){
		$query.=" AND a.failureCode IN ($failureCode)  ";
	}else{
		if(count($failuresToKeep) > 0) {
			$codes = implode(",", $failuresToKeep);
			$query .= " AND a.failureCode IN ($codes)";
		} else if(count($failuresToRemove) > 0) {
			$codes = implode(",", $failuresToRemove);
			$query .= " AND a.failureCode NOT IN ($codes)";
		}
	}
    
    if(isset($flightLegsCondition)) {
        $query .= "AND $flightLegsCondition";
    } else {
        //$query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
        $query .= "AND (DATE(a.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

    $query .= " GROUP BY accusedHostName";
	
    $result = mysqli_query($dbConnection, $query);
	$activefailureFlag=-1;
    if($result) {		
        while ($row = mysqli_fetch_array($result)) {
			$activefailureFlag=1;
            $hostname = $row['accusedHostName'];
            $count = $row['count'];
            $svdus[$hostname] = $count;
        }
    }

    $darkThreshold = 5;
	$dangerThreshold = 3;
    $warningThreshold = 1;
} else if($dataType == 'failures') {
    $query = "SELECT accusedHostName, COUNT(DISTINCT a.failureCode,a.correlationDate,a.monitorState,a.accusedHostName,a.lastUpdate,a.serialNumber,a.idFlightLeg) AS count
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) ";

	if($monitorState=='3'){
		$query .= " AND ((a.monitorState=3 AND a.correlationDate<=b.endTime)) ";
	}else if($monitorState=='1'){
		$query .= " AND (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))) ";
	}else {
		$query .= " AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime)))) ";
	}
	
    $query .= " AND ( accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___') ";

    // Apply customer filter if any
    if(count($failureCode)>0 and $failureCode!=''){
		$query.=" AND a.failureCode IN ($failureCode)  ";
	}else{
		if(count($failuresToKeep) > 0) {
			$codes = implode(",", $failuresToKeep);
			$query .= " AND a.failureCode IN ($codes) ";
		} else if(count($failuresToRemove) > 0) {
			$codes = implode(",", $failuresToRemove);
			$query .= " AND a.failureCode NOT IN ($codes) ";
		}
    }
    if(isset($flightLegsCondition)) {
        $query .= " AND $flightLegsCondition ";
    } else {
        //$query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    	$query .= " AND (DATE(a.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

    $query .= " GROUP BY accusedHostName";
	error_log('Query : '.$query);
    $result = mysqli_query($dbConnection, $query);
	$failureFlag=-1;
    if($result) {
        while ($row = mysqli_fetch_array($result)) {
			$failureFlag=1;
            $hostname = $row['accusedHostName'];
            $count = $row['count'];
            $svdus[$hostname] = $count;
        }
    }

    $darkThreshold = 20;
	$dangerThreshold = 10;
    $warningThreshold = 5;
} else if($dataType == 'faults') {
	$query = "SELECT hostName, COUNT(DISTINCT a.hostName,a.serialNumber,a.faultCode,a.reportingHostName,a.monitorState,a.detectionTime,a.clearingTime,a.idFlightLeg) AS count
                FROM BIT_fault a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg ";
	if($monitorState=='3'){
		$query.=" AND ((a.monitorState=3 AND a.detectionTime<=b.endTime)) ";
	}else if($monitorState=='1'){
		$query.=" AND (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime)))";
	}else {
		$query.=" AND ((a.monitorState=3 AND a.detectionTime<=b.endTime) OR (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime))))";
	}
       $query.="  AND (
						hostName LIKE 'SVDU__' OR hostName LIKE 'SVDU___' 
					) 
				AND b.idFlightPhase IN ($flightPhasescode) ";
				// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
    
    // Apply customer filter if any
  
				if(count($faultCode)>0 and $faultCode!=''){
					$query.=" AND a.faultCode IN ($faultCode)";
				}else{
					if(count($faultsToKeep) > 0) {
						$codes = implode(",", $faultsToKeep);
						$query .= " AND a.faultCode IN ($codes)";
					} else if(count($faultsToRemove) > 0) {
						$codes = implode(",", $faultsToRemove);
						$query .= " AND a.faultCode NOT IN ($codes)";
					}
				
				}

    if(isset($flightLegsCondition)) {
        $query .= "AND $flightLegsCondition";
    } else {
        //$query .= " AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime'";
    	$query .= "AND (DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

    $query .= " GROUP BY hostName";
	error_log('Query : '.$query);
    $result = mysqli_query($dbConnection, $query);
	$faultsFlag=-1;
    if($result) {
        while ($row = mysqli_fetch_array($result)) {
			$faultsFlag=1;
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
	            AND a.lastUpdate >= b.startTime AND a.lastUpdate <= b.endTime ";
				
				//if(count($resetsCode)>0){				
				if($resetsCode!=''){
				
					$query.="AND eventName IN ($resetsCode) ";
				}
	$query .= "AND (
						eventData LIKE 'SVDU__' OR eventData LIKE 'SVDU___' 
					) 
				AND b.idFlightPhase IN ($flightPhasescode) ";
				// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
	if(isset($flightLegsCondition)) {
		$query .= "AND $flightLegsCondition";
	} else {
        //$query .= " AND a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime'";
        $query .= " AND (DATE(a.lastUpdate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

	$query .= " GROUP BY eventData";	
		
	//echo $query; exit;
	$resetFlag=-1;
	$result = mysqli_query($dbConnection, $query);
	
	if($result) {		
		while ($row = mysqli_fetch_array($result)) {
			$resetFlag=1;
			$hostname = $row['eventData'];
			$count = $row['count'];
			$svdus[$hostname] = $count;
		}
	}	
	$darkThreshold = 10;
    $dangerThreshold = 5;
    $warningThreshold = 3;
} else if($dataType == 'applications') {
    $query="SELECT a.hostName, COUNT(*) AS COUNT  FROM BIT_extappevent a INNER JOIN SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
		/* if(count($faultCode)>0 and $faultCode!=''){
			$query.="a.faultCode IN ($faultCode) AND";
		} */
		$query.="(a.hostName LIKE 'SVDU__' OR a.hostName LIKE 'SVDU___' )   AND a.detectionTime BETWEEN b.startTime AND b.endTime AND b.idFlightPhase IN ($flightPhasescode)";
		
		//$query.=" AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime' GROUP BY a.hostName";
		
		$query .= "AND (DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))  GROUP BY a.hostName";
	
		$result = mysqli_query($dbConnection, $query);
		
		$impactedServicesFlag=-1;
		if($result) {		
			while ($row = mysqli_fetch_array($result)) {
				$impactedServicesFlag=1;
				$hostname = $row['hostName'];
				$count = $row['COUNT'];
				$svdus[$hostname] = $count;
			}
		}		
		$darkThreshold = 20;
		$dangerThreshold = 10;
		$warningThreshold = 5;
		
} else if($dataType == 'impactedServices') {
    
    $query = "SELECT accusedHostName, COUNT(DISTINCT a.failureCode,a.correlationDate,a.monitorState,a.accusedHostName,a.lastUpdate,a.idFlightLeg,a.idService,b.idFlightPhase) AS count
                FROM bit_servicefailure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) ";
    
	if($monitorState=='3'){
		$query.=" AND ((a.monitorState=3 AND a.correlationDate<=b.endTime)) ";
	}else if($monitorState=='1'){
		$query.=" AND (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))) ";
	}else {
		$query.=" AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime)))) ";
	}
    $query.=" AND (
						accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___' 
					) ";

    // Apply customer filter if any
    if(count($ImpactedServicesCode)>0 and $ImpactedServicesCode!=''){
		$query.=" AND a.failureCode IN ($ImpactedServicesCode) ";
	}
    if(isset($flightLegsCondition)) {
        $query .= "AND $flightLegsCondition";
    } else {
        //$query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    	$query .= "AND (DATE(a.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

    $query .= " GROUP BY accusedHostName";
    
    $result = mysqli_query($dbConnection, $query);
	$impactedServicesFlag=-1;
	
    if($result) {
        while ($row = mysqli_fetch_array($result)) {
			$impactedServicesFlag=1;
            $hostname = $row['accusedHostName'];
            $count = $row['count'];
            $svdus[$hostname] = $count;
        }
    }

    $darkThreshold = 20;
	$dangerThreshold = 10;
    $warningThreshold = 5;
}

displayLopaData();	  
function displayLopaData(){
	
}


// Display LOPA
$ids = array();
$maxId;
$dataFlag=0;
foreach(range('L','A') as $i) {

    $query = "SELECT DISTINCT hostName 
    FROM BIT_lru 
    WHERE hostName LIKE 'SVDU%$i' AND (hostName LIKE 'SVDU__' OR hostName LIKE 'SVDU___')
    ORDER BY LENGTH(hostName), hostName";
	
    $result = mysqli_query($dbConnection, $query);
    if($result) {
		
        if(mysqli_num_rows($result) > 0) {
            echo "<tr style=\"padding:0px; font-size:11px;\">";
            echo "<td style=\"vertical-align: middle\">$i&nbsp;&nbsp;&nbsp;</td>";
            $j = 1;
			$dataExists = false;
			
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
                    echo "<td></td>";
                    $j++;
                }
                echo "<td style=\"text-align: center;padding: 2px\">";
				if($count==0){
					$count="&nbsp;";
				}
                if(isset($flightLegsCondition)) {
                    echo    "<a href=\"unitTimeline.php?aircraftId=$aircraftId&id=$flightLegs&event=SVDU$seat\" target=\"_blank\" role=\"button\" id=\"$seat\" class=\"btn $buttonType btn-xs seat\" style=\"width: 25px;border-radius: 2px;height: 18px;line-height: 17px;font-size: 9px;\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Seat $seat - $countTooltip\" data-poload=\"/test.php\">$count</a>";
                } else {
					
					//if (ctype_digit($count))  {
					  //if($activefailureFlag==1 || $failureFlag==1 || $faultsFlag==1 || $resetFlag==1 || $appEventFlag==1 || $impactedServicesFlag==1 ){
					  if($countTooltip!=0){
						echo    "<span data-toggle=\"tab\" class=\"tabView\" data-target=\"#seatModal\" data-seat=\"$seat\">
                                <a role=\"button\" id=\"$seat\" class=\"btn $buttonType btn-xs  seat\" style=\"width: 25px;border-radius: 2px;height: 18px;line-height: 16px;font-size: 9px;\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Seat $seat - $countTooltip\" data-poload=\"/test.php\" onclick=\"seatSelected('$seat','$count');\">$count</a>
                            </span>";
						}else{
							echo    "<span data-toggle=\"tab\" class=\"tabView\" data-target=\"#seatModal\" data-seat=\"$seat\">
                                <a role=\"button\" id=\"$seat\" class=\"btn $buttonType btn-xs seat gradedBtn\" style=\"background-image: linear-gradient(to bottom,#C0C0C0 0,#C0C0C0 100%) !important;border-color: #C0C0C0;width: 25px;border-radius: 2px;height: 18px;line-height: 16px;font-size: 9px;\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Seat $seat - $countTooltip\" data-poload=\"/test.php\" onclick=\"seatSelected('$seat','$count');\">$count</a>
                            </span>";
						
						}
                }
                echo "</td>";
                $j++;
            }

            echo "</tr>";
        }else{
			$dataFlag++;
		}
    }
}

	if($dataFlag==12){	
		echo "<tr style=\"padding:0px; font-size:10px\">";
		echo "<td>No Data Available</td>";
		echo "</td></tr>";
	}

echo "<tr style=\"text-align: center\"><td></td>";
for ($i=1; $i <= $maxId; $i++) { 
    echo "<td style=\"font-size:11px; padding-top: 10px;\">";
    if(in_array($i, $ids)) {
        echo "$i";
    }
    echo "</td>";
}
echo "</tr>";

// Additional empty row for the scrollbar so it is not displayed on over the row numbers
// echo "<tr><td>&nbsp;</td></tr>";

?>
