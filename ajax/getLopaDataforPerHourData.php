<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);

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


$failuresToRemove = getFailuresToRemove();
$failuresToKeep = getFailuresToKeep();
$faultsToRemove = getFaultsToRemove();
$faultsToKeep = getFaultsToKeep();

$flightPhases = getFlightPhases();
$flightPhasescode=$flightPhases;

if(count($flightPhasescode)>0 and $flightPhasescode!=null){
	$flightPhasescode=$flightPhases;
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
        $query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    }

    $query .= " GROUP BY accusedHostName";
	
    $result = mysqli_query($dbConnection, $query);
	$activefailureFlag=-1;
    if($result) {		
        while ($row = mysqli_fetch_array($result)) {
			$activefailureFlag=1;
            $hostname = $row['accusedHostName'];
			$count=0.0;
			$activeFailuresquery = "SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND a.correlationDate >= b.startTime AND a.correlationDate <= b.endTime
                AND (
						accusedHostName LIKE '$hostname' OR accusedHostName LIKE '$hostname' 
					)
                AND monitorState = 3";

				// Apply customer filter if any
				if(count($failureCode)>0 and $failureCode!=''){
					$activeFailuresquery.=" AND a.failureCode IN ($failureCode)  ";
				}else{
					if(count($failuresToKeep) > 0) {
						$codes = implode(",", $failuresToKeep);
						$activeFailuresquery .= " AND a.failureCode IN ($codes)";
					} else if(count($failuresToRemove) > 0) {
						$codes = implode(",", $failuresToRemove);
						$activeFailuresquery .= " AND a.failureCode NOT IN ($codes)";
					}
				}
				
				if(isset($flightLegsCondition)) {
					$activeFailuresquery .= "AND $flightLegsCondition";
				} else {
					$activeFailuresquery .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
				}
				
				$result1 = mysqli_query($dbConnection, $activeFailuresquery);
				if($result1) {						
					while ($row1 = mysqli_fetch_array($result1)) {
							$cruiseTime+= $row1['cruiseTime'];													
						}
					}
				$count = $row['count'];							
				$count=$count/$cruiseTime;
				$svdus[$hostname] = round($count,2);
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
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))))
                AND (
						accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___' 
					)";

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
        $query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    }

    $query .= " GROUP BY accusedHostName";
	
    $result = mysqli_query($dbConnection, $query);
	$failureFlag=-1;
    if($result) {
        while ($row = mysqli_fetch_array($result)) {
			$failureFlag=1;
            $hostname = $row['accusedHostName'];
			$count=0.0;
			$failurequery = "SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))))
                AND (
						accusedHostName LIKE '$hostname' OR accusedHostName LIKE '$hostname' 
					)";

				// Apply customer filter if any
				if(count($failureCode)>0 and $failureCode!=''){
					$failurequery.=" AND a.failureCode IN ($failureCode)  ";
				}else{
					if(count($failuresToKeep) > 0) {
						$codes = implode(",", $failuresToKeep);
						$failurequery .= " AND a.failureCode IN ($codes)";
					} else if(count($failuresToRemove) > 0) {
						$codes = implode(",", $failuresToRemove);
						$failurequery .= " AND a.failureCode NOT IN ($codes)";
					}
				}
				if(isset($flightLegsCondition)) {
					$failurequery .= "AND $flightLegsCondition";
				} else {
					$failurequery .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
				}
				
				$result1 = mysqli_query($dbConnection, $failurequery);
				if($result1) {						
					while ($row1 = mysqli_fetch_array($result1)) {
						$cruiseTime+= $row1['cruiseTime'];													
						}
					}
				$count = $row['count'];							
				$count=$count/$cruiseTime;
				$svdus[$hostname] = round($count,2);
        }
    }

    $darkThreshold = 20;
	$dangerThreshold = 10;
    $warningThreshold = 5;
} else if($dataType == 'faults') {
	$query = "SELECT hostName, COUNT(DISTINCT a.hostName,a.serialNumber,a.faultCode,a.reportingHostName,a.monitorState,a.detectionTime,a.clearingTime,a.idFlightLeg) AS count
                FROM BIT_fault a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
                AND ((a.monitorState=3 AND a.detectionTime<=b.endTime) OR (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime))))
                AND (
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
        $query .= " AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime'";
    }

    $query .= " GROUP BY hostName";
	//echo $query;exit;
	
    $result = mysqli_query($dbConnection, $query);
	$faultsFlag=-1;
    if($result) {
        while ($row = mysqli_fetch_array($result)) {
			$faultsFlag=1;
            $hostname = $row['hostName'];
			$count=0.0;
			$faultquery = "SELECT DISTINCT a.hostName,a.serialNumber,a.faultCode,a.reportingHostName,a.monitorState,a.detectionTime,a.clearingTime,a.idFlightLeg
                FROM BIT_fault a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
                AND ((a.monitorState=3 AND a.detectionTime<=b.endTime) OR (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime))))
                AND (
						hostName LIKE '$hostname' OR hostName LIKE '$hostname' 
					) 
				AND b.idFlightPhase IN ($flightPhasescode) ";
				// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
				
    // Apply customer filter if any
  
				if(count($faultCode)>0 and $faultCode!=''){
					$faultquery.=" AND a.faultCode IN ($faultCode)";
				}else{
					if(count($faultsToKeep) > 0) {
						$codes = implode(",", $faultsToKeep);
						$faultquery .= " AND a.faultCode IN ($codes)";
					} else if(count($faultsToRemove) > 0) {
						$codes = implode(",", $faultsToRemove);
						$faultquery .= " AND a.faultCode NOT IN ($codes)";
					}
				
				}

				if(isset($flightLegsCondition)) {
					$faultquery .= "AND $flightLegsCondition";
				} else {
					$faultquery .= " AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime'";
				}
				
				$result1 = mysqli_query($dbConnection, $faultquery);
				if($result1) {						
					while ($row1 = mysqli_fetch_array($result1)) {
					$flytLeg=$row1['idFlightLeg'];
					$detectionTime=$row1['detectionTime'];
					$clearingTime=$row1['clearingTime'];
						$startTimequery = "SELECT startTime FROM SYS_flightPhase b WHERE b.idFlightLeg=$flytLeg AND ( (STR_TO_DATE('$detectionTime', '%Y-%m-%d %T') BETWEEN b.startTime AND b.endTime) OR (STR_TO_DATE('$clearingTime', '%Y-%m-%d %T') BETWEEN b.startTime AND b.endTime) ) ORDER BY idFlightPhase LIMIT 0,1";
						$result2 = mysqli_query($dbConnection, $startTimequery);
						if($result2) {						
							while ($row2 = mysqli_fetch_array($result2)) {
								$startTime=$row2['startTime'];
							}
						}
						$endTimequery = "SELECT endTime FROM SYS_flightPhase b WHERE b.idFlightLeg=$flytLeg AND ( (STR_TO_DATE('$detectionTime', '%Y-%m-%d %T') BETWEEN b.startTime AND b.endTime) OR (STR_TO_DATE('$clearingTime', '%Y-%m-%d %T') BETWEEN b.startTime AND b.endTime) ) ORDER BY idFlightPhase LIMIT 0,1";
						$result3 = mysqli_query($dbConnection, $endTimequery);
						if($result3) {						
							while ($row3 = mysqli_fetch_array($result3)) {
								$endTime=$row3['endTime'];
							}
						}
						
						$diff = strtotime($endTime) - strtotime($startTime);						
						$sec+=$diff;
												
					}
				}
			$count = $row['count'];
            $value=($sec/3600);
			$count=$count/$value;						
            $svdus[$hostname] = round($count,2);
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
        $query .= " AND a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime'";
    }

	$query .= " GROUP BY eventData";	
		
	//echo $query; exit;
	$resetFlag=-1;
	$result = mysqli_query($dbConnection, $query);
	
	if($result) {		
		while ($row = mysqli_fetch_array($result)) {
			
			$resetFlag=1;
			$hostname = $row['eventData'];
			$count=0.0;
			$resetquery = "SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime
				FROM BIT_events a
				INNER JOIN SYS_flightPhase b
	            ON a.idFlightLeg = b.idFlightLeg  
	            AND a.lastUpdate >= b.startTime AND a.lastUpdate <= b.endTime ";
				
				//if(count($resetsCode)>0){				
				if($resetsCode!=''){
				
					$resetquery.="AND eventName IN ($resetsCode) ";
				}
				$resetquery .= "AND (
									eventData LIKE '$hostname' OR eventData LIKE '$hostname' 
								) 
							AND b.idFlightPhase IN ($flightPhasescode) ";
							// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
				if(isset($flightLegsCondition)) {
					$resetquery .= "AND $flightLegsCondition";
				} else {
					$resetquery .= " AND a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime'";
				}
				
				$result1 = mysqli_query($dbConnection, $resetquery);
				if($result1) {						
					while ($row1 = mysqli_fetch_array($result1)) {																	
							$cruiseTime+= $row1['cruiseTime'];													
						}
					}
				$count = $row['count'];
				
				$count=$count/$cruiseTime;
				$svdus[$hostname] = round($count,2);
		}
	}	
	$darkThreshold = 10;
    $dangerThreshold = 5;
    $warningThreshold = 3;
} else if($dataType == 'applications') {
    $query="SELECT a.hostName, COUNT(*) AS COUNT  FROM BIT_extappevent a INNER JOIN SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
		if(count($faultCode)>0 and $faultCode!=''){
			$query.="a.faultCode IN ($faultCode) AND";
		}
		$query.="(a.hostName LIKE 'SVDU__' OR a.hostName LIKE 'SVDU___' )   AND a.detectionTime BETWEEN b.startTime AND b.endTime AND b.idFlightPhase IN ($flightPhasescode)";
		
		$query.=" AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime' GROUP BY a.hostName";
					 
		$result = mysqli_query($dbConnection, $query);
		
		$appEventFlag=-1;
		if($result) {		
			while ($row = mysqli_fetch_array($result)) {
				$appEventFlag=1;
				$hostname = $row['hostName'];
				$count=0.0;
				$appquery="SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime FROM BIT_extappevent a INNER JOIN SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
				if(count($faultCode)>0 and $faultCode!=''){
					$appquery.="a.faultCode IN ($faultCode) AND";
				}
				$appquery.="(a.hostName LIKE '$hostname' OR a.hostName LIKE '$hostname' )   AND a.detectionTime BETWEEN b.startTime AND b.endTime AND b.idFlightPhase IN ($flightPhasescode)";
				
				$appquery.=" AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime' ";
					$result1 = mysqli_query($dbConnection, $impactquery);
					if($result1) {						
						while ($row1 = mysqli_fetch_array($result1)) {					
							$cruiseTime+= $row1['cruiseTime'];													
						}
					}
				$count = $row['count'];							
				$count=$count/$cruiseTime;
				$svdus[$hostname] = round($count,2);
			}
		}		
		$darkThreshold = 20;
		$dangerThreshold = 10;
		$warningThreshold = 5;
		
} else if($dataType == 'impactedServices') {
    
    $query = "SELECT accusedHostName, COUNT(DISTINCT a.failureCode,a.correlationDate,a.monitorState,a.accusedHostName,a.lastUpdate,a.idFlightLeg,a.idService) AS count
                FROM bit_servicefailure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))))
                AND (
						accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___' 
					)";

    // Apply customer filter if any
    if(count($ImpactedServicesCode)>0 and $ImpactedServicesCode!=''){
		$query.=" AND a.failureCode IN ($ImpactedServicesCode) ";
	}
    if(isset($flightLegsCondition)) {
        $query .= "AND $flightLegsCondition";
    } else {
        $query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    }

    $query .= " GROUP BY accusedHostName";
	
    $result = mysqli_query($dbConnection, $query);
	$impactedServicesFlag=-1;
	
    if($result) {
        while ($row = mysqli_fetch_array($result)) {
			$impactedServicesFlag=1;
            $hostname = $row['accusedHostName'];
            $count=0.0;
			$impactquery = "SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime
                FROM bit_servicefailure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))))
                AND (
						accusedHostName LIKE '$hostname' OR accusedHostName LIKE '$hostname' 
					)";

					// Apply customer filter if any
					if(count($ImpactedServicesCode)>0 and $ImpactedServicesCode!=''){
						$impactquery.=" AND a.failureCode IN ($ImpactedServicesCode) ";
					}
					if(isset($flightLegsCondition)) {
						$impactquery .= "AND $flightLegsCondition";
					} else {
						$impactquery .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
					}
				$result1 = mysqli_query($dbConnection, $impactquery);
				if($result1) {						
					while ($row1 = mysqli_fetch_array($result1)) {					
							$cruiseTime+= $row1['cruiseTime'];													
						}
					}
				$count = $row['count'];							
				$count=$count/$cruiseTime;
				$svdus[$hostname] = round($count,2);
        }
    }

    $darkThreshold = 20;
	$dangerThreshold = 10;
    $warningThreshold = 5;
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
            echo "<tr style=\"padding:0px; font-size:10px\">";
            echo "<td style=\"vertical-align: middle\"><b>$i&nbsp;&nbsp;&nbsp;</b></td>";
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
					if($count==0){
						$count='0.0';
					}
                }else{
					$count='&nbsp;';
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
				if($count==0){
					
				}
                if(isset($flightLegsCondition)) {
                    echo    "<a href=\"unitTimeline.php?aircraftId=$aircraftId&id=$flightLegs&event=SVDU$seat\" target=\"_blank\" role=\"button\" id=\"$seat\" class=\"btn $buttonType btn-xs seat\" style=\"width:35px;\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Seat $seat - $countTooltip\" data-poload=\"/test.php\">$count</a>";
                } else {
				
					//if (ctype_digit($count))  {
					  if($activefailureFlag==1 || $failureFlag==1 || $faultsFlag==1 || $resetFlag==1 || $appEventFlag==1 || $impactedServicesFlag==1 ){
						echo    "<span data-toggle=\"tab\" class=\"tabView\" data-target=\"#seatModal\" data-seat=\"$seat\">
                                <a role=\"button\" id=\"$seat\" class=\"btn $buttonType btn-xs seat\" style=\"width:35px;\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Seat $seat - $countTooltip\" data-poload=\"/test.php\" onclick=\"seatSelected('$seat','$count');\">$count</a>
                            </span>";
						}else{
							echo    "<span data-toggle=\"tab\" class=\"tabView\" data-target=\"#seatModal\" data-seat=\"$seat\">
                                <a role=\"button\" id=\"$seat\" class=\"btn $buttonType btn-xs seat gradedBtn\" style=\"width:35px;background-image: linear-gradient(to bottom,#C0C0C0 0,#C0C0C0 100%) !important;border-color: #C0C0C0;\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Seat $seat - $countTooltip\" data-poload=\"/test.php\" onclick=\"seatSelected('$seat','$count');\">$count</a>
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
