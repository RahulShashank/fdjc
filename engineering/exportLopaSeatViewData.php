<?php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 3600);

require_once "../database/connecti_database.php";
require_once "../common/functions.php";
require_once "../common/customerFilters.php";
require_once('../engineering/checkEngineeringPermission.php');
require_once '../libs/PHPExcel/PHPExcel.php';
require_once '../libs/PHPExcel/PHPExcel/IOFactory.php';
require_once "../common/seatAnalyticsData.php";

$aircraftId = $_REQUEST['tailsign'];
$sqlDump = $_REQUEST['sqlDump'];
$startDateTime = $_REQUEST['startDateTime'];
$endDateTime = $_REQUEST['endDateTime'];
$seat = $_REQUEST['seat'];
$formatType = $_REQUEST['formatType'];
$dataType= $_REQUEST['dataType'];

$faultCode = $_REQUEST['faultCode'];
$failureCode = $_REQUEST['failureCode'];
$ImpactedServicesCode = $_REQUEST['ImpactedServicesCode'];
$flightPhasescode = $_REQUEST['flightPhases'];
$resetsCode = $_REQUEST['resetCode'];
error_log('MonitorSTatw : '.$_REQUEST['monitorState']);

$value=$_REQUEST['monitorState'];
if(is_array($value)){
	foreach ($value as $tss){
		$monitorState.=  $tss . ",";	
	}
}
$monitorState = rtrim($_REQUEST['monitorState'], ",");

$failuresToRemove = getFailuresToRemove();
$failuresToKeep = getFailuresToKeep();
$faultsToRemove = getFaultsToRemove();
$faultsToKeep = getFaultsToKeep();

//$flightPhases = getFlightPhases();
$flightPhases = getAllFlightPhases();

if(count($flightPhasescode)>0 and $flightPhasescode!=''){
	$flightPhasescode=$flightPhasescode;
}else{
	$flightPhasescode=$flightPhases;
}


$activeFailure = array();
$failure = array();
$reset = array();
$fault = array();
$impactedServices = array();
$appEvent = array();

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

if($dataType == 'all') {
    $activeFailurequery = "SELECT accusedHostName, COUNT(*) AS count
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
		$activeFailurequery.=" AND a.failureCode IN ($failureCode)  ";
	}else{
		if(count($failuresToKeep) > 0) {
			$codes = implode(",", $failuresToKeep);
			$activeFailurequery .= " AND a.failureCode IN ($codes)";
		} else if(count($failuresToRemove) > 0) {
			$codes = implode(",", $failuresToRemove);
			$activeFailurequery .= " AND a.failureCode NOT IN ($codes)";
		}
	}
    
    if(isset($flightLegsCondition)) {
        $activeFailurequery .= "AND $flightLegsCondition";
    } else {
        //$activeFailurequery .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    	$activeFailurequery .= " AND (DATE(a.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
        
    }

    $activeFailurequery .= " GROUP BY accusedHostName";
	
    $result = mysqli_query($dbConnection, $activeFailurequery);
	
	$activefailureFlag=-1;
    if($result) {		
        while ($row = mysqli_fetch_array($result)) {
			$activefailureFlag=1;
            $activefailurehostname = $row['accusedHostName'];
            $activefailurecount = $row['count'];
			
		/* 	$count=0.0;
			$activeFailuresquery = "SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND a.correlationDate >= b.startTime AND a.correlationDate <= b.endTime
                AND (
						accusedHostName LIKE '$activefailurehostname' OR accusedHostName LIKE '$activefailurehostname' 
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
							$activeFailurecruiseTime+= $row1['cruiseTime'];													
						}
					}
				$count = $row['count'];							
				$count=$count/$activeFailurecruiseTime;
				$activefailurePerHourcount = round($count,2); */
			
			$activeFailure[] = array(
				'activefailurehostname' => $activefailurehostname, 
				'activefailurecount' => $activefailurecount,
				'activefailurePerHourcount' => $activefailurePerHourcount
			);
        }
    }
	
	$failurequery = "SELECT accusedHostName, COUNT(DISTINCT a.failureCode,a.correlationDate,a.monitorState,a.accusedHostName,a.lastUpdate,a.serialNumber,a.idFlightLeg) AS count
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) ";

	if($monitorState=='3'){
		$failurequery .= " AND ((a.monitorState=3 AND a.correlationDate<=b.endTime)) ";
	}else if($monitorState=='1'){
		$failurequery .= " AND (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))) ";
	}else {
		$failurequery .= " AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime)))) ";
	}
	
    $failurequery .= " AND ( accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___') ";

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
        //$failurequery .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    	$failurequery .= " AND (DATE(a.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

    $failurequery .= " GROUP BY accusedHostName";

    $result = mysqli_query($dbConnection, $failurequery);
	$failureFlag=-1;
    if($result) {		
        while ($row = mysqli_fetch_array($result)) {
			$failureFlag=1;
            $failurehostname = $row['accusedHostName'];
            $failurecount = $row['count'];
			
			/* $count=0.0;
			$failure_query = "SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime
                FROM BIT_failure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))))
                AND (
						accusedHostName LIKE '$failurehostname' OR accusedHostName LIKE '$failurehostname' 
					)";

				// Apply customer filter if any
				if(count($failureCode)>0 and $failureCode!=''){
					$failure_query.=" AND a.failureCode IN ($failureCode)  ";
				}else{
					if(count($failuresToKeep) > 0) {
						$codes = implode(",", $failuresToKeep);
						$failure_query .= " AND a.failureCode IN ($codes)";
					} else if(count($failuresToRemove) > 0) {
						$codes = implode(",", $failuresToRemove);
						$failure_query .= " AND a.failureCode NOT IN ($codes)";
					}
				}
				if(isset($flightLegsCondition)) {
					$failure_query .= "AND $flightLegsCondition";
				} else {
					$failure_query .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
				}
				
				$result1 = mysqli_query($dbConnection, $failure_query);
				if($result1) {						
					while ($row1 = mysqli_fetch_array($result1)) {
						$failurecruiseTime+= $row1['cruiseTime'];													
						}
					}
				//$count = $row['count'];							
				$count=$failurecount/$failurecruiseTime;
				$failurePerHourcount = round($count,2); */
			
			$failure[] = array(
				'failurehostname' => $failurehostname, 
				'failurecount' => $failurecount,
				'failurePerHourcount' => $failurePerHourcount
			);
        }
    }
	

						
	$faultquery = "SELECT hostName, COUNT(DISTINCT a.hostName,a.serialNumber,a.faultCode,a.reportingHostName,a.monitorState,a.detectionTime,a.clearingTime,a.idFlightLeg) AS count
                FROM BIT_fault a
                INNER JOIN SYS_flightPhase b
                 ON a.idFlightLeg = b.idFlightLeg ";
	if($monitorState=='3'){
		$faultquery.=" AND ((a.monitorState=3 AND a.detectionTime<=b.endTime)) ";
	}else if($faultquery=='1'){
		$faultquery.=" AND (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime)))";
	}else {
		$faultquery.=" AND ((a.monitorState=3 AND a.detectionTime<=b.endTime) OR (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime))))";
	}
       $faultquery.="  AND (
						hostName LIKE 'SVDU__' OR hostName LIKE 'SVDU___' 
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
        //$faultquery .= " AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime'";
    	$faultquery .= " AND (DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

    $faultquery .= " GROUP BY hostName";
    error_log($faultquery);
    $result = mysqli_query($dbConnection, $faultquery);
	$faultFlag=-1;
    if($result) {		
        while ($row = mysqli_fetch_array($result)) {
			$faultFlag=1;
            $faulthostname = $row['hostName'];
            $faultcount = $row['count'];
			/* $count=0.0;
			$fault_query = "SELECT DISTINCT a.hostName,a.serialNumber,a.faultCode,a.reportingHostName,a.monitorState,a.detectionTime,a.clearingTime,a.idFlightLeg
                FROM BIT_fault a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
                AND ((a.monitorState=3 AND a.detectionTime<=b.endTime) OR (a.monitorState=1 AND ((a.detectionTime BETWEEN b.startTime AND b.endTime) OR (a.clearingTime BETWEEN b.startTime and b.endTime) OR (a.detectionTime<=b.startTime AND a.clearingTime>=b.endTime))))
                AND (
						hostName LIKE '$faulthostname' OR hostName LIKE '$faulthostname' 
					) 
				AND b.idFlightPhase IN ($flightPhasescode) ";
				// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
				
    // Apply customer filter if any
  
				if(count($faultCode)>0 and $faultCode!=''){
					$fault_query.=" AND a.faultCode IN ($faultCode)";
				}else{
					if(count($faultsToKeep) > 0) {
						$codes = implode(",", $faultsToKeep);
						$fault_query .= " AND a.faultCode IN ($codes)";
					} else if(count($faultsToRemove) > 0) {
						$codes = implode(",", $faultsToRemove);
						$fault_query .= " AND a.faultCode NOT IN ($codes)";
					}
				
				}

				if(isset($flightLegsCondition)) {
					$fault_query .= "AND $flightLegsCondition";
				} else {
					$fault_query .= " AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime'";
				}
				
				$result1 = mysqli_query($dbConnection, $fault_query);
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
			$count = $faultcount;
            $value=($sec/3600);
			$count=$count/$value;						
            $faultPerHourcount = round($count,2); */
			
			$fault[] = array(
				'faulthostname' => $faulthostname, 
				'faultcount' =>$faultcount,
				'faultPerHourcount' =>$faultPerHourcount
			);
        }
    }
	$resetquery  = "SELECT eventData, COUNT(DISTINCT(idEvent)) AS count
				FROM BIT_events a
				INNER JOIN SYS_flightPhase b
	            ON a.idFlightLeg = b.idFlightLeg  
	            AND a.lastUpdate >= b.startTime AND a.lastUpdate <= b.endTime ";				
				//if(count($resetsCode)>0){				
				if(count($resetsCode)>0 and $resetsCode!=''){				
					$resetquery.="AND eventName IN ($resetsCode) ";
				}
	$resetquery .= "AND (
						eventData LIKE 'SVDU__' OR eventData LIKE 'SVDU___' 
					) 
				AND b.idFlightPhase IN ($flightPhasescode) ";
				// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
	if(isset($flightLegsCondition)) {
		$resetquery .= "AND $flightLegsCondition";
	} else {
        //$resetquery .= " AND a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime'";
		$resetquery .= " AND (DATE(a.lastUpdate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

	$resetquery .= " GROUP BY eventData";	
	//echo $query; exit;
	$resetFlag=-1;
	$result = mysqli_query($dbConnection, $resetquery);
	
	if($result) {		
		while ($row = mysqli_fetch_array($result)) {
			$resetFlag=1;
			$resethostname = $row['eventData'];
			$resetcount = $row['count'];
			
			/* $count=0.0;
			$reset_query = "SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime
				FROM BIT_events a
				INNER JOIN SYS_flightPhase b
	            ON a.idFlightLeg = b.idFlightLeg  
	            AND a.lastUpdate >= b.startTime AND a.lastUpdate <= b.endTime ";
				
				//if(count($resetsCode)>0){				
				if($resetsCode!=''){
				
					$reset_query.="AND eventName IN ($resetsCode) ";
				}
				$reset_query .= "AND (
									eventData LIKE '$resethostname' OR eventData LIKE '$resethostname' 
								) 
							AND b.idFlightPhase IN ($flightPhasescode) ";
							// AND (eventData LIKE 'SVDU___' OR eventData LIKE 'TPMU%' OR eventData LIKE '%PCU%')";
				if(isset($flightLegsCondition)) {
					$reset_query .= "AND $flightLegsCondition";
				} else {
					$reset_query .= " AND a.lastUpdate BETWEEN '$startDateTime' AND '$endDateTime'";
				}
				
				$result1 = mysqli_query($dbConnection, $reset_query);
				if($result1) {						
					while ($row1 = mysqli_fetch_array($result1)) {																	
							$resetcruiseTime+= $row1['cruiseTime'];
						}
					}
				//$count = $row['count'];
				
				$count=$resetcount/$resetcruiseTime;				
				$resetPerHourcount = round($count,2);		 */		
			
			$reset[] = array(
				'resethostname' => $resethostname, 
				'resetcount' => $resetcount,
				'resetPerHourcount' => $resetPerHourcount
			);
		}
	}
	
	$appquery ="SELECT a.hostName, COUNT(*) AS COUNT  FROM BIT_extappevent a INNER JOIN SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
		if(count($faultCode)>0 and $faultCode!=''){
			$appquery.="a.faultCode IN ($faultCode) AND";
		}
		$appquery.="(a.hostName LIKE 'SVDU__' OR a.hostName LIKE 'SVDU___' )   AND a.detectionTime BETWEEN b.startTime AND b.endTime AND b.idFlightPhase IN ($flightPhasescode)";
		
		//$appquery.=" AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime' GROUP BY a.hostName";
		$appquery.=" AND (DATE(a.detectionTime) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE)) GROUP BY a.hostName";
	$appEventFlag=-1;
	
    $result = mysqli_query($dbConnection, $appquery);

    if($result) {
        while ($row = mysqli_fetch_array($result)) {
			$appEventFlag=1;
            $appEventhostname = $row['hostName'];
            $appEventcount = $row['COUNT'];
			
			/* $count=0.0;
				$app_query="SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime FROM BIT_extappevent a INNER JOIN SYS_flightPhase b ON (a.idFlightLeg = b.idFlightLeg) WHERE ";
				if(count($faultCode)>0 and $faultCode!=''){
					$app_query.="a.faultCode IN ($faultCode) AND";
				}
				$app_query.="(a.hostName LIKE '$appEventhostname' OR a.hostName LIKE '$appEventhostname' )   AND a.detectionTime BETWEEN b.startTime AND b.endTime AND b.idFlightPhase IN ($flightPhasescode)";
				
				$app_query.=" AND a.detectionTime BETWEEN '$startDateTime' AND '$endDateTime' ";
					$result1 = mysqli_query($dbConnection, $app_query);
					if($result1) {						
						while ($row1 = mysqli_fetch_array($result1)) {					
							$appEventcruiseTime+= $row1['cruiseTime'];													
						}
					}
				$count = $row['count'];							
				$count=$count/$appEventcruiseTime;
				$appEventPerHourcount = round($count,2); */
			
			$appEvent[] = array(
				'appEventhostname' => $appEventhostname, 
				'appEventcount' => $appEventcount,
				'appEventPerHourcount' => $appEventPerHourcount
			);
        }
    }
	
	$impactedServicesquery = "SELECT accusedHostName, COUNT(DISTINCT a.failureCode,a.correlationDate,a.monitorState,a.accusedHostName,a.lastUpdate,a.idFlightLeg,a.idService) AS count
                FROM bit_servicefailure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) ";
    
	if($monitorState=='3'){
		$impactedServicesquery.=" AND ((a.monitorState=3 AND a.correlationDate<=b.endTime)) ";
	}else if($monitorState=='1'){
		$impactedServicesquery.=" AND (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))) ";
	}else {
		$impactedServicesquery.=" AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime)))) ";
	}
    $impactedServicesquery.=" AND (
						accusedHostName LIKE 'SVDU__' OR accusedHostName LIKE 'SVDU___' 
					) ";

    // Apply customer filter if any
    if(count($ImpactedServicesCode)>0 and $ImpactedServicesCode!=''){
		$impactedServicesquery.=" AND a.failureCode IN ($ImpactedServicesCode) ";
	}
    if(isset($flightLegsCondition)) {
        $impactedServicesquery .= "AND $flightLegsCondition";
    } else {
        //$impactedServicesquery .= " AND a.correlationDate BETWEEN '$startDateTime' AND '$endDateTime'";
    	$impactedServicesquery .= " AND (DATE(a.correlationDate) BETWEEN CAST('$startDateTime' AS DATE) AND CAST('$endDateTime' AS DATE))";
    }

    $impactedServicesquery .= " GROUP BY accusedHostName";
	
	$impactedServicesFlag=-1;
    $result = mysqli_query($dbConnection, $impactedServicesquery);

    if($result) {
        while ($row = mysqli_fetch_array($result)) {
			$impactedServicesFlag=1;
            $impactedServiceshostname = $row['accusedHostName'];
            $impactedServicescount = $row['count'];
            $svdus[$impactedServiceshostname] = $impactedServicescount."_IC";
			/* $count=0.0;
				$impactquery = "SELECT *, TRUNCATE((TIME_TO_SEC(TIMEDIFF(b.endTime, b.startTime))/3600), 2) AS cruiseTime
                FROM bit_servicefailure a
                INNER JOIN SYS_flightPhase b
                ON a.idFlightLeg = b.idFlightLeg 
				AND b.idFlightPhase IN ($flightPhasescode) 
                AND ((a.monitorState=3 AND a.correlationDate<=b.endTime) OR (a.monitorState=1 AND ((a.correlationDate BETWEEN b.startTime AND b.endTime) OR (a.lastUpdate BETWEEN b.startTime and b.endTime) OR (a.correlationDate<=b.startTime AND a.lastUpdate>=b.endTime))))
                AND (
						accusedHostName LIKE '$impactedServiceshostname' OR accusedHostName LIKE '$impactedServiceshostname' 
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
							$impactcruiseTime+= $row1['cruiseTime'];													
						}
					}
				$count = $row['count'];							
				$count=$count/$impactcruiseTime;
				$impactedServicesPerHourcount = round($count,2); */
			
			$impactedServices[] = array(
				'impactedServiceshostname' => $impactedServiceshostname, 
				'impactedServicescount' => $impactedServicescount,
				'impactedServicesPerHourcount' => $impactedServicesPerHourcount
			);
        }
    }

    $darkThreshold = 5;
	$dangerThreshold = 3;
    $warningThreshold = 1;
} 

$data = array(
	'activeFailure' => $activeFailure,
	'failure' => $failure,
	'reset' => $reset,
	'fault' => $fault,
	'impactedServices' => $impactedServices,
	'appEvent' => $appEvent
);


$exportArray = array();
$exportPerHourArray = array();
foreach(range('L','A') as $i) {

    $query = "SELECT DISTINCT hostName 
    FROM BIT_lru 
    WHERE hostName LIKE 'SVDU%$i' AND (hostName LIKE 'SVDU__' OR hostName LIKE 'SVDU___')
    ORDER BY LENGTH(hostName), hostName";
    $result = mysqli_query($dbConnection, $query);
	
    if($result) {
        if(mysqli_num_rows($result) > 0) {
		
            while($row = mysqli_fetch_array($result)) {
                $hostname = $row['hostName'];		
				$exportArray[] = array(
					'hostname' => $hostname, 
					'activeFailure' => 0,
					'failure' => 0,
					'reset' => 0,
					'fault' => 0,
					'impactedServices' => 0,
					'appEvent' => 0
				);	
				$exportPerHourArray[] = array(
					'hostname' => $hostname, 
					'activeFailure' => 0,
					'failure' => 0,
					'reset' => 0,
					'fault' => 0,
					'impactedServices' => 0,
					'appEvent' => 0
				);				
            }
        }
    }
	foreach($data['activeFailure'] as $f) {
		foreach($exportArray as $a => $field) {	
			if($f['activefailurehostname']==$field['hostname']){
				$exportArray[$a]['activeFailure']= $f['activefailurecount'];
			}
		
		}
	}

	foreach($data['failure'] as $f ) {
		foreach($exportArray as $a => $field) {	
			if($f['failurehostname']==$field['hostname']){
				$exportArray[$a]['failure']= $f['failurecount'];
			}
		
		}
	}

	foreach($data['reset'] as $f) {
		foreach($exportArray as $a => $field) {	
			if($f['resethostname']==$field['hostname']){
				$exportArray[$a]['reset']= $f['resetcount'];
			}
		
		}
	}
	
	foreach($data['fault'] as $f) {
		foreach($exportArray as $a => $field) {	
			if($f['faulthostname']==$field['hostname']){
				$exportArray[$a]['fault']= $f['faultcount'];
			}
		
		}
	}
	
	foreach($data['appEvent'] as $f) {
		foreach($exportArray as $a => $field) {	
			if($f['appEventhostname']==$field['hostname']){		
				$exportArray[$a]['appEvent']= $f['appEventcount'];
			}
		
		}
	}
	
	foreach($data['impactedServices'] as $f) {
		foreach($exportArray as $a => $field) {	
			if($f['impactedServiceshostname']==$field['hostname']){			
				$exportArray[$a]['impactedServices']= $f['impactedServicescount'];				
			}
		
		}
	}
	
	//Per Hour Data
	foreach($data['activeFailure'] as $f) {
		foreach($exportPerHourArray as $a => $field) {	
			if($f['activefailurehostname']==$field['hostname']){
				$exportPerHourArray[$a]['activeFailure']= $f['activefailurePerHourcount'];
			}
		
		}
	}

	foreach($data['failure'] as $f ) {
		foreach($exportPerHourArray as $a => $field) {	
			if($f['failurehostname']==$field['hostname']){
				$exportPerHourArray[$a]['failure']= $f['failurePerHourcount'];
			}
		
		}
	}

	foreach($data['reset'] as $f) {
		foreach($exportPerHourArray as $a => $field) {	
			if($f['resethostname']==$field['hostname']){
				$exportPerHourArray[$a]['reset']= $f['resetPerHourcount'];
			}
		
		}
	}
	
	foreach($data['fault'] as $f) {
		foreach($exportPerHourArray as $a => $field) {	
			if($f['faulthostname']==$field['hostname']){
				$exportPerHourArray[$a]['fault']= $f['faultPerHourcount'];
			}
		
		}
	}
	
	foreach($data['appEvent'] as $f) {
		foreach($exportPerHourArray as $a => $field) {	
			if($f['appEventhostname']==$field['hostname']){		
				$exportPerHourArray[$a]['appEvent']= $f['appEventPerHourcount'];
			}
		
		}
	}
	
	foreach($data['impactedServices'] as $f) {
		foreach($exportPerHourArray as $a => $field) {	
			if($f['impactedServiceshostname']==$field['hostname']){			
				$exportPerHourArray[$a]['impactedServices']= $f['impactedServicesPerHourcount'];				
			}
		
		}
	}
	
}

if(!empty($exportArray) and !empty($exportPerHourArray)){
	$objPHPExcel = new PHPExcel();

	// Create a first sheet, representing sales data
	$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Hostname')->getStyle('A1:G1')->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
			$objPHPExcel->getActiveSheet()->setCellValue('B1', 'Reset Count');
			$objPHPExcel->getActiveSheet()->setCellValue('C1', 'Failure Count');
			$objPHPExcel->getActiveSheet()->setCellValue('D1', 'Fault Count');
			//$objPHPExcel->getActiveSheet()->setCellValue('E1', 'Active Failure Count');
			$objPHPExcel->getActiveSheet()->setCellValue('E1', 'Impacted Service Count');
			$objPHPExcel->getActiveSheet()->setCellValue('F1', 'App Event Count');
			
			$objPHPExcel->getActiveSheet()->getStyle('A1:G1')->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
			$ctr=1;
			foreach ($exportArray as $value) {
					$ctr++;
					$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $value['hostname']);
					$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $value['reset']);
					$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $value['failure']);
					$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $value['fault']);
					//$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $value['activeFailure']);
					$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $value['impactedServices']);
					$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $value['appEvent']);
				}

	// Rename sheet
	$objPHPExcel->getActiveSheet()->setTitle('Threshold or Heatmap View Data');
/*
	// Create a new worksheet, after the default sheet
	$objPHPExcel->createSheet();

	// Add some data to the second sheet, resembling some different data types
	$objPHPExcel->setActiveSheetIndex(1);
		$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Hostname')->getStyle('A1:G1')->applyFromArray(array('font' => array('size' => 13,'bold' => true,'color' => array('rgb' => 'FFFFFF'))));
			$objPHPExcel->getActiveSheet()->setCellValue('B1', 'Reset Count');
			$objPHPExcel->getActiveSheet()->setCellValue('C1', 'Failure Count');
			$objPHPExcel->getActiveSheet()->setCellValue('D1', 'Fault Count');
			$objPHPExcel->getActiveSheet()->setCellValue('E1', 'Active Failure Count');
			$objPHPExcel->getActiveSheet()->setCellValue('F1', 'Impacted Service Count');
			$objPHPExcel->getActiveSheet()->setCellValue('G1', 'App Event Count');
			
			$objPHPExcel->getActiveSheet()->getStyle('A1:G1')->applyFromArray(
				array(
					'fill' => array(
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array('rgb' => '04225e')
					)
				)
			);
			$ctr=1;
			
			foreach ($exportPerHourArray as $value1) {			
					$ctr++;
					$objPHPExcel->getActiveSheet()->setCellValue('A'.$ctr, $value1['hostname'])->getStyle('A'.$ctr.':G'.$ctr)->getNumberFormat()->setFormatCode('0.00'); ;
					$objPHPExcel->getActiveSheet()->setCellValue('B'.$ctr, $value1['reset']);
					$objPHPExcel->getActiveSheet()->setCellValue('C'.$ctr, $value1['failure']);
					$objPHPExcel->getActiveSheet()->setCellValue('D'.$ctr, $value1['fault']);
					$objPHPExcel->getActiveSheet()->setCellValue('E'.$ctr, $value1['activeFailure']);
					$objPHPExcel->getActiveSheet()->setCellValue('F'.$ctr, $value1['impactedServices']);
					$objPHPExcel->getActiveSheet()->setCellValue('G'.$ctr, $value1['appEvent']);
				}

	// Rename 2nd sheet
	$objPHPExcel->getActiveSheet()->setTitle('Per Hour View Data');*/

	// Redirect output to a client’s web browser (Excel5)
	if($formatType=="xls"){
		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="SeatCountInformation.xls"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	}else{
		// Redirect output to a client’s web browser (Excel5)
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename="SeatCountInformation.csv"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
		$objWriter->save('php://output');
	}

	
}


?>
